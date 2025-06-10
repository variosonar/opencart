<?php

class ModelJournal3Module extends Model {

	public function get($module_id, $module_type) {
		$query = $this->db->query("SELECT * FROM `{$this->journal3_db->escape(DB_PREFIX . 'journal3_module')}` WHERE `module_id` = '{$this->journal3_db->escape($module_id)}'");

		if (!$query->num_rows || $query->row['module_type'] !== $module_type) {
			return array();
		}

		return $this->journal3_db->decode($query->row['module_data'], true);
	}

	public function getByType($module_type) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "journal3_module` WHERE `module_type` = '" . $this->journal3_db->escape($module_type) . "'");

		$results = array();

		foreach ($query->rows as $row) {
			$results[$row['module_id']] = $this->journal3_db->decode($row['module_data'], true);
		}

		return $results;
	}

}

class_alias('ModelJournal3Module', '\Opencart\Catalog\Model\Journal3\Module');
