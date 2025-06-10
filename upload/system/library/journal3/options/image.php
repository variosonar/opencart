<?php

namespace Journal3\Options;

class Image extends Option {

	public static function checkIfExists($value, $data = null) {
		if (is_file(DIR_IMAGE . $value)) {
			return $value;
		}

		return null;
	}

}
