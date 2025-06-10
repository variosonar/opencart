<?php

class ControllerJournal3EventNotification extends Controller {

	public function controller_checkout_cart_add_after(&$route, &$args, &$output) {
		if (!$this->journal3->get('notificationStatus')) {
			return;
		}

		$json = json_decode($this->response->getOutput(), true);

		$product_info = $this->model_catalog_product->getProduct($this->request->post['product_id'] ?? 0);

		if ($product_info['image'] ?? null) {
			$image = $this->journal3_image->resize($product_info['image'], $this->journal3->get('image_dimensions_notification.width'), $this->journal3->get('image_dimensions_notification.height'), $this->journal3->get('image_dimensions_notification.resize'));
			$image2x = $this->journal3_image->resize($product_info['image'], $this->journal3->get('image_dimensions_notification.width') * 2, $this->journal3->get('image_dimensions_notification.height') * 2, $this->journal3->get('image_dimensions_notification.resize'));
		} else {
			$image = false;
			$image2x = false;
		}

		$this->load->model('journal3/cart');

		$totals = $this->model_journal3_cart->totals();

		if (!empty($json['success'])) {
			$this->load->language('common/cart');

			$json['notification'] = array(
				'className' => 'notification-cart',
				'position'  => $this->journal3->get('cartNotificationStylePosition'),
				'title'     => $product_info['name'],
				'image'     => $image,
				'image2x'   => $image2x,
				'message'   => $json['success'],
				'buttons'   => array(
					array(
						'className' => 'btn btn-cart notification-view-cart',
						'name'      => $this->language->get('text_cart'),
						'href'      => $this->journal3_url->link('checkout/cart', '', true),
					),
					array(
						'className' => 'btn btn-success notification-checkout',
						'name'      => $this->language->get('text_checkout'),
						'href'      => $this->journal3_url->link('checkout/checkout', '', true),
					),
				),
			);

			$json['items_count'] = $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0);
			$json['items_price'] = $this->currency->format($totals['total'], $this->session->data['currency']);

			switch ($this->journal3->get('addToCartAction')) {
				case 'redirect_cart':
					$json['redirect'] = str_replace('&amp;', '&', $this->journal3_url->link('checkout/cart'));
					break;

				case 'redirect_checkout':
					$json['redirect'] = str_replace('&amp;', '&', $this->journal3_url->link('checkout/checkout'));
					break;
			}
		} else {
			$json['options_popup'] = $this->journal3->get('globalOptionsPopupStatus', true);
		}

		if (empty($json['total'])) {
			$this->load->language('checkout/cart');

			$json['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($totals['total'], $this->session->data['currency']));
		}

		$this->response->setOutput(json_encode($json));
	}

	public function controller_checkout_cart_edit_after(&$route, &$args, &$output) {
		$json = json_decode($this->response->getOutput(), true);

		if (empty($json['total'])) {
			$this->load->model('journal3/cart');

			$totals = $this->model_journal3_cart->totals();

			$json['items_count'] = $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0);
			$json['items_price'] = $this->currency->format($totals['total'], $this->session->data['currency']);

			$this->load->language('checkout/cart');
			$this->load->language('common/cart');

			$json['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($totals['total'], $this->session->data['currency']));
		}

		$this->response->setOutput(json_encode($json));
	}

	public function controller_checkout_cart_remove_after(&$route, &$args, &$output) {
		$json = json_decode($this->response->getOutput(), true);

		if (empty($json['total'])) {
			$this->load->model('journal3/cart');

			$totals = $this->model_journal3_cart->totals();

			$json['items_count'] = $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0);
			$json['items_price'] = $this->currency->format($totals['total'], $this->session->data['currency']);

			$this->load->language('checkout/cart');
			$this->load->language('common/cart');

			$json['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($totals['total'], $this->session->data['currency']));
		}

		$this->response->setOutput(json_encode($json));
	}

	public function controller_account_wishlist_add_after(&$route, &$args, &$output) {
		if (!$this->journal3->get('notificationStatus')) {
			return;
		}

		$json = json_decode($this->response->getOutput(), true);

		$product_info = $this->model_catalog_product->getProduct($this->request->post['product_id'] ?? 0);

		if (!$product_info || !$json) {
			return;
		}

		if ($product_info['image'] ?? null) {
			$image = $this->journal3_image->resize($product_info['image'], $this->journal3->get('image_dimensions_notification.width'), $this->journal3->get('image_dimensions_notification.height'), $this->journal3->get('image_dimensions_notification.resize'));
			$image2x = $this->journal3_image->resize($product_info['image'], $this->journal3->get('image_dimensions_notification.width') * 2, $this->journal3->get('image_dimensions_notification.height') * 2, $this->journal3->get('image_dimensions_notification.resize'));
		} else {
			$image = false;
			$image2x = false;
		}

		$json['success'] = $json['success'] ?? $json['error'] ?? '';

		$json['notification'] = array(
			'className' => 'notification-wishlist',
			'position'  => $this->journal3->get('wishlistNotificationStylePosition'),
			'title'     => $product_info['name'],
			'image'     => $image,
			'image2x'   => $image2x,
			'message'   => $json['success'],
			'buttons'   => '',
		);

		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');
			$json['count'] = (version_compare(VERSION, '4.1.0.0', '>=') ? $this->model_account_wishlist->getTotalWishlist($this->customer->getId()) : $this->model_account_wishlist->getTotalWishlist());
		} else {
			$json['count'] = isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0;
		}

		$this->response->setOutput(json_encode($json));
	}

	public function controller_product_compare_add_after(&$route, &$args, &$output) {
		if (!$this->journal3->get('notificationStatus')) {
			return;
		}

		$json = json_decode($this->response->getOutput(), true);

		$product_info = $this->model_catalog_product->getProduct($this->request->post['product_id'] ?? 0);

		if ($product_info['image'] ?? null) {
			$image = $this->journal3_image->resize($product_info['image'], $this->journal3->get('image_dimensions_notification.width'), $this->journal3->get('image_dimensions_notification.height'), $this->journal3->get('image_dimensions_notification.resize'));
			$image2x = $this->journal3_image->resize($product_info['image'], $this->journal3->get('image_dimensions_notification.width') * 2, $this->journal3->get('image_dimensions_notification.height') * 2, $this->journal3->get('image_dimensions_notification.resize'));
		} else {
			$image = false;
			$image2x = false;
		}

		$json['notification'] = array(
			'className' => 'notification-compare',
			'position'  => $this->journal3->get('compareNotificationStylePosition'),
			'title'     => $product_info['name'],
			'image'     => $image,
			'image2x'   => $image2x,
			'message'   => $json['success'],
			'buttons'   => '',
		);

		$json['total'] = $this->journal3->countBadge($this->language->get('text_compare'), isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0);
		$json['count'] = isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0;

		$this->response->setOutput(json_encode($json));
	}

}

class_alias('ControllerJournal3EventNotification', '\Opencart\Catalog\Controller\Journal3\Event\Notification');
