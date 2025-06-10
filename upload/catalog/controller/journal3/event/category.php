<?php

class ControllerJournal3EventCategory extends Controller {

	private static $category_id;
	private static $category_info;
	private static $categories;

	public function controller_product_category_before(&$route, &$args) {
		if (empty($this->request->get['path'])) {
			$this->request->get['path'] = '';
		}

		$path = explode('_', (string)($this->request->get['path'] ?? ''));

		self::$category_id = (int)array_pop($path);

		if ($this->journal3->get('refineCategories') !== 'none' && $this->journal3->get('subcategoriesDisplay') === 'carousel') {
			$this->document->addStyle('catalog/view/theme/journal3/lib/swiper/swiper-critical.min.css');
			$this->document->addScript('catalog/view/theme/journal3/js/carousel.js', 'js-defer');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.css', 'lib-swiper');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.js', 'lib-swiper');
		}
	}

	public function model_catalog_category_getCategory_after(&$route, &$args, &$output) {
		list($category_id) = $args;

		if ($output && self::$category_info === null && self::$category_id && $category_id == self::$category_id) {
			self::$category_info = $output;

			$output['image'] = '';
		}
	}

	public function model_catalog_category_getCategories_after(&$route, &$args, &$output) {
		$category_id = $args[0] ?? 0;

		if (self::$categories === null && self::$category_id && $category_id == self::$category_id) {
			self::$categories = [];

			if (!empty($output)) {
				foreach ($output as $category) {
					self::$categories[] = $category;
				}
			}
		}
	}

	public function view_product_category_before(&$route, &$args) {
		if (self::$category_info) {
			// category image
			if (self::$category_info['image']) {
				$args['journal3_image_category_width'] = $this->journal3->get('image_dimensions_category.width');
				$args['journal3_image_category_height'] = $this->journal3->get('image_dimensions_category.height');
				$args['journal3_image_category_resize'] = $this->journal3->get('image_dimensions_category.resize');

				$args['thumb'] = $this->journal3_image->resize(self::$category_info['image'], $args['journal3_image_category_width'], $args['journal3_image_category_height'], $args['journal3_image_category_resize']);
				$args['thumb2x'] = $this->journal3_image->resize(self::$category_info['image'], $args['journal3_image_category_width'] * 2, $args['journal3_image_category_height'] * 2, $args['journal3_image_category_resize']);
			}

			// subcategories
			if (self::$categories) {
				$args['journal3_image_subcategory_width'] = $this->journal3->get('image_dimensions_subcategory.width');
				$args['journal3_image_subcategory_height'] = $this->journal3->get('image_dimensions_subcategory.height');
				$args['journal3_image_subcategory_resize'] = $this->journal3->get('image_dimensions_subcategory.resize');

				foreach (self::$categories as $index => $result) {
					$filter_data = array(
						'filter_category_id'  => $result['category_id'],
						'filter_sub_category' => true,
					);

					$args['categories'][$index] = array_merge($args['categories'][$index], array(
						'name'    => $this->journal3->countBadge($result['name'], $this->config->get('config_product_count') ? $this->model_catalog_product->getTotalProducts($filter_data) : null),
						'image'   => $this->journal3_image->resize($result['image'], $args['journal3_image_subcategory_width'], $args['journal3_image_subcategory_height'], $args['journal3_image_subcategory_resize']),
						'image2x' => $this->journal3_image->resize($result['image'], $args['journal3_image_subcategory_width'] * 2, $args['journal3_image_subcategory_height'] * 2, $args['journal3_image_subcategory_resize']),
						'alt'     => $result['name'],
					));
				}

				$args['journal3_images_carousel'] = $this->journal3->carousel($this->journal3_document->getJs(), 'subcategoriesCarouselStyle');
			}
		}
	}

}

class_alias('ControllerJournal3EventCategory', '\Opencart\Catalog\Controller\Journal3\Event\Category');
