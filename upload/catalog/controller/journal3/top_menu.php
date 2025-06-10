<?php

use Journal3\Opencart\MenuController;
use Journal3\Options\Parser;

class ControllerJournal3TopMenu extends MenuController {

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		return array(
			'edit' => 'module_header/' . $this->module_type . '/edit/' . $this->module_id,
			'name' => $parser->getSetting('name'),
		);
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		return array(
			'classes' => array(
				'icon-only' => $parser->getSetting('iconOnly'),
			),
		);
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

class_alias('ControllerJournal3TopMenu', '\Opencart\Catalog\Controller\Journal3\TopMenu');
