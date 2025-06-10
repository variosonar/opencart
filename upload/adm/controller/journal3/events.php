<?php

class ControllerJournal3Events extends Controller {

	public function index() {
		// post request json input
		$this->event->register('controller/journal3/*/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'controller_journal3_before'));

		// admin menu shortcut
		$this->event->register('controller/common/header/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'controller_common_header_before'));
		$this->event->register('view/common/column_left/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'view_common_column_left_before'));

		// oc2 theme image fix
		$this->event->register('controller/setting/setting/theme/after', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'controller_setting_setting_theme_after'));

		// permissions
		$this->event->register('view/error/permission/after', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'view_error_permission_after'));

		// not found
		$this->event->register('view/error/not_found/after', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'view_error_not_found_after'));

		// not logged in
		$this->event->register('view/common/login/after', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'view_common_login_after'));

		// newsletter
		$this->event->register('model/customer/customer' . JOURNAL3_EVENT_SEPARATOR . 'getCustomers/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'model_customer_customer_getCustomers_before'));
		$this->event->register('model/customer/customer' . JOURNAL3_EVENT_SEPARATOR . 'getTotalCustomers/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'model_customer_customer_getTotalCustomers_before'));

		// sentry
		$this->event->register('view/journal3/journal3/after', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'view_journal3_journal3_after'));

		// cache
		$this->event->register('model/catalog/category' . JOURNAL3_EVENT_SEPARATOR . 'addCategory/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_category_cache'));
		$this->event->register('model/catalog/category' . JOURNAL3_EVENT_SEPARATOR . 'editCategory/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_category_cache'));
		$this->event->register('model/catalog/category' . JOURNAL3_EVENT_SEPARATOR . 'deleteCategory/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_category_cache'));

		$this->event->register('model/catalog/information' . JOURNAL3_EVENT_SEPARATOR . 'addInformation/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_information_cache'));
		$this->event->register('model/catalog/information' . JOURNAL3_EVENT_SEPARATOR . 'editInformation/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_information_cache'));
		$this->event->register('model/catalog/information' . JOURNAL3_EVENT_SEPARATOR . 'deleteInformation/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_information_cache'));

		$this->event->register('model/catalog/manufacturer' . JOURNAL3_EVENT_SEPARATOR . 'addManufacturer/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_manufacturer_cache'));
		$this->event->register('model/catalog/manufacturer' . JOURNAL3_EVENT_SEPARATOR . 'editManufacturer/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_manufacturer_cache'));
		$this->event->register('model/catalog/manufacturer' . JOURNAL3_EVENT_SEPARATOR . 'deleteManufacturer/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_manufacturer_cache'));

		$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'addProduct/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_product_cache'));
		$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'editProduct/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_product_cache'));
		$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'copyProduct/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_product_cache'));
		$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'deleteProduct/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_product_cache'));

		$this->event->register('model/localisation/*/add*/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_cache'));
		$this->event->register('model/localisation/*/edit*/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_cache'));
		$this->event->register('model/localisation/*/delete*/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'clear_cache'));

		// oc4 installer version fix
		$this->event->register('model/setting/extension' . JOURNAL3_EVENT_SEPARATOR . 'getInstalls/after', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'model_setting_extension_getInstalls_after'));

	}

	public function controller_journal3_before(&$route, &$data) {
		// Php version check
		if (version_compare(phpversion(), '7.3', '<')) {
			$this->print_error(
				'Unsupported PHP Version',
				'Journal requires <b>PHP 7.3</b> (or higher)!',
				'Consult with your hosting provider for more information regarding how to upgrade PHP to <b>7.3</b> (or higher).'
			);
		}

		// Journal object
		$this->registry->set('journal3', new \Journal3\Journal($this->registry));

		// POST variables from raw body json
		if ($this->journal3_request->is_post) {
			$body = json_decode(file_get_contents('php://input'), true);

			if (!empty($body)) {
				foreach ($body as $key => $value) {
					$this->request->post[$key] = $value;
				}
			}
		}
	}

	public function controller_setting_setting_theme_after(&$route, &$data) {
		if ($this->request->get['theme'] == 'theme_journal3') {
			if ($this->request->server['HTTPS']) {
				$server = HTTPS_CATALOG;
			} else {
				$server = HTTP_CATALOG;
			}

			$this->response->setOutput($server . 'catalog/view/theme/journal3/image/journal3.png');
		}
	}

	public function controller_common_header_before($eventRoute, &$data) {
		if (!empty($this->request->get['j_edit'])) {
			$this->document->addStyle('view/javascript/journal3/assets/edit.css');
		}
		$this->document->addStyle('view/javascript/journal3/assets/menu.css');
		$this->document->addScript('view/javascript/journal3/assets/login.js');
	}

	public function model_customer_customer_getCustomers_before($eventRoute, &$data) {
		if (!empty($data[0]['filter_newsletter'])) {
			$this->load->model('journal3/newsletter');

			return $this->model_journal3_newsletter->getSubscribers($data[0]);
		}
	}

	public function model_customer_customer_getTotalCustomers_before($eventRoute, &$data) {
		if (!empty($data[0]['filter_newsletter'])) {
			$this->load->model('journal3/newsletter');

			return $this->model_journal3_newsletter->getTotalSubscribers($data[0]);
		}
	}

	public function view_common_column_left_before($eventRoute, &$data) {
		if ($this->user->hasPermission('access', 'journal3/journal')) {
			$journal = [];

			if (version_compare(VERSION, '3', '<')) {
				$base = $this->url->link('journal3/journal', 'token=' . $this->session->data['token'], true);
			} else {
				$base = $this->url->link('journal3/journal', 'user_token=' . $this->session->data['user_token'], true);
			}

			// dashboard
			$journal[] = [
				'name'     => $this->language->get('Dashboard'),
				'href'     => $base . '#/dashboard',
				'children' => [],
			];

			// variables
			if ($this->user->hasPermission('access', 'journal3/variable')) {
				$journal[] = [
					'name'     => $this->language->get('Variables'),
					'href'     => $base . '#/variable/color',
					'children' => [],
				];
			}

			// styles
			if ($this->user->hasPermission('access', 'journal3/style')) {
				$journal[] = array(
					'name'     => $this->language->get('Styles'),
					'href'     => $base . '#/style/page',
					'children' => array(),
				);
			}

			// skins
			if ($this->user->hasPermission('access', 'journal3/skin')) {
				$journal[] = array(
					'name'     => $this->language->get('Skins'),
					'href'     => $base . '#/skin',
					'children' => array(),
				);
			}

			// header modules
			if ($this->user->hasPermission('access', 'journal3/module_header')) {
				$journal[] = array(
					'name'     => $this->language->get('Header'),
					'href'     => $base . '#/module_header/main_menu',
					'children' => array(),
				);
			}

			// footer modules
			if ($this->user->hasPermission('access', 'journal3/module_footer')) {
				$journal[] = array(
					'name'     => $this->language->get('Footer'),
					'href'     => $base . '#/module_footer/footer_menu',
					'children' => array(),
				);
			}

			// layout
			if ($this->user->hasPermission('access', 'journal3/layout')) {
				$journal[] = array(
					'name'     => $this->language->get('Layouts'),
					'href'     => $base . '#/layout',
					'children' => array(),
				);
			}

			// layout modules
			if ($this->user->hasPermission('access', 'journal3/module_layout')) {
				$journal[] = array(
					'name'     => $this->language->get('Modules'),
					'href'     => $base . '#/module_layout/banners_grid',
					'children' => array(),
				);
			}

			// product modules
			if ($this->user->hasPermission('access', 'journal3/module_product')) {
				$journal[] = array(
					'name'     => $this->language->get('Product Extras'),
					'href'     => $base . '#/module_product/product_label',
					'children' => array(),
				);
			}

			// blog
			$children = array();

			if ($this->user->hasPermission('access', 'journal3/blog_setting')) {
				$children[] = array(
					'name'     => $this->language->get('Settings'),
					'href'     => $base . '#/blog_setting',
					'children' => array(),
				);
			}

			if ($this->user->hasPermission('access', 'journal3/blog_category')) {
				$children[] = array(
					'name'     => $this->language->get('Categories'),
					'href'     => $base . '#/blog_category',
					'children' => array(),
				);
			}

			if ($this->user->hasPermission('access', 'journal3/blog_post')) {
				$children[] = array(
					'name'     => $this->language->get('Posts'),
					'href'     => $base . '#/blog_post',
					'children' => array(),
				);
			}

			if ($this->user->hasPermission('access', 'journal3/blog_comment')) {
				$children[] = array(
					'name'     => $this->language->get('Comments'),
					'href'     => $base . '#/blog_comment',
					'children' => array(),
				);
			}

			if ($children) {
				$journal[] = array(
					'name'     => $this->language->get('Blog'),
					'href'     => '',
					'children' => $children,
				);
			}

			// system
			$children = array();

			// settings
			if ($this->user->hasPermission('access', 'journal3/setting')) {
				$children[] = array(
					'name'     => $this->language->get('Settings'),
					'href'     => $base . '#/setting',
					'children' => array(),
				);
			}

			// newsletter
			if ($this->user->hasPermission('access', 'journal3/newsletter')) {
				$children[] = array(
					'name'     => $this->language->get('Newsletter'),
					'href'     => $base . '#/newsletter',
					'children' => array(),
				);
			}

			// message
			if ($this->user->hasPermission('access', 'journal3/message')) {
				$children[] = array(
					'name'     => $this->language->get('Form E-Mails'),
					'href'     => $base . '#/message',
					'children' => array(),
				);
			}

			// import/export
			if ($this->user->hasPermission('access', 'journal3/import_export')) {
				$children[] = array(
					'name'     => $this->language->get('Import / Export'),
					'href'     => $base . '#/import_export',
					'children' => array(),
				);
			}

			// system settings
			if ($this->user->hasPermission('access', 'journal3/system')) {
				$children[] = array(
					'name'     => $this->language->get('System'),
					'href'     => $base . '#/system',
					'children' => array(),
				);
			}

			if ($children) {
				$journal[] = array(
					'name'     => $this->language->get('System'),
					'href'     => '',
					'children' => $children,
				);
			}

			array_splice($data['menus'], 1, 0, [
				[
					'id'       => 'journal3-theme',
					'icon'     => 'fa-cogs journal3-icon',
					'name'     => $this->language->get('Journal'),
					'href'     => '',
					'children' => $journal,
				],
			]);
		}
	}

	public function view_error_permission_after($eventRoute, &$data, &$output) {
		if (!empty($this->request->get['jf'])) {
			$this->response->addHeader('Content-Type: application/json');

			$output = json_encode(array(
				'status'   => 'error',
				'response' => $data['text_permission'],
			));
		}
	}

	public function view_error_not_found_after($eventRoute, &$data, &$output) {
		if (!empty($this->request->get['jf'])) {
			$this->response->addHeader('Content-Type: application/json');

			$output = json_encode(array(
				'status'   => 'error',
				'response' => $data['text_not_found'],
			));
		}
	}

	public function view_common_login_after($eventRoute, &$data, &$output) {
		if (!empty($this->request->get['jf'])) {
			$this->response->addHeader('Content-Type: application/json');

			$output = json_encode(array(
				'status'   => 'error',
				'response' => $data['error_token'],
				'reload'   => true,
			));
		}
	}

	public function view_journal3_journal3_after(&$route, &$args, &$output) {
		if (JOURNAL3_SENTRY_DSN_LOADER && ($pos = strpos($output, '<script')) !== false) {
			$output = substr_replace($output, '<script src="' . JOURNAL3_SENTRY_DSN_LOADER . '" crossorigin="anonymous"></script>' . PHP_EOL, $pos, 0);
		}
	}

	public function clear_category_cache(&$route, &$data) {
		$this->cache_delete('module');
		$this->cache_delete('catalog.category');
	}

	public function clear_information_cache(&$route, &$data) {
		$this->cache_delete('module');
		$this->cache_delete('catalog.information');
	}

	public function clear_manufacturer_cache(&$route, &$data) {
		$this->cache_delete('module');
		$this->cache_delete('catalog.manufacturer');
	}

	public function clear_product_cache(&$route, &$data) {
		$this->cache_delete('module');
		$this->cache_delete('catalog.product');

		// refresh attribute values
		$separator = $this->db->query("SELECT * FROM `" . DB_PREFIX . "journal3_setting` WHERE setting_name = 'filterAttributeValuesSeparator'")->row['setting_value'] ?? null;
		$product_id = $data[0] ?? null;
		$product_attributes = $data[1]['product_attribute'] ?? null ?: [];

		if ($separator && $product_id) {
			$this->load->model('journal3/module');
			$this->model_journal3_module->explodeAttributeValues($separator, $product_id, $product_attributes);
		}
	}

	public function clear_cache(&$route, &$data) {
		$this->cache_delete();
	}

	public function print_error($title, $error, $footer = '') {
		echo "
			<style>
				body {
					font-family: sans-serif;
					padding: 30px;
				}
				b {
					color: red;
				}
			</style>
			<div class=\"content\">
				<p><h2>{$title}</h2></p>
				<p>{$error}</p>
				<p>{$footer}</p>
			</div>
		";

		exit;
	}

	public function cache_delete($key = null) {
		if ($key === null) {
			$files = glob(DIR_CACHE . 'journal3.*');
		} else {
			$files = glob(DIR_CACHE . 'journal3.' . $key . '.*');
		}

		if ($files) {
			foreach ($files as $file) {
				if (is_file($file)) {
					@unlink($file);
				}
			}
		}
	}

	public function model_setting_extension_getInstalls_after(&$route, &$data, &$output) {
		if (is_array($output)) {
			foreach ($output as &$extension) {
				if ($extension['code'] === 'journal_3') {
					if ($extension['version'] !== JOURNAL3_VERSION) {
						$extension['version'] = JOURNAL3_VERSION;
						$this->db->query("UPDATE `" . DB_PREFIX . "extension_install` SET `version` = '" . $this->db->escape(JOURNAL3_VERSION) . "' WHERE `extension_install_id` = '" . (int)$extension['extension_install_id'] . "'");
					}
				}
			}
		}
	}

}

class_alias('ControllerJournal3Events', '\Opencart\Admin\Controller\Journal3\Events');
