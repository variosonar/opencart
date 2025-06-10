<?php

use Journal3\Utils\Arr;

class ControllerJournal3BlogSetting extends Controller {

	public function __construct($registry) {
		parent::__construct($registry);
		$this->load->model('journal3/setting');
		$this->load->language('error/permission');
	}

	public function get() {
		try {
			$id = $this->journal3_request->get('id');

			$this->journal3_response->json('success', $this->model_journal3_setting->get($id, array(
				'blog',
			)));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function edit() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/blog_setting')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = $this->journal3_request->get('id');
			$data = $this->journal3_request->post('data');

			$this->journal3_response->json('success', $this->model_journal3_setting->edit($id, array(
				'blog' => Arr::get($data, 'blog', array()),
			)));

			$this->journal3_cache->delete();
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

}

class_alias('ControllerJournal3BlogSetting', '\Opencart\Admin\Controller\Journal3\BlogSetting');

