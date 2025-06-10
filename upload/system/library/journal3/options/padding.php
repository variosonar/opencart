<?php

namespace Journal3\Options;

use Journal3\Utils\Arr;
use Journal3\Utils\Str;

class Padding extends Option {

	protected static function parseValue($value, $data = null) {
		$rtl = Arr::get($data, 'config.rtl') === true;

		$result = array();

		if (($v = static::getVarValue(Arr::get($value, 'padding', ''))) !== '') {
			$result['padding'] = static::getValueWithUnit($v);
			$result['--element-padding'] = static::getValueWithUnit($v);
		}

		if (($v = static::getVarValue(Arr::get($value, 'padding-top', ''))) !== '') {
			$result['padding-top'] = static::getValueWithUnit($v);
			$result['--element-padding-top'] = static::getValueWithUnit($v);
		}

		if ($rtl) {
			if (($v = static::getVarValue(Arr::get($value, 'padding-right', ''))) !== '') {
				$result['padding-left'] = static::getValueWithUnit($v);
				$result['--element-padding-left'] = static::getValueWithUnit($v);
			}
		} else {
			if (($v = static::getVarValue(Arr::get($value, 'padding-right', ''))) !== '') {
				$result['padding-right'] = static::getValueWithUnit($v);
				$result['--element-padding-right'] = static::getValueWithUnit($v);
			}
		}

		if (($v = static::getVarValue(Arr::get($value, 'padding-bottom', ''))) !== '') {
			$result['padding-bottom'] = static::getValueWithUnit($v);
			$result['--element-padding-bottom'] = static::getValueWithUnit($v);
		}

		if ($rtl) {
			if (($v = static::getVarValue(Arr::get($value, 'padding-left', ''))) !== '') {
				$result['padding-right'] = static::getValueWithUnit($v);
				$result['--element-padding-right'] = static::getValueWithUnit($v);
			}
		} else {
			if (($v = static::getVarValue(Arr::get($value, 'padding-left', ''))) !== '') {
				$result['padding-left'] = static::getValueWithUnit($v);
				$result['--element-padding-left'] = static::getValueWithUnit($v);
			}
		}

		return $result ? $result : null;
	}

	private static function getValueWithUnit($val) {
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
