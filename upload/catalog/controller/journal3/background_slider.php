<?php

use Journal3\Opencart\ModuleController;
use Journal3\Utils\Arr;

class ControllerJournal3BackgroundSlider extends ModuleController {

	/**
	 * @param \Journal3\Options\Parser $parser
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
			'edit'     => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'     => $parser->getSetting('name'),
			'width'    => $width,
			'height'   => $height,
			'options'  => array_merge_recursive(
				array(),
				$parser->getJs()
			),
			'classes'  => array(),
			'syncWith' => $parser->getSetting('syncWith') ? '.module-slider-' . $parser->getSetting('syncWith') : '',
		);

		if (!$data['syncWith'] && $parser->getSetting('autoplay')) {
			$data['options']['autoplay'] = [
				'delay' => $parser->getSetting('autoplayDelay'),
			];
		}

		$data['lazyload_placeholder'] = $this->journal3_image->transparent($width, $height);

		return $data;
	}

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		$width = Arr::get($this->settings, 'width');
		$height = Arr::get($this->settings, 'height');

		$data = array(
			'classes' => array(
				'swiper-slide',
			),
		);

		$data['image'] = $this->journal3_image->resize($parser->getSetting('image'), $width, $height, $this->settings['imageDimensions']['resize']);
		$data['image2x'] = $this->journal3_image->resize($parser->getSetting('image'), $width * 2, $height * 2, $this->settings['imageDimensions']['resize']);


		return $data;
	}

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseSubitemSettings($parser, $index) {
		return array();
	}

	protected function beforeRender() {
		if ($this->settings['shuffle']) {
			shuffle($this->settings['items']);
		}
	}

	protected function afterRender() {
		$this->document->addStyle('catalog/view/theme/journal3/lib/swiper-latest/swiper-bundle-critical.min.css');
		$this->document->addScript('catalog/view/theme/journal3/js/slider.js', 'js-defer');
		$this->document->addScript('catalog/view/theme/journal3/lib/swiper-latest/swiper-bundle.min.css', 'lib-swiper-latest');
		$this->document->addScript('catalog/view/theme/journal3/lib/swiper-latest/swiper-bundle.min.js', 'lib-swiper-latest');
	}

}

class_alias('ControllerJournal3BackgroundSlider', '\Opencart\Catalog\Controller\Journal3\BackgroundSlider');
