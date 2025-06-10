<?php

class ControllerProductCatalog extends Controller {
	public function index() {
		if ($this->journal3_opencart->is_oc4) {
			$this->load->language('extension/opencart/module/latest');
		} else {
			$this->load->language('extension/module/latest');
		}

		$text_latest = $this->language->get('heading_title');

		$this->load->language('product/category');
		$this->load->language('product/manufacturer');

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$this->load->model('journal3/product');
		$this->load->model('journal3/filter');

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = $this->journal3->get('productSort');
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = $this->journal3->get('productOrder');
		}

		if (isset($this->request->get['page'])) {
			$page = max((int)$this->request->get['page'], 1);
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit'])) {
			$limit = max((int)$this->request->get['limit'], 1);
		} else {
			$limit = $this->config->get('theme_journal3_product_limit');
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home'),
		);

		$this->document->setTitle($this->journal3->get('allProductsPageMetaTitle'));
		$this->document->setDescription($this->journal3->get('allProductsPageMetaDescription'));
		$this->document->setKeywords($this->journal3->get('allProductsPageMetaKeywords'));
		if (!empty($this->journal3->get('allProductsPageMetaRobots'))) {
			$this->journal3_document->addMeta('robots', $this->journal3->get('allProductsPageMetaRobots'));
		}

		$data['heading_title'] = $this->journal3->get('allProductsPageTitle');

		$data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

		// Set the last category breadcrumb
		$data['breadcrumbs'][] = array(
			'text' => $this->journal3->get('allProductsPageTitle'),
			'href' => $this->url->link('product/catalog'),
		);


		$data['compare'] = $this->url->link('product/compare');

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		$data['thumb'] = null;
		$data['description'] = null;
		$data['categories'] = array();
		$data['button_grid'] = null;
		$data['button_list'] = null;
		$data['text_sort'] = $this->language->get('text_sort');
		$data['text_limit'] = $this->language->get('text_limit');
		$data['text_tax'] = $this->language->get('text_tax');
		$data['button_cart'] = $this->language->get('button_cart');
		$data['button_wishlist'] = $this->language->get('button_wishlist');
		$data['button_compare'] = $this->language->get('button_compare');
		$data['text_empty'] = $this->language->get('text_empty');
		$data['button_continue'] = $this->language->get('button_continue');

		$data['products'] = array();

		$filter_data = array_merge($this->model_journal3_filter->getFilterData(), array(
			'filter_category_id' => 0,
			'sort'               => $sort,
			'order'              => $order,
			'start'              => ($page - 1) * $limit,
			'limit'              => $limit,
		));

		ControllerJournal3EventProducts::setFilterData($filter_data);

		$product_total = $this->model_journal3_filter->getTotalProducts($filter_data);

		$results = $this->model_journal3_filter->getProducts($filter_data);
		$results = $this->model_journal3_product->getProduct($results);

		foreach ($results as $result) {
			if ($result['image']) {
				$image = $this->journal3_image->resize($result['image'], $this->config->get('theme_journal3_image_product_width'), $this->config->get('theme_journal3_image_product_height'), $this->config->get('theme_journal3_image_product_resize'));
			} else {
				$image = $this->journal3_image->resize($this->journal3->get('placeholder'), $this->config->get('theme_journal3_image_product_width'), $this->config->get('theme_journal3_image_product_height'), $this->config->get('theme_journal3_image_product_resize'));
			}

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$price = false;
			}

			if ((float)$result['special']) {
				$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$special = false;
			}

			if ($this->config->get('config_tax')) {
				$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
			} else {
				$tax = false;
			}

			if ($this->config->get('config_review_status')) {
				$rating = (int)$result['rating'];
			} else {
				$rating = false;
			}

			$product_data = array(
				'product_id'  => $result['product_id'],
				'thumb'       => $image,
				'name'        => $result['name'],
				'description' => \Journal3\Utils\Str::utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, (int)$this->config->get('theme_journal3_product_description_length')) . '..',
				'price'       => $price,
				'special'     => $special,
				'tax'         => $tax,
				'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
				'rating'      => $result['rating'],
				'href'        => $this->url->link('product/product', '&product_id=' . $result['product_id'] . $url),
			);

			if ($this->journal3_opencart->is_oc4) {
				$data['products'][] = $this->load->controller('product/thumb', $product_data);
			} else {
				$data['products'][] = $product_data;
			}
		}

		$url = '';

		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		$data['sorts'] = array();

		$data['sorts'][] = array(
			'text'  => $this->language->get('text_default'),
			'value' => 'p.sort_order-ASC',
			'href'  => $this->url->link('product/catalog', '&sort=p.sort_order&order=ASC' . $url),
		);

		$data['sorts'][] = array(
			'text'  => $text_latest,
			'value' => 'p.date_added-DESC',
			'href'  => $this->url->link('product/catalog', '&sort=p.date_added&order=DESC' . $url),
		);

		$data['sorts'][] = array(
			'text'  => $this->language->get('text_name_asc'),
			'value' => 'pd.name-ASC',
			'href'  => $this->url->link('product/catalog', '&sort=pd.name&order=ASC' . $url),
		);

		$data['sorts'][] = array(
			'text'  => $this->language->get('text_name_desc'),
			'value' => 'pd.name-DESC',
			'href'  => $this->url->link('product/catalog', '&sort=pd.name&order=DESC' . $url),
		);

		$data['sorts'][] = array(
			'text'  => $this->language->get('text_price_asc'),
			'value' => 'p.price-ASC',
			'href'  => $this->url->link('product/catalog', '&sort=p.price&order=ASC' . $url),
		);

		$data['sorts'][] = array(
			'text'  => $this->language->get('text_price_desc'),
			'value' => 'p.price-DESC',
			'href'  => $this->url->link('product/catalog', '&sort=p.price&order=DESC' . $url),
		);

		if ($this->config->get('config_review_status')) {
			$data['sorts'][] = array(
				'text'  => $this->language->get('text_rating_desc'),
				'value' => 'rating-DESC',
				'href'  => $this->url->link('product/catalog', '&sort=rating&order=DESC' . $url),
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_rating_asc'),
				'value' => 'rating-ASC',
				'href'  => $this->url->link('product/catalog', '&sort=rating&order=ASC' . $url),
			);
		}

		$data['sorts'][] = array(
			'text'  => $this->language->get('text_model_asc'),
			'value' => 'p.model-ASC',
			'href'  => $this->url->link('product/catalog', '&sort=p.model&order=ASC' . $url),
		);

		$data['sorts'][] = array(
			'text'  => $this->language->get('text_model_desc'),
			'value' => 'p.model-DESC',
			'href'  => $this->url->link('product/catalog', '&sort=p.model&order=DESC' . $url),
		);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$data['limits'] = array();

		$limits = array_unique(array($this->config->get('theme_journal3_product_limit'), 25, 50, 75, 100));

		sort($limits);

		foreach ($limits as $value) {
			$data['limits'][] = array(
				'text'  => $value,
				'value' => $value,
				'href'  => $this->url->link('product/catalog', $url . '&limit=' . $value),
			);
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		if ($this->journal3_opencart->is_oc4) {
			$data['pagination'] = $this->load->controller('common/pagination', [
				'total' => $product_total,
				'page'  => $page,
				'limit' => $limit,
				'url'   => $this->journal3_url->link('product/catalog', $url . '&page={page}'),
			]);
		} else {
			$pagination = new Pagination();
			$pagination->total = $product_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->journal3_url->link('product/catalog', $url . '&page={page}');

			$data['pagination'] = $pagination->render();
		}

		$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

		// http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
		if ($page == 1) {
			$this->document->addLink($this->url->link('product/catalog'), 'canonical');
		} else {
			$this->document->addLink($this->url->link('product/catalog', '&page=' . $page), 'canonical');
		}

		if ($page > 1) {
			$this->document->addLink($this->url->link('product/catalog', (($page - 2) ? '&page=' . ($page - 1) : '')), 'prev');
		}

		if ($limit && ceil($product_total / $limit) > $page) {
			$this->document->addLink($this->url->link('product/catalog', '&page=' . ($page + 1)), 'next');
		}

		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['limit'] = $limit;

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('product/category', $data));
	}
}

class_alias('ControllerProductCatalog', '\Opencart\Catalog\Controller\Product\Catalog');
