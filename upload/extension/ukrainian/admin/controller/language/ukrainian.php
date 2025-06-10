<?php
namespace Opencart\Admin\Controller\Extension\Ukrainian\Language;
class Ukrainian extends \Opencart\System\Engine\Controller {

	private $separator;

	public function __construct($registry) {
		parent::__construct($registry);
		$this->separator = version_compare(VERSION,'4.0.2.0','>=') ? '.' : '|';
	}

	public function index(): void {
		$this->load->language('extension/ukrainian/language/ukrainian');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=language')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/ukrainian/language/ukrainian', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/ukrainian/language/ukrainian' . $this->separator . 'save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=language');

		$data['language_ukrainian_status'] = $this->config->get('language_ukrainian_status');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/ukrainian/language/ukrainian', $data));
	}

	public function save(): void {
		$this->load->language('extension/ukrainian/language/ukrainian');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/ukrainian/language/ukrainian')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('language_ukrainian', $this->request->post);

			$this->load->model('localisation/language');

			$language_info = $this->model_localisation_language->getLanguageByCode('uk-ua');

			$language_info['status'] = (empty($this->request->post['language_ukrainian_status']) ? '0' : '1');

			$this->model_localisation_language->editLanguage($language_info['language_id'], $language_info);

			$meta_tags = $this->config->get('config_description');
			if ($meta_tags && empty($meta_tags[$language_info['language_id']]) && !empty($meta_tags[1])) {
				$meta_tags[$language_info['language_id']] = $meta_tags[1];
				$this->load->model('setting/setting');
				$this->model_setting_setting->editValue('config', 'config_description', $meta_tags);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		if ($this->user->hasPermission('modify', 'extension/language')) {
			$language_data = [
				'name'       => 'Українська',
				'code'       => 'uk-ua',
				'locale'     => 'uk,ua,uk_ua,uk-ua,uk_UA,uk_UA.UTF-8,ukrainian',
				'extension'  => 'ukrainian',
				'status'     => 1,
				'sort_order' => 1
			];

			$this->load->model('localisation/language');

			$this->model_localisation_language->addLanguage($language_data);

			if (is_dir(DIR_EXTENSION . 'ukrainian/extension/opencart/')) {
				$this->copyExtensionTranslations(DIR_EXTENSION . '/ukrainian/extension/opencart/', DIR_EXTENSION . '/opencart/');
			}

		}
	}

	public function uninstall(): void {
		if ($this->user->hasPermission('modify', 'extension/language')) {
			$this->load->model('localisation/language');

			$language_info = $this->model_localisation_language->getLanguageByCode('uk-ua');

			if ($language_info) {
				$this->model_localisation_language->deleteLanguage($language_info['language_id']);
			}
		}
	}

	private function copyExtensionTranslations($src, $dst) : void { 
		$dir = opendir($src); 

		if(!is_dir($dst)) {
			mkdir($dst, 0755);
		}

		while( $file = readdir($dir) ) { 

			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($src . '/' . $file) ) {
					$this->copyExtensionTranslations($src . '/' . $file, $dst . '/' . $file); 
				} else { 
					copy($src . '/' . $file, $dst . '/' . $file); 
				}
			}
		}

		closedir($dir);
	}

}