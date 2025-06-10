<?php

use Journal3\Opencart\ModuleController;

class ControllerJournal3Newsletter extends ModuleController {

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$this->load->model('journal3/information');

		$data = array(
			'edit'       => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'       => $parser->getSetting('name'),
			'agree_data' => $this->model_journal3_information->getInformation($parser->getSetting('agree')),
		);

		if (version_compare(VERSION, '4.1.0.0', '>=')) {
			$data['action'] = 'index.php?route=journal3/newsletter' . JOURNAL3_ROUTE_SEPARATOR . 'send&module_id=' . (int)$this->module_id;
		} else {
			$data['action'] = 'index.php?route=api/journal3/newsletter&module_id=' . (int)$this->module_id;
		}

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

	protected function beforeRender() {
		if ($this->journal3_opencart->is_oc4 && !isset($this->session->data['api_id'])) {
			$this->session->data['api_id'] = 'journal3_newsletter_' . substr(md5(time()), 0, 10);
		}

		if ($this->settings['captcha']) {
			if (!isset($this->request->get['route'])) {
				$this->request->get['route'] = 'common/home';
			}

			if ($this->journal3_opencart->is_oc2) {
				if ($this->config->get($this->config->get('config_captcha') . '_status')) {
					$this->settings['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
				} else {
					$this->settings['captcha'] = '';
				}
			} else if ($this->journal3_opencart->is_oc3) {
				if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status')) {
					$this->settings['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
				} else {
					$this->settings['captcha'] = '';
				}
			} else {
				$this->load->model('setting/extension');

				$extension_info = $this->model_setting_extension->getExtensionByCode('captcha', $this->config->get('config_captcha'));

				if ($extension_info && $this->config->get('captcha_' . $this->config->get('config_captcha') . '_status')) {
					$this->settings['captcha'] = $this->load->controller('extension/' . $extension_info['extension'] . '/captcha/' . $extension_info['code']);
				} else {
					$this->settings['captcha'] = '';
				}
			}
		}
	}

	protected function afterRender() {
		$this->document->addScript('catalog/view/theme/journal3/js/newsletter.js', 'js-defer');
	}

	public function send() {
		return $this->load->controller('api/journal3/newsletter');
	}
}

class_alias('ControllerJournal3Newsletter', '\Opencart\Catalog\Controller\Journal3\Newsletter');
