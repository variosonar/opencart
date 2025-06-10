<?php

class ModelJournal3Setting extends Model {

	private static $INDEX_LIST = array(
		'product.model',
		'url_alias.query',
		'url_alias.keyword',
	);

	public function __construct($registry) {
		parent::__construct($registry);

		foreach (static::$INDEX_LIST as &$value) {
			$value = DB_PREFIX . $value;
		}
	}

	/**
	 * @throws Exception
	 */
	public function get($id, $setting_groups = array()) {
		$sql = "
			SELECT
				*
			FROM
				`{$this->journal3_db->prefix('journal3_setting')}`
			WHERE
				`store_id` = '{$this->journal3_db->escapeInt($id)}'
		";

		if ($setting_groups) {
			$sql .= "
				AND `setting_group` IN ({$this->journal3_db->escape($setting_groups)})
			";
		}

		$query = $this->db->query($sql);

		$result = array();

		foreach ($query->rows as $value) {
			$result[$value['setting_group']][$value['setting_name']] = $this->journal3_db->decode($value['setting_value'], $value['serialized']);
		}

		if (!$result) {
			$result = new stdClass();
		}

		return $result;
	}

	public function edit($id, $settings) {
		foreach ($settings as $setting_group => $data) {
			foreach ($data as $key => $value) {
				$serialized = is_scalar($value) ? 0 : 1;

				$this->db->query("
					INSERT INTO `{$this->journal3_db->prefix('journal3_setting')}` (
						`store_id`,
						`setting_group`,
						`setting_name`,
						`setting_value`,
						`serialized`
					) VALUES (
						'{$this->journal3_db->escapeInt($id)}',
						'{$this->journal3_db->escape($setting_group)}',
						'{$this->journal3_db->escape($key)}',
						'{$this->journal3_db->escape($this->journal3_db->encode($value, $serialized))}',
						'{$this->journal3_db->escapeInt($serialized)}'
					) ON DUPLICATE KEY UPDATE 
						`setting_value` = '{$this->journal3_db->escape($this->journal3_db->encode($value, $serialized))}',
						`serialized` = '{$this->journal3_db->escapeInt($serialized)}'
				");
			}
		}
	}

	public function indexes($add_indexes = false) {
		$query = $this->db->query("
			SELECT * 
			FROM 
				INFORMATION_SCHEMA.TABLES 
			WHERE 
				TABLE_SCHEMA = '{$this->journal3_db->escape(DB_DATABASE)}'
				AND TABLE_TYPE = 'BASE TABLE'
			  	AND TABLE_NAME LIKE '{$this->journal3_db->escape(DB_PREFIX)}%'
		");

		$tables_indexes = array();

		foreach ($query->rows as $table) {
			$indexes = $this->getTableIndexes($table['TABLE_NAME']);
			$columns = $this->getTableColumns($table['TABLE_NAME']);

			foreach ($columns as $column) {
				if ($this->canIndex($table['TABLE_NAME'] . '.' . $column) && !in_array($column, $indexes)) {
					if ($add_indexes) {
						$this->addIndex($table['TABLE_NAME'], $column);
					}

					$tables_indexes[] = $table['TABLE_NAME'] . '.' . $column;
				}
			}

		}

		return $tables_indexes;
	}

	private function getTableIndexes($table_name) {
		$query = $this->db->query("
			SELECT * 
			FROM INFORMATION_SCHEMA.STATISTICS 
			WHERE TABLE_SCHEMA = '{$this->journal3_db->escape(DB_DATABASE)}'
			AND TABLE_NAME = '{$this->journal3_db->escape($table_name)}'
		");

		$indexes = array();

		foreach ($query->rows as $index) {
			$indexes[] = $index['COLUMN_NAME'];
		}

		return $indexes;
	}

	private function getTableColumns($table_name) {
		$query = $this->db->query("
			SELECT * 
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE 
				TABLE_SCHEMA = '{$this->journal3_db->escape(DB_DATABASE)}' 
				AND TABLE_NAME = '{$this->journal3_db->escape($table_name)}'
				AND LCASE(DATA_TYPE) NOT IN ('blob', 'text', 'longtext')
		");

		$columns = array();

		foreach ($query->rows as $column) {
			$columns[] = $column['COLUMN_NAME'];
		}

		return $columns;
	}

	private function canIndex($column) {
		if (substr($column, -3) === '_id') {
			return true;
		}

		if (in_array($column, static::$INDEX_LIST)) {
			return true;
		}

		return false;
	}

	private function addIndex($table, $column) {
		ob_start();

		$this->db->query("ALTER TABLE `{$this->db->escape($table)}` ADD INDEX (`{$this->db->escape($column)}`)");

		$buf = ob_get_contents();

		ob_clean();

		if (strpos($buf, 'Error: ALTER') !== false) {
			throw new Exception('Your MySQL user may not have ALTER privilege.');
		}
	}

}

class_alias('ModelJournal3Setting', '\Opencart\Admin\Model\Journal3\Setting');
