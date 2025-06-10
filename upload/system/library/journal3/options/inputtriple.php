<?php

namespace Journal3\Options;

use Journal3\Utils\Arr;
use Journal3\Utils\Str;

class InputTriple extends Option {

	protected static function parseValue($value, $data = null) {
		$third = Arr::get($value, 'third');

		if (Str::startsWith($third, '__VAR__')) {
			if (!isset(static::$variables['gap'][$third])) {
				$third = '';
			} else {
				$third = Option::varName('gap', str_replace('__VAR__', '', $third));
				$third = "var({$third})";
			}
		}

		if (is_numeric($third)) {
			$third = $third . 'px';
		}

		return array(
			'first'  => Arr::get($value, 'first'),
			'minCol' => Arr::get($value, 'minCol'),
			'second' => Arr::get($value, 'second'),
			'third'  => $third,
			'fourth'  => Arr::get($value, 'fourth') ?: '',
		);
	}

	protected static function parseCss($value, $data = null) {
		$first_data = $data;
		$minCol_data = $data;
		$second_data = $data;
		$third_data = $data;
		$fourth_data = $data;

		$first_data['property'] = Arr::get($data, 'properties.first');
		$first_data['rtlProperty'] = Arr::get($data, 'rtlProperties.first');

		$minCol_data['property'] = Arr::get($data, 'properties.minCol');
		$minCol_data['rtlProperty'] = Arr::get($data, 'rtlProperties.minCol');

		$second_data['property'] = Arr::get($data, 'properties.second');
		$second_data['rtlProperty'] = Arr::get($data, 'rtlProperties.second');

		$third_data['property'] = Arr::get($data, 'properties.third');
		$third_data['rtlProperty'] = Arr::get($data, 'rtlProperties.third');

		$fourth_data['property'] = Arr::get($data, 'properties.fourth');
		$fourth_data['rtlProperty'] = Arr::get($data, 'rtlProperties.fourth');

		$first_result = parent::parseCss($value['first'], $first_data) ?? [];
		$minCol_result = parent::parseCss($value['minCol'], $minCol_data) ?? [];
		$second_result = parent::parseCss($value['second'], $second_data) ?? [];
		$third_result = parent::parseCss($value['third'], $third_data) ?? [];
		$fourth_result = parent::parseCss($value['fourth'], $fourth_data) ?? [];

		return array_merge_recursive($first_result, $minCol_result, $second_result, $third_result, $fourth_result);
	}
}
