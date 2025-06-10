<?php

class ControllerJournal3System extends Controller {

	public function __construct($registry) {
		parent::__construct($registry);

		$this->load->model('journal3/module');
		$this->load->model('journal3/setting');
		$this->load->language('error/permission');
	}

	public function get() {
		try {
			$id = $this->journal3_request->get('id');

			$this->journal3_response->json('success', $this->model_journal3_setting->get($id, array('system')));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function edit() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/system')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = $this->journal3_request->get('id');
			$data = $this->journal3_request->post('data');

			$this->journal3_response->json('success', $this->model_journal3_setting->edit($id, array('system' => $data['system'])));

			$this->journal3_cache->delete();
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function attributes() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/system')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$separator = $this->journal3_request->get('separator');

			$this->model_journal3_module->explodeAttributeValues($separator);

			$this->journal3_response->json('success');
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function fix_seo_urls() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/system')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$this->load->model('journal3/journal');

			$this->model_journal3_journal->fixSeoUrls();

			$this->journal3_response->json('success');
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

}

class_alias('ControllerJournal3System', '\Opencart\Admin\Controller\Journal3\System');
