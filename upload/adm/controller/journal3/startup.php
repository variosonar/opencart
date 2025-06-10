<?php

class ControllerJournal3Startup extends Controller {

	public function index() {
		if ((VERSION === '3.0.3.9') && function_exists('oc_strtoupper')) {
			define('JOURNAL3_OLD_OC3039', true);
		}

		// define route separator
		if (!defined('JOURNAL3_ROUTE_SEPARATOR')) {
			define('JOURNAL3_ROUTE_SEPARATOR', '/');
		}

		if (!defined('JOURNAL3_EVENT_SEPARATOR')) {
			define('JOURNAL3_EVENT_SEPARATOR', '/');
		}

		// check theme status
		if (version_compare(VERSION, '4', '>=')) {
			$config_key = 'theme_journal_3_status';
		} else {
			$config_key = 'theme_journal3_status';
		}

		if (empty($this->session->data['user_id']) || !$this->config->get($config_key)) {
			return;
		}

		// version - build
		require_once DIR_SYSTEM . 'library/journal3/build.php';

		// fix permissions caused by renaming journal3.php to journal.php
		if (version_compare(VERSION, '4', '<')) {
			$this->load->model('user/user_group');

			$changed = false;

			if ($this->user->hasPermission('access', 'journal3/journal3') && !$this->user->hasPermission('access', 'journal3/journal')) {
				$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'journal3/journal');
				$changed = true;
			}

			if ($this->user->hasPermission('modify', 'journal3/journal3') && !$this->user->hasPermission('modify', 'journal3/journal')) {
				$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'journal3/journal');
				$changed = true;
			}

			if ($changed) {
				header('Location: ' . $_SERVER['REQUEST_URI']);
				exit;
			}
		}

		// events
		$this->load->controller('journal3/events');

		// set admin url, needed for frontend quick edits
		if (version_compare(VERSION, '3', '<')) {
			$this->session->data['journal3_admin_url'] = str_replace('&amp;', '&', $this->url->link('journal3/journal', 'token=' . $this->session->data['token'] . '&j_edit=1', true));
		} else {
			$this->session->data['journal3_admin_url'] = str_replace('&amp;', '&', $this->url->link('journal3/journal', 'user_token=' . $this->session->data['user_token'] . '&j_edit=1', true));
		}
	}

}

class_alias('ControllerJournal3Startup', '\Opencart\Admin\Controller\Journal3\Startup');
