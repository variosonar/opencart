<?php

namespace Journal3\Options;

use Journal3\Utils\Arr;
use Journal3\Utils\Str;

class Margin extends Option {

	protected static function parseValue($value, $data = null) {
		$rtl = Arr::get($data, 'config.rtl') === true;

		$result = array();

		if (($v = static::getVarValue(Arr::get($value, 'margin', ''))) !== '') {
			$result['margin'] = static::getValueWithUnit($v);
			$result['--element-margin'] = static::getValueWithUnit($v);
		}

		if (($v = static::getVarValue(Arr::get($value, 'margin-top', ''))) !== '') {
			$result['margin-top'] = static::getValueWithUnit($v);
			$result['--element-margin-top'] = static::getValueWithUnit($v);
		}

		if ($rtl) {
			if (($v = static::getVarValue(Arr::get($value, 'margin-right', ''))) !== '') {
				$result['margin-left'] = static::getValueWithUnit($v);
				$result['--element-margin-left'] = static::getValueWithUnit($v);
			}
		} else {
			if (($v = static::getVarValue(Arr::get($value, 'margin-right', ''))) !== '') {
				$result['margin-right'] = static::getValueWithUnit($v);
				$result['--element-margin-right'] = static::getValueWithUnit($v);
			}
		}

		if (($v = static::getVarValue(Arr::get($value, 'margin-bottom', ''))) !== '') {
			$result['margin-bottom'] = static::getValueWithUnit($v);
			$result['--element-margin-bottom'] = static::getValueWithUnit($v);
		}

		if ($rtl) {
			if (($v = static::getVarValue(Arr::get($value, 'margin-left', ''))) !== '') {
				$result['margin-right'] = static::getValueWithUnit($v);
				$result['--element-margin-right'] = static::getValueWithUnit($v);
			}
		} else {
			if (($v = static::getVarValue(Arr::get($value, 'margin-left', ''))) !== '') {
				$result['margin-left'] = static::getValueWithUnit($v);
				$result['--element-margin-left'] = static::getValueWithUnit($v);
			}
		}

		return $result ? $result : null;
	}

	private static function getValueWithUnit($val) {
		if ($val === 'a') {
			return 'auto';
		}

		if (is_numeric($val)) {
			return $val . 'px';
		}

		return $val;
	}

	private static function getVarValue($x) {
		$value = Option::parseValue($x);

		if (Str::startsWith($value, '__VAR__')) {
			if (!isset(static::$variables['gap'][$value])) {
				$value = '';
			} else {
				$value = Option::varName('gap', str_replace('__VAR__', '', $value));
				$value = "var({$value})";
			}
		}

		return $value;
	}

}
