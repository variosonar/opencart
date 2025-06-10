<?php

class ControllerJournal3Layout extends Controller {

	public function __construct($registry) {
		parent::__construct($registry);
		$this->load->model('design/layout');
		$this->load->model('journal3/layout');
		$this->load->language('error/permission');
	}

	public function all() {
		try {
			$filters = array(
				'filter' => $this->journal3_request->get('filter', ''),
				'sort'   => $this->journal3_request->get('sort', ''),
				'order'  => $this->journal3_request->get('order', ''),
				'page'   => $this->journal3_request->get('page', '1'),
				'limit'  => $this->journal3_request->get('limit', '10'),
			);

			$this->journal3_response->json('success', $this->model_journal3_layout->all($filters));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function get() {
		try {
			$id = $this->journal3_request->get('id');

			$this->journal3_response->json('success', $this->model_journal3_layout->get($id));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function add() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/layout')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$data = $this->journal3_request->post('data');

			$this->journal3_response->json('success', $this->model_journal3_layout->add($data));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function edit() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/layout')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = $this->journal3_request->get('id');
			$data = $this->journal3_request->post('data');

			$this->journal3_response->json('success', $this->model_journal3_layout->edit($id, $data));

			$this->journal3_cache->delete('layout');
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function copy() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/layout')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = $this->journal3_request->get('id');

			$this->journal3_response->json('success', $this->model_journal3_layout->copy($id));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function remove() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/layout')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = $this->journal3_request->get('id');

			$this->journal3_response->json('success', $this->model_journal3_layout->remove($id));

			$this->journal3_cache->delete('layout');
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

}

class_alias('ControllerJournal3Layout', '\Opencart\Admin\Controller\Journal3\Layout');

