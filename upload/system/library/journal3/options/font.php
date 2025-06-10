<?php

namespace Journal3\Options;

use Journal3\Utils\Arr;
use Journal3\Utils\Str;

class Font extends Option {

	protected static function parseValue($value, $data = null) {
		$result = array();

		$style = Arr::get($value, 'style');

		if ($style) {
			$style = static::getVariable('font_style', $style);

			$value = Arr::trimAll($value);

			if (!empty($style['value'])) {
				$value = Arr::merge($style['value'], $value);
			}
		}

		$variable = Arr::get($value, 'font-family');

		if ($variable && Str::startsWith($variable, '__VAR__')) {
			$variable = static::getVariable('font', $variable);

			if ($v = Arr::get($variable, 'type')) {
				$result['type'] = $v;
			}

			if ($v = Arr::get($variable, 'font-family')) {
				$result['font-family'] = $v;
			}

			if ($v = Arr::get($variable, 'font-weight')) {
				if ($v === 'regular') {
					$result['font-weight'] = '400';
				} else if ($v !== 'italic') {
					$result['font-weight'] = $v;
				}
			}

//			if ($v = Arr::get($variable, 'font-style')) {
//				$result['font-style'] = $v;
//			}

			if ($v = Arr::get($variable, 'subsets')) {
				$result['subsets'] = $v;
			}
		}

		if (strlen($v = trim(InputValue::parseValue(Arr::get($value, 'font-size', ''), $data, 'font_size'))) > 0) {
			if (is_numeric($v)) {
				$result['font-size'] = $v . 'px';
			} else {
				$result['font-size'] = $v;
			}
		}

		if ($v = Color::parseValue(Arr::get($value, 'color'))) {
			//$result['color'] = $v . ' !important';
			$result['color'] = $v;
		}

		if ($v = Arr::get($value, 'font-weight')) {
			$result['font-weight'] = $v;
		}

		if ($v = Arr::get($value, 'font-style')) {
			$result['font-style'] = $v;
		}

		if ($v = Arr::get($value, 'text-align')) {
			if (Arr::get($data, 'config.rtl') === true) {
				if ($v === 'left') {
					$v = 'right';
				} else if ($v === 'right') {
					$v = 'left';
				}
			}
			$result['text-align'] = $v;
		}

		if ($v = Arr::get($value, 'text-transform')) {
			$result['text-transform'] = $v;
		}

		if ($v = Arr::get($value, 'text-decoration')) {
			if ($v === 'none') {
				$result['text-decoration'] = 'none';
				$result['-webkit-text-decoration'] = 'none';
			}
			if ($v === 'underline') {
				$result['text-decoration'] = 'underline';
				$result['-webkit-text-decoration'] = 'underline';
			}
			if ($v === 'line-through') {
				$result['text-decoration'] = 'line-through';
				$result['-webkit-text-decoration'] = 'line-through';
			}
			if ($v === 'dashed' || $v === 'dotted' || $v === 'double' || $v === 'wavy') {
				$result['text-decoration-style'] = $v;
				$result['-webkit-text-decoration-style'] = $v;
				$result['text-decoration-line'] = 'underline';
				$result['-webkit-text-decoration-line'] = 'underline';
			}
		}

		if (($v = static::getVarValue(Arr::get($value, '-webkit-text-stroke-width', ''))) !== '') {
			$result['-webkit-text-stroke-width'] = Option::addUnit($v);
		}

		if ($v = Color::parseValue(Arr::get($value, '-webkit-text-stroke-color'))) {
			$result['-webkit-text-stroke-color'] = $v;
		}

		if ($v = Color::parseValue(Arr::get($value, 'text-decoration-color'))) {
			$result['text-decoration-color'] = $v;
			$result['-webkit-text-decoration-color'] = $v;
		}

		if (($v = static::getVarValue(Arr::get($value, 'text-decoration-thickness', ''))) !== '') {
			$result['text-decoration-thickness'] = Option::addUnit($v);
			$result['-webkit-text-decoration-thickness'] = Option::addUnit($v);
		}

		if (($v = static::getVarValue(Arr::get($value, 'text-underline-offset', ''))) !== '') {
			$result['text-underline-offset'] = Option::addUnit($v);
		}

		if ($v = Arr::get($value, 'word-break')) {
			if ($v === 'hyphens') {
				$result['hyphens'] = 'auto';
				$result['-webkit-hyphens'] = 'auto';
				$result['overflow-wrap'] = 'break-word';
			}
		}

		if (($v = static::getVarValue(Arr::get($value, 'letter-spacing', ''))) !== '') {
			$result['letter-spacing'] = Option::addUnit($v);
		}

		if (($v = static::getVarValue(Arr::get($value, 'word-spacing', ''))) !== '') {
			$result['word-spacing'] = Option::addUnit($v);
		}

		if (($v = static::getVarValue(Arr::get($value, 'line-height', ''))) !== '') {
			$result['line-height'] = $v;
		}

		if (($v = Option::parseValue(Arr::get($value, 'text-indent', ''))) !== '') {
			$result['text-indent'] = Option::addUnit($v);
		}

		$text_shadow = array();

		if (($v = Option::parseValue(Arr::get($value, 'textShadowOffsetX', ''))) !== '') {
			$text_shadow['textShadowOffsetX'] = Option::addUnit($v);
		}

		if (($v = Option::parseValue(Arr::get($value, 'textShadowOffsetY', ''))) !== '') {
			$text_shadow['textShadowOffsetY'] = Option::addUnit($v);
		}

		if (($v = Option::parseValue(Arr::get($value, 'textShadowBlur', ''))) !== '') {
			$text_shadow['textShadowBlur'] = Option::addUnit($v);
		}

		if ($v = Color::parseValue(Arr::get($value, 'textShadowColor'))) {
			$text_shadow['textShadowColor'] = $v;
		}

		if ($text_shadow) {
			$result['text-shadow'] = implode(' ', $text_shadow);
		}

		return $result ? $result : null;
	}

	protected static function parseCss($value, $data = null) {
		if (isset($value['type'])) {
			if ($value['type'] === 'google') {
				if (isset($value['font-family'])) {
					$value['font-family'] = "'" . $value['font-family'] . "'";
				}
			}
		}

		unset($value['subsets']);
		unset($value['type']);

		if (Arr::get($value, 'font-weight') === 'regular') {
			unset($value['font-weight']);
		}

		return parent::parseCss($value, $data);
	}

	private static function getVarValue($x) {
		$value = Option::parseValue($x);

		if (Str::startsWith($value, '__VAR__')) {
			$value = Arr::get(static::$variables, 'value' . '.' . $value);
		}

		return $value;
	}

}
