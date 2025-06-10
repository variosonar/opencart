<?php

use Journal3\Utils\Arr;

class ModelJournal3Layout extends Model {

	public function all($filters = array()) {
		$filter_sql = "";

		if ($filter = Arr::get($filters, 'filter')) {
			$filter_sql .= " WHERE `name` LIKE '%{$this->journal3_db->escape($filter)}%'";
		}

		$order_sql = "ORDER BY name";

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
				`{$this->journal3_db->prefix('layout')}`
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
					'id'   => $row['layout_id'],
					'name' => $row['name'],
				);
			}
		}

		return array(
			'count' => $count,
			'items' => $result,
		);
	}

	public function get($id) {
		$layout = $this->db->query("
			SELECT
				*
			FROM
				`{$this->journal3_db->prefix('layout')}` l
			LEFT JOIN `{$this->journal3_db->prefix('layout_route')}` lr ON (l.layout_id = lr.layout_id)
			WHERE 
				l.layout_id = '{$this->journal3_db->escapeInt($id)}'
			GROUP BY l.layout_id
		");

		if ($layout->num_rows === 0) {
			throw new Exception('Layout not found!');
		}

		$query = $this->db->query("
			SELECT
				layout_id,
				layout_data
			FROM
				`{$this->journal3_db->prefix('journal3_layout')}`
			WHERE 
				`layout_id` = '{$this->journal3_db->escapeInt($id)}'
				OR `layout_id` = -1
			ORDER BY
				`layout_id` DESC
		");

		$result = array();

		if ($query->num_rows) {
			foreach ($query->rows as $row) {
				if ($row['layout_id'] > 0) {
					$data = $this->journal3_db->decode($row['layout_data'], true);
				} else {
					$data = array(
						'positions' => array(
							'global' => $this->journal3_db->decode($row['layout_data'], true),
						),
					);
				}

				$result = Arr::merge($result, $data);
			}
		}

		$result['layout_name'] = $layout->row['name'];
		$result['layout_route'] = $layout->row['route'];

		return $result;
	}

	public function add($data) {
		$global = $data['positions']['global'];

		unset($data['positions']['global']);

		$name = $data['layout_name'];

		unset($data['layout_name']);

		$this->db->query("
			INSERT INTO `{$this->journal3_db->prefix('layout')}` 
			SET name = '{$this->journal3_db->escape($name)}'
		");

		$id = $this->db->getLastId();

		$this->db->query("
			INSERT INTO `{$this->journal3_db->prefix('journal3_layout')}`
                (`layout_id`, `layout_data`)
            VALUES
                ('{$this->journal3_db->escapeInt($id)}', '{$this->journal3_db->escape($this->journal3_db->encode($data, true))}')
            ON DUPLICATE KEY UPDATE
                `layout_data` = '{$this->journal3_db->escape($this->journal3_db->encode($data, true))}'
		");

		/* @todo check global */

//		$this->db->query("
//			INSERT INTO `{$this->journal3_db->prefix('journal3_layout')}`
//                (`layout_id`, `layout_data`)
//            VALUES
//                ('-1', '{$this->journal3_db->escape($this->journal3_db->encode($global, true))}')
//            ON DUPLICATE KEY UPDATE
//                `layout_data` = '{$this->journal3_db->escape($this->journal3_db->encode($global, true))}'
//		");

		return (string)$id;
	}

	public function edit($id, $data) {
		$global = $data['positions']['global'];

		unset($data['positions']['global']);

		$name = $data['layout_name'];

		unset($data['layout_name']);

		$this->db->query("
			UPDATE `{$this->journal3_db->prefix('layout')}` 
			SET name = '{$this->journal3_db->escape($name)}' 
			WHERE layout_id = '{$this->journal3_db->escapeInt($id)}'
		");

		if (JOURNAL3_ENV === 'development') {
			$route = $data['layout_route'];

			unset($data['layout_route']);

			$layout_route = $this->db->query("
				SELECT *
				FROM `{$this->journal3_db->prefix('layout_route')}` 
				WHERE layout_id = '{$this->journal3_db->escapeInt($id)}'
			");

			if ($layout_route->num_rows) {
				$this->db->query("
					UPDATE `{$this->journal3_db->prefix('layout_route')}` 
					SET route = '{$this->journal3_db->escape($route)}' 
					WHERE layout_id = '{$this->journal3_db->escapeInt($id)}'
				");
			} else {
				$this->db->query("
					INSERT INTO `{$this->journal3_db->prefix('layout_route')}`
						(`layout_id`, `store_id`, `route`)
					VALUES 
						('{$this->journal3_db->escapeInt($id)}', 0, '{$this->journal3_db->escape($route)}')
				");
			}

			if ($route) {
				$this->db->query("
					UPDATE `{$this->journal3_db->prefix('layout_route')}` 
					SET route = '' 
					WHERE
			    		layout_id != '{$this->journal3_db->escapeInt($id)}'
						AND route = '{$this->journal3_db->escape($route)}'
					");
			}
		}

		$this->db->query("
			INSERT INTO `{$this->journal3_db->prefix('journal3_layout')}`
                (`layout_id`, `layout_data`)
            VALUES
                ('{$this->journal3_db->escapeInt($id)}', '{$this->journal3_db->escape($this->journal3_db->encode($data, true))}')
            ON DUPLICATE KEY UPDATE
                `layout_data` = '{$this->journal3_db->escape($this->journal3_db->encode($data, true))}'
		");

		$this->db->query("
			INSERT INTO `{$this->journal3_db->prefix('journal3_layout')}`
                (`layout_id`, `layout_data`)
            VALUES
                ('-1', '{$this->journal3_db->escape($this->journal3_db->encode($global, true))}')
            ON DUPLICATE KEY UPDATE
                `layout_data` = '{$this->journal3_db->escape($this->journal3_db->encode($global, true))}'
		");

		return $this->get($id);
	}

	public function copy($id) {
		$query = $this->db->query("
			SELECT
				*
			FROM
				`{$this->journal3_db->prefix('layout')}`
			WHERE 
				`layout_id` = '{$this->journal3_db->escapeInt($id)}'
		");

		if ($query->num_rows === 0) {
			throw new Exception('Layout not found!');
		}

		$data = $this->get($id);

		$data['layout_name'] = $query->row['name'] . ' Copy';

		return $this->add($data);
	}

	public function remove($id) {
		$id = explode(',', $id);
		$id = $this->journal3_db->escapeInt($id);

		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('layout')}` WHERE  `layout_id` IN ({$id})");
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('layout_route')}` WHERE  `layout_id` IN ({$id})");
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('layout_module')}` WHERE  `layout_id` IN ({$id})");
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_layout')}` WHERE  `layout_id` IN ({$id})");
	}

}

class_alias('ModelJournal3Layout', '\Opencart\Admin\Model\Journal3\Layout');
