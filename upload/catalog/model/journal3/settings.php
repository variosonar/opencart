<?php

use Journal3\Utils\Arr;

class ModelJournal3Settings extends Model {

	public function getVariables() {
		$query = $this->db->query("
			SELECT
                *
            FROM
                `{$this->journal3_db->prefix('journal3_variable')}`
		");

		$results = array();

		foreach ($query->rows as $row) {
			$results[$row['variable_type']]['__VAR__' . $row['variable_name']] = $this->journal3_db->decode($row['variable_value'], $row['serialized']);
		}

		$query = $this->db->query("
			SELECT
                *
            FROM
                `{$this->journal3_db->prefix('journal3_style')}`
		");

		foreach ($query->rows as $row) {
			$values = $this->journal3_db->decode($row['style_value'], $row['serialized']);

			foreach ($values as $key => $value) {
				if (is_array($value)) {
					foreach ($value as $k => $v) {
						if ($v === '') {
							unset($values[$key][$k]);
						}
					}
				}

			}

			$results[$row['style_type']]['__VAR__' . $row['style_name']] = $values;
		}

		return $results;
	}

	public function getSettings() {
		$results = array();

		// global settings

		$query = $this->db->query("
			SELECT
                setting_name,
                setting_value,
                serialized
            FROM
                `{$this->journal3_db->prefix('journal3_setting')}`
            WHERE
            	`store_id` = '0'
                OR `store_id` = '{$this->config->get('config_store_id')}'
			ORDER BY 
				store_id ASC
		");

		foreach ($query->rows as $row) {
			$results[$row['setting_name']] = $this->journal3_db->decode($row['setting_value'], $row['serialized']);
		}

		$results['dashboard_user'] = $results['dashboard_user_' . $this->config->get('config_store_id')] ?? '';
		$results['dashboard_key'] = $results['dashboard_key_' . $this->config->get('config_store_id')] ?? '';

		$skin_id = Arr::get($results, 'active_skin', 0);

		// skin settings

		$query = $this->db->query("
			SELECT
                setting_name,
                setting_value,
                serialized
            FROM
                `{$this->journal3_db->prefix('journal3_skin_setting')}`
            WHERE
                `skin_id` = '{$this->journal3_db->escapeInt($skin_id)}'
		");

		foreach ($query->rows as $row) {
			$results[$row['setting_name']] = $this->journal3_db->decode($row['setting_value'], $row['serialized']);
		}

		return $results;
	}

}

class_alias('ModelJournal3Settings', '\Opencart\Catalog\Model\Journal3\Settings');
