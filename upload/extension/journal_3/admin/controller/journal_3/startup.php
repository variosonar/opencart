<?php

namespace Opencart\Admin\Controller\Extension\Journal3\Journal3;

class Startup extends \Opencart\System\Engine\Controller {

	public function index() {
		if (!defined('HTTPS_CATALOG')) {
			define('HTTPS_CATALOG', HTTP_CATALOG);
		}

		$json = json_decode(file_get_contents(DIR_EXTENSION . 'journal_3/install.json'), true);

		if (!defined('JOURNAL3_INSTALLED')) {
			define('JOURNAL3_INSTALLED', $json['version'] ?? '3.2.5');
		}

		if (!defined('JOURNAL3_ROUTE_SEPARATOR')) {
			if (version_compare(VERSION, '4.0.2.0', '>=')) {
				define('JOURNAL3_ROUTE_SEPARATOR', '.');
			} else {
				define('JOURNAL3_ROUTE_SEPARATOR', '|');
			}
		}

		if (!defined('JOURNAL3_EVENT_SEPARATOR')) {
			if (version_compare(VERSION, '4.1.0.0', '>=')) {
				define('JOURNAL3_EVENT_SEPARATOR', '.');
			} else {
				define('JOURNAL3_EVENT_SEPARATOR', '/');
			}
		}

		if (!class_exists('\Action')) {
			class_alias('\Opencart\System\Engine\Action', '\Action', false);
			class_alias('\Opencart\System\Engine\Controller', '\Controller', false);
			class_alias('\Opencart\System\Engine\Model', '\Model', false);
		}

		spl_autoload_register(function ($class) {
			$file = DIR_SYSTEM . 'library/' . str_replace('\\', '/', strtolower($class)) . '.php';

			if (is_file($file)) {
				include_once($file);

				return true;
			} else {
				return false;
			}
		});

		$this->autoloader->register('Opencart\Admin\Controller\Journal3', DIR_APPLICATION . 'controller/journal3/');
		$this->autoloader->register('Opencart\Admin\Model\Journal3', DIR_APPLICATION . 'model/journal3/');

		$this->load->controller('journal3/startup');
	}

}
