<?php

use Journal3\Utils\Arr;

class ModelJournal3Style extends Model {

	public function all($filters = array()) {
		$filter_sql = "";

		$filter_sql .= "`style_type` = '{$this->journal3_db->escape(Arr::get($filters, 'type'))}'";

		if ($filter = Arr::get($filters, 'filter')) {
			$filter_sql .= " AND `style_name` LIKE '%{$this->journal3_db->escape($filter)}%'";
		}

		$order_sql = "ORDER BY style_label";

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
				`{$this->journal3_db->prefix('journal3_style')}`
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
					'id'    => $row['style_name'],
					'name'  => $row['style_name'],
					'label' => $row['style_label'] ? $row['style_label'] : $row['style_name'],
				);
			}
		}

		return array(
			'count' => $count,
			'items' => $result,
		);
	}

	public function get($id, $type) {
		$query = $this->db->query("
			SELECT
				*
			FROM
				`{$this->journal3_db->prefix('journal3_style')}`
			WHERE 
				`style_name` = '{$this->journal3_db->escape($id)}'
				AND `style_type` = '{$this->journal3_db->escape($type)}'
		");

		if ($query->num_rows === 0) {
			throw new Exception('Style not found!');
		}

		return array(
			'name'  => $query->row['style_name'],
			'label' => $query->row['style_label'] ? $query->row['style_label'] : $query->row['style_name'],
			'value' => $this->journal3_db->decode($query->row['style_value'], $query->row['serialized']),
		);
	}

	public function add($type, $data) {
		$name = Arr::get($data, 'name');
		$label = Arr::get($data, 'label');
		$value = Arr::get($data, 'value');
		$serialized = is_scalar($value) ? 0 : 1;
		$value = $this->journal3_db->encode($value, $serialized);

		$query = $this->db->query("
			SELECT
				COUNT(*) AS total 
			FROM
				`{$this->journal3_db->prefix('journal3_style')}` 
			WHERE
				`style_name` = '{$this->journal3_db->escape($name)}'
				AND `style_type` = '{$this->journal3_db->escape($type)}'
		");

		if ($query->row['total'] > 0) {
			throw new Exception("Style ID already exists. IDs must be unique.");
		}

		$this->db->query("
			INSERT INTO `{$this->journal3_db->prefix('journal3_style')}` (
				`style_name`,
				`style_label`,
				`style_type`,
				`style_value`,
				`serialized`
			) VALUES (
				'{$this->journal3_db->escape($name)}',
				'{$this->journal3_db->escape($label)}',
				'{$this->journal3_db->escape($type)}',
				'{$this->journal3_db->escape($value)}',
				'{$this->journal3_db->escapeInt($serialized)}'
			)
		");

		return $name;
	}

	public function edit($id, $type, $data) {
		$name = Arr::get($data, 'name');
		$label = Arr::get($data, 'label');
		$value = Arr::get($data, 'value');
		$serialized = is_scalar($value) ? 0 : 1;
		$value = $this->journal3_db->encode($value, $serialized);

		$query = $this->db->query("
			SELECT 
				COUNT(*) AS total 
			FROM 
				`{$this->journal3_db->prefix('journal3_style')}` 
			WHERE
				`style_name` != '{$this->journal3_db->escape($id)}'
				AND `style_type` = '{$this->journal3_db->escape($type)}' 
				AND `style_name` = '{$this->journal3_db->escape($name)}'
		");

		if ($query->row['total'] > 0) {
			throw new Exception("Style name already exists!");
		}

		$this->db->query("
			UPDATE `{$this->journal3_db->prefix('journal3_style')}` 
			SET 
				`style_name` = '{$this->journal3_db->escape($name)}',
				`style_label` = '{$this->journal3_db->escape($label)}',
				`style_value` = '{$this->journal3_db->escape($value)}',
				`serialized` = '{$this->journal3_db->escapeInt($serialized)}'
			WHERE
				`style_name` = '{$this->journal3_db->escape($id)}'
				AND `style_type` = '{$this->journal3_db->escape($type)}'
		");

		return $this->get($name, $type);
	}

	public function copy($id, $type) {
		$query = $this->db->query("
			SELECT
				*
			FROM
				`{$this->journal3_db->prefix('journal3_style')}`
			WHERE 
				`style_name` = '{$this->journal3_db->escape($id)}'
				AND `style_type` = '{$this->journal3_db->escape($type)}'
		");

		if ($query->num_rows === 0) {
			throw new Exception('Style not found!');
		}

		$type = $query->row['style_type'];

		$data = array(
			'name'  => $query->row['style_name'] . '_COPY',
			'label' => $query->row['style_label'],
			'value' => $this->journal3_db->decode($query->row['style_value'], $query->row['serialized']),
		);

		return $this->add($type, $data);
	}

	public function remove($id, $type) {
		$id = explode(',', $id);
		$id = $this->journal3_db->escape($id);

		$this->db->query("
			DELETE FROM
				`{$this->journal3_db->prefix('journal3_style')}`
			WHERE 
				`style_name` IN ({$id})
				AND `style_type` = '{$this->journal3_db->escape($type)}'
		");
	}

}

class_alias('ModelJournal3Style', '\Opencart\Admin\Model\Journal3\Style');
