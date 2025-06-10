<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;

class ControllerJournal3Marquee extends ModuleController {

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$width = $parser->getSetting('imageDimensions.width');
		$height = $parser->getSetting('imageDimensions.height');
		$resize = $parser->getSetting('imageDimensions.resize');

		$data = [
			'edit'         => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'         => $parser->getSetting('name'),
			'image_width'  => $width,
			'image_height' => $height,
			'image_resize' => $resize,
		];

		if ($this->journal3->get('performanceLazyLoadImagesStatus')) {
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
		$data = [];

		switch ($parser->getSetting('type')) {
			case 'image':
				$width = $parser->getSetting('imageDimensions.width');
				$height = $parser->getSetting('imageDimensions.height');
				$resize = $parser->getSetting('imageDimensions.resize');

				$image = $parser->getSetting('image');

				$data['image'] = $this->journal3_image->resize($image, $width, $height, $resize);
				$data['w2x'] = $this->journal3_image->resize($image, $width * 2, $height * 2, $resize);
				$data['alt'] = '';
				break;
		}

		return $data;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseSubitemSettings($parser, $index) {
		return $this->parseItemSettings($parser, $index);
	}

}

class_alias('ControllerJournal3Marquee', '\Opencart\Catalog\Controller\Journal3\Marquee');
