<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;

class ControllerJournal3Text extends ModuleController {

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		return array(
			'edit' => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name' => $parser->getSetting('name'),
		);
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		return array();
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
		if ($this->settings['contentType'] === 'dynamic') {
			$this->settings['content'] = $this->load->controller($this->settings['dynamic'], array(
				'module_id' => $this->module_id,
				'settings'  => $this->settings,
			));
		}
	}

}

class_alias('ControllerJournal3Text', '\Opencart\Catalog\Controller\Journal3\Text');
