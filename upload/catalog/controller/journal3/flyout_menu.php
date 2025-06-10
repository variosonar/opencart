<?php

use Journal3\Opencart\MenuController;
use Journal3\Options\Parser;

class ControllerJournal3FlyoutMenu extends MenuController {

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$display = $this->is_mobile ? 'accordion' : 'dropdown';

		$data = array(
			'edit'    => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'    => $parser->getSetting('name'),
			'classes' => array(
				'accordion-menu' => $display !== 'dropdown',

			),
			'display' => $display,
			'first'   => false,
		);

		return $data;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		return array(
			'isOpen' => false,
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

class_alias('ControllerJournal3FlyoutMenu', '\Opencart\Catalog\Controller\Journal3\FlyoutMenu');
