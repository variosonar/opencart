<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3Manufacturers extends ModuleController {

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
				'module-manufacturers-' . $parser->getSetting('display'),
				'carousel-mode'    => $parser->getSetting('gridType') === 'ipr' && $parser->getSetting('carousel'),
				'align-to-content' => $parser->getSetting('gridType') === 'auto' && $parser->getSetting('autoGridContainerAlignToContent'),
			],
			'image_width'     => $parser->getSetting('imageDimensions.width', $this->config->get('theme_journal3_image_manufacturer_width')),
			'image_height'    => $parser->getSetting('imageDimensions.height', $this->config->get('theme_journal3_image_manufacturer_height')),
			'image_resize'    => $parser->getSetting('imageDimensions.resize'),
			'carouselOptions' => $this->journal3->carousel($parser->getJs(), 'carouselStyle'),
		);

		if ($this->journal3->get('performanceLazyLoadImagesStatus')) {
			$data['dummy_image'] = $this->journal3_image->transparent($parser->getSetting('imageDimensions.width', $this->config->get('theme_journal3_image_manufacturer_width')), $parser->getSetting('imageDimensions.height', $this->config->get('theme_journal3_image_manufacturer_height')));
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
		$this->load->model('catalog/manufacturer');
		$this->load->model('journal3/manufacturer');

		$results = array();

		switch ($parser->getSetting('type')) {
			case 'top':
				$results = $this->model_catalog_manufacturer->getManufacturers(array(
					'start' => 0,
					'limit' => $parser->getSetting('limit'),
					'sort'  => 'sort_order',
				));

				$results = array_map(function ($result) {
					return array_merge($result, $this->model_journal3_manufacturer->getManufacturer($result['manufacturer_id']) ?? []);
				}, $results);

				break;

			case 'custom':
				$manufacturers = $parser->getSetting('manufacturers', array());

				if ($manufacturers) {
					foreach ($manufacturers as $manufacturer) {
						$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($manufacturer);

						if ($manufacturer_info) {
							$results[] = array_merge($manufacturer_info, $this->model_journal3_manufacturer->getManufacturer($manufacturer));
						}
					}
				}

				break;
		}

		$manufacturers = array();

		foreach ($results as $result) {
			if ($result['image']) {
				$image = $this->journal3_image->resize($result['image'], $this->settings['image_width'], $this->settings['image_height'], $this->settings['image_resize']);
				$image2x = $this->journal3_image->resize($result['image'], $this->settings['image_width'] * 2, $this->settings['image_height'] * 2, $this->settings['image_resize']);
			} else {
				$image = $this->journal3_image->resize($this->journal3->get('placeholder'), $this->settings['image_width'], $this->settings['image_height'], $this->settings['image_resize']);
				$image2x = $this->journal3_image->resize($this->journal3->get('placeholder'), $this->settings['image_width'] * 2, $this->settings['image_height'] * 2, $this->settings['image_resize']);
			}

			$manufacturers[$result['manufacturer_id']] = array(
				'classes'         => array(
					'swiper-slide' => $this->settings['swiper_carousel'],
				),
				'manufacturer_id' => $result['manufacturer_id'],
				'thumb'           => $image,
				'thumb2x'         => $image2x,
				'name'            => $result['name'],
				'href'            => $result['link']['href'],
			);
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
			'manufacturers' => $manufacturers,
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
		$this->settings['items'] = array_map(function ($item) {
			$item['manufacturers'] = $this->load->view('journal3/manufacturers', array_merge($this->settings, $item));

			return $item;
		}, $this->settings['items']);
	}

	protected function afterRender() {
		$this->document->addScript('catalog/view/theme/journal3/js/carousel.js', 'js-defer');

		if ($this->settings['swiper_carousel']) {
			$this->document->addStyle('catalog/view/theme/journal3/lib/swiper/swiper-critical.min.css');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.css', 'lib-swiper');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.js', 'lib-swiper');
		}
	}

}

class_alias('ControllerJournal3Manufacturers', '\Opencart\Catalog\Controller\Journal3\Manufacturers');
