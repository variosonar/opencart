<?php

use Journal3\Utils\Arr;

class ControllerJournal3Startup extends Controller {

	public function index() {
		if ((VERSION === '3.0.3.9') && function_exists('oc_strtoupper')) {
			define('JOURNAL3_OLD_OC3039', true);
		}

		// define route separator
		if (!defined('JOURNAL3_ROUTE_SEPARATOR')) {
			define('JOURNAL3_ROUTE_SEPARATOR', '/');
		}

		if (!defined('JOURNAL3_EVENT_SEPARATOR')) {
			define('JOURNAL3_EVENT_SEPARATOR', '/');
		}

		// Preview page as default theme by setting j3dt GET parameter
		if (!empty($this->session->data['user_id']) && !empty($this->request->get['j3dt'])) {
			if (version_compare(VERSION, '3', '<')) {
				$this->config->set('config_theme', 'theme_default');
				$this->config->set('theme_default_status', 1);
			} else {
				$this->config->set('config_theme', 'default');
				$this->config->set('theme_default_status', 1);
			}
		}

		// Avoid using Journal 3 as template folder for default Opencart theme
		if (
			($this->config->get('config_theme') === 'theme_default' || $this->config->get('config_theme') === 'default') &&
			($this->config->get('config_template') === 'journal3' || $this->config->get('theme_default_directory') === 'journal3')
		) {
			$this->print_error(
				'Journal Installation Error',
				'Journal3 must be activated from System > Settings > Your Store > General > Theme and not from Extension > Extension > Themes (like in Journal2).'
			);
		}

		// If current template is not Journal, no further Journal code will be executed
		if ($this->config->get('config_theme') !== 'journal_3' && $this->config->get('config_theme') !== 'journal3' && $this->config->get('config_theme') !== 'theme_journal3' && $this->config->get('config_template') !== 'journal3') {
			return;
		}

		define('JOURNAL3_ACTIVE', true);

		// Prevent direct access to this file
		if (Arr::get($this->request->get, 'route') === 'journal3/startup') {
			$this->response->redirect($this->url->link('common/home'));

			return;
		}

		// Php version check
		if (version_compare(phpversion(), '7.3', '<')) {
			$this->print_error(
				'Unsupported PHP Version',
				'Journal requires <b>PHP 7.3</b> (or higher)!',
				'Consult with your hosting provider for more information regarding how to upgrade PHP to <b>7.3</b> (or higher).'
			);
		}

		// Older Opencart 3 versions don't work well with PHP 7.4+, for example, php errors appear when logging in as customer without addresses
		if (!defined('JOURNAL3_DISABLE_PHP_CHECK') && version_compare(VERSION, '3', '>=') && version_compare(VERSION, '3.0.3.6', '<=') && version_compare(phpversion(), '7.4', '>=')) {
			$this->print_error(
				'Unsupported PHP Version',
				'Opencart <b>' . VERSION . '</b> does not fully support PHP <b>' . phpversion() . '</b> version!',
				'Consult with your hosting provider for more information regarding how to downgrade PHP to <b>7.3</b>.'
			);
		}

		// version - build
		require_once DIR_SYSTEM . 'library/journal3/build.php';

		// Opencart modifications refreshed check
		if (!defined('JOURNAL3_INSTALLED') || (JOURNAL3_INSTALLED !== JOURNAL3_VERSION)) {
			if (version_compare(VERSION, '4', '>=')) {
				$this->print_error(
					'Journal Installation Error',
					'journal_3.ocmod.zip extension is not installed or updated to the latest version.'
				);
			} else {
				$this->print_error(
					'Journal Installation Error',
					'Make sure you have refreshed Opencart Modifications.'
				);
			}
		}

		// Journal object
		$this->registry->set('journal3', new \Journal3\Journal($this->registry));

		// Device classes
		switch ($this->journal3->device) {
			case 'phone':
				$this->journal3_document->addClass('mobile');
				$this->journal3_document->addClass('phone');
				$this->journal3_document->addClass('touchevents');
				break;

			case 'tablet':
				$this->journal3_document->addClass('mobile');
				$this->journal3_document->addClass('tablet');
				$this->journal3_document->addClass('touchevents');
				break;

			case 'desktop':
				$this->journal3_document->addClass('desktop');
				$this->journal3_document->addClass('no-touchevents');
				break;
		}

		// Browser classes
		foreach ($this->journal3->browser_classes as $class) {
			$this->journal3_document->addClass($class);
		}

		// Opencart version
		$this->journal3_document->addClass('oc' . $this->journal3_opencart->ver);

		// Current store
		$this->journal3_document->addClass('store-' . $this->journal3_opencart->store_id);

		// Popup classes
		if ($this->journal3->is_popup) {
			$this->journal3_document->addClass('popup');
			$this->journal3_document->addClass('popup-' . $this->journal3->popup);
		}

		// Admin classes
		if ($this->journal3_opencart->is_admin) {
			$this->journal3_document->addClass('is-admin');
		}

		// Customer classes
		if ($this->journal3_opencart->is_customer) {
			$this->journal3_document->addClass('is-customer');
		} else {
			$this->journal3_document->addClass('is-guest');
		}

		// Maintenance classes
		if ($this->config->get('config_maintenance') && !$this->journal3_opencart->is_admin) {
			$this->journal3_document->addClass('maintenance-page');
		}

		// Cache cart, compare, wishlist, customer
		$this->load->model('account/wishlist');

		$this->journal3_cache->cart_count = $this->cart->countProducts();
		$this->journal3_cache->compare_count = count(Arr::get($this->session->data, 'compare', []));
		$this->journal3_cache->wishlist_count = $this->journal3_opencart->is_customer ?
			(version_compare(VERSION, '4.1.0.0', '>=') ? $this->model_account_wishlist->getTotalWishlist($this->customer->getId()) : $this->model_account_wishlist->getTotalWishlist())
			: count(Arr::get($this->session->data, 'wishlist', []));
		$this->journal3_cache->customer_firstname = $this->journal3_opencart->is_customer ? $this->customer->getFirstName() : null;
		$this->journal3_cache->customer_lastname = $this->journal3_opencart->is_customer ? $this->customer->getLastName() : null;
		$this->journal3_cache->customer_token = $this->journal3_opencart->is_customer ? ($this->session->data['customer_token'] ?? null) : null;

		// Assets folder does not exist
		if (!is_dir(JOURNAL3_ASSETS_PATH)) {
			$this->print_error(
				'Folder missing',
				JOURNAL3_ASSETS_PATH . ' does not exist!',
				'Create folder on your server.'
			);
		}

		// Assets folder writable check
		if (!is_writable(JOURNAL3_ASSETS_PATH)) {
			$this->print_error(
				'Not Writable',
				JOURNAL3_ASSETS_PATH . ' is not writable!',
				'Consult with your hosting provider for more information.'
			);
		}

		// models
		$this->load->model('journal3/module');

		// blog seo urls
		$this->load->controller('journal3/blog' . JOURNAL3_ROUTE_SEPARATOR . 'seo_url');

		// settings
		$this->load->controller('journal3/settings');

		// events
		$this->load->controller('journal3/events');

		// assets
		$this->load->controller('journal3/assets');

		// skin
		$this->load->controller('journal3/skin');

		// product extras
		$this->journal3_product_extras->setExtras([
			'product_exclude_button' => $this->load->controller('journal3/product_extras', ['module_type' => 'product_exclude_button']),
			'product_extra_button'   => $this->load->controller('journal3/product_extras', ['module_type' => 'product_extra_button']),
			'product_label'          => $this->load->controller('journal3/product_extras', ['module_type' => 'product_label']),
		]);

		// Dashboard
		if (($this->journal3_opencart->store_id > 0) && (JOURNAL3_ENV !== 'development') && (!$this->journal3->get('dashboard_user') || !$this->journal3->get('dashboard_key'))) {
			$this->print_error(
				'Journal License Error',
				'Your current store does not have a Journal license setup, access <b>Journal admin interface</b> and input your license details there.',
				''
			);
		}

		// Active skin check
		if (!$this->journal3->get('active_skin')) {
			$this->print_error(
				'No Skins Found',
				'You can import demo content by following the documentation on: <a href="https://docs.journal-theme.com/docs/demos/demo" target="_blank">Demo Import</a>.'
			);
		}

		// Default js
		$this->journal3_document->addJs(array(
			'isPopup'          => $this->journal3->is_popup,
			'isLoginPopup'     => $this->journal3->is_login_popup,
			'isRegisterPopup'  => $this->journal3->is_register_popup,
			'isQuickviewPopup' => $this->journal3->is_quickview_popup,
			'isOptionsPopup'   => $this->journal3->is_options_popup,
			'isPhone'          => $this->journal3->is_phone,
			'isTablet'         => $this->journal3->is_tablet,
			'isDesktop'        => $this->journal3->is_desktop,
			'isTouch'          => !$this->journal3->is_desktop,
			'isAdmin'          => $this->journal3_opencart->is_admin,
			'isRTL'            => $this->journal3->is_rtl,
			'ocv'              => (int)explode('.', VERSION)[0] ?? null,
			'admin_url'        => Arr::get($this->session->data, 'journal3_admin_url'),
			'route_separator'  => JOURNAL3_ROUTE_SEPARATOR,
			'language'         => $this->config->get('config_language'),
			'add_cart_url'     => 'index.php?route=checkout/cart' . JOURNAL3_ROUTE_SEPARATOR . 'add',
			'edit_cart_url'    => 'index.php?route=checkout/cart' . JOURNAL3_ROUTE_SEPARATOR . 'edit',
			'remove_cart_url'  => 'index.php?route=checkout/cart' . JOURNAL3_ROUTE_SEPARATOR . 'remove',
			'remove_cart_method' => version_compare(VERSION, '4.1.0.0', '>=') ? 'get': 'post',
			'info_cart_url'    => 'index.php?route=common/cart' . JOURNAL3_ROUTE_SEPARATOR . 'info ul li',
			'add_wishlist_url' => 'index.php?route=account/wishlist' . JOURNAL3_ROUTE_SEPARATOR . 'add',
			'add_compare_url'  => 'index.php?route=product/compare' . JOURNAL3_ROUTE_SEPARATOR . 'add',
			'search_url'       => str_replace('&amp;', '&', $this->journal3_url->link('product/search', 'search=__QUERY__')),
			'autosuggest_url'  => 'index.php?route=journal3/search&search=__QUERY__',
		));
	}

	public function error_not_installed() {
		if (!defined('JOURNAL3_INSTALLED')) {
			$this->print_error(
				'Journal Installation Error',
				'Make sure you have refreshed Opencart Modifications.'
			);
		} else {
			$this->response->redirect($this->url->link('common/home'));
		}
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

}

class_alias('ControllerJournal3Startup', '\Opencart\Catalog\Controller\Journal3\Startup');
