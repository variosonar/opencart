<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3CatalogBlocks extends ModuleController {

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$default = $parser->getSetting('default');

		$data = array(
			'edit'            => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'            => $parser->getSetting('name'),
			'swiper_carousel' => $parser->getSetting('gridType') === 'ipr' && $parser->getSetting('carousel'),
			'classes'         => [
				'image-on-hover' => $parser->getSetting('changeImageOnHover'),
			],
			'carouselOptions' => $this->journal3->carousel($parser->getJs(), 'carouselStyle'),
		);

		if ($this->journal3->get('performanceLazyLoadImagesStatus') && $parser->getSetting('lazyLoad')) {
			$data['dummy_image'] = $this->journal3_image->transparent($parser->getSetting('imageDimensions.width'), $parser->getSetting('imageDimensions.height'));
		}

		$data['default_index'] = $parser->getSetting('display') === 'tabs' ? 1 : 0;

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
		$this->load->model('catalog/category');
		$this->load->model('catalog/manufacturer');
		$this->load->model('journal3/category');
		$this->load->model('journal3/filter');
		$this->load->model('journal3/manufacturer');
		$this->load->model('journal3/product');

		$data = array(
			'tab_classes'   => array(
				'tab-' . $this->item_id,
				'active' => ($this->settings['display'] === 'tabs') && ($index === $this->settings['default_index']),
			),
			'panel_classes' => array(
				'panel-collapse',
				'collapse',
				'in' => ($this->settings['display'] === 'accordion') && ($index === $this->settings['default_index']),
			),
			'classes'       => array(
				'block-item',
				'tab-pane'     => $this->settings['display'] === 'tabs',
				'active'       => ($this->settings['display'] === 'tabs') && ($index === $this->settings['default_index']),
				'panel'        => $this->settings['display'] === 'accordion',
				'panel-active' => ($this->settings['display'] === 'accordion') && ($index === $this->settings['default_index']),
				'swiper-slide' => ($this->settings['display'] === 'grid') && $this->settings['swiper_carousel'],
			),
			'title'         => $parser->getSetting('title'),
			'description'   => '',
		);

		$image = $parser->getSetting('image');

		$limit = (int)$parser->getSetting('limit');

		if (!$limit) {
			$limit = (int)$this->settings['limit'];
		}

		switch ($parser->getSetting('subtype')) {
			case 'category':
			case 'product':
				$category_info = $this->model_catalog_category->getCategory((int)$parser->getSetting('category'));

				if ($category_info) {
					$image = $image ?: $category_info['image'];

					$data['title'] = $category_info['name'];
					$data['description'] = \Journal3\Utils\Str::utf8_substr(trim(strip_tags(html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8'))), 0, (int)$this->settings['moduleCategoryDescriptionLimit']) . '..';

					$category_path = (string)$parser->getSetting('category');

					if ($category_prefix = (string)Arr::get($this->module_args, 'category_prefix')) {
						$category_prefix_info = $this->model_journal3_category->getCategory($category_prefix);

						if ($category_prefix_info && ($category_path !== $category_prefix)) {
							$category_path = $category_prefix . '_' . $category_path;
						}
					}

					$data['href'] = $this->journal3_url->link('product/category', 'path=' . $category_path);

					// generate subcategories
					$subcategories = $this->model_journal3_category->getCategories($parser->getSetting('category'));

					foreach ($subcategories as $subcategory) {
						$subcategory['classes'] = [];
						$subcategory['href'] = $this->journal3_url->link('product/category', 'path=' . $category_path . '_' . $subcategory['category_id']);

						if ($this->settings['images']) {
							$subcategory_image = $subcategory['image'];
							$subcategory['image'] = $this->journal3_image->resize($subcategory_image, $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']);
							$subcategory['image2x'] = $this->journal3_image->resize($subcategory_image, $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']);
						} else {
							$subcategory['image'] = false;
							$subcategory['image2x'] = false;
						}

						switch ($parser->getSetting('subtype')) {
							case 'category':
								$results = $this->model_journal3_category->getCategories($subcategory['category_id']);

								$subcategory['total'] = count($results);

								if ($limit) {
									$results = array_slice($results, 0, $limit);
								}

								$subcategory['items'] = [];

								foreach ($results as $result) {
									$subcategory['items'][] = array(
										'name'    => $result['name'],
										'href'    => $this->journal3_url->link('product/category', 'path=' . $category_path . '_' . $subcategory['category_id'] . '_' . $result['category_id']),
										'image'   => $this->settings['images'] && $this->settings['changeImageOnHover'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']) : '',
										'image2x' => $this->settings['images'] && $this->settings['changeImageOnHover'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']) : '',
									);
								}

								break;

							case 'product':
								$filter_data = array(
									'filter_category_id' => $subcategory['category_id'],
									'limit'              => $limit,
									'sort'               => 'p.sort_order',
								);

								$subcategory['total'] = $this->model_journal3_filter->getTotalProducts($filter_data);

								$results = $this->model_journal3_filter->getProducts($filter_data);
								$results = $this->model_journal3_product->getProduct($results);

								$subcategory['items'] = [];

								foreach ($results as $result) {
									$subcategory['items'][] = array(
										'name'    => $result['name'],
										'href'    => $this->journal3_url->link('product/product', 'path=' . $category_path . '_' . $subcategory['category_id'] . '&product_id=' . $result['product_id']),
										'image'   => $this->settings['images'] && $this->settings['changeImageOnHover'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']) : '',
										'image2x' => $this->settings['images'] && $this->settings['changeImageOnHover'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']) : '',
									);
								}

								break;
						}

						$data['items'][] = $subcategory;
					}
				}

				break;

			case 'categories':
				$subcategories = $this->model_journal3_category->getCategories(0);

				foreach ($subcategories as $subcategory) {
					$subcategory['classes'] = [];
					$subcategory['href'] = $this->journal3_url->link('product/category', 'path=' . $subcategory['category_id']);

					if ($this->settings['images']) {
						$subcategory_image = $subcategory['image'];
						$subcategory['image'] = $this->journal3_image->resize($subcategory_image, $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']);
						$subcategory['image2x'] = $this->journal3_image->resize($subcategory_image, $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']);
					} else {
						$subcategory['image'] = false;
						$subcategory['image2x'] = false;
					}

					$results = $this->model_journal3_category->getCategories($subcategory['category_id']);

					$subcategory['total'] = count($results);

					if ($limit) {
						$results = array_slice($results, 0, $limit);
					}

					$subcategory['items'] = [];

					foreach ($results as $result) {
						$subcategory['items'][] = array(
							'name'    => $result['name'],
							'href'    => $this->journal3_url->link('product/category', 'path=' . $subcategory['category_id'] . '_' . $result['category_id']),
							'image'   => $this->settings['images'] && $this->settings['changeImageOnHover'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']) : '',
							'image2x' => $this->settings['images'] && $this->settings['changeImageOnHover'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']) : '',
						);
					}

					$data['items'][] = $subcategory;
				}

				break;

			case 'manufacturers':
				$results = $this->model_catalog_manufacturer->getManufacturers(array(
					'sort' => 'sort_order',
				));

				$results = array_map(function ($result) {
					return array_merge($result, $this->model_journal3_manufacturer->getManufacturer($result['manufacturer_id']));
				}, $results);

				$data['total'] = count($results);

				foreach ($results as $result) {
					$data['items'][] = array(
						'name'    => $result['name'],
						'href'    => $result['link']['href'],
						'image'   => $this->settings['images'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']) : '',
						'image2x' => $this->settings['images'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']) : '',
					);
				}

				break;
		}

		if ($parser->getSetting('header') === 'image') {
			$data['image'] = $this->journal3_image->resize($image, $this->settings['imageDimensionsFeatured']['width'], $this->settings['imageDimensionsFeatured']['height'], $this->settings['imageDimensionsFeatured']['resize']);
			$data['image2x'] = $this->journal3_image->resize($image, $this->settings['imageDimensionsFeatured']['width'] * 2, $this->settings['imageDimensionsFeatured']['height'] * 2, $this->settings['imageDimensionsFeatured']['resize']);
		} else {
			$data['image'] = false;
			$data['image2x'] = false;
		}

		if ($this->settings['imagesTab']) {
			$data['image_tab'] = $this->journal3_image->resize($image, $this->settings['imageDimensionsTab']['width'], $this->settings['imageDimensionsTab']['height'], $this->settings['imageDimensionsTab']['resize']);
			$data['image_tab2x'] = $this->journal3_image->resize($image, $this->settings['imageDimensionsTab']['width'] * 2, $this->settings['imageDimensionsTab']['height'] * 2, $this->settings['imageDimensionsTab']['resize']);
		} else {
			$data['image_tab'] = false;
			$data['image_tab2x'] = false;
		}

		$data['tab_classes']['no-items'] = empty($data['items']);
		$data['panel_classes']['no-items'] = empty($data['items']);
		$data['classes']['no-items'] = empty($data['items']);

		$data['background_image'] = $this->settings['imageBg'] && $image && !$parser->getSetting('itemBackground.background-image') ? $data['image'] : '';

		return $data;
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
		$background_image_css = [];

		foreach ($this->settings['items'] as $key => &$item) {
			if ($item['products']) {
				$item['products'] = $this->load->controller('journal3/products', array(
					'module_type' => 'products',
					'module_id'   => $item['products'],
				));
			}

			if ($item['manufacturers']) {
				$item['manufacturers'] = $this->load->controller('journal3/manufacturers', array(
					'module_type' => 'manufacturers',
					'module_id'   => $item['manufacturers'],
				));
			}

			if ($item['subtype'] === 'custom') {
				$item['items'] = null;

				$item['catalog'] = $this->load->controller('journal3/catalog', array(
					'module_type' => 'catalog',
					'module_id'   => $item['catalog'],
				));
			}

			if (!empty($item['background_image'])) {
				$background_image_css[] = '.module-' . $this->module_type . '-' . $this->module_id . ' .module-item-' . $key . ' .block-body > .block-wrapper {background-image: url(' . $item['background_image'] . ');}';
			}
		}

		if ($background_image_css) {
			$this->css .= implode("\n", $background_image_css);
		}

		if ($this->settings['display'] === 'tabs') {
			if ($this->settings['default_index'] === -1) {
				reset($this->settings['items']);
				$key = key($this->settings['items']);

				$this->settings['items'][$key]['tab_classes'][] = 'active';
				$this->settings['items'][$key]['classes'][] = 'active';
			}
		}

		if ($this->settings['display'] === 'accordion') {
			if ($this->settings['default_index'] === -1) {
				reset($this->settings['items']);
				$key = key($this->settings['items']);

				$this->settings['items'][$key]['panel_classes'][] = 'in';
				$this->settings['items'][$key]['classes'][] = 'active';
			}
		}
	}

	protected function afterRender() {
		$this->document->addScript('catalog/view/theme/journal3/js/carousel.js', 'js-defer');

		if ($this->settings['swiper_carousel']) {
			$this->document->addStyle('catalog/view/theme/journal3/lib/swiper/swiper-critical.min.css');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.css', 'lib-swiper');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.js', 'lib-swiper');
		}

		$this->document->addScript('catalog/view/theme/journal3/js/catalog.js', 'js-defer');
	}

}

class_alias('ControllerJournal3CatalogBlocks', '\Opencart\Catalog\Controller\Journal3\CatalogBlocks');
