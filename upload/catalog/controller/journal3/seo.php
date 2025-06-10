<?php

use Journal3\Utils\Arr;

class ControllerJournal3Seo extends Controller {
	private static $tags = null;

	public function meta_tags() {
		$tags = array();

		if ($this->journal3->get('seoOpenGraphTagsStatus')) {
			$tags['fb:app_id'] = array(
				'type'    => 'property',
				'content' => $this->journal3->get('seoOpenGraphTagsAppId'),
			);

			$tags['og:type'] = array(
				'type'    => 'property',
				'content' => static::getTags('type'),
			);

			$tags['og:title'] = array(
				'type'    => 'property',
				'content' => static::getTags('title'),
			);

			$tags['og:url'] = array(
				'type'    => 'property',
				'content' => static::getTags('url'),
			);

			$tags['og:image'] = array(
				'type'    => 'property',
				'content' => $this->journal3_image->resize(
					static::getTags('image'),
					$this->journal3->get('seoOpenGraphTagsImageDimensions.width'),
					$this->journal3->get('seoOpenGraphTagsImageDimensions.height'),
					$this->journal3->get('seoOpenGraphTagsImageDimensions.resize')
				),
			);

			$tags['og:image:width'] = array(
				'type'    => 'property',
				'content' => $this->journal3->get('seoOpenGraphTagsImageDimensions.width'),
			);

			$tags['og:image:height'] = array(
				'type'    => 'property',
				'content' => $this->journal3->get('seoOpenGraphTagsImageDimensions.height'),
			);

			$tags['og:description'] = array(
				'type'    => 'property',
				'content' => htmlspecialchars(static::getTags('description') ?? ''),
			);
		}

		if ($this->journal3->get('seoTwitterCardsStatus')) {
			$tags['twitter:card'] = array(
				'type'    => 'name',
				'content' => 'summary',
			);

			$tags['twitter:site'] = array(
				'type'    => 'name',
				'content' => '@' . trim($this->journal3->get('seoTwitterCardsTwitterUser', ''), '@'),
			);

			$tags['twitter:title'] = array(
				'type'    => 'name',
				'content' => static::getTags('title'),
			);

			$tags['twitter:image'] = array(
				'type'    => 'name',
				'content' => $this->journal3_image->resize(
					static::getTags('image'),
					$this->journal3->get('seoTwitterCardsImageDimensions.width'),
					$this->journal3->get('seoTwitterCardsImageDimensions.height'),
					$this->journal3->get('seoTwitterCardsImageDimensions.resize')
				),
			);

			$tags['twitter:image:width'] = array(
				'type'    => 'name',
				'content' => $this->journal3->get('seoTwitterCardsImageDimensions.width'),
			);

			$tags['twitter:image:height'] = array(
				'type'    => 'name',
				'content' => $this->journal3->get('seoTwitterCardsImageDimensions.height'),
			);

			$tags['twitter:description'] = array(
				'type'    => 'name',
				'content' => htmlspecialchars(static::getTags('description') ?? ''),
			);
		}

		return $tags;
	}

	public function rich_snippets($breadcrumbs = array()) {
		if (!$this->journal3->get('seoGoogleRichSnippetsStatus')) {
			return null;
		}

		$jsons = array();

		$json = array(
			'@context'        => 'http://schema.org',
			'@type'           => 'WebSite',
			'url'             => $this->config->get($this->journal3_request->is_https ? 'config_ssl' : 'config_url'),
			'name'            => self::getTags('site_title'),
			'description'     => self::getTags('site_description'),
			'potentialAction' =>
				array(
					'@type'       => 'SearchAction',
					'target'      => str_replace('___SEARCH___', '{search}', $this->url->link('product/search', '&search=___SEARCH___')),
					'query-input' => 'required name=search',
				),
		);

		$jsons[] = '<script type="application/ld+json">' . json_encode($json) . '</script>';

		$json = array(
			'@context' => 'http://schema.org',
			'@type'    => 'Organization',
			'url'      => $this->config->get($this->journal3_request->is_https ? 'config_ssl' : 'config_url'),
			'logo'     => $this->journal3_image->resize(self::getTags('logo')),
		);

		$jsons[] = '<script type="application/ld+json">' . json_encode($json) . '</script>';

		if ($breadcrumbs) {
			$this->load->language('common/header');

			$index = 0;

			$json = array(
				'@context'        => 'http://schema.org',
				'@type'           => 'BreadcrumbList',
				'itemListElement' => array_map(function ($breadcrumb) use (&$index) {
					$index++;

					return array(
						'@type'    => 'ListItem',
						'position' => $index,
						'item'     => array(
							'@id'  => $breadcrumb['href'],
							'name' => $index === 1 ? $this->language->get('text_home') : $breadcrumb['text'],
						),
					);
				}, $breadcrumbs),
			);

			$jsons[] = '<script type="application/ld+json">' . json_encode($json) . '</script>';
		}

		switch ($this->journal3_document->getPageRoute()) {
			case 'product/product':
				$json = array(
					'@context'    => 'https://schema.org/',
					'@type'       => 'Product',
					'name'        => static::getTags('title'),
					'image'       => $this->journal3_image->resize(static::getTags('image')),
					'description' => static::getTags('description'),
					"sku"         => static::getTags('sku'),
					"mpn"         => static::getTags('mpn'),
					"model"       => static::getTags('model'),
					'offers'      =>
						array(
							'@type'           => 'Offer',
							'priceCurrency'   => static::getTags('priceCurrency'),
							'price'           => static::getTags('price'),
							'itemCondition'   => 'https://schema.org/NewCondition',
							'availability'    => static::getTags('stock') ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
							'seller'          => array(
								'@type' => 'Organization',
								'name'  => self::getTags('site_title'),
							),
							'priceValidUntil' => static::getTags('date_end'),
							'url'             => static::getTags('url'),
						),
				);

				if (static::getTags('reviews')) {
					$review = static::getTags('reviews');
					$json['review'] = array(
						"@type"         => "Review",
						"reviewRating"  => array(
							"@type"       => "Rating",
							"ratingValue" => $review[0]['rating'],
						),
						"name"          => $review[0]['name'],
						"author"        => array(
							"@type" => "Person",
							"name"  => $review[0]['author'],
						),
						"datePublished" => $review[0]['date_added'],
						"reviewBody"    => $review[0]['text'],
					);
				}

				if (static::getTags('brand')) {
					$json['brand'] = array(
						'@type' => 'Brand',
						'name'  => static::getTags('brand'),
					);
				}

				if (static::getTags('ratingCount')) {
					$json['aggregateRating'] = array(
						'@type'       => 'AggregateRating',
						'ratingValue' => static::getTags('ratingValue'),
						'reviewCount' => static::getTags('ratingCount'),
					);
				}

				$jsons[] = '<script type="application/ld+json">' . json_encode($json) . '</script>';
				break;

			case 'journal3/blog' . JOURNAL3_ROUTE_SEPARATOR . 'post':
				$json = array(
					'@context'      => 'https://schema.org/',
					'@type'         => 'Article',
					'headline'      => static::getTags('title'),
					'image'         => $this->journal3_image->resize(static::getTags('image')),
					'datePublished' => static::getTags('date_added'),
					'articleBody'   => static::getTags('description'),
					"author"        => [
						"@type"      => "Person",
						"givenName"  => static::getTags('author_firstname'),
						"familyName" => static::getTags('author_lastname'),
					],
				);

				$jsons[] = '<script type="application/ld+json">' . json_encode($json) . '</script>';
				break;
		}

		if (!$jsons) {
			return null;
		}

		return implode(PHP_EOL, $jsons);
	}

	public function getTags($tag) {
		if (is_array($tag)) {
			$tag = $tag[0];
		}

		if (static::$tags === null) {
			if (is_array($this->config->get('config_meta_description'))) {
				$description = Arr::get($this->config->get('config_meta_description'), $this->config->get('config_language_id'));
			} else {
				$description = $this->config->get('config_meta_description');
			}

			if ($this->journal3->get('logoSocialShare')) {
				$logo = $this->journal3->get('logoSocialShare');
			} else if ($this->journal3->get('logo2x')) {
				$logo = $this->journal3->get('logo2x');
			} else if ($this->journal3->get('logo')) {
				$logo = $this->journal3->get('logo');
			} else {
				$logo = $this->config->get('config_logo');
			}

			static::$tags = array(
				'type'             => 'website',
				'title'            => $this->config->get('config_name'),
				'url'              => $this->config->get($this->journal3_request->is_https ? 'config_ssl' : 'config_url'),
				'image'            => $logo,
				'logo'             => $logo,
				'description'      => $description,
				'site_title'       => $this->config->get('config_name'),
				'site_description' => $description,
			);

			switch ($this->journal3_document->getPageRoute()) {
				case 'information/information':
					$id = $this->journal3_document->getPageId();

					if ($id) {
						$this->load->model('catalog/information');

						$information_info = $this->model_catalog_information->getInformation($id);

						if ($information_info) {
							static::$tags['title'] = $information_info['title'];
							static::$tags['url'] = $this->journal3_url->link('information/information', 'information_id=' . $id);
							static::$tags['description'] = trim(\Journal3\Utils\Str::utf8_substr(strip_tags(html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8')), 0, 300));
						}
					}

					break;

				case 'product/product':
					$id = $this->journal3_document->getPageId();

					if ($id) {
						$this->load->model('catalog/product');

						$product_info = $this->model_catalog_product->getProduct($id);

						if ($product_info) {
							if (!isset($product_info['manufacturer'])) {
								$this->load->model('catalog/manufacturer');

								$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($product_info['manufacturer_id'] ?? 0);
								$product_info['manufacturer'] = $manufacturer_info['name'] ?? '';
							}

							static::$tags['type'] = 'product';
							static::$tags['title'] = $product_info['name'];
							static::$tags['url'] = $this->journal3_url->link('product/product', 'product_id=' . $id);
							static::$tags['image'] = $product_info['image'];
							static::$tags['description'] = trim(\Journal3\Utils\Str::utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, 300));
							static::$tags['sku'] = $product_info['sku'];
							static::$tags['mpn'] = $product_info['mpn'];
							static::$tags['model'] = $product_info['model'];
							static::$tags['price'] = number_format($this->currency->format($this->tax->calculate($product_info['special'] ? $product_info['special'] : $product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'], 0, false), 2, '.', '');
							static::$tags['priceCurrency'] = $this->session->data['currency'];
							static::$tags['stock'] = $product_info['quantity'] > 0;
							static::$tags['brand'] = $product_info['manufacturer'];
							static::$tags['ratingCount'] = $product_info['reviews'];
							static::$tags['ratingValue'] = $product_info['rating'];
							static::$tags['date_end'] = date('Y-m-d', strtotime('+1 year'));

							$query = $this->db->query("
								SELECT r.review_id, r.author, r.rating, r.text, p.product_id, pd.name, p.price, p.image, r.date_added 
								FROM " . DB_PREFIX . "review r 
								LEFT JOIN " . DB_PREFIX . "product p ON (r.product_id = p.product_id) 
								LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
								WHERE 
									p.product_id = '" . (int)$id . "' 
									AND p.date_available <= NOW() 
									AND p.status = '1' 
									AND r.status = '1' 
									AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
								ORDER BY 
									r.date_added DESC 
								LIMIT 1
							");

							static::$tags['reviews'] = $query->rows;
						}

					}

					break;

				case 'product/category':
					$id = $this->journal3_document->getPageId();

					if ($id) {
						$this->load->model('catalog/category');

						$category_info = $this->model_catalog_category->getCategory((int)$id);

						if ($category_info) {
							static::$tags['title'] = $category_info['name'];
							static::$tags['url'] = $this->journal3_url->link('product/category', 'path=' . $id);
							static::$tags['image'] = $category_info['image'];
							static::$tags['description'] = trim(\Journal3\Utils\Str::utf8_substr(strip_tags(html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8')), 0, 300));
							static::$tags['category_description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');
						}
					}

					break;

				case 'product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info':
					$id = $this->journal3_document->getPageId();

					if ($id) {
						$this->load->model('catalog/manufacturer');

						$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($id);

						if ($manufacturer_info) {
							static::$tags['title'] = $manufacturer_info['name'];
							static::$tags['url'] = $this->journal3_url->link('product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info', 'manufacturer_id=' . $id);
							static::$tags['image'] = $manufacturer_info['image'];
							static::$tags['description'] = $manufacturer_info['name'];
						}
					}

					break;

				case 'journal3/blog':
					$id = $this->journal3_document->getPageId();

					if ($id) {
						$this->load->model('journal3/blog');

						$category_info = $this->model_journal3_blog->getCategory($id);

						if ($category_info) {
							static::$tags['title'] = $category_info['name'];
							static::$tags['url'] = $this->journal3_url->link('journal3/blog', 'journal_blog_category_id=' . $id);
							static::$tags['description'] = trim(\Journal3\Utils\Str::utf8_substr(strip_tags(html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8')), 0, 300));
						}
					} else {
						static::$tags['title'] = $this->journal3->get('blogPageTitle');
					}

					break;

				case 'journal3/blog' . JOURNAL3_ROUTE_SEPARATOR . 'post':
					$id = $this->journal3_document->getPageId();

					if ($id) {
						$this->load->model('journal3/blog');

						$post_info = $this->model_journal3_blog->getPost($id);

						if ($post_info) {
							static::$tags['type'] = 'article';
							static::$tags['title'] = $post_info['name'];
							static::$tags['url'] = $this->journal3_url->link('journal3/blog' . JOURNAL3_ROUTE_SEPARATOR . 'post', 'journal_blog_post_id=' . $id);
							static::$tags['image'] = $post_info['image'];
							static::$tags['description'] = trim(\Journal3\Utils\Str::utf8_substr(strip_tags(html_entity_decode($post_info['description'], ENT_QUOTES, 'UTF-8')), 0, 300));
							static::$tags['date_added'] = $post_info['date_added'];
							static::$tags['author_firstname'] = $post_info['firstname'];
							static::$tags['author_lastname'] = $post_info['lastname'];
						}
					}

					break;
			}

			static::$tags['url'] = str_replace('&amp;', '&', static::$tags['url']);
		}

		return Arr::get(static::$tags, $tag);
	}

	public function view_before(&$route, &$args) {
		$_route = $route;

		if ($this->journal3_opencart->is_oc4 && strpos($_route, 'journal3/') === 0) {
			$_route = substr($_route, strlen('journal3/'));
		}

		switch ($_route) {
			case 'common/home':
				$args['journal3_rich_snippets'] = $this->rich_snippets();
				break;

			case 'product/category':
			case 'product/product':
			case 'journal3/blog/post':
				$args['journal3_rich_snippets'] = $this->rich_snippets($args['breadcrumbs'] ?? []);
				break;

			default:
				$args['journal3_rich_snippets'] = null;
		}

		// accessibility fix icon tag + title
		if (!empty($args['breadcrumbs'])) {
			$first = array_key_first($args['breadcrumbs']);

			if (!empty($args['breadcrumbs'][$first]['text'])) {
				$args['breadcrumbs'][$first]['text'] = str_replace(['<i', '</i>', 'fas'], ['<em', '</em>', 'fas fa'], $args['breadcrumbs'][$first]['text']);
				$args['breadcrumbs'][$first]['text'] .= '<span class="sr-only">home</span>';
			}
		}
	}
}

class_alias('ControllerJournal3Seo', '\Opencart\Catalog\Controller\Journal3\Seo');
