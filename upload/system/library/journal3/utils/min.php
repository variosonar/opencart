<?php

namespace Journal3\Utils;

use Exception;
use JSMin\JSMin;
use Minify_CSSmin;
use Minify_HTML;

/**
 * Class Min contains minifier utilities
 *
 * @package Journal3\Utils
 */
class Min {

	/**
	 * @param string $html
	 * @return string
	 */
	public static function minifyHTML(string $html): string {
		return Minify_HTML::minify($html);
	}

	/**
	 * @param string $css
	 * @param array $options
	 * @return string
	 */
	public static function minifyCSS(string $css, $options = []): string {
		return Minify_CSSmin::minify($css, $options);
	}

	/**
	 * @param string $js
	 * @return string
	 */
	public static function minifyJS(string $js): string {
		try {
			return JSMin::minify($js);
		} catch (Exception $exception) {
			return $js;
		}
	}

}
