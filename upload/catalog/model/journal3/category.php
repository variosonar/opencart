<?php

class ModelJournal3Category extends Model {

	public function getCategory($category_id) {
		$sql = "
			SELECT DISTINCT * 
			FROM " . DB_PREFIX . "category c 
			LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) 
			LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) 
			WHERE 
				c.category_id = '" . (int)$category_id . "' 
				AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
				AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' 
				AND c.status = '1'
		";

		$query = $this->db->query($sql);

		return $query->row;
	}

	public function getCategories($parent_id = 0, $limit = 0) {
		$sql = "
			SELECT *";

		if ($this->config->get('config_product_count')) {
			$sql .= ", (
					SELECT
						COUNT(p.product_id)
					FROM " . DB_PREFIX . "product_to_category p2c
					LEFT JOIN " . DB_PREFIX . "product p ON (p.product_id = p2c.product_id)
					LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)
					WHERE
						p.status = '1'
						AND p.date_available <= NOW()
						AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'
						AND p2c.category_id = c.category_id) as total
				";
		}

		$sql .= "
			FROM " . DB_PREFIX . "category c 
			LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) 
			LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) 
			WHERE 
				c.parent_id = '" . (int)$parent_id . "' 
				AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
				AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "'  
				AND c.status = '1' 
			ORDER BY 
				c.sort_order, 
				LCASE(cd.name)
		";

		if ($limit) {
			$sql .= "
				LIMIT 0, {$this->journal3_db->escapeNat($limit)}
			";
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTopCategories($limit = 0) {
		$sql = "
			SELECT * 
			FROM " . DB_PREFIX . "category c 
			LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) 
			LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) 
			WHERE 
				c.top = '1'
				AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
				AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "'  
				AND c.status = '1' 
			ORDER BY 
				c.sort_order, 
				LCASE(cd.name)
		";

		if ($limit) {
			$sql .= "
				LIMIT 0, {$this->journal3_db->escapeNat($limit)}
			";
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getCategoryTree($category_id, $max_levels = 0) {
		$max_levels = abs($max_levels) ?: 9;

		if ($category_id) {
			$category = $this->getCategory($category_id);
		} else {
			$category = [];
		}

		$category['items'] = $this->getCategoryTreeRecursive($category_id, $max_levels - 1);

		return $category;
	}

	private function getCategoryTreeRecursive($category_id, $level) {

		$items = $this->getCategories($category_id);

		foreach ($items as &$item) {
			if ($level > 0) {
				$item['items'] = $this->getCategoryTreeRecursive($item['category_id'], $level - 1);
			} else {
				$item['items'] = [];
			}
		}

		return $items;
	}

}

class_alias('ModelJournal3Category', '\Opencart\Catalog\Model\Journal3\Category');
