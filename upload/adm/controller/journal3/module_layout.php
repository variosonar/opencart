<?php

class ControllerJournal3ModuleLayout extends Controller {

	public function __construct($registry) {
		parent::__construct($registry);
		$this->load->model('journal3/journal');
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

			if ($filters['type'] === 'opencart') {
				$modules = $this->model_journal3_journal->getOpencartModules();

				$this->journal3_response->json('success', array(
					'count' => count($modules),
					'items' => $modules,
				));
			} else {
				$this->journal3_response->json('success', $this->model_journal3_module->all($filters));
			}
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
			if (!$this->user->hasPermission('modify', 'journal3/module_layout')) {
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
			if (!$this->user->hasPermission('modify', 'journal3/module_layout')) {
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
			if (!$this->user->hasPermission('modify', 'journal3/module_layout')) {
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
			if (!$this->user->hasPermission('modify', 'journal3/module_layout')) {
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
		$category_id = (int)$this->journal3_request->get('category_id', 0);

		$query = $this->db->query("
			SELECT
				c.category_id,
				c.image,
				cd.language_id,
				cd.name
			FROM " . DB_PREFIX . "category c 
			LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) 
			WHERE 
				c.parent_id = {$category_id} 
				AND c.status = '1' 
			ORDER BY c.sort_order, LCASE(cd.name)
		");

		$results = array();

		foreach ($query->rows as $row) {
			if (!isset($results[$row['category_id']])) {
				$results[$row['category_id']] = $row;
			}

			$results[$row['category_id']]['title']['lang_' . $row['language_id']] = $row['name'];
		}

		$this->load->model('catalog/category');

		if (!$category_id) {
			foreach ($this->model_catalog_category->getCategories() as $category) {
				if (isset($results[$category['category_id']])) {
					$results[$category['category_id']]['name'] = $category['name'];
				}
			}
		}

		$this->journal3_response->json('success', array_values($results));
	}

	public function manufacturers() {
		$results = array();

		$this->load->model('catalog/manufacturer');

		foreach ($this->model_catalog_manufacturer->getManufacturers() as $manufacturer) {
			$results[] = $manufacturer;
		}

		$this->journal3_response->json('success', array_values($results));
	}

}

class_alias('ControllerJournal3ModuleLayout', '\Opencart\Admin\Controller\Journal3\ModuleLayout');
