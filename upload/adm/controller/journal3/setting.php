<?php

class ControllerJournal3Setting extends Controller {

	private static $SETTING_GROUP = array(
		'general',
		'active_skin',
		'seo',
		'performance',
		'custom_code',
	);

	public function __construct($registry) {
		parent::__construct($registry);
		$this->load->model('journal3/setting');
		$this->load->language('error/permission');
	}

	public function get() {
		try {
			$id = $this->journal3_request->get('id');

			$this->journal3_response->json('success', $this->model_journal3_setting->get($id, static::$SETTING_GROUP));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function edit() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/setting')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = $this->journal3_request->get('id');
			$data = $this->journal3_request->post('data');

			$this->journal3_response->json('success', $this->model_journal3_setting->edit($id, $data));

			$this->journal3_cache->delete();
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function check_indexes() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/setting')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$this->journal3_response->json('success', $this->model_journal3_setting->indexes());
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function add_indexes() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/setting')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$this->journal3_response->json('success', $this->model_journal3_setting->indexes(true));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

}

class_alias('ControllerJournal3Setting', '\Opencart\Admin\Controller\Journal3\Setting');
