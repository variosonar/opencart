<?php

use Journal3\Opencart\ModuleController;

class ControllerJournal3Notification extends ModuleController {

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		return array(
			'edit'    => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'    => $parser->getSetting('name'),
			'classes' => array(
				'notification',
			),
			'options' => $parser->getJs(),
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

	protected function afterRender() {
		$this->journal3_document->addJs(array('notification' => array(
			'm' => $this->module_id,
			'c' => $this->settings['cookie'],
			'o' => $this->settings['options'],
		)));
	}

}

class_alias('ControllerJournal3Notification', '\Opencart\Catalog\Controller\Journal3\Notification');
