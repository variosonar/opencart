<?php

class ControllerJournal3Variable extends Controller {

	public function __construct($registry) {
		parent::__construct($registry);
		$this->load->model('journal3/variable');
		$this->load->language('error/permission');
	}

	public function all() {
		try {
			$filters = array(
				'type'   => $this->journal3_request->get('type'),
				'filter' => $this->journal3_request->get('filter', ''),
				'sort'   => $this->journal3_request->get('sort', ''),
				'order'  => $this->journal3_request->get('order', ''),
				'page'   => $this->journal3_request->get('page', '1'),
				'limit'  => $this->journal3_request->get('limit', '10'),
			);

			$this->journal3_response->json('success', $this->model_journal3_variable->all($filters));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function get() {
		try {
			$id = urldecode($this->journal3_request->get('id'));
			$type = $this->journal3_request->get('type');

			$this->journal3_response->json('success', $this->model_journal3_variable->get($id, $type));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function add() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/variable')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$type = $this->journal3_request->get('type');
			$data = $this->journal3_request->post('data');

			$this->journal3_response->json('success', $this->model_journal3_variable->add($type, $data));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function edit() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/variable')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = urldecode($this->journal3_request->get('id'));
			$type = $this->journal3_request->get('type');
			$data = $this->journal3_request->post('data');

			$this->journal3_response->json('success', $this->model_journal3_variable->edit($id, $type, $data));

			$this->journal3_cache->delete('variables');
			$this->journal3_cache->delete('settings');
			$this->journal3_cache->delete('skin');
			$this->journal3_cache->delete('layout');
			$this->journal3_cache->delete('module');
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function copy() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/variable')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = urldecode($this->journal3_request->get('id'));
			$type = $this->journal3_request->get('type');

			$this->journal3_response->json('success', $this->model_journal3_variable->copy($id, $type));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function remove() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/variable')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = urldecode($this->journal3_request->get('id'));
			$type = $this->journal3_request->get('type');

			$this->journal3_response->json('success', $this->model_journal3_variable->remove($id, $type));

			$this->journal3_cache->delete('variables');
			$this->journal3_cache->delete('settings');
			$this->journal3_cache->delete('skin');
			$this->journal3_cache->delete('layout');
			$this->journal3_cache->delete('module');
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

}

class_alias('ControllerJournal3Variable', '\Opencart\Admin\Controller\Journal3\Variable');
