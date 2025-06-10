<?php

namespace Journal3\Options;

use Journal3\Utils\Arr;
use Journal3\Utils\Str;

class Border extends Option {

	protected static function parseValue($value, $data = null) {
		$rtl = Arr::get($data, 'config.rtl') === true;

		$result = array();

		$has_width = false;

		if (($v = static::getVarValue(Arr::get($value, 'border-width', ''))) !== '') {
			$result['border-width'] = $v . 'px';
			$has_width = true;
		}

		$has_width2 = false;

		if (($v = static::getVarValue(Arr::get($value, 'border-top-width', ''))) !== '') {
			$result['border-top-width'] = $v . 'px';
			$has_width2 = true;
		}

		if ($rtl) {
			if (($v = static::getVarValue(Arr::get($value, 'border-right-width', ''))) !== '') {
				$result['border-left-width'] = $v . 'px';
				$has_width2 = true;
			}
		} else {
			if (($v = static::getVarValue(Arr::get($value, 'border-right-width', ''))) !== '') {
				$result['border-right-width'] = $v . 'px';
				$has_width2 = true;
			}
		}

		if (($v = static::getVarValue(Arr::get($value, 'border-bottom-width', ''))) !== '') {
			$result['border-bottom-width'] = $v . 'px';
			$has_width2 = true;
		}

		if ($rtl) {
			if (($v = static::getVarValue(Arr::get($value, 'border-left-width', ''))) !== '') {
				$result['border-right-width'] = $v . 'px';
				$has_width2 = true;
			}
		} else {
			if (($v = static::getVarValue(Arr::get($value, 'border-left-width', ''))) !== '') {
				$result['border-left-width'] = $v . 'px';
				$has_width2 = true;
			}
		}

		if (!$has_width && $has_width2) {
			$result = array_merge(array('border-width' => 0), $result);
		}

		if ($v = static::getVarValue(Arr::get($value, 'border-style'))) {
			$result['border-style'] = $v;
		}

		if ($v = Color::parseValue(Arr::get($value, 'border-color'))) {
			$result['border-color'] = $v;
		}

		if (($v = trim(Arr::get($value, 'gradient', '')))) {
			if (Str::startsWith($v, '__VAR__')) {
				$v = Arr::get(static::$variables, 'gradient.' . $v);
			}

			if ($v) {
				$v = explode(':', $v);
				$v = $v[1] ?? null;

				if ($v) {
					$result['border-image'] = $v;
					$result['border-image-slice'] = 1;
				}
			}
		}

		return $result ? $result : null;
	}

	private static function getVarValue($x) {
		$value = Option::parseValue($x);

		if (Str::startsWith($value, '__VAR__')) {
			$value = Arr::get(static::$variables, 'border' . '.' . $value);
		}

		return $value;
	}

}
