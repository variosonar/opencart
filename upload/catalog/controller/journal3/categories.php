<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3Categories extends ModuleController {

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
			'classes'         => array(
				'module-categories-' . $parser->getSetting('display'),
				'carousel-mode'    => $parser->getSetting('gridType') === 'ipr' && $parser->getSetting('carousel'),
				'align-to-content' => $parser->getSetting('gridType') === 'auto' && $parser->getSetting('autoGridContainerAlignToContent'),
			),
			'image_width'     => $parser->getSetting('imageDimensions.width', $this->config->get('theme_journal3_image_category_width')),
			'image_height'    => $parser->getSetting('imageDimensions.height', $this->config->get('theme_journal3_image_category_height')),
			'image_resize'    => $parser->getSetting('imageDimensions.resize'),
			'carouselOptions' => $this->journal3->carousel($parser->getJs(), 'carouselStyle'),
		);

		if ($this->journal3->get('performanceLazyLoadImagesStatus')) {
			$data['dummy_image'] = $this->journal3_image->transparent($parser->getSetting('imageDimensions.width', $this->config->get('theme_journal3_image_category_width')), $parser->getSetting('imageDimensions.height', $this->config->get('theme_journal3_image_category_height')));
		}

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
		$this->load->model('catalog/category');
		$this->load->model('journal3/category');

		$results = array();
		$categories = null;

		switch ($parser->getSetting('type')) {
			case 'top':
				$results = $this->model_journal3_category->getCategories(0, (int)$parser->getSetting('limit'));
				$categories = $this->parseCategories($results);

				break;

			case 'sub':
				$results = $this->model_journal3_category->getCategories($parser->getSetting('category'), (int)$parser->getSetting('limit'));
				$categories = $this->parseCategories($results);

				break;

			case 'current_sub':
				break;

			case 'custom':
				$categories = array_filter($parser->getSetting('categories') ?: array());

				if ($categories) {
					foreach ($categories as $category) {
						$category_info = $this->model_journal3_category->getCategory($category);

						if ($category_info) {
							$results[] = $category_info;
						}
					}
				}

				$categories = $this->parseCategories($results);

				break;
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
			'categories'    => $categories,
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

	public function beforeRender() {
		if (!$this->settings['items']) {
			$this->settings = null;

			return;
		}

		foreach ($this->settings['items'] as $key => &$item) {
			switch ($item['type']) {
				case 'current_sub':
					$this->load->model('journal3/category');

					$path = (string)($this->request->get['path'] ?? '');
					$parts = explode('_', $path);
					$category_id = (int)array_pop($parts);

					$results = $this->model_journal3_category->getCategories($category_id, (int)$item['limit']);
					$item['categories'] = $this->parseCategories($results, $path);
					break;
			}

			$item['categories'] = $this->load->view('journal3/categories', array_merge($this->settings, $item));

			if (!$item['categories']) {
				unset($this->settings['items'][$key]);
			}
		}

		if (!$this->settings['items']) {
			$this->settings = null;
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

	private function parseCategories($results, $path = '') {
		$this->load->model('journal3/filter');

		$categories = array();

		foreach ($results as $result) {
			if ($this->settings['images']) {
				if ($result['image']) {
					$image = $this->journal3_image->resize($result['image'], $this->settings['image_width'], $this->settings['image_height'], $this->settings['image_resize']);
					$image2x = $this->journal3_image->resize($result['image'], $this->settings['image_width'] * 2, $this->settings['image_height'] * 2, $this->settings['image_resize']);
				} else {
					$image = $this->journal3_image->resize($this->journal3->get('placeholder'), $this->settings['image_width'], $this->settings['image_height'], $this->settings['image_resize']);
					$image2x = $this->journal3_image->resize($this->journal3->get('placeholder'), $this->settings['image_width'] * 2, $this->settings['image_height'] * 2, $this->settings['image_resize']);
				}
			} else {
				$image = $image2x = null;
			}

			if ($this->settings['productsCount']) {
				$filter_data = array(
					'filter_category_id' => $result['category_id'],
				);

				$total = $this->model_journal3_filter->getTotalProducts($filter_data);

				if ($this->settings['productsCountTextPosition'] === "below") {
					$total_text = $this->journal3->replaceWithValue($this->settings['productsCountText'], '<span class="count-badge">' . $total . '</span>');
				} else {
					$total_text = '<span class="count-badge">' . $total . '</span>';
				}
			} else {
				$total = 0;
				$total_text = null;
			}

			if ($path) {
				$href = $this->journal3_url->link('product/category', 'path=' . $path . '_' . $result['category_id'], $this->journal3_request->is_https);
			} else {
				$href = $this->journal3_url->link('product/category', 'path=' . $result['category_id'], $this->journal3_request->is_https);
			}

			$categories[$result['category_id']] = array(
				'classes'     => array(
					'swiper-slide' => $this->settings['swiper_carousel'],
				),
				'category_id' => $result['category_id'],
				'thumb'       => $image,
				'thumb2x'     => $image2x,
				'name'        => $result['name'],
				'total'       => $total,
				'total_text'  => $total_text,
				'description' => \Journal3\Utils\Str::utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, (int)$this->settings['descLimit']) . '..',
				'href'        => $href,
			);
		}

		return $categories;
	}

}

class_alias('ControllerJournal3Categories', '\Opencart\Catalog\Controller\Journal3\Categories');
