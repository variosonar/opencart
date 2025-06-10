<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;

class ControllerJournal3Form extends ModuleController {

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$data = array(
			'edit' => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name' => $parser->getSetting('name'),
		);

		$data['text_select'] = $this->language->get('text_select');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['button_submit'] = $parser->getSetting('sendButtonText') ?: $this->language->get('button_submit');
		$data['button_upload'] = $this->language->get('button_upload');
		$data['datepicker'] = $this->language->get('datepicker');

		if (version_compare(VERSION, '4.1.0.0', '>=')) {
			$data['action'] = 'index.php?route=journal3/form' . JOURNAL3_ROUTE_SEPARATOR . 'send&module_id=' . (int)$this->module_id;
		} else {
			$data['action'] = 'index.php?route=api/journal3/form&module_id=' . (int)$this->module_id;
		}

		$this->load->model('journal3/information');

		$data['agree_data'] = $this->model_journal3_information->getInformation($parser->getSetting('agree'));

		return $data;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		return array(
			'input_class' => $parser->getSetting('customClass'),
		);
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
		if ($this->journal3_opencart->is_oc4 && !isset($this->session->data['api_id'])) {
			$this->session->data['api_id'] = 'journal3_popup_' . substr(md5(time()), 0, 10);
		}

		if (!isset($this->request->get['route'])) {
			$this->request->get['route'] = 'common/home';
		}

		if ($this->journal3_opencart->is_oc2) {
			if ($this->config->get($this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
				$this->settings['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
			} else {
				$this->settings['captcha'] = '';
			}
		} else if ($this->journal3_opencart->is_oc3) {
			if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
				$this->settings['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
			} else {
				$this->settings['captcha'] = '';
			}
		} else {
			$this->load->model('setting/extension');

			$extension_info = $this->model_setting_extension->getExtensionByCode('captcha', $this->config->get('config_captcha'));

			if ($extension_info && $this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
				$this->settings['captcha'] = $this->load->controller('extension/' . $extension_info['extension'] . '/captcha/' . $extension_info['code']);
			} else {
				$this->settings['captcha'] = '';
			}
		}

		foreach ($this->settings['items'] as &$item) {
			if (!$item['placeholder']) {
				if ($item['type'] === 'select') {
					$item['placeholder'] = $this->settings['text_select'];
				} else {
					$item['placeholder'] = $item['label'];
				}
			}
		}
	}

	public function download() {
		$this->load->model('tool/upload');

		if (isset($this->request->get['code'])) {
			$code = $this->request->get['code'];
		} else {
			$code = 0;
		}

		$upload_info = $this->model_tool_upload->getUploadByCode($code);

		if ($upload_info) {
			$file = DIR_UPLOAD . $upload_info['filename'];
			$mask = basename($upload_info['name']);

			if (!headers_sent()) {
				if (is_file($file)) {
					header('Content-Type: application/octet-stream');
					header('Content-Description: File Transfer');
					header('Content-Disposition: attachment; filename="' . ($mask ? $mask : basename($file)) . '"');
					header('Content-Transfer-Encoding: binary');
					header('Expires: 0');
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header('Pragma: public');
					header('Content-Length: ' . filesize($file));

					readfile($file, 'rb');
					exit;
				} else {
					exit('Error: Could not find file ' . $file . '!');
				}
			} else {
				exit('Error: Headers already sent out!');
			}
		} else {
			$this->load->language('error/not_found');

			$this->document->setTitle($this->language->get('heading_title'));

			$data['heading_title'] = $this->language->get('heading_title');

			$data['text_not_found'] = $this->language->get('text_not_found');

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true),
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('error/not_found', 'token=' . $this->session->data['token'], true),
			);

			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}

	protected function afterRender() {
		foreach ($this->settings['items'] as $index => $item) {
			if (in_array($item['type'], array('date', 'time', 'datetime'))) {
				if ($this->journal3_opencart->is_oc2) {
					$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment.js', 'lib-datetimepicker');
					$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js', 'lib-datetimepicker');
					$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css', 'lib-datetimepicker');
				} else {
					if (version_compare(VERSION, '4.1.0.0', '<')) {
						$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js', 'lib-datetimepicker');
						$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js', 'lib-datetimepicker');
						$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js', 'lib-datetimepicker');
						$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css', 'lib-datetimepicker');
					}
				}

				break;
			}
		}

		$this->document->addScript('catalog/view/theme/journal3/js/form.js', 'js-defer');
	}

	public function send() {
		return $this->load->controller('api/journal3/form');
	}

}

class_alias('ControllerJournal3Form', '\Opencart\Catalog\Controller\Journal3\Form');
