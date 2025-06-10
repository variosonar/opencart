<?php

namespace Journal3\Options;

use Journal3\Utils\Arr;

class ImageDimensions extends Option {

	protected static function parseValue($value, $data = null) {
		$device = $data['config']['device'];

		$width = (int)Arr::get($value, 'width');
		$height = (int)Arr::get($value, 'height');
		$resize = Arr::get($value, 'resize', 'fill');

		if ($device === 'tablet') {
			$i = 0;

			if ($w = (int)Arr::get($value, 'tablet_width')) {
				$i++;
				$width = $w;
			}

			if ($h = (int)Arr::get($value, 'tablet_height')) {
				$i++;
				$height = $h;
			}

			if ($i === 2) {
				$resize = Arr::get($value, 'tablet_resize');
			}
		} else if ($device === 'phone') {
			$i = 0;

			if ($w = (int)Arr::get($value, 'phone_width')) {
				$i++;
				$width = $w;
			}

			if ($h = (int)Arr::get($value, 'phone_height')) {
				$i++;
				$height = $h;
			}

			if ($i === 2) {
				$resize = Arr::get($value, 'phone_resize');
			}
		}

		return array(
			'width'  => $width ? $width : null,
			'height' => $height ? $height : null,
			'resize' => $resize,
		);
	}

	protected static function parseCss($value, $data = null) {
		$first_data = $data;
		$second_data = $data;

		$first_data['property'] = Arr::get($data, 'properties.width');
		$second_data['property'] = Arr::get($data, 'properties.height');

		$first_result = parent::parseCss($value['width'], $first_data);
		$second_result = parent::parseCss($value['height'], $second_data);

		if ($first_result && $second_result) {
			return array_merge_recursive($first_result, $second_result);
		}

		if ($first_result) {
			return $first_result;
		}

		return $second_result;
	}

}
