<?php

use Journal3\Utils\Arr;

class ControllerJournal3Search extends Controller {

	public function index() {
		$search = Arr::get($this->request->get, 'search');
		$category_id = (int)Arr::get($this->request->get, 'category_id');
		$sub_category = (int)Arr::get($this->request->get, 'sub_category');
		$description = (int)$this->journal3->get('searchStyleSearchAutoSuggestDescription');

		$url = '';

		if ($search) {
			$url .= '&search=' . urlencode(html_entity_decode($search, ENT_QUOTES, 'UTF-8'));
		}

		if ($category_id) {
			$url .= '&category_id=' . $category_id;

			if ($sub_category) {
				$url .= '&sub_category=' . $sub_category;
			}
		}

		if ($description) {
			$url .= '&description=' . $description;
		}

		$limit = (int)$this->journal3->get('searchStyleSearchAutoSuggestLimit');

		if (!$limit) {
			$limit = 10;
		}

		$filter_data = array(
			'filter_name'        => $search,
			'filter_tag'         => $search,
			'filter_description' => (bool)$description,
			'start'              => 0,
			'limit'              => $limit,
		);

		if ($category_id) {
			$filter_data['filter_category_id'] = $category_id;

			if ($sub_category) {
				$filter_data['filter_sub_category'] = true;
			}
		}

		$this->load->model('journal3/filter');
		$this->load->model('journal3/product');

		$products = array();

		$results = $this->model_journal3_filter->getProducts($filter_data);
		$results = $this->model_journal3_product->getProduct($results);

		foreach ($results as $result) {
			if ($result['image']) {
				$image = $this->journal3_image->resize($result['image'], $this->journal3->get('image_dimensions_autosuggest.width'), $this->journal3->get('image_dimensions_autosuggest.height'), $this->journal3->get('image_dimensions_autosuggest.resize'));
				$image2 = $this->journal3_image->resize($result['image'], $this->journal3->get('image_dimensions_autosuggest.width') * 2, $this->journal3->get('image_dimensions_autosuggest.height') * 2, $this->journal3->get('image_dimensions_autosuggest.resize'));
			} else {
				$image = $this->journal3_image->resize($this->journal3->get('placeholder'), $this->journal3->get('image_dimensions_autosuggest.width'), $this->journal3->get('image_dimensions_autosuggest.height'), $this->journal3->get('image_dimensions_autosuggest.resize'));
				$image2 = $this->journal3_image->resize($this->journal3->get('placeholder'), $this->journal3->get('image_dimensions_autosuggest.width') * 2, $this->journal3->get('image_dimensions_autosuggest.height') * 2, $this->journal3->get('image_dimensions_autosuggest.resize'));
			}

			$price = false;
			$special = false;

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

				if (!is_null($result['special']) && (float)$result['special'] >= 0) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				}
			}

			$products[] = array(
				'quantity'    => (int)$result['quantity'],
				'price_value' => $result['special'] ? $result['special'] > 0 : $result['price'] > 0,
				'product_id'  => $result['product_id'],
				'name'        => html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'),
				'thumb'       => $image,
				'thumb2'      => $image2,
				'price'       => $price,
				'special'     => $special,
				'href'        => $this->journal3_url->link('product/product', '&product_id=' . $result['product_id'] . $url),
			);
		}

		if ($products) {
			$products[] = array(
				'view_more' => true,
				'name'      => $this->journal3->get('searchStyleSearchViewMoreText'),
				'href'      => $this->journal3_url->link('product/search', $url),
			);
		} else {
			$products[] = array(
				'no_results' => true,
				'name'       => $this->journal3->get('searchStyleSearchNoResultsText'),
			);
		}

		$this->journal3_response->json('success', $products);
	}

}

class_alias('ControllerJournal3Search', '\Opencart\Catalog\Controller\Journal3\Search');
