<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;

class ControllerJournal3InfoBlocks extends ModuleController {

	/**
	 * @param Parser $parser
	 * @param $module_id
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $module_id) {
		return array(
			'edit'    => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'    => $parser->getSetting('name'),
			'classes' => [
				'align-to-content' => $parser->getSetting('gridType') === 'auto' && $parser->getSetting('autoGridContainerAlignToContent'),
			],
		);
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		$data = array(
			'classes' => array(
				'info-blocks',
				'info-blocks-' . $parser->getSetting('type'),
			),
		);

		if ($parser->getSetting('type') === 'image') {
			$data['image'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']);
			$data['image2x'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']);
		}

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
		foreach ($this->settings['items'] as $item) {
			if ($item['type'] === 'counter') {
				$this->settings['countup'] = true;
				$this->settings['classes'][] = 'has-countup';
				break;
			}
		}
	}

	protected function afterRender() {
		if (!empty($this->settings['countup'])) {
			$this->document->addScript('catalog/view/theme/journal3/js/countup.js', 'js-defer');
			$this->document->addScript('catalog/view/theme/journal3/lib/countup/countup.min.js', 'lib-countup');
		}
	}

}

class_alias('ControllerJournal3InfoBlocks', '\Opencart\Catalog\Controller\Journal3\InfoBlocks');
