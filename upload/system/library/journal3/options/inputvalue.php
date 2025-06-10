<?php

namespace Journal3\Options;

use Journal3\Utils\Arr;
use Journal3\Utils\Str;

class InputValue extends Option {

	protected static function parseValue($value, $data = null, $type = 'value') {
		if (Str::startsWith($value, '__VAR__')) {
			$current_type = $data['variableType'] ?? $type;

			if ($current_type === 'font_size') {
				if (!isset(static::$variables[$current_type][$value])) {
					$value = '';
				} else {
					$value = Option::varName($current_type, str_replace('__VAR__', '', $value));
					$value = "var({$value})";
				}
			} else {
				$value = Arr::get(static::$variables, ($data['variableType'] ?? $type) . '.' . $value, '');
			}
		}

		return $value;
	}

}
