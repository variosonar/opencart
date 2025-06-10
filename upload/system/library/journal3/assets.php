<?php

namespace Journal3;

use Journal3\Utils\Min;
use Journal3\Utils\Str;

/**
 * Class Assets
 *
 * @package Journal3
 */
class Assets {

	/**
	 * @var string
	 */
	private $ver;

	/**
	 * Assets constructor.
	 */
	public function __construct() {
		if (empty($_SERVER['DOCUMENT_ROOT'])) {
			$_SERVER['DOCUMENT_ROOT'] = realpath(DIR_SYSTEM . '../');
		}

		$this->ver = JOURNAL3_DEBUG ? 't' . time() : JOURNAL3_BUILD;
	}

	/**
	 *
	 */
	public static function clearCache() {
		$files = glob(DIR_SYSTEM . '../catalog/view/theme/journal3/assets/*.{js,css}', GLOB_BRACE);

		foreach ($files as $file) {
			if (is_file($file)) {
				@unlink($file);
			}
		}
	}

	/**
	 * @param $data
	 * @param string $ext
	 * @return string
	 */
	public static function hash($data, string $ext): string {
		$hash = is_scalar($data) ? $data : implode('', array_keys($data));

		$hash .= JOURNAL3_STATIC_URL;
		$hash .= JOURNAL3_VERSION;
		$hash .= JOURNAL3_BUILD;

		return md5($hash) . '.' . $ext;
	}

	/**
	 * @param array $styles
	 * @return array
	 */
	public function inlineStyles(array $styles): array {
		foreach ($styles as &$style) {
			if (is_file($style['href'])) {
				$style['content'] = file_get_contents($style['href']);
				$style['href'] = null;
			}
		}

		return $styles;
	}

	/**
	 * @param array $styles
	 * @param bool $mixed
	 * @return array
	 */
	public function minifyStyles(array $styles, $mixed = true): array {
		if (function_exists('clock')) {
			clock()->event('CSS Minify')->name('css_minify')->begin();
		}

		$result = [];
		$files = [];

		foreach ($styles as $href => $style) {
			if (is_file($href)) {
				$files[$href] = $href;
			} else {
				$result[$href] = $style;
			}
		}

		if ($files) {
			$file_hash = JOURNAL3_ASSETS_PATH . static::hash($files, 'css');
			$file_content = '';

			if (!is_file($file_hash)) {
				foreach ($files as $file) {
					$file_content .= '/* src: ' . $file . ' */' . PHP_EOL;

					$content = file_get_contents($file);

					$file_content .= Min::minifyCSS($content, [
						// 'compress'   => !Str::endsWith($file, '.min.css'),
						'currentDir' => dirname($file),
					]);

					$file_content .= PHP_EOL;
				}

				file_put_contents($file_hash, $file_content);
			}

			if ($mixed) {
				$result[$file_hash] = [
					'href'  => $file_hash,
					'rel'   => 'stylesheet',
					'media' => 'screen',
				];
			} else {
				$result[$file_hash] = $file_hash;
			}
		}

		if (function_exists('clock')) {
			clock()->event('css_minify')->end();
		}

		return $result;
	}

	/**
	 * @param array $scripts
	 * @return array
	 */
	public function minifyScripts(array $scripts): array {
		if (function_exists('clock')) {
			clock()->event('JS Minify')->name('js_minify')->begin();
		}

		$result = [];
		$files = [];

		foreach ($scripts as $script) {
			if (version_compare(VERSION, '4', '>=') || defined('JOURNAL3_OLD_OC3039')) {
				if (is_file($script['href'])) {
					$files[$script['href']] = $script['href'];
				} else {
					$result[$script['href']] = $script;
				}
			} else {
				if (is_file($script)) {
					$files[$script] = $script;
				} else {
					$result[$script] = $script;
				}
			}
		}

		if ($files) {
			$file_hash = JOURNAL3_ASSETS_PATH . static::hash($files, 'js');
			$file_content = '';

			if (!is_file($file_hash)) {
				foreach ($files as $file) {
					$file_content .= '/* src: ' . $file . ' */' . PHP_EOL;

					$content = file_get_contents($file);

					if (!Str::endsWith($file, '.min.js')) {
						$content = Min::minifyJS($content);
					}

					$file_content .= $content;

					$file_content .= PHP_EOL;
				}

				file_put_contents($file_hash, $file_content);
			}

			if (version_compare(VERSION, '4', '>=') || defined('JOURNAL3_OLD_OC3039')) {
				$result[$file_hash] = [
					'href' => $file_hash,
				];
			} else {
				$result[$file_hash] = $file_hash;
			}
		}

		if (function_exists('clock')) {
			clock()->event('js_minify')->end();
		}

		return $result;
	}

	/**
	 * @param array $scripts
	 * @return array
	 */
	public function inlineScripts(array $scripts): array {
		foreach ($scripts as &$script) {
			if (version_compare(VERSION, '4', '>=') || defined('JOURNAL3_OLD_OC3039')) {
				if (is_file($script['href'])) {
					$script['href'] = file_get_contents($script['href']);
				}
			} else {
				if (is_file($script)) {
					$script = file_get_contents($script);
				}
			}
		}

		return $scripts;
	}

	/**
	 * @param $url
	 * @return string
	 */
	public function url($url, $ver = null) {
		return (defined('JOURNAL3_STATIC_URL') ? JOURNAL3_STATIC_URL : '') . $url . '?v=' . ($ver ?: $this->ver);
	}

	/**
	 * @param array $styles
	 * @return array
	 */
	public function styles(array $styles): array {
		foreach ($styles as $href => &$style) {
			if ($href && is_file($href)) {
				if (is_array($style)) {
					$style['href'] = $this->url($style['href']);
				} else {
					$style = $this->url($style);
				}
			}
		}

		return $styles;
	}

	/**
	 * @param array $scripts
	 * @return array
	 */
	public function scripts(array $scripts): array {
		foreach ($scripts as &$script) {
			if (version_compare(VERSION, '4', '>=') || defined('JOURNAL3_OLD_OC3039')) {
				if ($script && is_file($script['href'])) {
					$script['href'] = $this->url($script['href']);
				}
			} else {
				if ($script && is_file($script)) {
					$script = $this->url($script);
				}
			}
		}

		return $scripts;
	}

}
