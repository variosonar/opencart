<?php

namespace Journal3;

use Action;
use Clockwork\Support\Vanilla\Clockwork;
use DB;
use Dotenv\Dotenv;
use Journal3\Utils\Arr;
use Journal3\Utils\Html;
use Journal3\Utils\Str;
use Sentry;

// autoloader
require_once DIR_SYSTEM . 'library/journal3/vendor/autoload.php';

// define root dir
define('JOURNAL3_DIR_ROOT', realpath(DIR_SYSTEM . '..') . '/');
define('JOURNAL3_ASSETS_PATH', 'catalog/view/theme/journal3/assets/');

// check .env file
if (is_file(JOURNAL3_DIR_ROOT . '.env')) {
	$dotenv = Dotenv::createImmutable(JOURNAL3_DIR_ROOT);
	$dotenv->load();
	$dotenv->ifPresent('JOURNAL3_ENV')->allowedValues(['development', 'production']);
	$dotenv->ifPresent('JOURNAL3_CACHE')->isBoolean();
	$dotenv->ifPresent('JOURNAL3_DEBUG')->isBoolean();
	$dotenv->ifPresent('JOURNAL3_FORM_LOG')->isBoolean();
	$dotenv->ifPresent('JOURNAL3_LIVERELOAD')->isBoolean();
	$dotenv->ifPresent('JOURNAL3_LOG')->isBoolean();
	$dotenv->ifPresent('JOURNAL3_PROFILER')->isBoolean();
}

// useful constants based on .env file (if present)
if (!defined('JOURNAL3_ENV')) define('JOURNAL3_ENV', $_ENV['JOURNAL3_ENV'] ?? 'production');
if (!defined('JOURNAL3_CACHE')) define('JOURNAL3_CACHE', filter_var($_ENV['JOURNAL3_CACHE'] ?? true, FILTER_VALIDATE_BOOLEAN));
if (!defined('JOURNAL3_DEBUG')) define('JOURNAL3_DEBUG', filter_var($_ENV['JOURNAL3_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));
if (!defined('JOURNAL3_FORM_LOG')) define('JOURNAL3_FORM_LOG', filter_var($_ENV['JOURNAL3_FORM_LOG'] ?? false, FILTER_VALIDATE_BOOLEAN));
if (!defined('JOURNAL3_LIVERELOAD')) define('JOURNAL3_LIVERELOAD', filter_var($_ENV['JOURNAL3_LIVERELOAD'] ?? false, FILTER_VALIDATE_BOOLEAN));
if (!defined('JOURNAL3_LOG')) define('JOURNAL3_LOG', filter_var($_ENV['JOURNAL3_LOG'] ?? false, FILTER_VALIDATE_BOOLEAN));
if (!defined('JOURNAL3_PROFILER')) define('JOURNAL3_PROFILER', filter_var($_ENV['JOURNAL3_PROFILER'] ?? false, FILTER_VALIDATE_BOOLEAN));
if (!defined('JOURNAL3_EXPORT')) define('JOURNAL3_EXPORT', $_ENV['JOURNAL3_EXPORT'] ?? false);
if (!defined('JOURNAL3_NO_IMAGE')) define('JOURNAL3_NO_IMAGE', $_ENV['JOURNAL3_NO_IMAGE'] ?? false);
if (!defined('JOURNAL3_SENTRY_DSN')) define('JOURNAL3_SENTRY_DSN', $_ENV['JOURNAL3_SENTRY_DSN'] ?? null);
if (!defined('JOURNAL3_SENTRY_DSN_LOADER')) define('JOURNAL3_SENTRY_DSN_LOADER', $_ENV['JOURNAL3_SENTRY_DSN_LOADER'] ?? null);
if (!defined('JOURNAL3_BLOCKED_EMAILS')) define('JOURNAL3_BLOCKED_EMAILS', $_ENV['JOURNAL3_BLOCKED_EMAILS'] ?? '');

/**
 * Class Journal
 *
 * This is the main class, it holds various properties and it acts as a startup class by instantiating other classes
 *
 * @package Journal3
 */
class Journal {

	public $is_rtl;

	public $device;
	public $is_desktop = false;
	public $is_mobile = false;
	public $is_tablet = false;
	public $is_phone = false;
	public $browser_classes;

	public $popup;
	public $is_popup;
	public $is_login_popup;
	public $is_register_popup;
	public $is_options_popup;
	public $is_quickview_popup;

	public $js_defer = false;

	/** @var \Registry */
	private static $registry;

	/** @var Journal */
	private static $instance;

	private $language_code = null;

	private static $settings = array();

	public function __construct($registry) {
		self::$registry = $registry;

		// sentry
		if (JOURNAL3_SENTRY_DSN) {
			Sentry\init(array(
				'dsn' => JOURNAL3_SENTRY_DSN,
			));
		}

		// profiler
		if (JOURNAL3_PROFILER && !defined('DIR_CATALOG')) {
			Clockwork::init(array(
				'api'                => parse_url(HTTP_SERVER, PHP_URL_PATH) . 'index.php?route=journal3/profiler/clockwork&request=',
				'storage_files_path' => DIR_LOGS . 'clockwork',
				'register_helpers'   => true,
				'storage_expiration' => 60 * 12,
			));

			if (!empty(DB::$JOURNAL3_QUERY_LOG)) {
				foreach (DB::$JOURNAL3_QUERY_LOG as $log) {
					clock()->addDatabaseQuery($log['sql'], array(), $log['time'], $log['data']);
				}
			}

			/** @var \Event $event */
			$event = self::$registry->get('event');

			$event->register('controller/*/before', new Action('journal3/profiler/before_controller'), -9999);
			$event->register('controller/*/after', new Action('journal3/profiler/after_controller'), 9999);
			$event->register('view/*/before', new Action('journal3/profiler/before_view'));
			$event->register('view/*/after', new Action('journal3/profiler/after_view'));
		}

		$this->language_code = $registry->get('language')->get('code');

		self::$instance = $this;

		/** @var \Language $language */
		$language = self::$registry->get('language');

		/** @var \Request $request */
		$request = self::$registry->get('request');

		$this->is_rtl = $language->get('direction') === 'rtl';

		$this->popup = $request->get['popup'] ?? null;
		$this->is_popup = (bool)$this->popup;
		$this->is_login_popup = $this->popup === 'login';
		$this->is_register_popup = $this->popup === 'register';
		$this->is_options_popup = $this->popup === 'options';
		$this->is_quickview_popup = $this->popup === 'quickview';

		// Journal objects
		self::$registry->set('journal3_assets', new \Journal3\Assets());
		self::$registry->set('journal3_browser', new \Journal3\Browser(self::$registry));
		self::$registry->set('journal3_cache', new \Journal3\Cache(self::$registry));
		self::$registry->set('journal3_db', new \Journal3\DB(self::$registry));
		self::$registry->set('journal3_document', new \Journal3\Document());
		self::$registry->set('journal3_image', new \Journal3\Image(self::$registry));
		self::$registry->set('journal3_opencart', new \Journal3\Opencart(self::$registry));
		self::$registry->set('journal3_product_extras', new \Journal3\Productextras(self::$registry));
		self::$registry->set('journal3_request', new \Journal3\Request(self::$registry));
		self::$registry->set('journal3_response', new \Journal3\Response(self::$registry));
		self::$registry->set('journal3_url', new \Journal3\Url(self::$registry));

		// browser detect
		/** @var Browser $browser */
		$browser = self::$registry->get('journal3_browser');

		$this->device = $browser->getDevice();

		if ($this->device === 'desktop') {
			$this->is_desktop = true;
		} else {
			$this->is_mobile = true;
			if ($this->device === 'tablet') {
				$this->is_tablet = true;
			} else {
				$this->is_phone = true;
			}
		}

		$this->browser_classes = $browser->getClasses();
	}

	public static function getInstance() {
		return self::$instance;
	}

	public function getRegistry() {
		return self::$registry;
	}

	public function uniqueId($prefix = '') {
		return uniqid($prefix);
	}

	public function countBadge($text, $count, $classes = array()) {
		return Html::countBadge($text, $count, $classes);
	}

	public function classes($classes) {
		return Html::classes($classes);
	}

	public function linkAttrs($link) {
		$attrs = Arr::get($link, 'attrs');

		return $attrs ? implode(' ', $attrs) : null;
	}

	public function carousel($options, $key) {
		return array(
			'speed'        => (int)Arr::get($options, "{$key}Speed"),
			'autoplay'     => (bool)Arr::get($options, "{$key}AutoPlay") ? array(
				'delay' => (int)Arr::get($options, "{$key}Delay"),
			) : false,
			'pauseOnHover' => (bool)Arr::get($options, "{$key}PauseOnHover"),
			'loop'         => (bool)Arr::get($options, "{$key}Loop"),
		);
	}

	public function blog_date($date) {
		$format = $this->getWith('blogDateFormat', null, 'd \<\i\>M\<\/\i\>');
		$format_intl = $this->get('blogDateFormatIntl', null);

		if (!$format_intl || !class_exists('\IntlDateFormatter') || !class_exists('\IntlCalendar')) {
			$result = date($format, strtotime($date));
		} else {

			$result = \IntlDateFormatter::formatObject(\IntlCalendar::fromDateTime($date), $format_intl, $this->language_code);
		}

		$result = str_replace(['<i>', '</i>'], ['<em>', '</em>'], $result);

		return $result;
	}

	public static function jsonAttrs($attrs): ?string {
		return json_encode($attrs, JSON_HEX_APOS);
	}

	public function all() {
		return self::$settings;
	}

	public function load($settings) {
		if ($settings) {
			self::$settings = array_merge(self::$settings, $settings);
		}
	}

	public function set($key, $value) {
		self::$settings[$key] = $value;
	}

	public function get($key, $default = null) {
		return Arr::get(self::$settings, $key, $default);
	}

	public function getWith($key, $default = null, $default2 = null) {
		$value = Arr::get(self::$settings, $key, $default);

		if (!$value) {
			$value = $default2;
		}

		return $value;
	}

	public function getIn($key, $context, $default = null) {
		return Arr::get($context, 'module' . $key, $this->get('global' . $key, $default));
	}

	public function getWithValue($key, $value) {
		$key = $this->get($key);

		return $this->replaceWithValue($key, $value);
	}

	public function replaceWithValue($key, $value) {
		if (Str::contains($key, '%s')) {
			return str_replace('%s', $value, $key);
		}

		return $key . ' ' . $value;
	}

	public function version_compare($v1, $v2, $o) {
		return version_compare($v1, $v2, $o);
	}
}
