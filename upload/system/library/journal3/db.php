<?php

namespace Journal3;

/**
 * Class DB is used for various database helpers
 *
 * It's similar to Opencart DB class but with additional methods
 *
 * @package Journal3
 */
class DB extends Base {

	/**
	 * Used for better encode / decode json elements
	 */
	const REPL = [
		'search'  => ["\n", "\r", "\t"],
		'replace' => ['[~nl~]', '[~nr~]', '[~nt~]'],
	];

	/**
	 * @param string $table
	 * @return string
	 */
	public function prefix($table) {
		return $this->escape(DB_PREFIX . $table);
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	public function escape($value) {
		if (is_array($value)) {
			return implode(', ', array_map(function ($val) {
				return "'{$this->db->escape($val)}'";
			}, $value));
		}

		return $this->db->escape($value);
	}

	/**
	 * @param $value
	 * @return int|string
	 */
	public function escapeInt($value) {
		if (is_array($value)) {
			return implode(', ', array_map(function ($val) {
				return (int)$val;
			}, $value));
		}

		return (int)$value;
	}

	/**
	 * @param $value
	 * @return int|string
	 */
	public function escapeNat($value) {
		if (is_array($value)) {
			return implode(', ', array_map(function ($val) {
				return abs((int)$val);
			}, $value));
		}

		return abs((int)$value);
	}

	/**
	 * @param $table
	 * @return bool
	 */
	public function dbTableExists($table) {
		return $this->db->query("SHOW TABLES LIKE '{$this->prefix($table)}'")->num_rows > 0;
	}

	/**
	 * @param $table
	 * @param $column
	 * @return bool
	 */
	public function dbTableHasColumn($table, $column) {
		$query = $this->db->query("DESCRIBE `{$this->journal3_db->prefix($table)}`");

		foreach ($query->rows as $row) {
			if ($row['Field'] === $column) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $value
	 * @param $serialized
	 * @return false|mixed|string
	 */
	public function encode($value, $serialized) {
		if ($serialized) {
			return json_encode($this->encodeData($value));
		}

		return $value;
	}

	/**
	 * @param $value
	 * @param $serialized
	 * @return mixed|string
	 */
	public function decode($value, $serialized) {
		if ($serialized) {
			return $this->decodeData(json_decode($value, true));
		}

		return $value;
	}

	/**
	 * @param $data
	 * @return array|mixed|string|string[]
	 */
	private function encodeData($data) {
		if (is_object($data)) {
			$data = (array)$data;
		}

		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[$key] = $this->encodeData($value);
			}
		} else if ($data) {
			$data = str_replace(self::REPL['search'], self::REPL['replace'], $data);
		}

		return $data;
	}

	/**
	 * @param $data
	 * @return mixed
	 */
	private function decodeData($data) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[$key] = $this->decodeData($value);
			}
		} else if ($data) {
			$data = str_replace(self::REPL['replace'], self::REPL['search'], $data);
		}

		return $data;
	}

}
