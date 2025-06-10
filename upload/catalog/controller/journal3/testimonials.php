<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3Testimonials extends ModuleController {

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
				'blocks-' . $parser->getSetting('display'),
				'carousel-mode'    => $parser->getSetting('gridType') === 'ipr' && $parser->getSetting('carousel'),
				'align-to-content' => $parser->getSetting('gridType') === 'auto' && $parser->getSetting('autoGridContainerAlignToContent'),
			],
			'carouselOptions' => $this->journal3->carousel($parser->getJs(), 'carouselStyle'),
		);

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
		$title = $parser->getSetting('title');

		switch ($parser->getSetting('contentType')) {
			case 'description':
			case 'attributes':
			case 'reviews':
				$content = '';
				break;

			default:
				$content = $parser->getSetting('content');
		}

		return array(
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
				'tab-pane'     => $this->settings['display'] === 'tabs',
				'active'       => ($this->settings['display'] === 'tabs') && ($index === $this->settings['default_index']),
				'panel'        => $this->settings['display'] === 'accordion',
				'panel-active' => ($this->settings['display'] === 'accordion') && ($index === $this->settings['default_index']),
				'swiper-slide' => ($this->settings['display'] === 'grid') && $this->settings['swiper_carousel'],
			),
			'image'         => $this->journal3_image->resize($parser->getSetting('image'), $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']),
			'title'         => $title,
			'content'       => $content,
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

	protected function afterRender() {
		$this->document->addScript('catalog/view/theme/journal3/js/carousel.js', 'js-defer');

		if ($this->settings['swiper_carousel']) {
			$this->document->addStyle('catalog/view/theme/journal3/lib/swiper/swiper-critical.min.css');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.css', 'lib-swiper');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.js', 'lib-swiper');
		}
	}

}

class_alias('ControllerJournal3Testimonials', '\Opencart\Catalog\Controller\Journal3\Testimonials');
