<?php

use Journal3\Utils\Arr;

class ModelJournal3Message extends Model {

	public function all($filters = array()) {
		$filter_sql = "";

		if ($filter = Arr::get($filters, 'filter')) {
			$filter_sql .= " WHERE `email` LIKE '%{$this->journal3_db->escape($filter)}%'";
		}

		$order_sql = "ORDER BY date DESC";

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
				`{$this->journal3_db->prefix('journal3_message')}`
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
					'id'     => $row['message_id'],
					'name'   => $row['name'],
					'email'  => $row['email'],
					'fields' => $this->journal3_db->decode($row['fields'], true),
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
				`{$this->journal3_db->prefix('journal3_message')}`
			WHERE 
				`message_id` = '{$this->journal3_db->escapeInt($id)}'
		");

		if ($query->num_rows === 0) {
			throw new Exception('Message not found!');
		}

		$data = $query->row;
		$data['fields'] = array();

		foreach ($this->journal3_db->decode($query->row['fields'], true) as $field) {
			if (is_array($field['value'])) {
				$field['value'] = implode(', ', $field['value']);
			}
			$data['fields'][] = $field;
		}

		return $data;
	}

	public function remove($id) {
		$id = explode(',', $id);
		$id = $this->journal3_db->escapeInt($id);

		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_message')}` WHERE  `message_id` IN ({$id})");
	}

}

class_alias('ModelJournal3Message', '\Opencart\Admin\Model\Journal3\Message');
