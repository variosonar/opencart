<?php

use Journal3\Utils\Arr;

class ControllerApiJournal3Form extends Controller {

	public function index() {
		if (JOURNAL3_LOG) {
			$file = DIR_LOGS . 'journal3_form/' . date('Y/m/d') . '.log';
			$dir = pathinfo($file, PATHINFO_DIRNAME);

			if (!is_dir($dir)) {
				@mkdir($dir, 0777, true);
			}

			$data = array(
				'date'   => date('Y-m-d H:i:s'),
				'id'     => $this->session->getId(),
				'SERVER' => $_SERVER,
				'GET'    => $_GET,
				'POST'   => $_POST,
			);

			file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL, FILE_APPEND);
		}

		try {
			if (!$this->journal3_request->is_ajax) {
				$this->journal3_response->json('success', array(
					'message' => 'Success!',
				));

				return;
			}

			$module_id = (int)$this->journal3_request->get('module_id');
			$agree = $this->journal3_request->post('agree', '');

			$settings = $this->load->controller('journal3/form', array(
				'module_id'       => $module_id,
				'module_type'     => 'form',
				'return_settings' => true,
			));

			if (!$settings) {
				throw new \Exception('Invalid module id!');
			}

			$this->load->language('account/register');

			$errors = array();
			$data = array();

			$data['title'] = $settings['title'];
			$data['sentEmailTitle'] = $settings['sentEmailTitle'];
			$data['sentEmailField'] = $settings['sentEmailField'];
			$data['sentEmailValue'] = $settings['sentEmailValue'];
			$data['sentEmailUsingModule'] = $settings['sentEmailUsingModule'];
			$data['sentEmailFrom'] = $settings['sentEmailFrom'];
			$data['sentEmailIPAddress'] = $settings['sentEmailIPAddress'];

			$data['url'] = htmlspecialchars_decode($this->journal3_request->post('url', ''));

			if (!$data['url']) {
				$data['url'] = $this->config->get($this->journal3_request->is_https ? 'config_ssl' : 'config_url');
			}

			$data['ip'] = $this->request->server['REMOTE_ADDR'];

			if (isset($settings['agree'])) {
				$this->load->model('journal3/information');

				$agree_data = $this->model_journal3_information->getInformation($settings['agree']);

				if ($agree_data && !$agree) {
					$errors['agree'] = $agree_data['error'];
				}
			}

			$blocked_emails = defined('JOURNAL3_BLOCKED_EMAILS') ? explode(',', JOURNAL3_BLOCKED_EMAILS) : [];

			foreach ($settings['items'] as $index => $item) {
				$value = Arr::get($this->request->post, 'item.' . $index);

				if ($item['type'] !== 'legend') {
					if ($item['required'] && empty($value)) {
						$errors['item[' . $index . ']'] = sprintf($this->language->get('error_custom_field'), $item['label']);
					}
				}

				if ($item['type'] === 'name') {
					$data['name'] = $value;
				} else if ($item['type'] === 'email') {
					$data['email'] = $value;

					if ($value && $blocked_emails && in_array($value, $blocked_emails)) {
						$this->journal3_response->json('success', array(
							'message' => 'Success!',
						));

						return;
					}

					if ($value && !isset($errors['item[' . $index . ']']) && ((\Journal3\Utils\Str::utf8_strlen($value) > 96) || !filter_var($value, FILTER_VALIDATE_EMAIL))) {
						$errors['item[' . $index . ']'] = $this->language->get('error_email');
					}
				}

				$data['items'][$index] = array(
					'input_class' => $item['input_class'],
					'type'        => $item['type'],
					'label'       => $item['label'],
					'value'       => $value,
				);

				if ($item['type'] === 'file') {
					$this->load->model('tool/upload');

					$upload_info = $this->model_tool_upload->getUploadByCode($value);

					if ($upload_info) {
						$data['items'][$index]['code'] = $upload_info['code'];
						$data['items'][$index]['value'] = $upload_info['name'];
						$data['items'][$index]['url'] = $this->url->link('journal3/form' . JOURNAL3_ROUTE_SEPARATOR . 'download', 'code=' . $upload_info['code']);
					}
				}
			}

			if (!isset($this->request->post['g-recaptcha-response'])) {
				$this->request->post['g-recaptcha-response'] = '';
			}

			if (!isset($this->request->post['captcha'])) {
				$this->request->post['captcha'] = '';
			}

			if ($this->journal3_opencart->is_oc2) {
				if ($this->config->get($this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
					$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

					if ($captcha) {
						$errors['captcha'] = $captcha;
					}
				}
			} else if ($this->journal3_opencart->is_oc3) {
				if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
					$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

					if ($captcha) {
						$errors['captcha'] = $captcha;
					}
				}
			} else {
				$this->load->model('setting/extension');

				$extension_info = $this->model_setting_extension->getExtensionByCode('captcha', $this->config->get('config_captcha'));

				if ($extension_info && $this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
					$captcha = $this->load->controller('extension/' . $extension_info['extension'] . '/captcha/' . $extension_info['code'] . '.validate');

					if ($captcha) {
						$errors['captcha'] = $captcha;
					}
				}
			}

			if ($errors) {
				$this->journal3_response->json('error', array('errors' => $errors));
			} else {
				unset($this->session->data['gcapcha']);

				$this->load->model('journal3/message');

				$email_data = array(
					'title'      => $this->config->get('config_name'),
					'logo'       => $settings['sentEmailLogo'] && $this->config->get('config_logo') ? $this->journal3_image->resize($this->config->get('config_logo')) : false,
					'store_name' => $this->config->get('config_name'),
					'store_url'  => $this->config->get($this->journal3_request->is_https ? 'config_ssl' : 'config_url'),
					'data'       => $data,
				);

				$this->model_journal3_message->addMessage($data);

				$replace_keys = array(
					'{{ $module }}',
					'{{ $store }}',
					'{{ $email }}',
				);

				$replace_values = array(
					$settings['name'],
					$this->config->get('config_name'),
					Arr::get($data, 'email'),
				);

				foreach ($data['items'] as $key => $value) {
					if (is_scalar($v = Arr::get($value, 'value'))) {
						$replace_keys[] = '{{ $field' . $key . ' }}';
						$replace_values[] = $v;
					}
				}

				$params = array(
					'to'      => $settings['sentEmailTo'] ? $settings['sentEmailTo'] : $this->config->get('config_email'),
					'subject' => str_replace($replace_keys, $replace_values, $settings['sentEmailSubject']),
					'message' => $this->load->view('journal3/module/form_email', $email_data),
				);

				if (Arr::get($data, 'email')) {
					$params['reply_to'] = $data['email'];
				}

				$this->load->controller('journal3/mail' . JOURNAL3_ROUTE_SEPARATOR . 'send', $params);

				$this->journal3_response->json('success', array(
					'message'  => $settings['sentText'],
					'redirect' => $settings['redirect']['href'] ?? '',
				));
			}
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

}

class_alias('ControllerApiJournal3Form', '\Opencart\Catalog\Controller\Api\Journal3\Form');
