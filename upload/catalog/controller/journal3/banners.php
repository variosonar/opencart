<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3Banners extends ModuleController {

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$image = Arr::get($this->module_data, 'items.0.image.lang_' . $this->config->get('config_language_id'));

		if (is_file(DIR_IMAGE . $image)) {
			list($width, $height) = @getimagesize('image/' . $image);
		} else {
			$width = null;
			$height = null;
		}

		if ($parser->getSetting('imageDimensions.width')) {
			$width = $parser->getSetting('imageDimensions.width');
		}

		if ($parser->getSetting('imageDimensions.height')) {
			$height = $parser->getSetting('imageDimensions.height');
		}

		$data = array(
			'edit'            => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'            => $parser->getSetting('name'),
			'swiper_carousel' => $parser->getSetting('gridType') === 'ipr' && $parser->getSetting('carousel'),
			'classes'         => [
				'carousel-mode'    => $parser->getSetting('gridType') === 'ipr' && $parser->getSetting('carousel'),
				'align-to-content' => $parser->getSetting('gridType') === 'auto' && $parser->getSetting('autoGridContainerAlignToContent'),
			],
			'carouselOptions' => $this->journal3->carousel($parser->getJs(), 'carouselStyle'),
			'width'    => $width,
			'height'   => $height,
		);

		if ($this->journal3->get('performanceLazyLoadImagesStatus') && $parser->getSetting('lazyLoad')) {
			$data['dummy_image'] = $this->journal3_image->transparent($width, $height);
		}

		return $data;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		$width = $parser->getSetting('imageDimensions.width');
		$height = $parser->getSetting('imageDimensions.height');
		$resize = $parser->getSetting('imageDimensions.resize');

		if (!$width || !$height) {
			$width = Arr::get($this->settings, 'width');
			$height = Arr::get($this->settings, 'height');
			$resize = Arr::get($this->settings, 'imageDimensions.resize');
		}

		$data = array(
			'classes' => array(
				'swiper-slide' => $this->settings['swiper_carousel'],
			),
			'image'   => $this->journal3_image->resize($parser->getSetting('image'), $width, $height, $resize),
			'image2x' => $this->journal3_image->resize($parser->getSetting('image'), $width * 2, $height * 2, $resize),
			'image_width'   => $width,
			'image_height'  => $height,
			'text'    => $parser->getSetting('text'),
		);

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

	protected function afterRender() {
		$this->document->addScript('catalog/view/theme/journal3/js/carousel.js', 'js-defer');

		if ($this->settings['swiper_carousel']) {
			$this->document->addStyle('catalog/view/theme/journal3/lib/swiper/swiper-critical.min.css');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.css', 'lib-swiper');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.js', 'lib-swiper');
		}
	}

}

class_alias('ControllerJournal3Banners', '\Opencart\Catalog\Controller\Journal3\Banners');
