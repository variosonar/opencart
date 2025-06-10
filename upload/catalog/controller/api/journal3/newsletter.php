<?php

use Journal3\Utils\Str;

class ControllerApiJournal3Newsletter extends Controller {

	public function index() {
		try {
			if (!$this->journal3_request->is_ajax) {
				$this->journal3_response->json('success', array(
					'message' => 'Success!',
				));

				return;
			}

			$module_id = (int)$this->journal3_request->get('module_id');
			$email = $this->journal3_request->post('email', '');
			$agree = $this->journal3_request->post('agree', '');

			$settings = $this->load->controller('journal3/newsletter', array(
				'module_id'       => $module_id,
				'module_type'     => 'newsletter',
				'return_settings' => true,
			));

			if (!$settings) {
				throw new \Exception('Invalid module id!');
			}

			if (!isset($this->request->post['g-recaptcha-response'])) {
				$this->request->post['g-recaptcha-response'] = '';
			}

			if (!isset($this->request->post['captcha'])) {
				$this->request->post['captcha'] = '';
			}

			if ($settings['captcha']) {
				if (!isset($this->request->get['route'])) {
					$this->request->get['route'] = 'common/home';
				}

				if ($this->journal3_opencart->is_oc2) {
					if ($this->config->get($this->config->get('config_captcha') . '_status')) {
						$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

						if ($captcha) {
							throw new \Exception($captcha);
						}
					}
				} else if ($this->journal3_opencart->is_oc3) {
					if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status')) {
						$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

						if ($captcha) {
							throw new \Exception($captcha);
						}
					}
				} else {
					$this->load->model('setting/extension');

					$extension_info = $this->model_setting_extension->getExtensionByCode('captcha', $this->config->get('config_captcha'));

					if ($extension_info && $this->config->get('captcha_' . $this->config->get('config_captcha') . '_status')) {
						$captcha = $this->load->controller('extension/' . $extension_info['extension'] . '/captcha/' . $extension_info['code'] . '.validate');

						if ($captcha) {
							throw new \Exception($captcha);
						}
					}
				}
			}

			$this->load->model('journal3/information');

			$agree_data = $this->model_journal3_information->getInformation($settings['agree']);

			if ($agree_data && !$agree) {
				throw new \Exception($agree_data['error']);
			}

			if ((Str::utf8_strlen($email) > 96) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$this->load->language('information/contact');

				throw new \Exception($this->language->get('error_email'));
			}

			$this->load->model('journal3/newsletter');

			$email_data = array(
				'title'      => $this->config->get('config_name'),
				'logo'       => $settings['emailLogo'] && $this->config->get('config_logo') ? $this->journal3_image->resize($this->config->get('config_logo')) : false,
				'store_name' => $this->config->get('config_name'),
				'store_url'  => $this->config->get($this->journal3_request->is_https ? 'config_ssl' : 'config_url'),
			);

			if ($this->model_journal3_newsletter->isSubscribed($email)) {
				$unsubscribe = (bool)$this->journal3_request->get('unsubscribe', '');

				if ($unsubscribe) {
					$this->model_journal3_newsletter->unsubscribe($email);

					$data['message'] = $settings['unsubscribedMessage'];

					if ($settings['unsubscribedEmail'] && !defined('JOURNAL3_DEMO')) {
						$email_data['message'] = $settings['unsubscribedEmailMessage'];

						$this->load->controller('journal3/mail' . JOURNAL3_ROUTE_SEPARATOR . 'send', array(
							'to'         => $email,
							'subject'    => $this->config->get('config_name'),
							'message'    => $this->load->view('journal3/module/newsletter_unsubscribed_email', $email_data),
							'additional' => false,
						));
					}

					if ($settings['adminAlerts']) {
						$email_data['message'] = Str::textPrint($settings['adminUnsubscribedEmailMessage'], $email);

						$this->load->controller('journal3/mail' . JOURNAL3_ROUTE_SEPARATOR . 'send', array(
							'to'      => $this->config->get('config_email'),
							'subject' => $this->config->get('config_name'),
							'message' => $this->load->view('journal3/module/newsletter_admin_email', $email_data),
						));
					}
				} else {
					$data['message'] = $settings['unsubscribeMessage'];
					$data['unsubscribe'] = true;
				}
			} else {
				$this->model_journal3_newsletter->subscribe($email);

				$data['subscribed'] = true;
				$data['message'] = $settings['subscribedMessage'];

				if ($settings['subscribedEmail'] && !defined('JOURNAL3_DEMO')) {
					$email_data['message'] = $settings['subscribedEmailMessage'];

					$this->load->controller('journal3/mail' . JOURNAL3_ROUTE_SEPARATOR . 'send', array(
						'to'         => $email,
						'subject'    => $this->config->get('config_name'),
						'message'    => $this->load->view('journal3/module/newsletter_subscribed_email', $email_data),
						'additional' => false,
					));
				}

				if ($settings['adminAlerts']) {
					$email_data['message'] = Str::textPrint($settings['adminSubscribedEmailMessage'], $email);

					$this->load->controller('journal3/mail' . JOURNAL3_ROUTE_SEPARATOR . 'send', array(
						'to'      => $this->config->get('config_email'),
						'subject' => $this->config->get('config_name'),
						'message' => $this->load->view('journal3/module/newsletter_admin_email', $email_data),
					));
				}
			}

			$this->journal3_response->json('success', $data);
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

}

class_alias('ControllerApiJournal3Newsletter', '\Opencart\Catalog\Controller\Api\Journal3\Newsletter');
