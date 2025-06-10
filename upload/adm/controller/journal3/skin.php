<?php

class ControllerJournal3Skin extends Controller {

	public function __construct($registry) {
		parent::__construct($registry);
		$this->load->model('journal3/skin');
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

			$this->journal3_response->json('success', $this->model_journal3_skin->all($filters));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function get() {
		try {
			$id = $this->journal3_request->get('id');

			$this->journal3_response->json('success', $this->model_journal3_skin->get($id));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function add() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/skin')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$data = $this->journal3_request->post('data');

			$this->journal3_response->json('success', $this->model_journal3_skin->add($data));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function edit() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/skin')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = $this->journal3_request->get('id');
			$data = $this->journal3_request->post('data');

			$this->journal3_response->json('success', $this->model_journal3_skin->edit($id, $data));

			$this->journal3_cache->delete("skin");
			$this->journal3_cache->delete("layout");
			$this->journal3_cache->delete("module");
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function copy() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/skin')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = $this->journal3_request->get('id');

			$this->journal3_response->json('success', $this->model_journal3_skin->copy($id));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function remove() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/skin')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = $this->journal3_request->get('id');

			$this->journal3_response->json('success', $this->model_journal3_skin->remove($id));

			$this->journal3_cache->delete('skin');
			$this->journal3_cache->delete('layout');
			$this->journal3_cache->delete('module');
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function load() {
		$this->journal3_response->json('success', $this->model_journal3_skin->load());
	}

	public function save() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/skin')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$data = $this->journal3_request->post('data');

			$this->journal3_response->json('success', $this->model_journal3_skin->save($data));

			$this->journal3_cache->delete('settings');
			$this->journal3_cache->delete('skin');
			$this->journal3_cache->delete('layout');
			$this->journal3_cache->delete('module');
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function reset() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/skin')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = $this->journal3_request->get('id');

			$this->journal3_response->json('success', $this->model_journal3_skin->reset($id));

			$this->journal3_cache->delete();
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

}

class_alias('ControllerJournal3Skin', '\Opencart\Admin\Controller\Journal3\Skin');
