<?php

namespace Journal3\Options;

use Journal3\Utils\Str;

class Gap extends Option {

	protected static function parseValue($value, $data = null, $type = 'value') {
		if (Str::startsWith($value, '__VAR__')) {
			if (!isset(static::$variables[$data['variableType'] ?? $type][$value])) {
				$value = '';
			} else {
				$value = Option::varName($data['variableType'] ?? $type, str_replace('__VAR__', '', $value));
				$value = "var({$value})";
			}
		}

		if (is_numeric($value)) {
			$value = $value . 'px';
		}

		return $value;
	}

}
