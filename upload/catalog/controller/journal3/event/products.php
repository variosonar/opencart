<?php

use Journal3\Opencart\EventResult;
use KubAT\PhpSimple\HtmlDomParser;

class ControllerJournal3EventProducts extends Controller {

	/**
	 *
	 * Used to store current filter data (parameters) used to filter products by
	 *
	 * @var mixed
	 */
	private static $filter_data;

	public function controller_products_before(&$route, &$args) {
		$this->load->model('journal3/filter');

		// load needed assets
		$this->document->addScript('catalog/view/theme/journal3/lib/ias/jquery-ias.min.js', 'js-defer');
		$this->document->addScript('catalog/view/theme/journal3/js/products.js', 'js-defer');

		// register filter events, we use our own journal3/filter model to display products
		$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'getProducts/after', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_product_getProducts_after'));
		$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'getTotalProducts/after', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_product_getTotalProducts_after'));
		$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'getProductSpecials/after', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_product_getProductSpecials_after'));
		$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'getSpecials/after', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_product_getProductSpecials_after'));
		$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'getTotalProductSpecials/after', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_product_getTotalProductSpecials_after'));
		$this->event->register('model/catalog/product' . JOURNAL3_EVENT_SEPARATOR . 'getTotalSpecials/after', new Action('journal3/event/products' . JOURNAL3_ROUTE_SEPARATOR . 'model_catalog_product_getTotalProductSpecials_after'));
	}

	public function model_catalog_product_getProducts_after(&$route, &$args, &$output) {
		if (isset($args[0]['start']) && isset($args[0]['limit'])) {
			$this->load->model('journal3/product');

			self::$filter_data = $this->model_journal3_filter->getFilterData();

			if (!empty($args[0])) {
				foreach ($args[0] as $key => $value) {
					if (!isset(self::$filter_data[$key])) {
						self::$filter_data[$key] = $value;
					}
				}
			}

			$results = $this->model_journal3_filter->getProducts(self::$filter_data);
			$products = $this->model_journal3_product->getProduct($results);

			$output = $products;
		}
	}

	public function model_catalog_product_getTotalProducts_after(&$route, &$args, &$output) {
		if (isset($args[0]['start']) && isset($args[0]['limit'])) {
			self::$filter_data = $this->model_journal3_filter->getFilterData();

			if (!empty($args[0])) {
				foreach ($args[0] as $key => $value) {
					if (!isset(self::$filter_data[$key])) {
						self::$filter_data[$key] = $value;
					}
				}
			}

			$products = $this->model_journal3_filter->getTotalProducts(self::$filter_data);

			$output = $products;
		}
	}

	public function model_catalog_product_getProductSpecials_after(&$route, &$args, &$output) {
		if (isset($args[0]['start']) && isset($args[0]['limit'])) {
			$this->load->model('journal3/product');

			self::$filter_data = $this->model_journal3_filter->getFilterData();

			if (!empty($args[0])) {
				foreach ($args[0] as $key => $value) {
					if (!isset(self::$filter_data[$key])) {
						self::$filter_data[$key] = $value;
					}
				}
			}

			$results = $this->model_journal3_filter->getProducts(self::$filter_data);
			$products = $this->model_journal3_product->getProduct($results);

			$output = $products;
		}
	}

	public function model_catalog_product_getTotalProductSpecials_after(&$route, &$args, &$output) {
		if (true) {
			self::$filter_data = $this->model_journal3_filter->getFilterData();

			if (!empty($args[0])) {
				foreach ($args[0] as $key => $value) {
					if (!isset(self::$filter_data[$key])) {
						self::$filter_data[$key] = $value;
					}
				}
			}

			$products = $this->model_journal3_filter->getTotalProducts(self::$filter_data);

			$output = $products;
		}
	}

	private function product($product, $product_info) {
		$data['text_tax'] = $this->language->get('text_tax');

		$data['button_cart'] = $this->language->get('button_cart');
		$data['button_wishlist'] = $this->language->get('button_wishlist');
		$data['button_compare'] = $this->language->get('button_compare');

		$data['display'] = $this->journal3->get('globalProductView');

		$data['image_width'] = $this->journal3->get('image_dimensions_product.width');
		$data['image_height'] = $this->journal3->get('image_dimensions_product.height');
		$data['image_resize'] = $this->journal3->get('image_dimensions_product.resize');

		$data['dummy_image'] = $this->journal3_image->transparent($data['image_width'], $data['image_height']);

		$result = $product_info[$product['product_id']];

		if ($result['image']) {
			$image = $this->journal3_image->resize($result['image'], $data['image_width'], $data['image_height'], $data['image_resize']);
			$image2x = $this->journal3_image->resize($result['image'], $data['image_width'] * 2, $data['image_height'] * 2, $data['image_resize']);
		} else {
			$image = $this->journal3_image->resize($this->journal3->get('placeholder'), $data['image_width'], $data['image_height'], $data['image_resize']);
			$image2x = $this->journal3_image->resize($this->journal3->get('placeholder'), $data['image_width'] * 2, $data['image_height'] * 2, $data['image_resize']);
		}

		if ($result['second_image'] && $this->journal3->is_desktop && $this->journal3->get('globalProductGridSecondImageStatus')) {
			$second_image = $this->journal3_image->resize($result['second_image'], $data['image_width'], $data['image_height'], $data['image_resize']);
			$second_image2x = $this->journal3_image->resize($result['second_image'], $data['image_width'] * 2, $data['image_height'] * 2, $data['image_resize']);
		} else {
			$second_image = false;
			$second_image2x = false;
		}

		$classes = $this->journal3_product_extras->exclude_button($result);
		$labels = $this->journal3_product_extras->labels($result);
		$extra_buttons = $this->journal3_product_extras->extra_buttons($result);

		$classes['out-of-stock'] = $result['quantity'] <= 0;
		$classes['has-zero-price'] = ($result['special'] ?: $result['price']) <= 0;
		$classes['has-countdown'] = (bool)$result['special_date_end'];
		$classes['has-special'] = (bool)$result['special'];
		$classes['has-extra-button'] = (bool)$extra_buttons;

		$data['product'] = array_merge($product, array(
			'thumb'          => $image,
			'thumb2x'        => $image2x,
			'second_thumb'   => $second_image,
			'second_thumb2x' => $second_image2x,
			'classes'        => $classes,
			'quantity'       => $result['quantity'],
			'stock_status'   => $result['stock_status'],
			'labels'         => $labels,
			'extra_buttons'  => $extra_buttons,
			'date_end'       => $result['special_date_end'],
			'price_value'    => ($result['special'] ? $result['special'] > 0 : $result['price'] > 0),
			'stat1'          => $this->journal3_product_extras->stat($result, $this->journal3->get('globalProductStat1')),
			'stat2'          => $this->journal3_product_extras->stat($result, $this->journal3->get('globalProductStat2')),
			'qid'            => uniqid('q-'),
			'button_cart'    => $this->journal3->get('filterAddToCartStock') && $result['quantity'] <= 0 ? $result['stock_status'] : $this->language->get('button_cart'),
		));

		return $data;
	}

	public function view_products_before(&$route, &$args) {
		// sort order and limit
		$args['journal3_filter_limit'] = $this->request->get['limit'] ?? '';
		$args['journal3_filter_sort'] = $this->request->get['sort'] ?? '';
		$args['journal3_filter_order'] = $this->request->get['order'] ?? '';

		// text compare
		$args['journal3_text_compare'] = $this->journal3->countBadge(str_replace('(%s)', '', $this->language->get('text_compare')), count($this->session->data['compare'] ?? []));

		// products
		$args['journal3_products_count'] = count($args['products']);

		if (!empty($args['products'])) {
			if (!$this->journal3_opencart->is_oc4) {
				$this->load->model('journal3/product');

				$product_info = $this->model_journal3_product->getProduct($args['products']);

				$products = [];

				foreach ($args['products'] as $product) {
					$products[] = $this->load->view('journal3/products', $this->product($product, $product_info));
				}

				$args['products'] = implode('', $products);
			} else {
				$args['products'] = implode(' ', $args['products']);
			}
		}

		// filter query
		$filter_query = self::$filter_data ? $this->model_journal3_filter->getFilterParams(self::$filter_data) : null;

		// add filter params to sorts urls
		if ($filter_query) {
			foreach ($args['sorts'] as &$sort) {
				$url = Sabre\Uri\parse($sort['href']);
				$url['query'] = $url['query'] . '&amp;' . $filter_query;
				$sort['href'] = Sabre\Uri\build($url);
			}

			foreach ($args['limits'] as &$limit) {
				$url = Sabre\Uri\parse($limit['href']);
				$url['query'] = $url['query'] . '&amp;' . $filter_query;
				$limit['href'] = Sabre\Uri\build($url);
			}
		}

		// add filter params
		if (!empty($args['pagination'])) {
			$dom = HtmlDomParser::str_get_html($args['pagination']);

			foreach ($dom->find('li a') as $a) {
				$url = Sabre\Uri\parse($a->href);
				if ($filter_query) {
					$url['query'] = $url['query'] . '&amp;' . $filter_query;
				}
				$a->href = Sabre\Uri\build($url);
			}

			$args['pagination'] = (string)$dom;
		}
	}

	public function view_product_thumb_after(&$route, &$args, &$output) {
		$this->load->model('journal3/product');

		$product = $args;
		$product_info = $this->model_journal3_product->getProduct($args['product_id']);

		$output = $this->load->view('journal3/products', $this->product($product, $product_info));
	}

	public static function getFilterData() {
		return static::$filter_data;
	}

	public static function setFilterData($filter_data) {
		static::$filter_data = $filter_data;
	}

}

class_alias('ControllerJournal3EventProducts', '\Opencart\Catalog\Controller\Journal3\Event\Products');
