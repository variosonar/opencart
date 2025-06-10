<?php

class ControllerJournal3EventAccount extends Controller {

	const DUMMY = '**********';
	const DUMMY_NUM = '-1';

	public function controller_account_account_before(&$route, &$args) {
		if ($this->journal3_request->is_post) {
			if (empty($this->request->post['firstname']) && $this->journal3->get('accountAccountFirstNameField') !== 'required') {
				$this->request->post['firstname'] = self::DUMMY;
			}

			if (empty($this->request->post['lastname']) && $this->journal3->get('accountAccountLastNameField') !== 'required') {
				$this->request->post['lastname'] = self::DUMMY;
			}

			if (empty($this->request->post['telephone']) && $this->journal3->get('accountAccountTelephoneField') !== 'required') {
				$this->request->post['telephone'] = self::DUMMY;
			}
		}
	}

	public function model_account_customer_addCustomer_before(&$route, &$args) {
		foreach ($args[0] as &$val) {
			if ($val === self::DUMMY || $val === self::DUMMY_NUM) {
				$val = '';
			}
		}
	}

	public function model_account_customer_editCustomer_before(&$route, &$args) {
		foreach ($args[1] as &$val) {
			if ($val === self::DUMMY || $val === self::DUMMY_NUM) {
				$val = '';
			}
		}
	}

	public function view_account_account_before(&$route, &$args) {
		if ($this->journal3_request->is_post) {
			if (($args['firstname'] ?? '') === self::DUMMY) {
				$args['firstname'] = '';
			}

			if (($args['lastname'] ?? '') === self::DUMMY) {
				$args['lastname'] = '';
			}

			if (($args['telephone'] ?? '') === self::DUMMY) {
				$args['telephone'] = '';
			}
		}
	}

	public function controller_account_address_before(&$route, &$args) {
		if ($this->journal3_request->is_post) {
			if (empty($this->request->post['firstname']) && $this->journal3->get('accountAddressFirstNameField') !== 'required') {
				$this->request->post['firstname'] = self::DUMMY;
			}

			if (empty($this->request->post['lastname']) && $this->journal3->get('accountAddressLastNameField') !== 'required') {
				$this->request->post['lastname'] = self::DUMMY;
			}

			if (empty($this->request->post['address_1']) && $this->journal3->get('accountAddressAddress1Field') !== 'required') {
				$this->request->post['address_1'] = self::DUMMY;
			}

			if (empty($this->request->post['city']) && $this->journal3->get('accountAddressCityField') !== 'required') {
				$this->request->post['city'] = self::DUMMY;
			}

			if (empty($this->request->post['country_id']) && $this->journal3->get('accountAddressCountryField') !== 'required') {
				$this->request->post['country_id'] = self::DUMMY_NUM;
			}

			if (empty($this->request->post['zone_id']) && $this->journal3->get('accountAddressRegionField') !== 'required') {
				$this->request->post['zone_id'] = self::DUMMY_NUM;
			}
		}
	}

	public function model_account_address_addAddress_before(&$route, &$args) {
		foreach ($args[1] as &$val) {
			if ($val === self::DUMMY || $val === self::DUMMY_NUM) {
				$val = '';
			}
		}
	}

	public function model_account_address_editAddress_before(&$route, &$args) {
		foreach ($args[1] as &$val) {
			if ($val === self::DUMMY || $val === self::DUMMY_NUM) {
				$val = '';
			}
		}
	}

	public function view_account_address_before(&$route, &$args) {
		if ($this->journal3_request->is_post) {
			if ($args['firstname'] === self::DUMMY) {
				$args['firstname'] = '';
			}

			if ($args['lastname'] === self::DUMMY) {
				$args['lastname'] = '';
			}

			if ($args['address_1'] === self::DUMMY) {
				$args['address_1'] = '';
			}

			if ($args['city'] === self::DUMMY) {
				$args['city'] = '';
			}

			if ($args['country_id'] === self::DUMMY_NUM) {
				$args['country_id'] = '';
			}

			if ($args['zone_id'] === self::DUMMY_NUM) {
				$args['zone_id'] = '';
			}
		}
	}

	public function view_account_wishlist_before(&$route, &$args) {
		if (!empty($args['products'])) {
			$this->load->model('journal3/product');
			$product_info = $this->model_journal3_product->getProduct($args['products']);

			$args['image_width'] = $this->journal3->get('image_dimensions_wishlist.width');
			$args['image_height'] = $this->journal3->get('image_dimensions_wishlist.height');
			$args['image_resize'] = $this->journal3->get('image_dimensions_wishlist.resize');

			foreach ($args['products'] as &$product) {
				$result = $product_info[$product['product_id']];

				// image
				if ($result['image']) {
					$image = $this->journal3_image->resize($result['image'], $args['image_width'], $args['image_height'], $args['image_resize']);
					$image2x = $this->journal3_image->resize($result['image'], $args['image_width'] * 2, $args['image_height'] * 2, $args['image_resize']);
				} else {
					$image = $this->journal3_image->resize($this->journal3->get('placeholder'), $args['image_width'], $args['image_height'], $args['image_resize']);
					$image2x = $this->journal3_image->resize($this->journal3->get('placeholder'), $args['image_width'] * 2, $args['image_height'] * 2, $args['image_resize']);
				}

				// product extras
				$classes = $this->journal3_product_extras->exclude_button($result);

				// classes
				$classes['out-of-stock'] = $result['quantity'] <= 0;
				$classes['has-zero-price'] = ($result['special'] ? $result['special'] : $result['price']) <= 0;

				$product = array_merge($product, array(
					'thumb'   => $image,
					'thumb2x' => $image2x,
					'classes' => $classes,
				));
			}
		}
	}

	public function view_account_order_info_before(&$route, &$args) {
		$this->load->model('account/order');
		$this->load->model('journal3/product');

		if ($this->journal3_opencart->is_oc4) {
			$products = $this->model_account_order->getProducts($this->request->get['order_id']);
		} else {
			$products = $this->model_account_order->getOrderProducts($this->request->get['order_id']);
		}

		$product_info = $this->model_journal3_product->getProduct($products);

		foreach ($args['products'] as $index => &$product) {
			$result = $product_info[$products[$index]['product_id']] ?? null;

			if ($result) {
				// product extras
				$classes = $this->journal3_product_extras->exclude_button($result);

				// classes
				$classes['out-of-stock'] = !$result || $result['quantity'] <= 0;
				$classes['has-zero-price'] = $result && (($result['special'] ? $result['special'] : $result['price']) <= 0);
			} else {
				$classes = [];
			}

			$product = array_merge($product, array(
				'classes' => $classes,
			));
		}
	}

	public function controller_account_before(&$route, &$args) {
		if ($this->journal3->is_popup) {
			$this->document->addScript('catalog/view/theme/journal3/js/account.js', 'js-defer');
		}
	}

	public function view_account_account_after(&$route, &$args, &$output) {
		if ($this->journal3_request->is_ajax) {
			$output = json_encode([
				'status'   => 'success',
				'customer' => $this->customer->isLogged(),
			]);
		}
	}

}

class_alias('ControllerJournal3EventAccount', '\Opencart\Catalog\Controller\Journal3\Event\Account');
