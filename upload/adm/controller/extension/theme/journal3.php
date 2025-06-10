<?php

class ControllerExtensionThemeJournal3 extends Controller {

	public function index() {
		$this->response->redirect($this->url->link('journal3/journal', 'user_token=' . $this->session->data['user_token'], true) . '#/dashboard');
	}

	public function install() {
		$this->load->model('journal3/journal');
		$this->load->model('setting/setting');

		$this->model_journal3_journal->install();

		$this->model_setting_setting->editSetting('theme_journal3', array(
			'theme_journal3_status' => '1',
		), 0);

		$this->config->set('theme_journal3_status', '1');
	}

	public function uninstall() {
		$this->load->model('journal3/journal');

		$this->model_journal3_journal->uninstall();
	}

}
