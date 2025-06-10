<?php

namespace Journal3\Options;

use Journal3\Utils\Arr;
use Journal3\Utils\Min;

class Parser {

	private static $config = array(
		'language_id' => '',
		'currency_id' => '',
		'device'      => '',
		'rtl'         => '',
	);

	private static $cache;
	private $selector_params;
	private $selector_prefix;
	private $settings = array();
	private $css = array();
	private $js = array();
	private $php = array();
	private $fonts = array();
	private $device_settings = array();

	public function __construct($files, $db_settings, $selector_prefix = null, $selector_params = null) {
		if (!is_array($files)) {
			$files = array($files);
		}

		$settings = array();

		foreach ($files as $file) {
			$f = DIR_SYSTEM . 'library/journal3/data/settings/' . $file . '.json';

			if (!is_file($f)) {
				die('Error: File ' . $f . ' not found!');
			}

			$file_settings = json_decode(file_get_contents($f), true);

			if (json_last_error() !== JSON_ERROR_NONE) {
				die('Error: File ' . $f . ' is invalid!');
			}

			foreach ($file_settings as $k => $v) {
				if (isset($settings[$k])) {
					trigger_error($k . ' is already used!');
				}

				$settings[$k] = $v;
			}
		}

		$this->selector_prefix = $selector_prefix;
		$this->selector_params = $selector_params;

		$this->parse($settings, $db_settings);
	}

	private function parse($settings, $db_settings) {
		foreach ($settings as $setting_name => $setting_data) {
			if (isset($setting_data['variable'])) {
				if (!Arr::get($db_settings, $setting_name)) {
					continue;
				}

				if ($setting_data['variable'] === 'product_list') {
					$f = DIR_SYSTEM . 'library/journal3/data/settings/common/product_grid.json';
				} else {
					$f = DIR_SYSTEM . 'library/journal3/data/settings/common/' . $setting_data['variable'] . '.json';
				}

				if (!is_file($f)) {
					die('Error: File ' . $f . ' not found!');
				}

				$file_settings = json_decode(file_get_contents($f), true);

				if (json_last_error() !== JSON_ERROR_NONE) {
					die('Error: File ' . $f . ' is invalid!');
				}

				$new_settings = array();
				$new_db_settings = array();
				$variable = Option::getVariable($setting_data['variable'], Arr::get($db_settings, $setting_name));
				$this->php[$setting_name] = str_replace('__VAR__', '', Arr::get($db_settings, $setting_name));

				foreach ($file_settings as $k => $v) {
					if (isset($v['selector_prefix']) && isset($setting_data['selector_prefix'])) {
						$v['selector_prefix'] = $setting_data['selector_prefix'] . ' ' . $v['selector_prefix'];
					} else if (isset($setting_data['selector_prefix'])) {
						$v['selector_prefix'] = $setting_data['selector_prefix'];
					}

					if (isset($setting_data['touchevents'])) {
						$v['touchevents'] = $setting_data['touchevents'];
					}

					$new_settings[$setting_name . $k] = $v;
				}

				if (is_array($variable)) {
					foreach ($variable as $k => $v) {
						$new_db_settings[str_replace('value', $setting_name, $k)] = $v;
					}
				}

				$this->parse($new_settings, $new_db_settings);
			} else if (isset($setting_data['include'])) {
				$f = DIR_SYSTEM . 'library/journal3/data/settings/common/' . $setting_data['include'] . '.json';

				if (!is_file($f)) {
					die('Error: File ' . $f . ' not found!');
				}

				$file_settings = json_decode(file_get_contents($f), true);

				if (json_last_error() !== JSON_ERROR_NONE) {
					die('Error: File ' . $f . ' is invalid!');
				}

				$new_settings = array();

				foreach ($file_settings as $k => $v) {
					if (isset($v['selector_prefix']) && isset($setting_data['selector_prefix'])) {
						$v['selector_prefix'] = $setting_data['selector_prefix'] . ' ' . $v['selector_prefix'];
					} else if (isset($setting_data['selector_prefix'])) {
						$v['selector_prefix'] = $setting_data['selector_prefix'];
					}

					if (isset($setting_data['touchevents'])) {
						$v['touchevents'] = $setting_data['touchevents'];
					}

					$new_settings[$setting_name . $k] = $v;
				}

				$this->parse($new_settings, $db_settings);
			} else {
				$type = Arr::get($setting_data, 'type');
				$class = 'Journal3\\Options\\' . $type;

				if (!class_exists($class)) {
					$class = 'Journal3\\Options\\Option';
				}

				$setting_data['config'] = static::$config;
				$setting_data['name'] = $setting_name;
				$setting_data['selector_prefix'] = trim(Arr::get($setting_data, 'selector_prefix') . ' ' . $this->selector_prefix);
				$setting_data['selector_params'] = $this->selector_params;

				if (isset($db_settings[$setting_name])) {
					if ($type === 'Checkbox') {
						$setting_data['value'] = $db_settings[$setting_name];
					} else if (is_array($db_settings[$setting_name])) {
						$arr = Arr::get($setting_data, 'value', array());
						if (!is_array($arr)) {
							$arr = array();
						}
						$setting_data['value'] = array_replace_recursive($arr, $db_settings[$setting_name]);
					} else {
						$setting_data['value'] = $db_settings[$setting_name];
					}
				}

				/** @var Option $obj */
				$obj = new $class($setting_data);

				$css = $obj->css();

				if ($css) {
					$this->css = array_merge_recursive($this->css, $css);
				}

				$value = $obj->value();

				if ($value !== null) {
					$this->settings[$setting_name] = $value;

					if (Arr::get($setting_data, 'device') === true) {
						$this->device_settings[$setting_name] = $setting_name;
					}

					if (Arr::get($setting_data, 'php') === true) {
						$this->php[$setting_name] = $value;
					}

					if (Arr::get($setting_data, 'js') === true) {
						$this->addJs($setting_name, $value);
					}

					if ($setting_data['type'] === 'Font') {
						if (Arr::get($value, 'type') === 'google') {
							$this->addFont($value);
						} else if (Arr::get($value, 'type') === 'custom') {
							$this->addFontCustom($value);
						}
					}
				}

				foreach (Arr::get($db_settings, $setting_name . '_multi', array()) as $multi_setting_data) {
					$min = Option::parseBreakpoint(Arr::get($multi_setting_data, 'min'));
					$max = Option::parseBreakpoint(Arr::get($multi_setting_data, 'max'));
					$value = Arr::get($multi_setting_data, 'value');

					if (($value !== null) && ($value !== '') && ($min || $max)) {
						$setting_data['value'] = $value;
						$setting_data['media'] = $min . '_' . $max;

						/** @var Option $obj */
						$obj = new $class($setting_data);

						$css = $obj->css();

						if ($css) {
							$this->css = array_merge_recursive($this->css, $css);
						}

						if ($setting_data['type'] === 'Font') {
							$value = $obj->value();

							if (Arr::get($value, 'type') === 'google') {
								$this->addFont($value);
							} else if (Arr::get($value, 'type') === 'custom') {
								$this->addFontCustom($value);
							}
						}
					}
				}
			}
		}

		if ($this->device_settings) {
			foreach ($this->device_settings as $device_setting) {
				switch (static::$config['device']) {
					case 'tablet':
						$value = Arr::get($this->settings, $device_setting . 'Tablet');

						if ($value !== '' && $value !== null) {
							$this->settings[$device_setting] = $value;

							if (isset($this->php[$device_setting])) {
								$this->php[$device_setting] = $value;
							}

							if (isset($this->js[$device_setting])) {
								$this->addJs($device_setting, $value);
							}
						}

						break;

					case 'phone':
						$value = Arr::get($this->settings, $device_setting . 'Phone');

						if ($value !== '' && $value !== null) {
							$this->settings[$device_setting] = $value;

							if (isset($this->php[$device_setting])) {
								$this->php[$device_setting] = $value;
							}

							if (isset($this->js[$device_setting])) {
								$this->addJs($device_setting, $value);
							}
						}

						break;
				}
			}
		}
	}

	public static function setConfig($key, $value) {
		static::$config[$key] = $value;
	}

	public static function setCache($cache) {
		static::$cache = $cache;
	}

	public function getSettings() {
		return $this->settings;
	}

	public function getSetting($key, $default = null) {
		return Arr::get($this->settings, $key, $default);
	}

	public function getCss() {
		$result = array();

		uksort($this->css, function ($a, $b) {
			if ($a === $b) {
				return 0;
			}

			if ($a === '_') {
				return -1;
			}

			if ($b === '_') {
				return 1;
			}

			$a = explode('_', $a);
			$b = explode('_', $b);

			if ((int)$a[1] < (int)$b[1]) {
				return 1;
			} else if ((int)$a[1] > (int)$b[1]) {
				return -1;
			}

			return 0;
		});

		foreach ($this->css as $media => $selectors) {
			if (!$selectors) {
				continue;
			}

			$media = explode('_', $media);

			$is_media = $media[0] || $media[1];

			$css = array();

			foreach ($selectors as $selector => $properties) {
				if ($selector && $properties) {
					if (strpos($selector, ':hover') !== false) {
						$selector = explode(',', $selector);

						if (self::$config['device'] === 'desktop') {
							$selector = array_map(function ($selector) {
								if (strpos($selector, ':hover') !== false) {
									$selector = str_replace('.desktop.', '.', $selector);
									$selector = str_replace('.desktop ', ' ', $selector);
									if (strpos($selector, 'html') !== false) {
										$selector = str_replace('html', 'html.no-touchevents', $selector);
									} else if (strpos($selector, '.route-') !== false) {
										$selector = str_replace('.route-', '.no-touchevents.route-', $selector);
									} else if (strpos($selector, '.layout-') !== false) {
										$selector = str_replace('.layout-', '.no-touchevents.layout-', $selector);
									} else if (strpos($selector, '.desktop-header-') !== false) {
										$selector = str_replace('.desktop-header-', '.no-touchevents.desktop-header-', $selector);
									} else {
										$selector = '.no-touchevents ' . $selector;
									}
								}

								return $selector;
							}, $selector);
						} else {
							$selector = array_filter($selector, function ($sel) {
								return strpos($sel, ':hover') === false;
							});
						}

						$selector = implode(',', $selector);
					}

					if ($selector) {
						$css[] = $selector . " { " . implode("; ", $properties) . " } ";
					}
				}
			}

			if (!$css) {
				continue;
			}

			if ($media[0] && $media[1]) {
				$result[] = "@media (min-width: {$media[0]}px) and (max-width: {$media[1]}px) {";
				$result[] = "\t" . implode("\n\t", $css);
				$result[] = "}";
			} else if ($media[0]) {
				$result[] = "@media (min-width: {$media[0]}px) {";
				$result[] = "\t" . implode("\n\t", $css);
				$result[] = "}";
			} else if ($media[1]) {
				$result[] = "@media (max-width: {$media[1]}px) {";
				$result[] = "\t" . implode("\n\t", $css);
				$result[] = "}";
			} else {
				$result[] = implode("\n", $css);
			}
		}

		if (!$result) {
			return null;
		}

		$result = implode("\n", $result);

		if (JOURNAL3_ENV !== 'development') {
			$result = Min::minifyCSS($result);
		}

		return $result;
	}

	public function addJs($name, $value) {
		if (is_numeric($value)) {
			if (strpos($value, '.') !== false) {
				$value = (float)$value;
			} else {
				$value = (int)$value;
			}
		}

		$this->js[$name] = $value;
	}

	public function getJs() {
		return $this->js;
	}

	public function getPhp() {
		return $this->php;
	}

	private function addFont($font) {
		if (!$this->fonts) {
			$this->fonts = array(
				'fonts'   => array(),
				'subsets' => array(),
			);
		}

		$name = Arr::get($font, 'font-family');
		$weight = Arr::get($font, 'font-weight');
		$subsets = explode(',', Arr::get($font, 'subsets'));

		$this->fonts['fonts'][$name][$weight] = $weight;

		foreach ($subsets as $subset) {
			$this->fonts['subsets'][$subset] = $subset;
		}
	}

	public function getFonts() {
		if (!$this->fonts) {
			return array();
		}

		return $this->fonts;
	}

	private function addFontCustom($font) {
		$font_family = $font['font-family'] ?? null;

		if ($font_family) {
			if (is_file(DIR_TEMPLATE . 'journal3/fonts_custom/' . $font_family . '.woff2')) {
				$this->fonts['fonts_custom'][$font_family]['woff2'] = substr(md5(DIR_TEMPLATE . 'journal3/fonts_custom/' . $font_family . '.woff2'), 0, 10);
			}

			if (is_file(DIR_TEMPLATE . 'journal3/fonts_custom/' . $font_family . '.woff')) {
				$this->fonts['fonts_custom'][$font_family]['woff'] = substr(md5(DIR_TEMPLATE . 'journal3/fonts_custom/' . $font_family . '.woff'), 0, 10);
			}
		}
	}

}
