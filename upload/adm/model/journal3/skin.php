<?php

use Journal3\Utils\Arr;

class ModelJournal3Skin extends Model {

	public function all($filters = array()) {
		$filter_sql = "";

		if ($filter = Arr::get($filters, 'filter')) {
			$filter_sql .= " WHERE `skin_name` LIKE '%{$this->journal3_db->escape($filter)}%'";
		}

		$order_sql = "ORDER BY skin_name";

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
				`{$this->journal3_db->prefix('journal3_skin')}`
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
					'id'   => $row['skin_id'],
					'name' => $row['skin_name'],
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
				`{$this->journal3_db->prefix('journal3_skin')}`
			WHERE 
				`skin_id` = '{$this->journal3_db->escapeInt($id)}'
		");

		if ($query->num_rows === 0) {
			throw new Exception('Skin not found!');
		}

		$skin_name = $query->row['skin_name'];

		$query = $this->db->query("
			SELECT
				*
			FROM
				`{$this->journal3_db->prefix('journal3_skin_setting')}`
			WHERE 
				`skin_id` = '{$this->journal3_db->escapeInt($id)}'
		");

		$result = array();

		foreach ($query->rows as $value) {
			$result['general'][$value['setting_name']] = $this->journal3_db->decode($value['setting_value'], $value['serialized']);
		}

		$result['general']['skinName'] = $skin_name;

		return $result;
	}

	public function add($data) {
		$name = Arr::get($data, 'general.skinName');

		$query = $this->db->query("
			SELECT
				COUNT(*) AS total 
			FROM
				`{$this->journal3_db->prefix('journal3_skin')}` 
			WHERE
				`skin_name` = '{$this->journal3_db->escape($name)}'
		");

		if ($query->row['total'] > 0) {
			throw new Exception("Skin name already exists!");
		}

		$this->db->query("
			INSERT INTO `{$this->journal3_db->prefix('journal3_skin')}` (
				`skin_name`
			) VALUES (
				'{$this->journal3_db->escape($name)}'
			)
		");

		$id = $this->db->getLastId();

		foreach ($data['general'] as $key => $value) {
			$serialized = is_scalar($value) ? 0 : 1;

			$this->db->query("
				INSERT INTO `{$this->journal3_db->prefix('journal3_skin_setting')}` (
					`skin_id`,
					`setting_name`,
					`setting_value`,
					`serialized`
				) VALUES (
					'{$this->journal3_db->escapeInt($id)}',
					'{$this->journal3_db->escape($key)}',
					'{$this->journal3_db->escape($this->journal3_db->encode($value, $serialized))}',
					'{$this->journal3_db->escapeInt($serialized)}'
				)
			");
		}

		return (string)$id;
	}

	public function edit($id, $data) {
		$name = Arr::get($data, 'general.skinName');

		$query = $this->db->query("
			SELECT
				COUNT(*) AS total 
			FROM
				`{$this->journal3_db->prefix('journal3_skin')}` 
			WHERE
				`skin_name` = '{$this->journal3_db->escape($name)}'
				AND `skin_id` != '{$this->journal3_db->escapeInt($id)}'
		");

		if ($query->row['total'] > 0) {
			throw new Exception("Skin name already exists!");
		}

		$this->db->query("
			UPDATE `{$this->journal3_db->prefix('journal3_skin')}` 
			SET 
				`skin_name` = '{$this->journal3_db->escape($name)}'
			WHERE `skin_id` = '{$this->journal3_db->escapeInt($id)}'
		");

		foreach ($data['general'] as $key => $value) {
			$serialized = is_scalar($value) ? 0 : 1;

			$this->db->query("
				INSERT INTO `{$this->journal3_db->prefix('journal3_skin_setting')}` (
					`skin_id`,
					`setting_name`,
					`setting_value`,
					`serialized`
				) VALUES (
					'{$this->journal3_db->escapeInt($id)}',
					'{$this->journal3_db->escape($key)}',
					'{$this->journal3_db->escape($this->journal3_db->encode($value, $serialized))}',
					'{$this->journal3_db->escapeInt($serialized)}'
				) ON DUPLICATE KEY UPDATE 
					`setting_value` = '{$this->journal3_db->escape($this->journal3_db->encode($value, $serialized))}',
					`serialized` = '{$this->journal3_db->escapeInt($serialized)}'
			");
		}

		return $this->get($id);
	}

	public function copy($id) {
		$query = $this->db->query("
			SELECT
				*
			FROM
				`{$this->journal3_db->prefix('journal3_skin')}`
			WHERE 
				`skin_id` = '{$this->journal3_db->escapeInt($id)}'
		");

		if ($query->num_rows === 0) {
			throw new Exception('Skin not found!');
		}

		$data = $this->get($id);

		$data['general']['skinName'] = $query->row['skin_name'] . ' Copy';

		return $this->add($data);
	}

	public function remove($id) {
		$id = explode(',', $id);
		$id = $this->journal3_db->escapeInt($id);

		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_skin')}` WHERE skin_id IN ({$id})");
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_skin_setting')}` WHERE skin_id IN ({$id})");
	}

	public function load() {
		$query = $this->db->query("
			SELECT
				*
			FROM
				`{$this->journal3_db->prefix('journal3_setting')}`
			WHERE
				`setting_name` = 'active_skin'
		");

		$results = array();

		foreach ($query->rows as $row) {
			$results['store_' . $row['store_id']] = $row['setting_value'];
		}

		if (!count($results)) {
			$results = new stdClass();
		}

		return $results;
	}

	public function save($data) {
		foreach ($data as $key => $value) {
			$store_id = str_replace('store_', '', $key);

			$this->db->query("
				INSERT INTO `{$this->journal3_db->prefix('journal3_setting')}` (
					`store_id`,
					`setting_group`,
					`setting_name`,
					`setting_value`,
					`serialized`
				) VALUES (
					'{$this->journal3_db->escapeInt($store_id)}',
					'active_skin',
					'active_skin',
					'{$this->journal3_db->escapeInt($value)}',
					'0'
				) ON DUPLICATE KEY UPDATE
					`setting_value` = '{$this->journal3_db->escapeInt($value)}',
					`serialized` = '0'
			");
		}

		return null;
	}

	public function reset($id) {
		$this->db->query("
			DELETE FROM
				`{$this->journal3_db->prefix('journal3_skin_setting')}`
			WHERE
				`skin_id` = {$this->journal3_db->escapeInt($id)}
			");

		return null;
	}

}

class_alias('ModelJournal3Skin', '\Opencart\Admin\Model\Journal3\Skin');
