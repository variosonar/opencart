<?php

use Journal3\Utils\Arr;

class ControllerJournal3Price extends Controller {

	public function index() {
		$this->load->model('catalog/product');
		$this->load->model('journal3/product');
		$this->load->language('product/product');

		$product_id = Arr::get($this->request->post, 'product_id');
		$popup = (bool)Arr::get($this->request->get, 'popup');
		$product_info = $this->model_catalog_product->getProduct($product_id);

		if ($product_info) {
			$quantity = (int)Arr::get($this->request->post, 'quantity', 1);

			// stock
			$data['in_stock'] = $product_info['quantity'] > 0;
			$data['quantity'] = $product_info['quantity'];

			// options price
			$options_price = 0;

			// options points
			$options_points = 0;

			// options weight
			$options_weight = 0;

			$product_option_values = $this->model_journal3_product->getProductOptionValues($product_id, Arr::get($this->request->post, 'option', array()));

			foreach ($product_option_values as $product_option_value) {
				if ($product_option_value['price_prefix'] === '+') {
					$options_price += $product_option_value['price'];
				}

				if ($product_option_value['price_prefix'] === '-') {
					$options_price -= $product_option_value['price'];
				}

				if ($product_option_value['points_prefix'] === '+') {
					$options_points += $product_option_value['points'];
				}

				if ($product_option_value['points_prefix'] === '-') {
					$options_points -= $product_option_value['points'];
				}

				if ($product_option_value['weight_prefix'] === '+') {
					$options_weight += $product_option_value['weight'];
				}

				if ($product_option_value['weight_prefix'] === '-') {
					$options_weight -= $product_option_value['weight'];
				}

				if ($product_option_value['subtract']) {
					if ($product_option_value['quantity'] < $data['quantity']) {
						$data['quantity'] = $product_option_value['quantity'];
					}

					if (!$product_option_value['quantity'] || $product_option_value['quantity'] < $quantity) {
						$data['in_stock'] = false;
					}
				}
			}

			// stock status
			if ($data['quantity'] < $quantity) {
				$data['stock'] = $product_info['stock_status'] ?? '';
				$data['in_stock'] = false;
			} elseif ($this->config->get('config_stock_display')) {
				$data['stock'] = $data['quantity'];
			} else {
				$data['stock'] = $this->journal3->get($popup ? 'quickviewPageStyleProductInStockText' : 'productPageStyleProductInStockText');

				// some third party addons for in stock status
				if (isset($product_info['in_stock_status']) && $product_info['in_stock_status']) {
					$data['stock'] = $product_info['in_stock_status'];
				}
			}

			// base price
			if (version_compare(VERSION, '4.1.0.0', '>=')) {
				$base_price = $product_info['price'];
			} else {
				$product_discount_query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND quantity <= '" . (int)$quantity . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1");

				if ($product_discount_query->num_rows) {
					$base_price = $product_discount_query->row['price'];
				} else {
					$base_price = $product_info['price'];
				}
			}

			// weight
			$data['weight'] = $this->weight->format((float)$product_info['weight'] + (float)$options_weight, $product_info['weight_class_id']);

			// price
			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$data['price'] = $this->currency->format($this->tax->calculate($base_price + $options_price, $product_info['tax_class_id'], $this->config->get('config_tax')) * $quantity, $this->session->data['currency']);
			} else {
				$data['price'] = false;
			}

			// points
			$points = (int)$product_info['points'] + (int)$options_points;

			if ($points) {
				$data['points'] = $this->language->get('text_points') . ' ' . $points;
			} else {
				$data['points'] = false;
			}

			// special
			if ((float)$product_info['special']) {
				$data['special'] = $this->currency->format($this->tax->calculate($product_info['special'] + $options_price, $product_info['tax_class_id'], $this->config->get('config_tax')) * $quantity, $this->session->data['currency']);
			} else {
				$data['special'] = false;
			}

			// ex tax
			if ($this->config->get('config_tax')) {
				$tax = (float)$product_info['special'] ? $product_info['special'] : $base_price;
				$tax = ($tax + $options_price) * $quantity;
				$data['tax'] = $this->language->get('text_tax') . ' ' . $this->currency->format($tax, $this->session->data['currency']);
			} else {
				$data['tax'] = false;
			}

			// discounts
			if ($this->journal3_opencart->is_oc4) {
				$discounts = $this->model_catalog_product->getDiscounts($product_id);
			} else {
				$discounts = $this->model_catalog_product->getProductDiscounts($product_id);
			}

			$data['discounts'] = array();

			foreach ($discounts as $discount) {
				$discount_price = $this->currency->format($this->tax->calculate($discount['price'] + $options_price, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				$data['discounts'][] = $discount['quantity'] . $this->language->get('text_discount') . $discount_price;
			}

			$this->journal3_response->json('success', $data);
		} else {
			$this->journal3_response->json('error', array(
				'message' => $this->language->get('text_error'),
			));
		}

	}

}

class_alias('ControllerJournal3Price', '\Opencart\Catalog\Controller\Journal3\Price');
