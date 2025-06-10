<?php

use Journal3\Utils\Arr;

class ModelJournal3Layout extends Model {

	public function get($id) {
		$result = array();

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

		return $result;
	}

}

class_alias('ModelJournal3Layout', '\Opencart\Catalog\Model\Journal3\Layout');

