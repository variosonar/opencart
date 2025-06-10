<?php

use Journal3\Opencart\ModuleController;

class ControllerJournal3Spacer extends ModuleController {

	/**
	 * @param \Journal3\Options\Parser $parser
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
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		return array();
	}

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseSubitemSettings($parser, $index) {
		return array();
	}
}

class_alias('ControllerJournal3Spacer', '\Opencart\Catalog\Controller\Journal3\Spacer');
