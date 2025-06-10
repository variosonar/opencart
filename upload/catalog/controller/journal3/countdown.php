<?php

use Journal3\Opencart\ModuleController;

class ControllerJournal3Countdown extends ModuleController {

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		return array(
			'edit' => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name' => $parser->getSetting('name'),
			'date' => date('D M d Y H:i:s O', strtotime($parser->getSetting('date'))),
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

	protected function beforeRender() {
		if (!empty($this->settings['productsModule'])) {
			$this->settings['products'] = $this->load->controller('journal3/products', array(
				'module_type' => 'products',
				'module_id'   => $this->settings['productsModule'],
			));
		}
	}

}

class_alias('ControllerJournal3Countdown', '\Opencart\Catalog\Controller\Journal3\Countdown');
