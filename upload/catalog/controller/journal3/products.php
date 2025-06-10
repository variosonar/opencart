<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;
use Journal3\Utils\Str;

class ControllerJournal3Products extends ModuleController {

	public function __construct($registry) {
		parent::__construct($registry);

		$this->load->language('product/product');

		$this->load->model('journal3/filter');
		$this->load->model('journal3/product');
		$this->load->model('catalog/product');
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$default = $parser->getSetting('default');

		$has_style = false;

		if ($parser->getSetting('display') === 'grid') {
			foreach (array_keys($parser->getSettings()) as $key) {
				if (Str::startsWith($key, 'moduleProductGrid')) {
					$has_style = true;
					break;
				}
			}
		} else {
			foreach (array_keys($parser->getSettings()) as $key) {
				if (Str::startsWith($key, 'moduleProductList')) {
					$has_style = true;
					break;
				}
			}
		}

		$data = array(
			'edit'             => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'             => $parser->getSetting('name'),
			'swiper_carousel'  => $parser->getSetting('gridType') === 'ipr' && $parser->getSetting('carousel'),
			'classes'          => [
				'module-products-' . $parser->getSetting('display'),
				'carousel-mode'    => $parser->getSetting('gridType') === 'ipr' && $parser->getSetting('carousel'),
				'align-to-content' => $parser->getSetting('gridType') === 'auto' && $parser->getSetting('autoGridContainerAlignToContent'),
			],
			'products_classes' => array(
				'product-' . $parser->getSetting('display'),
				'main-products-style' => !$has_style,
			),
			'image_width'      => $parser->getSetting('imageDimensions.width', $this->config->get('theme_journal3_image_product_width')),
			'image_height'     => $parser->getSetting('imageDimensions.height', $this->config->get('theme_journal3_image_product_height')),
			'image_resize'     => $parser->getSetting('imageDimensions.resize'),
			'carouselOptions'  => $this->journal3->carousel($parser->getJs(), 'carouselStyle'),
		);

		if ($this->journal3->get('performanceLazyLoadImagesStatus')) {
			$data['dummy_image'] = $this->journal3_image->transparent($parser->getSetting('imageDimensions.width', $this->config->get('theme_journal3_image_product_width')), $parser->getSetting('imageDimensions.height', $this->config->get('theme_journal3_image_product_height')));
		}

		$data['text_tax'] = $this->language->get('text_tax');

		$data['button_cart'] = $this->language->get('button_cart');
		$data['button_wishlist'] = $this->language->get('button_wishlist');
		$data['button_compare'] = $this->language->get('button_compare');

		$data['default_index'] = $parser->getSetting('sectionsDisplay') === 'tabs' ? 1 : 0;

		if ($default) {
			foreach (Arr::get($this->module_data, 'items') as $index => $item) {
				if ($default === Arr::get($item, 'id')) {
					$data['default_index'] = $index + 1;
					break;
				}
			}
		}

		return $data;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		$filter_data = $parser->getSetting('filter');

		if (empty($filter_data['limit']) && !empty($this->settings['limit'])) {
			$filter_data['limit'] = $this->settings['limit'];
		}

		$preset = Arr::get($filter_data, 'preset');
		$limit = Arr::get($filter_data, 'limit');

		if ($this->settings['sectionsDisplay'] === 'tabs' && $parser->getSetting('tabType') === 'link') {
			$products = array();
		} else {
			switch ($preset) {
				case 'related':
				case 'related_category':
				case 'related_subcategory':
				case 'related_manufacturer':
				case 'alsobought':
				case 'recently_viewed':
					$products = null;
					break;

				case 'most_viewed':
					$products = $this->model_journal3_product->getMostViewedProducts($limit);
					break;

				case 'custom':
					$products = $this->model_journal3_product->getProduct(array_filter(Arr::get($filter_data, 'products')));
					break;

				default:
					if (!empty($filter_data['current'])) {
						$products = null;
					} else {
						$results = $this->model_journal3_filter->getProducts($filter_data);
						$products = $this->model_journal3_product->getProduct($results);
					}
			}
		}

		if (($this->settings['sectionsDisplay'] === 'tabs' || $this->settings['sectionsDisplay'] === 'accordion') && $index !== $this->settings['default_index']) {
			$active = false;
		} else {
			$active = true;
		}

		return array(
			'active'        => $active,
			'tab_classes'   => array(
				'tab-' . $this->item_id,
				'active' => $this->settings['sectionsDisplay'] === 'tabs' && $active,
			),
			'panel_classes' => array(
				'panel-collapse',
				'collapse',
				'in' => $this->settings['sectionsDisplay'] === 'accordion' && $active,
			),
			'classes'       => array(
				'tab-pane'     => $this->settings['sectionsDisplay'] === 'tabs',
				'active'       => $this->settings['sectionsDisplay'] === 'tabs' && $active,
				'panel'        => $this->settings['sectionsDisplay'] === 'accordion',
				'panel-active' => $this->settings['sectionsDisplay'] === 'accordion' && $active,
				'swiper-slide' => $this->settings['sectionsDisplay'] === 'blocks' && $this->settings['swiper_carousel'],
			),
			'products'      => $products,
		);
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseSubitemSettings($parser, $index) {
		return array();
	}

	protected function beforeRender() {
		if (!$this->settings['items']) {
			$this->settings = null;

			return;
		}

		foreach ($this->settings['items'] as $key => $item) {
			$products = $item['products'];

			if ($products === null) {
				$filter_data = Arr::get($item, 'filter');

				if (empty($filter_data['limit']) && !empty($this->settings['limit'])) {
					$filter_data['limit'] = $this->settings['limit'];
				}

				$preset = Arr::get($filter_data, 'preset');
				$limit = Arr::get($filter_data, 'limit');
				$results = null;

				switch ($preset) {
					case 'related':
						switch (Arr::get($this->request->get, 'route')) {
							case 'journal3/blog' . JOURNAL3_ROUTE_SEPARATOR . 'post':
								$post_id = (int)Arr::get($this->request->get, 'journal_blog_post_id');
								$results = $this->model_journal3_blog->getRelatedProducts($post_id, $limit);
								break;

							case 'product/product':
								$product_id = (int)Arr::get($this->request->get, 'product_id');
								$results = $this->model_journal3_product->getRelatedProducts($product_id, $limit);
								break;

							case 'checkout/cart':
							case 'checkout/checkout':
								$product_ids = $this->cart->getProducts();
								$results = $this->model_journal3_product->getRelatedProducts($product_ids, $limit);
								break;

							case 'account/wishlist':
								$this->load->model('account/wishlist');
								$product_ids = $this->model_account_wishlist->getWishlist();
								$results = $this->model_journal3_product->getRelatedProducts($product_ids, $limit);
								break;
						}

						break;

					case 'related_category':
					case 'related_subcategory':
						$product_id = (int)Arr::get($this->request->get, 'product_id');

						if ($product_id) {
							$subcategory = $preset === 'related_subcategory';

							$results = $this->model_journal3_product->getRelatedProductsByCategory($product_id, $limit, $subcategory);
						}

						break;

					case 'related_manufacturer':
						$product_id = (int)Arr::get($this->request->get, 'product_id');

						if ($product_id) {
							$results = $this->model_journal3_product->getRelatedProductsByManufacturer($product_id, $limit);
						}

						break;

					case 'alsobought':
						switch (Arr::get($this->request->get, 'route')) {
							case 'product/product':
								$product_id = (int)Arr::get($this->request->get, 'product_id');
								$results = $this->model_journal3_product->getAlsoBoughtProducts($product_id, $limit);
								break;

							case 'checkout/cart':
							case 'checkout/checkout':
								$product_ids = $this->cart->getProducts();
								$results = $this->model_journal3_product->getAlsoBoughtProducts($product_ids, $limit);
								break;

							case 'account/wishlist':
								$this->load->model('account/wishlist');
								$product_ids = $this->model_account_wishlist->getWishlist();
								$results = $this->model_journal3_product->getAlsoBoughtProducts($product_ids, $limit);
								break;
						}

						break;

					case 'recently_viewed':
						$results = $this->model_journal3_product->getRecentlyViewedProducts($limit);
						break;

					default:
						if (!empty($filter_data['current'])) {
							switch (Arr::get($this->request->get, 'route')) {
								case 'product/category':
									$filter_data['categories'] = [$this->journal3_document->getPageId()];
									$results = $this->model_journal3_filter->getProducts($filter_data);
									$results = $this->model_journal3_product->getProduct($results);
									break;

								case 'product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info':
									$filter_data['manufacturers'] = [$this->journal3_document->getPageId()];
									$results = $this->model_journal3_filter->getProducts($filter_data);
									$results = $this->model_journal3_product->getProduct($results);
									break;
							}
						}
				}

				if (!$results) {
					unset($this->settings['items'][$key]);

					continue;
				}

				$products = $this->parseProducts($results);
			} else {
				$products = $this->parseProducts($products);
			}

			if (!$products && !($this->settings['sectionsDisplay'] === 'tabs' && $item['tabType'] === 'link')) {
				unset($this->settings['items'][$key]);

				continue;
			}

			$item['products'] = $products;

			$products = [];

			foreach ($item['products'] as $product) {
				$data = array_merge($this->settings, $item);
				$data['product'] = $product;

				$products[] = $this->load->view('journal3/products', $data);
			}

			$this->settings['items'][$key]['products'] = implode('', $products);
		}

		if (!$this->settings['items']) {
			$this->settings = null;

			return;
		}

		$keys = array_keys($this->settings['items']);

		if (!in_array($this->settings['default_index'], $keys)) {
			$this->settings['default_index'] = $keys[0];
		}

		if ($this->settings['sectionsDisplay'] === 'tabs') {
			$this->settings['items'][$this->settings['default_index']]['active'] = true;
			$this->settings['items'][$this->settings['default_index']]['classes'][] = 'active';
			$this->settings['items'][$this->settings['default_index']]['tab_classes'][] = 'active';
		}

		if ($this->settings['sectionsDisplay'] === 'accordion') {
			$this->settings['items'][$this->settings['default_index']]['active'] = true;
			$this->settings['items'][$this->settings['default_index']]['classes'][] = 'active';
			$this->settings['items'][$this->settings['default_index']]['panel_classes'][] = 'in';
		}
	}

	protected function afterRender() {
		$this->document->addScript('catalog/view/theme/journal3/js/carousel.js', 'js-defer');

		if ($this->settings['swiper_carousel']) {
			$this->document->addStyle('catalog/view/theme/journal3/lib/swiper/swiper-critical.min.css');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.css', 'lib-swiper');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.js', 'lib-swiper');
		}
	}

	private function parseProducts($results) {
		$products = array();

		foreach ($results as $result) {
			if ($result['image']) {
				$image = $this->journal3_image->resize($result['image'], $this->settings['image_width'], $this->settings['image_height'], $this->settings['image_resize']);
				$image2x = $this->journal3_image->resize($result['image'], $this->settings['image_width'] * 2, $this->settings['image_height'] * 2, $this->settings['image_resize']);
			} else {
				$image = $this->journal3_image->resize($this->journal3->get('placeholder'), $this->settings['image_width'], $this->settings['image_height'], $this->settings['image_resize']);
				$image2x = $this->journal3_image->resize($this->journal3->get('placeholder'), $this->settings['image_width'] * 2, $this->settings['image_height'] * 2, $this->settings['image_resize']);
			}

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$price = false;
			}

			if (!is_null($result['special']) && (float)$result['special'] >= 0) {
				if ($result['special_date_end'] && strtotime($result['special_date_end']) < time()) {
					$special = false;
				} else {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				}
			} else {
				$special = false;
			}

			if ($this->config->get('config_tax')) {
				if ($special) {
					$tax = $this->currency->format((float)$result['special'], $this->session->data['currency']);
				} else {
					$tax = $this->currency->format((float)$result['price'], $this->session->data['currency']);
				}
			} else {
				$tax = false;
			}

			if ($this->config->get('config_review_status')) {
				$rating = $result['rating'];
			} else {
				$rating = false;
			}

			if ($this->journal3->is_desktop && Arr::get($this->settings, 'moduleProductGridSecondImageStatus', $this->journal3->get('globalProductGridSecondImageStatus')) && ($additional_image = $result['second_image'])) {
				$second_image = $this->journal3_image->resize($additional_image, $this->settings['image_width'], $this->settings['image_height'], $this->settings['image_resize']);
				$second_image2x = $this->journal3_image->resize($additional_image, $this->settings['image_width'] * 2, $this->settings['image_height'] * 2, $this->settings['image_resize']);
			} else {
				$second_image = false;
				$second_image2x = false;
			}

			$classes = $this->journal3_product_extras->exclude_button($result);
			$labels = $this->journal3_product_extras->labels($result);
			$extra_buttons = $this->journal3_product_extras->extra_buttons($result);

			$classes['out-of-stock'] = $result['quantity'] <= 0;
			$classes['has-zero-price'] = ($special ? (float)$result['special'] : (float)$result['price']) <= 0;
			$classes['has-countdown'] = $special && $result['special_date_end'];
			$classes['has-special'] = (bool)$special;
			$classes['has-extra-button'] = (bool)$extra_buttons;

			$products[$result['product_id']] = array(
				'product_id'     => $result['product_id'],
				'name'           => $result['name'],
				'description'    => !empty($result['description']) ? \Journal3\Utils\Str::utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, (int)$this->settings['moduleProductDescriptionLimit']) . '..' : '',
				'price'          => $price,
				'special'        => $special,
				'tax'            => $tax,
				'minimum'        => $result['minimum'] > 0 ? $result['minimum'] : 1,
				'rating'         => $rating,
				'href'           => $this->journal3_url->link('product/product', 'product_id=' . $result['product_id']),
				'thumb'          => $image,
				'thumb2x'        => $image2x,
				'second_thumb'   => $second_image,
				'second_thumb2x' => $second_image2x,
				'classes'        => $classes,
				'quantity'       => $result['quantity'],
				'stock_status'   => $result['stock_status'],
				'labels'         => $labels,
				'extra_buttons'  => $extra_buttons,
				'date_end'       => $result['special_date_end'],
				'price_value'    => ($result['special'] ? $result['special'] > 0 : $result['price'] > 0),
				'stat1'          => $this->journal3_product_extras->stat($result, $this->settings['moduleProductStat1']),
				'stat2'          => $this->journal3_product_extras->stat($result, $this->settings['moduleProductStat2']),
				'qid'            => uniqid('q-'),
				'button_cart'    => $this->journal3->get('filterAddToCartStock') && $result['quantity'] <= 0 ? $result['stock_status'] : $this->language->get('button_cart'),
			);

			if ($this->settings['swiper_carousel']) {
				$products[$result['product_id']]['classes'][] = 'swiper-slide';
			}
		}

		return $products;
	}

}

class_alias('ControllerJournal3Products', '\Opencart\Catalog\Controller\Journal3\Products');
