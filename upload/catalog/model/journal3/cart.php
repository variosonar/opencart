<?php

class ModelJournal3Cart extends Model {

	public function totals() {
		if ($this->journal3_opencart->is_oc4) {
			$this->load->model('checkout/cart');

			$data['totals'] = [];

			$totals = [];
			$taxes = $this->cart->getTaxes();
			$total = 0;

			// Display prices
			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				($this->model_checkout_cart->getTotals)($totals, $taxes, $total);

				foreach ($totals as $result) {
					$data['totals'][] = [
						'title' => $result['title'],
						'text'  => $this->currency->format($result['value'], $this->session->data['currency']),
					];
				}
			}

			return array(
				'total'  => $total,
				'totals' => $data['totals'],
			);
		} else {
			$totals = array();
			$taxes = $this->cart->getTaxes();
			$total = 0;

			// Because __call can not keep var references so we put them into an array.
			$total_data = array(
				'totals' => &$totals,
				'taxes'  => &$taxes,
				'total'  => &$total,
			);

			if ($this->journal3_opencart->is_oc2) {
				$this->load->model('extension/extension');

				$sort_order = array();

				$results = $this->model_extension_extension->getExtensions('total');

				foreach ($results as $key => $value) {
					$sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
				}

				array_multisort($sort_order, SORT_ASC, $results);

				foreach ($results as $result) {
					if ($this->config->get($result['code'] . '_status')) {
						$this->load->model('extension/total/' . $result['code']);

						// We have to put the totals in an array so that they pass by reference.
						$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
					}
				}
			} else {
				$sort_order = array();

				$this->load->model('setting/extension');

				// Get a list of installed modules
				if (version_compare(VERSION, '4', '>=') || defined('JOURNAL3_OLD_OC3039')) {
					$results = $this->model_setting_extension->getExtensionsByType('total');
				} else {
					$results = $this->model_setting_extension->getExtensions('total');
				}

				foreach ($results as $key => $value) {
					$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
				}

				array_multisort($sort_order, SORT_ASC, $results);

				foreach ($results as $result) {
					if ($this->config->get('total_' . $result['code'] . '_status')) {
						$this->load->model('extension/total/' . $result['code']);

						// We have to put the totals in an array so that they pass by reference.
						$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
					}
				}
			}

			$sort_order = array();

			foreach ($totals as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $totals);

			return array(
				'total'  => $total,
				'totals' => $totals,
			);
		}
	}

}

class_alias('ModelJournal3Cart', '\Opencart\Catalog\Model\Journal3\Cart');
