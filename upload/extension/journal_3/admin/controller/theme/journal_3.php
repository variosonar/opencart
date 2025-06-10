<?php

namespace Opencart\Admin\Controller\Extension\Journal3\Theme;

class Journal3 extends \Opencart\System\Engine\Controller {

	public function __construct(\Opencart\System\Engine\Registry $registry) {
		parent::__construct($registry);

		if (!defined('JOURNAL3_INSTALLED')) {
			$this->load->controller('extension/journal3/journal3/startup');
		}
	}

	public function index() {
		$this->response->redirect($this->url->link('journal3/journal', 'user_token=' . $this->session->data['user_token'], true) . '#/dashboard');
	}

	public function install() {
		$this->check();

		$this->load->model('journal3/journal');
		$this->load->model('setting/setting');

		$this->model_journal3_journal->install();

		$this->model_setting_setting->editSetting('theme_journal_3', array(
			'theme_journal_3_status' => '1',
		), 0);

		$this->config->set('theme_journal_3_status', '1');

		$this->db->query("DELETE FROM `" . DB_PREFIX . "startup` WHERE `code` LIKE '%journal_3%'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "startup` (code, action, status) VALUES ('journal_3', 'admin/extension/journal3/journal3/startup', 1)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "startup` (code, action, status) VALUES ('journal_3', 'catalog/extension/journal3/journal3/startup', 1)");
	}

	public function uninstall() {
		$this->check();

		$this->load->model('journal3/journal');

		$this->model_journal3_journal->uninstall();

		$this->db->query("DELETE FROM `" . DB_PREFIX . "startup` WHERE `code` LIKE '%journal_3%'");
	}

	private function check() {
		if (!is_file(DIR_APPLICATION . 'model/journal3/journal.php')) {
			$this->load->model('setting/extension');

			$this->model_setting_extension->uninstall('theme', 'journal_3', 'journal_3');

			$error = "Journal files are missing, reupload admin, catalog, image and system folders to your server!\n\nIf you are using a custom admin folder, make sure files from Journal admin folder are uploaded to your custom admin folder.";

			if (strtolower($this->request->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
				echo json_encode([
					'error' => $error,
				]);
			} else {
				echo nl2br($error);
			}

			exit;
		}
	}

}
