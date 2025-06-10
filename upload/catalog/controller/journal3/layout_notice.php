<?php

use Journal3\Opencart\ModuleController;

class ControllerJournal3LayoutNotice extends ModuleController {

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$data = array(
			'edit'    => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'    => $parser->getSetting('name'),
			'options' => $parser->getJs(),
		);

		return $data;
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

	protected function afterRender() {
		$this->journal3_document->addJs(array('layout_notice' => array(
			'm' => $this->module_id,
			'c' => $this->settings['cookie'],
			'o' => $this->settings['options'],
		)));
	}

}

class_alias('ControllerJournal3LayoutNotice', '\Opencart\Catalog\Controller\Journal3\LayoutNotice');
