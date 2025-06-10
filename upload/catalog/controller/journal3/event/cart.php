<?php

class ControllerJournal3EventCart extends Controller {

	public function view_common_cart_before(&$route, &$args) {
		$this->load->model('journal3/cart');

		$totals = $this->model_journal3_cart->totals();

		$args['items_count'] = $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0);
		$args['items_price'] = $this->currency->format($totals['total'], $this->session->data['currency']);

		if (!empty($args['products'])) {
			$products = array_values($this->cart->getProducts());

			foreach ($products as $index => $product) {
				if ($product['image']) {
					$image2x = $this->model_tool_image->resize($product['image'], $this->journal3->get('image_dimensions_cart.width') * 2, $this->journal3->get('image_dimensions_cart.height') * 2);
				} else {
					$image2x = $this->model_tool_image->resize('placeholder.png', $this->journal3->get('image_dimensions_cart.width') * 2, $this->journal3->get('image_dimensions_cart.height') * 2);
				}

				$args['products'][$index]['thumb2x'] = $image2x;
			}
		}
	}

	public function view_checkout_cart_before(&$route, &$args) {
		if (!empty($args['products'])) {
			$this->load->model('journal3/product');

			$products = array_values($this->cart->getProducts());
			$product_info = $this->model_journal3_product->getProduct($products);

			foreach ($products as $index => $product) {
				$result = $product_info[$product['product_id']] ?? null;

				if ($result) {
					// product extras
					$classes = $this->journal3_product_extras->exclude_button($result);

					// classes
					$classes['out-of-stock'] = $result['quantity'] <= 0;
					$classes['has-zero-price'] = ($result['special'] ?: $result['price']) <= 0;
				} else {
					$classes = [];
				}

				$args['products'][$index] = array_merge($args['products'][$index], array(
					'classes' => $classes,
				));
			}
		}
	}

}

class_alias('ControllerJournal3EventCart', '\Opencart\Catalog\Controller\Journal3\Event\Cart');
