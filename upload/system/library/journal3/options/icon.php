<?php

namespace Journal3\Options;

use Journal3\Utils\Arr;

class Icon extends Option {

	protected static function parseValue($value, $data = null) {
		$result = array();

		if (Arr::get($value, 'none') === 'true') {
			return array(
				'content' => 'none !important',
			);
		}

		if ($v = Arr::get($value, 'icon.code')) {
			$result['content'] = "'\\" . $v . "' !important";
			$result['font-family'] = 'icomoon !important';
		}

		if ($v = Arr::get($value, 'size')) {
			if (is_numeric($v)) {
				$result['font-size'] = $v . 'px';
			} else {
				$result['font-size'] = $v;
			}
		}

		if ($v = Color::parseValue(Arr::get($value, 'color'))) {
			$result['color'] = $v;
		}

		$x = (int)Arr::get($value, 'offsetX');
		$y = (int)Arr::get($value, 'offsetY');

		if ($x && Arr::get($data, 'config.rtl') === true) {
			$x = -$x;
		}

		if ($x || $y) {
			$result['transform'] = "translate3d({$x}px, {$y}px, 0)";
		}

		if ($v = Arr::get($value, 'flip')) {
			if ($v === 'all' || Arr::get($data, 'config.rtl') === true) {
				$result['display'] = 'inline-block';
				$result['transform'] = ($result['transform'] ?? '') . ' scaleX(-1)';
			}
		}

		if ($v = Margin::parseValue(Arr::get($value, 'margin'), $data)) {
			$result = Arr::merge($result, $v);
		}

		return $result;
	}

}
