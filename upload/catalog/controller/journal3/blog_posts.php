<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3BlogPosts extends ModuleController {

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$default = $parser->getSetting('default');

		$data = [
			'edit'            => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'            => $parser->getSetting('name'),
			'swiper_carousel' => $parser->getSetting('gridType') === 'ipr' && $parser->getSetting('carousel'),
			'classes'         => [
				'module-blog_posts-' . $parser->getSetting('display'),
				'carousel-mode'    => $parser->getSetting('gridType') === 'ipr' && $parser->getSetting('carousel'),
				'align-to-content' => $parser->getSetting('gridType') === 'auto' && $parser->getSetting('autoGridContainerAlignToContent'),
			],
			'image_width'     => $parser->getSetting('imageDimensions.width', $this->journal3->get('image_dimensions_blog.width')),
			'image_height'    => $parser->getSetting('imageDimensions.height', $this->journal3->get('image_dimensions_blog.height')),
			'image_resize'    => $parser->getSetting('imageDimensions.resize'),
			'carouselOptions' => $this->journal3->carousel($parser->getJs(), 'carouselStyle'),
		];

		if ($this->journal3->get('performanceLazyLoadImagesStatus')) {
			$data['dummy_image'] = $this->journal3_image->transparent($parser->getSetting('imageDimensions.width', $this->journal3->get('image_dimensions_blog.width')), $parser->getSetting('imageDimensions.height', $this->journal3->get('image_dimensions_blog.height')));
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
		$this->load->model('journal3/blog');

		$preset = $parser->getSetting('filter.preset');
		$filter_data = $parser->getSetting('filter');

		switch ($preset) {
			case 'related':
			case 'related_category':
				$posts = null;
				break;

			default:
				$results = $this->model_journal3_blog->getPosts($filter_data);
				$posts = $this->parsePosts($results);
		}

		if (($this->settings['sectionsDisplay'] === 'tabs' || $this->settings['sectionsDisplay'] === 'accordion') && $index !== $this->settings['default_index']) {
			$active = false;
		} else {
			$active = true;
		}

		return [
			'active'        => $active,
			'tab_classes'   => [
				'tab-' . $this->item_id,
				'active' => $this->settings['sectionsDisplay'] === 'tabs' && $active,
			],
			'panel_classes' => [
				'panel-collapse',
				'collapse',
				'in' => $this->settings['sectionsDisplay'] === 'accordion' && $active,
			],
			'classes'       => [
				'tab-pane'     => $this->settings['sectionsDisplay'] === 'tabs',
				'active'       => $this->settings['sectionsDisplay'] === 'tabs' && $active,
				'panel'        => $this->settings['sectionsDisplay'] === 'accordion',
				'panel-active' => $this->settings['sectionsDisplay'] === 'accordion' && $active,
				'swiper-slide' => $this->settings['sectionsDisplay'] === 'blocks' && $this->settings['swiper_carousel'],
			],
			'posts'         => $posts,
		];
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseSubitemSettings($parser, $index) {
		return [];
	}

	protected function beforeRender() {
		if (!$this->settings['items']) {
			$this->settings = null;

			return;
		}

		foreach ($this->settings['items'] as $key => $item) {
			$posts = $item['posts'];

			if ($posts === null) {
				$this->load->model('journal3/blog');

				$filter_data = Arr::get($item, 'filter');
				$preset = Arr::get($filter_data, 'preset');
				$limit = Arr::get($filter_data, 'limit');
				$results = null;

				switch ($preset) {
					case 'related':
						switch (Arr::get($this->request->get, 'route')) {
							case 'product/product':
								$product_id = (int)Arr::get($this->request->get, 'product_id');
								$results = $this->model_journal3_blog->getRelatedPosts($product_id, $limit);
								break;

							default:
								$results = [];
						}

						break;

					case 'related_category':
						$post_id = (int)Arr::get($this->request->get, 'journal_blog_post_id');
						$category_id = (int)Arr::get($this->request->get, 'journal_blog_category_id');

						if (!$category_id) {
							$category_id = $this->model_journal3_blog->getCategoriesByPostId($post_id);

							$categories = array_map(function ($category) {
								return $category['category_id'];
							}, $category_id);
						} else {
							$categories = [$category_id];
						}

						if ($post_id) {
							$filter_data['categories'] = $categories;
							$results = $this->model_journal3_blog->getPosts($filter_data);
						}

						break;

					case 'custom':
						$results = $this->model_journal3_blog->getPost(Arr::get($filter_data, 'posts'));
						break;

					default:
						$results = $this->model_journal3_blog->getPosts($filter_data);
				}

				if (!$results) {
					unset($this->settings['items'][$key]);

					continue;
				}

				$posts = $this->parsePosts($results);
			}

			if (!$posts) {
				unset($this->settings['items'][$key]);

				continue;
			}

			$item['posts'] = $posts;
			$this->settings['items'][$key]['posts'] = $this->load->view('journal3/blog_posts', array_merge($this->settings, $item));
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

	private function parsePosts($results) {
		$posts = [];

		foreach ($results as $result) {
			if ($result['image']) {
				$image = $this->journal3_image->resize($result['image'], $this->settings['image_width'], $this->settings['image_height'], $this->settings['image_resize']);
				$image2x = $this->journal3_image->resize($result['image'], $this->settings['image_width'] * 2, $this->settings['image_height'] * 2, $this->settings['image_resize']);
			} else {
				$image = $this->journal3_image->resize($this->journal3->get('placeholder'), $this->settings['image_width'], $this->settings['image_height'], $this->settings['image_resize']);
				$image2x = $this->journal3_image->resize($this->journal3->get('placeholder'), $this->settings['image_width'] * 2, $this->settings['image_height'] * 2, $this->settings['image_resize']);
			}

			$posts[$result['post_id']] = [
				'classes'     => [
					'swiper-slide' => $this->settings['swiper_carousel'],
				],
				'post_id'     => $result['post_id'],
				'thumb'       => $image,
				'thumb2x'     => $image2x,
				'author'      => $this->model_journal3_blog->getAuthorName($result),
				'name'        => $result['name'],
				'comments'    => $result['comments'],
				'views'       => $result['views'],
				'date'        => $this->journal3->blog_date($result['date']),
				'description' => \Journal3\Utils\Str::utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, (int)$this->journal3->get('blogPostsDescriptionLimit')) . '..',
				'href'        => $this->journal3_url->link('journal3/blog' . JOURNAL3_ROUTE_SEPARATOR . 'post', 'journal_blog_post_id=' . $result['post_id']),
			];
		}

		return $posts;
	}

}

class_alias('ControllerJournal3BlogPosts', '\Opencart\Catalog\Controller\Journal3\BlogPosts');
