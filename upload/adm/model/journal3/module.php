<?php

use Journal3\Utils\Arr;

class ModelJournal3Module extends Model {

	private static $SORTS = array(
		'name' => 'module_name',
	);

	public function all($filters = array()) {
		$filter_sql = "";

		$filter_sql .= "`module_type` = '{$this->journal3_db->escape(Arr::get($filters, 'type'))}'";

		if ($filter = Arr::get($filters, 'filter')) {
			$filter_sql .= " AND `module_name` LIKE '%{$this->journal3_db->escape($filter)}%'";
		}

		$order_sql = "";

		if (($sort = Arr::get($filters, 'sort')) !== null) {
			$sort = Arr::get(static::$SORTS, $sort);

			if ($sort) {
				$order_sql .= " ORDER BY {$this->journal3_db->escape($sort)}";

				if (($sort = Arr::get($filters, 'sort')) === 'desc') {
					$order_sql .= ' DESC';
				} else {
					$order_sql .= ' ASC';
				}
			}
		}

		$page = (int)Arr::get($filters, 'page');
		$limit = (int)Arr::get($filters, 'limit');

		if ($page || $limit) {
			if ($page < 1) {
				$page = 1;
			}

			if ($limit < 1) {
				$limit = 10;
			}

			$order_sql .= ' LIMIT ' . (($page - 1) * $limit) . ', ' . $limit;
		}

		$sql = "
			FROM
				`{$this->journal3_db->prefix('journal3_module')}`
			WHERE
				{$filter_sql}						
		";

		$count = (int)$this->db->query("SELECT COUNT(*) AS total {$sql}")->row['total'];

		$result = array();

		if ($count) {
			$query = $this->db->query("
				SELECT
					* 
				{$sql} 
				{$order_sql}
			");

			foreach ($query->rows as $row) {
				$result[] = array(
					'id'   => $row['module_id'],
					'name' => $row['module_name'],
				);
			}
		}

		return array(
			'count' => $count,
			'items' => $result,
		);
	}

	public function get($id) {
		$query = $this->db->query("
			SELECT
				*
			FROM
				`{$this->journal3_db->prefix('journal3_module')}`
			WHERE 
				`module_id` = '{$this->journal3_db->escapeInt($id)}'
		");

		if ($query->num_rows === 0) {
			throw new Exception('Module not found!');
		}

		return $this->journal3_db->decode($query->row['module_data'], true);
	}

	public function add($type, $data) {
		$name = Arr::get($data, 'general.name');

		$query = $this->db->query("
			SELECT
				COUNT(*) AS total 
			FROM
				`{$this->journal3_db->prefix('journal3_module')}` 
			WHERE
				`module_name` = '{$this->journal3_db->escape($name)}'
				AND `module_type` = '{$this->journal3_db->escape($type)}'
		");

		if ($query->row['total'] > 0) {
			throw new Exception("Module name already exists!");
		}

		$this->db->query("
			INSERT INTO `{$this->journal3_db->prefix('journal3_module')}` (
				`module_name`,
				`module_type`,
				`module_data`
			) VALUES (
				'{$this->journal3_db->escape($name)}',
				'{$this->journal3_db->escape($type)}',
				'{$this->journal3_db->escape($this->journal3_db->encode($data, true))}'
			)
		");

		return (string)$this->db->getLastId();
	}

	public function edit($id, $type, $data) {
		$name = Arr::get($data, 'general.name');

		$query = $this->db->query("
			SELECT 
				COUNT(*) AS total 
			FROM 
				`{$this->journal3_db->prefix('journal3_module')}` 
			WHERE 
				`module_name` = '{$this->journal3_db->escape($name)}'
				AND `module_type` = '{$this->journal3_db->escape($type)}'
				AND `module_id` != '{$this->journal3_db->escapeInt($id)}'
		");

		if ($query->row['total'] > 0) {
			throw new Exception("Module name already exists!");
		}

		$this->db->query("
			UPDATE `{$this->journal3_db->prefix('journal3_module')}` 
			SET 
				`module_name` = '{$this->journal3_db->escape($name)}',
				`module_data` = '{$this->journal3_db->escape($this->journal3_db->encode($data, true))}'
			WHERE `module_id` = '{$this->journal3_db->escapeInt($id)}'
		");

		return $this->get($id);
	}

	public function copy($id) {
		$query = $this->db->query("
			SELECT
				*
			FROM
				`{$this->journal3_db->prefix('journal3_module')}`
			WHERE 
				`module_id` = '{$this->journal3_db->escapeInt($id)}'
		");

		if ($query->num_rows === 0) {
			throw new Exception('Module not found!');
		}

		$type = $query->row['module_type'];

		$data = $this->journal3_db->decode($query->row['module_data'], true);
		$data['general']['name'] = $data['general']['name'] . ' Copy';

		return $this->add($type, $data);
	}

	public function remove($id) {
		$id = explode(',', $id);
		$id = $this->journal3_db->escapeInt($id);

		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_module')}` WHERE  `module_id` IN ({$id})");
	}

	public function explodeAttributeValues($separator, $product_id = null, $product_attributes = []) {
		if (!$product_id) {
			$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "journal3_product_attribute`");
		}

		$this->db->query(sprintf(\Journal3\Opencart\Tables::TABLES['journal3_product_attribute'], DB_PREFIX . "journal3_product_attribute"));

		$insert_values = array();

		if ($product_id) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "journal3_product_attribute` WHERE product_id = " . (int)$product_id);

			foreach ($product_attributes as $product_attribute) {
				foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
					$values = explode($separator, htmlspecialchars_decode($product_attribute_description['text']));

					foreach ($values as $value) {
						$value = trim($value);

						if ($value) {
							$insert_values[] = " (
								'{$product_id}',
								'{$product_attribute['attribute_id']}',
								'{$language_id}',
								'{$this->db->escape(htmlspecialchars($value, ENT_COMPAT, 'UTF-8'))}',
								'0'
							) ";
						}
					}
				}
			}
		} else {
			$attribute_values = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_attribute`")->rows;

			foreach ($attribute_values as $attribute_value) {
				$values = explode($separator, htmlspecialchars_decode($attribute_value['text']));

				foreach ($values as $value) {
					$value = trim($value);

					if ($value) {
						$insert_values[] = " (
							'{$attribute_value['product_id']}',
							'{$attribute_value['attribute_id']}',
							'{$attribute_value['language_id']}',
							'{$this->db->escape(htmlspecialchars($value, ENT_COMPAT, 'UTF-8'))}',
							'0'
						) ";
					}
				}
			}
		}

		$insert_values = array_chunk($insert_values, 500);

		foreach ($insert_values as $insert_value) {
			$sql = "
				INSERT IGNORE INTO `" . DB_PREFIX . "journal3_product_attribute` (
					`product_id`,
					`attribute_id`,
					`language_id`,
					`text`,
					`sort_order`
				) VALUES " . implode(',', $insert_value);
			try {
				$this->db->query($sql);
			} catch (Exception $e) {
			}
		}

	}

}

class_alias('ModelJournal3Module', '\Opencart\Admin\Model\Journal3\Module');
