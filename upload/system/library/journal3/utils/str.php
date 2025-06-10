<?php

namespace Journal3\Utils;

/**
 * Class Str contains string utilities like contains, starts with, ends with, etc...
 *
 * @package Journal3\Utils
 */
class Str {

	/**
	 * @param $string
	 * @return string
	 */
	public static function toBool($string) {
		$string = (string)$string;

		if ($string === '0') {
			return 'false';
		}

		if ($string === '1') {
			return 'true';
		}

		return '';
	}

	/**
	 * @param $bool
	 * @return int
	 */
	public static function fromBool($bool) {
		if ($bool === 'false') {
			return 0;
		}

		if ($bool === 'true') {
			return 1;
		}

		return 2;
	}

	/**
	 * @param $haystack
	 * @param $needle
	 * @return bool
	 */
	public static function startsWith($haystack, $needle) {
		return strpos($haystack ?? '', $needle) === 0;
	}

	/**
	 * @param $haystack
	 * @param $needle
	 * @return bool
	 */
	public static function endsWith($haystack, $needle) {
		return substr($haystack ?? '', -strlen($needle)) === $needle;
	}

	/**
	 * @param $src
	 * @param $string
	 * @return string|null
	 */
	public static function concatIfNotEmpty($src, $string) {
		return $src ? $src . $string : $src;
	}

	/**
	 * @param $haystack
	 * @param $needle
	 * @return bool
	 */
	public static function contains($haystack, $needle) {
		return strpos($haystack, $needle) !== false;
	}

	/**
	 * @param $url
	 * @return false|mixed
	 */
	public static function YoutubeId($url) {
		// http://stackoverflow.com/a/37186299

		$pattern =
			'%^# Match any youtube URL
                (?:https?://)?  # Optional scheme. Either http or https
                (?:www\.)?      # Optional www subdomain
                (?:             # Group host alternatives
                  youtu\.be/    # Either youtu.be,
                | youtube\.com  # or youtube.com
                  (?:           # Group path alternatives
                    /embed/     # Either /embed/
                  | /v/         # or /v/
                  | /watch\?v=  # or /watch\?v=
                  )             # End path alternatives.
                )               # End host alternatives.
                ([\w-]{10,12})  # Allow 10-12 for 11 char youtube id.
                $%x';

		$result = preg_match($pattern, $url, $matches);

		if ($result) {
			return $matches[1];
		}

		return false;
	}

	/**
	 * @param $url
	 * @return false|string
	 */
	public static function VimeoId($url) {
		return substr(parse_url($url, PHP_URL_PATH), 1);
	}

	/**
	 * @param $url
	 * @return array|mixed|string|string[]
	 */
	public static function urlPathEncode($url) {
		// https://stackoverflow.com/a/10903374

		$path = parse_url($url, PHP_URL_PATH);

		if (strpos($path, '%') !== false) {
			return $url; //avoid double encoding
		}

		$encoded_path = array_map('urlencode', explode('/', $path));

		return str_replace($path, implode('/', $encoded_path), $url);
	}

	public static function utf8_strlen($string) {
		if (version_compare(VERSION, '4.0.2.0', '>=')) {
			return oc_strlen($string);
		} else if (version_compare(VERSION, '4', '>=')) {
			return \Opencart\System\Helper\Utf8\strlen($string);
		} else if (defined('JOURNAL3_OLD_OC3039')) {
			return oc_strlen($string);
		} else {
			return utf8_strlen($string);
		}
	}

	public static function utf8_strpos($string, $needle, $offset = 0) {
		if (version_compare(VERSION, '4.0.2.0', '>=')) {
			return oc_strpos($string, $needle, $offset);
		} else if (version_compare(VERSION, '4', '>=')) {
			return \Opencart\System\Helper\Utf8\strpos($string, $needle, $offset);
		} else if (defined('JOURNAL3_OLD_OC3039')) {
			return oc_strpos($string, $needle, $offset);
		} else {
			return utf8_strpos($string, $needle, $offset);
		}
	}

	public static function utf8_strrpos($string, $needle, $offset = 0) {
		if (version_compare(VERSION, '4.0.2.0', '>=')) {
			return oc_strrpos($string, $needle, $offset);
		} else if (version_compare(VERSION, '4', '>=')) {
			return \Opencart\System\Helper\Utf8\strrpos($string, $needle, $offset);
		} else if (defined('JOURNAL3_OLD_OC3039')) {
			return oc_strrpos($string, $needle, $offset);
		} else {
			return utf8_strrpos($string, $needle, $offset);
		}
	}

	public static function utf8_substr($string, $offset, $length = null) {
		if (version_compare(VERSION, '4.0.2.0', '>=')) {
			return oc_substr($string, $offset, $length);
		} else if (version_compare(VERSION, '4', '>=')) {
			return \Opencart\System\Helper\Utf8\substr($string, $offset, $length);
		} else if (defined('JOURNAL3_OLD_OC3039')) {
			return oc_substr($string, $offset, $length);
		} else {
			return utf8_substr($string, $offset, $length);
		}
	}

	public static function utf8_strtoupper($string) {
		if (version_compare(VERSION, '4.0.2.0', '>=')) {
			return oc_strtoupper($string);
		} else if (version_compare(VERSION, '4', '>=')) {
			return \Opencart\System\Helper\Utf8\strtoupper($string);
		} else if (defined('JOURNAL3_OLD_OC3039')) {
			return oc_strtoupper($string);
		} else {
			return utf8_strtoupper($string);
		}
	}

	public static function utf8_strtolower($string) {
		if (version_compare(VERSION, '4.0.2.0', '>=')) {
			return oc_strtolower($string);
		} else if (version_compare(VERSION, '4', '>=')) {
			return \Opencart\System\Helper\Utf8\strtolower($string);
		} else if (defined('JOURNAL3_OLD_OC3039')) {
			return oc_strtolower($string);
		} else {
			return utf8_strtolower($string);
		}
	}

	public static function textPrint($text, $var, $separator = ': ') {
		if (self::contains($text, '%s')) {
			return sprintf($text, $var);
		}

		return $text . $separator . $var;
	}

	public static function handleize($string) {
		$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
		$string = preg_replace('/[^A-Za-z0-9\-]/', '-', $string); // Removes special chars.

		return strtolower($string);
	}

}
