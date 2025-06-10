<?php

use Journal3\Utils\Str;

class ModelJournal3ImportExport extends Model {

	public function all($filters = array()) {
		$result = array();
		$count = 0;

		$files = glob(DIR_SYSTEM . 'library/journal3/data/import_export/*.sql');

		natsort($files);

		foreach ($files as $file) {
			$size = filesize($file);

			$i = 0;

			$suffix = array(
				'B',
				'KB',
				'MB',
				'GB',
				'TB',
				'PB',
				'EB',
				'ZB',
				'YB',
			);

			while (($size / 1024) > 1) {
				$size = $size / 1024;

				$i++;
			}

			$count++;

			$result[] = array(
				'id'   => $count,
				'name' => basename($file),
				'size' => round(substr($size, 0, strpos($size, '.') + 4), 2) . $suffix[$i],
			);
		}

		return array(
			'count' => $count,
			'items' => $result,
		);
	}

	public function export($tables) {
		$output = '';

		foreach ($tables as $table) {
			if ($this->journal3_db->dbTableExists($table)) {
				$output .= 'TRUNCATE TABLE `oc_' . $this->journal3_db->escape($table) . '`;' . "\n\n";

				$output .= $this->exportTable($table);
			}
		}

		// active skin
		if (in_array('journal3_skin', $tables) && !in_array('journal3_setting', $tables)) {
			$output .= $this->exportTable('journal3_setting', " WHERE `setting_group` = 'active_skin'");
		}

		// blog settings
		if (in_array('journal3_blog_post', $tables) && !in_array('journal3_setting', $tables)) {
			$output .= $this->exportTable('journal3_setting', " WHERE `setting_group` = 'blog'");
		}

		return $output;
	}

	public function exportTable($table, $conditions = '') {
		$output = '';

		$query = $this->db->query("SELECT * FROM `" . $this->journal3_db->prefix($table) . "`" . $conditions);

		foreach ($query->rows as $result) {
			$fields = '';

			foreach (array_keys($result) as $value) {
				$fields .= '`' . $value . '`, ';
			}

			$values = '';

			foreach (array_values($result) as $value) {
				$value = $this->escape($value);

				$values .= '\'' . $value . '\', ';
			}

			if ($table === 'journal3_variable') {
				$duplicate = ' ON DUPLICATE KEY UPDATE `variable_value` = ' . '\'' . $this->escape($result['variable_value']) . '\', `serialized` = ' . '\'' . $this->escape($result['serialized']) . '\'';
			} else if ($table === 'journal3_style') {
				$duplicate = ' ON DUPLICATE KEY UPDATE `style_value` = ' . '\'' . $this->escape($result['style_value']) . '\', `serialized` = ' . '\'' . $this->escape($result['serialized']) . '\'';
			} else if ($table === 'journal3_setting') {
				$duplicate = ' ON DUPLICATE KEY UPDATE `setting_value` = ' . '\'' . $this->escape($result['setting_value']) . '\', `serialized` = ' . '\'' . $this->escape($result['serialized']) . '\'';
			} else {
				$duplicate = '';
			}

			$output .= 'INSERT INTO `oc_' . $this->journal3_db->escape($table) . '` (' . preg_replace('/, $/', '', $fields) . ') VALUES (' . preg_replace('/, $/', '', $values) . ')' . $duplicate . ';' . "\n";
		}

		$output .= "\n\n";

		return $output;
	}

	public function import($content) {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `{$this->db->escape(DB_PREFIX . 'journal3_product_sales')}` (
			    `product_id` INT(11) NOT NULL,
			    `sales` INT(11) NOT NULL,
			    PRIMARY KEY (`product_id`),
			    INDEX (`sales`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8
		");

		if ($this->journal3_opencart->is_oc4) {
			$this->import_oc4($content);
		} else if ($this->journal3_opencart->is_oc3) {
			$this->import_oc3($content);
		} else {
			$this->import_oc2($content);
		}
	}

	private function import_oc4($content) {
		$missing_columns = [];
		$missing_tables = [];
		$null_values = [];

		foreach (explode(";\n", $content) as $sql) {
			$sql = trim($sql);

			if ($sql) {
				$sql = str_replace('`oc_', '`' . DB_PREFIX, $sql);

				if ($sql === "TRUNCATE TABLE `{$this->journal3_db->prefix('category')}`") {
					if (!$this->journal3_db->dbTableHasColumn('category', 'top')) {
						$missing_columns[] = 'category@top';

						$this->db->query("ALTER TABLE `{$this->journal3_db->prefix('category')}` ADD `top` INT");
					}

					if (!$this->journal3_db->dbTableHasColumn('category', 'column')) {
						$missing_columns[] = 'category@column';

						$this->db->query("ALTER TABLE `{$this->journal3_db->prefix('category')}` ADD `column` INT");
					}

					if (!$this->journal3_db->dbTableHasColumn('category', 'date_added')) {
						$missing_columns[] = 'category@date_added';

						$this->db->query("ALTER TABLE `{$this->journal3_db->prefix('category')}` ADD `date_added` DATETIME");
					}

					if (!$this->journal3_db->dbTableHasColumn('category', 'date_modified')) {
						$missing_columns[] = 'category@date_modified';

						$this->db->query("ALTER TABLE `{$this->journal3_db->prefix('category')}` ADD `date_modified` DATETIME");
					}
				}

				if ($sql === "TRUNCATE TABLE `{$this->journal3_db->prefix('filter_description')}`") {
					if (!$this->journal3_db->dbTableHasColumn('filter_description', 'filter_group_id')) {
						$missing_columns[] = 'filter_description@filter_group_id';

						$this->db->query("ALTER TABLE `{$this->journal3_db->prefix('filter_description')}` ADD `filter_group_id` INT NOT NULL DEFAULT 0");
					}
				}

				if ($sql === "TRUNCATE TABLE `{$this->journal3_db->prefix('information')}`") {
					if (!$this->journal3_db->dbTableHasColumn('information', 'bottom')) {
						$missing_columns[] = 'information@bottom';

						$this->db->query("ALTER TABLE `{$this->journal3_db->prefix('information')}` ADD `bottom` INT NOT NULL DEFAULT 0");
					}
				}

				if ($sql === "TRUNCATE TABLE `{$this->journal3_db->prefix('product')}`") {
					if (!$this->journal3_db->dbTableHasColumn('product', 'viewed')) {
						$missing_columns[] = 'product@viewed';

						$this->db->query("ALTER TABLE `{$this->journal3_db->prefix('product')}` ADD `viewed` INT NOT NULL DEFAULT 0");
					}
					$null_values[] = 'product@variant';
					$null_values[] = 'product@override';
				}

				if ($sql === "TRUNCATE TABLE `{$this->journal3_db->prefix('product_recurring')}`") {
					continue;
				}

				if ($sql === "TRUNCATE TABLE `{$this->journal3_db->prefix('product_special')}`") {
					if (!$this->journal3_db->dbTableExists('product_special')) {
						$missing_tables[] = 'product_special';

						$this->db->query("
							CREATE TABLE `{$this->journal3_db->prefix('product_special')}` (
								`product_special_id` int NOT NULL,
								`product_id` int NOT NULL,
								`customer_group_id` int NOT NULL,
								`priority` int NOT NULL DEFAULT '1',
								`price` decimal(15,4) NOT NULL DEFAULT '0.0000',
								`date_start` date NOT NULL,
								`date_end` date NOT NULL
							) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
						");
					}
				}

				if ($sql === "TRUNCATE TABLE `{$this->journal3_db->prefix('seo_url')}`") {
					if (!$this->journal3_db->dbTableHasColumn('seo_url', 'query')) {
						$missing_columns[] = 'seo_url@query';

						$this->db->query("ALTER TABLE `{$this->journal3_db->prefix('seo_url')}` ADD `query` VARCHAR(255) NOT NULL DEFAULT ''");
					}
				}

				if ($sql === "TRUNCATE TABLE `{$this->journal3_db->prefix('order')}`") {
					if (!$this->journal3_db->dbTableHasColumn('order', 'fax')) {
						$missing_columns[] = 'order@fax';

						$this->db->query("ALTER TABLE `{$this->journal3_db->prefix('order')}` ADD `fax` VARCHAR(255) NOT NULL DEFAULT ''");
					}

					if (!$this->journal3_db->dbTableHasColumn('order', 'payment_code')) {
						$missing_columns[] = 'order@payment_code';

						$this->db->query("ALTER TABLE `{$this->journal3_db->prefix('order')}` ADD `payment_code` VARCHAR(255) NOT NULL DEFAULT ''");
					}

					if (!$this->journal3_db->dbTableHasColumn('order', 'shipping_code')) {
						$missing_columns[] = 'order@shipping_code';

						$this->db->query("ALTER TABLE `{$this->journal3_db->prefix('order')}` ADD `shipping_code` VARCHAR(255) NOT NULL DEFAULT ''");
					}
				}

				if ($sql === "TRUNCATE TABLE `{$this->journal3_db->prefix('order_recurring')}`") {
					continue;
				}

				if ($sql === "TRUNCATE TABLE `{$this->journal3_db->prefix('order_recurring_transaction')}`") {
					continue;
				}

				if ($sql === "TRUNCATE TABLE `{$this->journal3_db->prefix('order_shipment')}`") {
					continue;
				}

				if ($sql === "TRUNCATE TABLE `{$this->journal3_db->prefix('order_voucher')}`") {
					continue;
				}

				$this->db->query($sql);
			}
		}

		if (in_array('product@viewed', $missing_columns)) {
			$this->db->query("TRUNCATE TABLE `{$this->journal3_db->prefix('product_viewed')}`");

			$this->db->query("
				INSERT INTO `{$this->journal3_db->prefix('product_viewed')}` (`product_id`, `viewed`)
				SELECT `product_id`, `viewed` FROM `{$this->journal3_db->prefix('product')}`
			");
		}

		if (in_array('seo_url@query', $missing_columns)) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url");

			foreach ($query->rows as $row) {
				if (!empty($row['query'])) {
					$seo_query = explode('=', $row['query']);
					$this->db->query("UPDATE " . DB_PREFIX . "seo_url SET `key` = '" . $this->db->escape($seo_query[0] ?? '') . "', value = '" . $this->db->escape($seo_query[1] ?? '') . "', `sort_order` = 0 WHERE seo_url_id = '" . (int)$row['seo_url_id'] . "'");
				}
			}

			$this->load->model('journal3/journal');
			$this->model_journal3_journal->fixSeoUrls();
		}

		foreach ($missing_columns as $column) {
			[$table, $column] = explode('@', $column);

			$this->db->query("ALTER TABLE `{$this->journal3_db->prefix($table)}` DROP COLUMN `{$column}`");
		}

		if (in_array('product_special', $missing_tables)) {
			$this->db->query("TRUNCATE TABLE `{$this->journal3_db->prefix('product_discount')}`");

			$this->db->query("
				INSERT INTO `{$this->journal3_db->prefix('product_discount')}` (`product_id`, `customer_group_id`, `quantity`, `priority`, `price`, `type`, `special`, `date_start`, `date_end`)
				SELECT `product_id`, `customer_group_id`, 1 as `quantity`, `priority`, `price`, 'F' as `type`, 1 as `special`, `date_start`, `date_end` FROM `{$this->journal3_db->prefix('product_special')}`
			");
		}

		foreach ($missing_tables as $table) {
			$this->db->query("DROP TABLE `{$this->journal3_db->prefix($table)}`");
		}

		foreach ($null_values as $null_value) {
			[$table, $column] = explode('@', $null_value);

			$this->db->query("UPDATE `{$this->journal3_db->prefix($table)}` SET `{$column}` = '' WHERE `{$column}` IS NULL");
		}
	}

	private function import_oc3($content) {
		foreach (explode(";\n", $content) as $sql) {
			$sql = trim($sql);

			if ($sql) {
				$sql = str_replace('`oc_', '`' . DB_PREFIX, $sql);

				if (Str::contains($sql, '`' . $this->journal3_db->prefix('url_alias') . '`')) {
					continue;
				}

				$this->db->query($sql);
			}
		}
	}

	private function import_oc2($content) {
		foreach (explode(";\n", $content) as $sql) {
			$sql = trim($sql);

			if ($sql) {
				$sql = str_replace('`oc_', '`' . DB_PREFIX, $sql);

				if (Str::contains($sql, '`' . $this->journal3_db->prefix('order_shipment') . '`')) {
					continue;
				}

				if (Str::contains($sql, '`' . $this->journal3_db->prefix('seo_url') . '`')) {
					continue;
				}

				$this->db->query($sql);
			}
		}
	}

	private function escape($value) {
		if (!$value) {
			return $value;
		}

		$value = str_replace(array("\x00", "\x0a", "\x0d", "\x1a"), array('\0', '\n', '\r', '\Z'), $value);
		$value = str_replace(array("\n", "\r", "\t"), array('\n', '\r', '\t'), $value);
		$value = str_replace('\\', '\\\\', $value);
		$value = str_replace('\'', '\\\'', $value);
		$value = str_replace('\\\n', '\n', $value);
		$value = str_replace('\\\r', '\r', $value);
		$value = str_replace('\\\t', '\t', $value);

//		if (strpos($prefixed_table, DB_PREFIX . 'journal3') === 0) {
//			$value = str_replace('\n', '\\\n', $value);
//			$value = str_replace('\t', '\\\t', $value);
//		}

		return $value;
	}

}

class_alias('ModelJournal3ImportExport', '\Opencart\Admin\Model\Journal3\ImportExport');
