<?php

class ControllerJournal3ModuleFooter extends Controller {

	public function __construct($registry) {
		parent::__construct($registry);
		$this->load->model('journal3/module');
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

			$this->journal3_response->json('success', $this->model_journal3_module->all($filters));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function get() {
		try {
			$id = $this->journal3_request->get('id');

			$this->journal3_response->json('success', $this->model_journal3_module->get($id));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function add() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/module_footer')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$type = $this->journal3_request->get('type');
			$data = $this->journal3_request->post('data');

			$this->journal3_response->json('success', $this->model_journal3_module->add($type, $data));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function edit() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/module_footer')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = (int)$this->journal3_request->get('id');
			$type = $this->journal3_request->get('type');
			$data = $this->journal3_request->post('data');

			$this->journal3_response->json('success', $this->model_journal3_module->edit($id, $type, $data));

			$this->journal3_cache->delete("module.{$type}.{$id}");
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function copy() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/module_footer')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = $this->journal3_request->get('id');

			$this->journal3_response->json('success', $this->model_journal3_module->copy($id));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function remove() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/module_footer')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = $this->journal3_request->get('id');
			$type = $this->journal3_request->get('type');

			$this->journal3_response->json('success', $this->model_journal3_module->remove($id));

			$this->journal3_cache->delete("module.{$type}");
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function categories() {
		$this->load->model('catalog/category');

		$this->journal3_response->json('success', array_values(array_filter($this->model_catalog_category->getCategories(), function ($category) {
			return $category['parent_id'] == 0;
		})));
	}

	public function attributes() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/module_footer')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$this->model_journal3_module->explodeAttributeValues();

			$this->journal3_response->json('success');
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

}

class_alias('ControllerJournal3ModuleFooter', '\Opencart\Admin\Controller\Journal3\ModuleFooter');
