<?php

namespace Journal3\Options;

use Journal3\Utils\Arr;
use Journal3\Utils\Str;

class BorderRadius extends Option {

	protected static function parseValue($value, $data = null) {
		$rtl = Arr::get($data, 'config.rtl') === true;

		$result = array();

		if (($v = static::getVarValue(Arr::get($value, 'custom', ''))) !== '') {
			$result['border-radius'] = $v;
			$result['--element-border-radius'] = $v;
		} else {
			$unit = static::getVarValue(Arr::get($value, 'borderRadiusUnit', 'px'));

			if (strlen(trim($v = static::getVarValue(Arr::get($value, 'border-radius', '')))) > 0) {
				$result['border-radius'] = is_numeric($v) ? ($v . $unit) : $v;
				$result['--element-border-radius'] = is_numeric($v) ? ($v . $unit) : $v;
			}

			if ($rtl) {
				if (strlen(trim($v = static::getVarValue(Arr::get($value, 'border-top-left-radius', '')))) > 0) {
					$result['border-top-right-radius'] = is_numeric($v) ? ($v . $unit) : $v;
					$result['--element-border-top-right-radius'] = is_numeric($v) ? ($v . $unit) : $v;
				}

				if (strlen(trim($v = static::getVarValue(Arr::get($value, 'border-top-right-radius', '')))) > 0) {
					$result['border-top-left-radius'] = is_numeric($v) ? ($v . $unit) : $v;
					$result['--element-border-top-left-radius'] = is_numeric($v) ? ($v . $unit) : $v;
				}

				if (strlen(trim($v = static::getVarValue(Arr::get($value, 'border-bottom-right-radius', '')))) > 0) {
					$result['border-bottom-left-radius'] = is_numeric($v) ? ($v . $unit) : $v;
					$result['--element-border-bottom-left-radius'] = is_numeric($v) ? ($v . $unit) : $v;
				}

				if (strlen(trim($v = static::getVarValue(Arr::get($value, 'border-bottom-left-radius', '')))) > 0) {
					$result['border-bottom-right-radius'] = is_numeric($v) ? ($v . $unit) : $v;
					$result['--element-border-bottom-right-radius'] = is_numeric($v) ? ($v . $unit) : $v;
				}
			} else {
				if (strlen(trim($v = static::getVarValue(Arr::get($value, 'border-top-left-radius', '')))) > 0) {
					$result['border-top-left-radius'] = is_numeric($v) ? ($v . $unit) : $v;
					$result['--element-border-top-left-radius'] = is_numeric($v) ? ($v . $unit) : $v;
				}

				if (strlen(trim($v = static::getVarValue(Arr::get($value, 'border-top-right-radius', '')))) > 0) {
					$result['border-top-right-radius'] = is_numeric($v) ? ($v . $unit) : $v;
					$result['--element-border-top-right-radius'] = is_numeric($v) ? ($v . $unit) : $v;
				}

				if (strlen(trim($v = static::getVarValue(Arr::get($value, 'border-bottom-right-radius', '')))) > 0) {
					$result['border-bottom-right-radius'] = is_numeric($v) ? ($v . $unit) : $v;
					$result['--element-border-bottom-right-radius'] = is_numeric($v) ? ($v . $unit) : $v;
				}

				if (strlen(trim($v = static::getVarValue(Arr::get($value, 'border-bottom-left-radius', '')))) > 0) {
					$result['border-bottom-left-radius'] = is_numeric($v) ? ($v . $unit) : $v;
					$result['--element-border-bottom-left-radius'] = is_numeric($v) ? ($v . $unit) : $v;
				}
			}
		}

		return $result ? $result : null;
	}

	private static function getVarValue($x) {
		$value = Option::parseValue($x);

		if (Str::startsWith($value, '__VAR__')) {
			$value = Arr::get(static::$variables, 'radius' . '.' . $value, '');
		}

		return $value;
	}

}
