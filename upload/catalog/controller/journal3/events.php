<?php

use Journal3\Utils\Min;

class ControllerJournal3Events extends Controller {

	public function index() {
		// journal3
		$this->event->register('view/*/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'view'));

		// js defer
		if ($this->journal3->get('performanceJSDefer')) {
			$routes = array(
				'common/home',
				'information/contact',
				'information/information',
				'product/category',
				'product/manufacturer_info',
				'product/manufacturer_list',
				'product/product',
				'product/search',
				'product/special',
			);

			foreach ($routes as $route) {
				$this->event->register('controller/' . $route . '/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'controller_js_defer_before'));
				$this->event->register('controller/' . $route . '/after', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'controller_js_defer_after'));
			}
		}

		// html minify
		if ($this->journal3->get('performanceHTMLMinify')) {
			$routes = array(
				'common/home',
				'information/contact',
				'information/information',
				'product/category',
				'product/manufacturer_info',
				'product/manufacturer_list',
				'product/product',
				'product/search',
				'product/special',
			);

			foreach ($routes as $route) {
				$this->event->register('view/' . $route . '/after', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'html_minify'));
			}
		}

		// footer data
		$this->event->register('controller/common/footer/before', new Action('journal3/event/footer' . JOURNAL3_ROUTE_SEPARATOR . 'controller_common_footer_before'));
		$this->event->register('view/common/footer/before', new Action('journal3/event/footer' . JOURNAL3_ROUTE_SEPARATOR . 'view_common_footer_before'));

		// header data
		$this->event->register('controller/common/header/before', new Action('journal3/event/header' . JOURNAL3_ROUTE_SEPARATOR . 'controller_common_header_before'));
		$this->event->register('view/common/header/before', new Action('journal3/event/header' . JOURNAL3_ROUTE_SEPARATOR . 'view_common_header_before'));

		// header data search ajax
		$this->event->register('controller/journal3/search/before', new Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'controller_common_header_before'));

		// layout
		$this->event->register('controller/common/column_left/before', new Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'controller_common_position_before'));
		$this->event->register('controller/common/column_right/before', new Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'controller_common_position_before'));
		$this->event->register('controller/common/content_top/before', new Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'controller_common_position_before'));
		$this->event->register('controller/common/content_bottom/before', new Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'controller_common_position_before'));

		if (version_compare(VERSION, '4.1.0.0', '>=')) {
			$this->event->register('view/common/column_left/before', new Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'view_common_position_before'));
			$this->event->register('view/common/column_right/before', new Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'view_common_position_before'));
			$this->event->register('view/common/content_top/before', new Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'view_common_position_before'));
			$this->event->register('view/common/content_bottom/before', new Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'view_common_position_before'));
			$this->event->register('model/design/layout' . JOURNAL3_EVENT_SEPARATOR . 'getModules/after', new Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'model_design_layout_getModules_after'));
		}

		// oc2: disable unused categories menu
		$this->event->register('model/catalog/category' . JOURNAL3_EVENT_SEPARATOR . 'getCategories/after', new Action('journal3/event/performance' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_category_getCategories_after'));

		// oc3: disable unused Opencart menu for performance reasons
		$this->event->register('controller/common/menu/before', new Action('journal3/event/performance' . JOURNAL3_ROUTE_SEPARATOR . 'controller_common_menu_before'));

		// disable unused getInformations in footer
		$this->event->register('model/catalog/information' . JOURNAL3_EVENT_SEPARATOR . 'getInformations/before', new Action('journal3/event/performance' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_information_getInformations_before'));

		// header layout
		$this->event->register('controller/common/header/before', new Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'controller_common_header_before'));
		$this->event->register('view/common/header/before', new Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'view_common_header_before'));

		// footer layout
		$this->event->register('controller/common/footer/before', new Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'controller_common_footer_before'));
		$this->event->register('view/common/footer/before', new Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'view_common_footer_before'));

		// top
		$this->event->register('view/*/before', new Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'view_before'));

		// product listing
		$this->event->register('controller/product/catalog/before', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'controller_products_before'));
		$this->event->register('controller/product/category/before', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'controller_products_before'));
		$this->event->register('controller/product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info/before', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'controller_products_before'));
		$this->event->register('controller/product/search/before', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'controller_products_before'));
		$this->event->register('controller/product/special/before', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'controller_products_before'));
		$this->event->register('view/product/category/before', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'view_products_before'));
		$this->event->register('view/product/manufacturer_info/before', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'view_products_before'));
		$this->event->register('view/product/search/before', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'view_products_before'));
		$this->event->register('view/product/special/before', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'view_products_before'));
		$this->event->register('view/product/thumb/after', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'view_product_thumb_after'));

		// category page
		$this->event->register('controller/product/category/before', new Action('journal3/event/category' . JOURNAL3_ROUTE_SEPARATOR . 'controller_product_category_before'));
		$this->event->register('model/catalog/category' . JOURNAL3_EVENT_SEPARATOR . 'getCategory/after', new Action('journal3/event/category' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_category_getCategory_after'));
		$this->event->register('model/catalog/category' . JOURNAL3_EVENT_SEPARATOR . 'getCategories/after', new Action('journal3/event/category' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_category_getCategories_after'));
		$this->event->register('view/product/category/before', new Action('journal3/event/category' . JOURNAL3_ROUTE_SEPARATOR . 'view_product_category_before'));

		// manufacturer page
		$this->event->register('model/catalog/manufacturer' . JOURNAL3_EVENT_SEPARATOR . 'getManufacturers/after', new Action('journal3/event/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_manufacturer_getManufacturers_after'));
		$this->event->register('view/product/manufacturer_list/before', new Action('journal3/event/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'view_product_manufacturer_list_before'));

		// compare page
		$this->event->register('view/product/compare/before', new Action('journal3/event/product' . JOURNAL3_ROUTE_SEPARATOR . 'view_product_compare_before'));

		// common
		$this->event->register('view/common/cart/before', new Action('journal3/event/cart' . JOURNAL3_ROUTE_SEPARATOR . 'view_common_cart_before'));
		$this->event->register('view/common/maintenance/before', new Action('journal3/event/maintenance' . JOURNAL3_ROUTE_SEPARATOR . 'view_common_maintenance_before'));
		$this->event->register('view/common/search/before', new Action('journal3/event/search' . JOURNAL3_ROUTE_SEPARATOR . 'view_common_search_before'));

		// product page
		$this->event->register('controller/product/product/before', new Action('journal3/event/product' . JOURNAL3_ROUTE_SEPARATOR . 'controller_product_product_before'));
		$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'getProduct/after', new Action('journal3/event/product' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_product_getProduct_after'));

		if ($this->journal3_opencart->is_oc4) {
			$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'getImages/after', new Action('journal3/event/product' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_product_getProductImages_after'));
			$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'getRelated/before', new Action('journal3/event/product' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_product_getProductRelated_before'));
			$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'getOptions/after', new Action('journal3/event/product' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_product_getProductOptions_after'));
		} else {
			$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'getProductImages/after', new Action('journal3/event/product' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_product_getProductImages_after'));
			$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'getProductRelated/before', new Action('journal3/event/product' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_product_getProductRelated_before'));
			$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'getProductOptions/after', new Action('journal3/event/product' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_product_getProductOptions_after'));
		}

		if ($this->journal3_opencart->is_oc4) {
			$this->event->register('controller/product/review/before', new Action('journal3/event/product' . JOURNAL3_ROUTE_SEPARATOR . 'controller_product_review_before'));
			$this->event->register('controller/product/review/after', new Action('journal3/event/product' . JOURNAL3_ROUTE_SEPARATOR . 'controller_product_review_after'));
		}

		$this->event->register('view/product/product/before', new Action('journal3/event/product' . JOURNAL3_ROUTE_SEPARATOR . 'view_product_product_before'));

		// one page checkout
		$this->event->register('controller/checkout/checkout/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'controller_checkout_checkout_before'));
		$this->event->register('controller/checkout/cart' . JOURNAL3_ROUTE_SEPARATOR . 'add/before', new Action('journal3/events' . JOURNAL3_ROUTE_SEPARATOR . 'controller_checkout_cart_add_before'));

		// notification
		$this->event->register('controller/checkout/cart' . JOURNAL3_ROUTE_SEPARATOR . 'add/after', new Action('journal3/event/notification' . JOURNAL3_ROUTE_SEPARATOR . 'controller_checkout_cart_add_after'));
		$this->event->register('controller/checkout/cart' . JOURNAL3_ROUTE_SEPARATOR . 'edit/after', new Action('journal3/event/notification' . JOURNAL3_ROUTE_SEPARATOR . 'controller_checkout_cart_edit_after'));
		$this->event->register('controller/checkout/cart' . JOURNAL3_ROUTE_SEPARATOR . 'remove/after', new Action('journal3/event/notification' . JOURNAL3_ROUTE_SEPARATOR . 'controller_checkout_cart_remove_after'));
		$this->event->register('controller/account/wishlist' . JOURNAL3_ROUTE_SEPARATOR . 'add/after', new Action('journal3/event/notification' . JOURNAL3_ROUTE_SEPARATOR . 'controller_account_wishlist_add_after'));
		$this->event->register('controller/product/compare' . JOURNAL3_ROUTE_SEPARATOR . 'add/after', new Action('journal3/event/notification' . JOURNAL3_ROUTE_SEPARATOR . 'controller_product_compare_add_after'));

		// account validation fields
		$this->event->register('controller/account/register/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'controller_account_account_before'));
		$this->event->register('controller/account/edit/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'controller_account_account_before'));
		$this->event->register('model/account/customer' . JOURNAL3_EVENT_SEPARATOR . 'addCustomer/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'model_account_customer_addCustomer_before'));
		$this->event->register('model/account/customer' . JOURNAL3_EVENT_SEPARATOR . 'editCustomer/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'model_account_customer_editCustomer_before'));
		$this->event->register('view/account/register/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'view_account_account_before'));
		$this->event->register('view/account/edit/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'view_account_account_before'));

		// address validation fields
		$this->event->register('controller/account/address' . JOURNAL3_ROUTE_SEPARATOR . 'add/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'controller_account_address_before'));
		$this->event->register('controller/account/address' . JOURNAL3_ROUTE_SEPARATOR . 'edit/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'controller_account_address_before'));
		$this->event->register('model/account/address' . JOURNAL3_EVENT_SEPARATOR . 'addAddress/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'model_account_address_addAddress_before'));
		$this->event->register('model/account/address' . JOURNAL3_EVENT_SEPARATOR . 'editAddress/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'model_account_address_editAddress_before'));
		$this->event->register('view/account/address_form/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'view_account_address_before'));

		// account login / register popups
		$this->event->register('controller/account/login/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'controller_account_before'));
		$this->event->register('controller/account/register/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'controller_account_before'));
		$this->event->register('view/account/account/after', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'view_account_account_after'));

		// account wishlist / order products
		$this->event->register('view/account/wishlist/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'view_account_wishlist_before'));
		$this->event->register('view/account/wishlist_list/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'view_account_wishlist_before'));
		$this->event->register('view/account/order_info/before', new Action('journal3/event/account' . JOURNAL3_ROUTE_SEPARATOR . 'view_account_order_info_before'));

		// blog sitemap
		$this->event->register('controller/extension/feed/google_sitemap/after', new Action('journal3/event/sitemap' . JOURNAL3_ROUTE_SEPARATOR . 'google_sitemap'));
		$this->event->register('view/information/sitemap/before', new Action('journal3/event/sitemap' . JOURNAL3_ROUTE_SEPARATOR . 'sitemap'));

		// language flag image
		$this->event->register('model/localisation/language' . JOURNAL3_EVENT_SEPARATOR . 'getLanguages/after', new Action('journal3/event/language' . JOURNAL3_ROUTE_SEPARATOR . 'model_localisation_language_after'));
		$this->event->register('view/common/language/before', new Action('journal3/event/language' . JOURNAL3_ROUTE_SEPARATOR . 'view_common_language_before'));

		// category / product not found message
		if (in_array($this->request->get['route'] ?? '', ['product/category', 'product/product'])) {
			$this->event->register('view/error/not_found/before', new Action('journal3/event/not_found' . JOURNAL3_ROUTE_SEPARATOR . 'view_error_not_found_before'));
		}

		// cart
		$this->event->register('view/checkout/cart/before', new Action('journal3/event/cart' . JOURNAL3_ROUTE_SEPARATOR . 'view_checkout_cart_before'));

		// seo
		$this->event->register('view/*/before', new Action('journal3/seo' . JOURNAL3_ROUTE_SEPARATOR . 'view_before'));

		// cache
		$this->event->register('model/checkout/order' . JOURNAL3_EVENT_SEPARATOR . 'addOrderHistory/after', new Action('journal3/event/cache' . JOURNAL3_ROUTE_SEPARATOR . 'model_checkout_order_addOrderHistory_after'));
	}

	public function controller_js_defer_before(&$route, &$args) {
		$this->journal3->js_defer = true;
	}

	public function controller_js_defer_after(&$route, &$args, &$output) {
		if (function_exists('clock')) {
			clock()->event('HTML JS Defer')->name('html_js_defer')->begin();
		}

		$response = $this->response->getOutput();

		$response = str_replace('<script type="text/javascript"', '<script type="text/javascript/defer"', $response ?? '');

		$this->response->setOutput($response);

		if (function_exists('clock')) {
			clock()->event('html_js_defer')->end();
		}
	}

	public function html_minify(&$route, &$args, &$output) {
		if (function_exists('clock')) {
			clock()->event('HTML Minify')->name('html_minify')->begin();
		}

		$output = Min::minifyHTML($output);

		if (function_exists('clock')) {
			clock()->event('html_minify')->end();
		}
	}

	public function view(&$route, &$args) {
		$args['journal3'] = $this->journal3;
		$args['journal3_is_oc3'] = $this->journal3_opencart->is_oc3;
		$args['journal3_is_oc4'] = $this->journal3_opencart->is_oc4;
		$args['journal3_is_oc41'] = version_compare(VERSION, '4.1.0.0', '>=');

		if ($this->journal3_opencart->is_oc2) {
			$file = 'journal3/' . substr($route, 8);

			if (is_file(DIR_TEMPLATE . $file . '.tpl')) {
				$route = $file;
			}
		}
	}

	public function controller_checkout_checkout_before(&$route, &$args) {
		if (!$this->journal3_opencart->is_oc4 && ($this->journal3->get('activeCheckout') === 'journal')) {
			return new Action('journal3/checkout');
		}
	}

	public function controller_checkout_cart_before(&$route, &$args) {
		if (!$this->journal3_opencart->is_oc4 && ($this->journal3->get('activeCheckout') === 'journal')) {
			$this->load->model('journal3/checkout');

			$this->model_journal3_checkout->setCheckoutId();
		}
	}

}

class_alias('ControllerJournal3Events', '\Opencart\Catalog\Controller\Journal3\Events');
