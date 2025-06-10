<?php

use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3ProductExtras extends Controller {

	public function index($args) {
		$cache = $this->journal3_cache->get('module.' . $args['module_type']);

		if ($cache === false) {
			$this->load->model('journal3/filter');
			$this->load->model('journal3/module');
			$this->load->model('journal3/product');

			$cache = array(
				'data'       => array(),
				'all'        => array(),
				'special'    => array(),
				'outofstock' => array(),
				'custom'     => array(),
			);

			$modules = $this->model_journal3_module->getByType($args['module_type']);

			foreach ($modules as $module_id => $module_data) {
				$parser = new Parser('module/' . $args['module_type'] . '/' . $args['module_type'], Arr::get($module_data, 'general'), null, array($module_id));

				if ($parser->getSetting('status') === false) {
					continue;
				}

				$cache['data'][$module_id]['module_id'] = $module_id;
				$cache['data'][$module_id]['php'] = $parser->getPhp();
				$cache['data'][$module_id]['js'] = $parser->getJs();
				$cache['data'][$module_id]['fonts'] = $parser->getFonts();
				$cache['data'][$module_id]['css'] = $parser->getCss();

				$custom_css = str_replace('%s', '.product-' . str_replace('product_', '', $args['module_type']) . '-' . $module_id, $parser->getSetting('customCss') ?? '');
				$cache['data'][$module_id]['css'] .= ' ' . $custom_css;
				$cache['data'][$module_id]['php']['classes'][] = $parser->getSetting('customClass');

				switch ($parser->getSetting('type')) {
					case 'special':
						$cache['special'][$module_id] = $module_id;
						break;

					case 'outofstock':
						$cache['outofstock'][$module_id] = $module_id;
						break;

					case 'custom':
						$preset = $parser->getSetting('filter.preset');
						$filter_data = $parser->getSetting('filter');
						$limit = $parser->getSetting('filter.limit');

						if ($preset === 'all') {
							$cache['all'][$module_id] = $module_id;
							break;
						}

						switch ($preset) {
							case 'most_viewed':
								$results = $this->model_journal3_product->getMostViewedProducts($limit);
								break;

							case 'custom':
								$results = $this->model_journal3_product->getProduct($parser->getSetting('filter.products'));
								break;

							default:
								$filter_data['ignore_stock'] = true;
								$results = $this->model_journal3_filter->getProducts($filter_data);
								break;
						}

						foreach ($results as $result) {
							if (!empty($result['product_id'])) {
								$cache['custom'][$result['product_id']][$module_id] = $module_id;
							}
						}

						break;
				}
			}

			$this->journal3_cache->set('module.' . $args['module_type'], $cache);
		}

		foreach ($cache['data'] as $data) {
			if ($data['css']) {
				$this->journal3_document->addCss($data['css'], "{$args['module_type']}-{$data['module_id']}");
			}

			if ($data['fonts']) {
				$this->journal3_document->addFonts($data['fonts']);
			}
		}

		return $cache;
	}

	public function blocks($product) {
		$results = array();

		if (!$product) {
			return $results;
		}

		$extras = $this->index([
			'module_type' => 'product_blocks',
		]);

		$product_block_ids = Arr::get($extras, 'all', array());

		foreach ($product_block_ids as $product_block_id) {
			$results[$product_block_id] = Arr::get($extras, 'data.' . $product_block_id . '.php');
		}

		$product_block_ids = Arr::get($extras, 'custom.' . $product['product_id'], array());

		foreach ($product_block_ids as $product_block_id) {
			$results[$product_block_id] = Arr::get($extras, 'data.' . $product_block_id . '.php');
		}

		if ($product['special']) {
			$product_block_ids = Arr::get($extras, 'special', array());

			foreach ($product_block_ids as $product_block_id) {
				$results[$product_block_id] = Arr::get($extras, 'data.' . $product_block_id . '.php');
			}
		}

		if ($product['quantity'] <= 0) {
			$product_block_ids = Arr::get($extras, 'outofstock', array());

			foreach ($product_block_ids as $product_block_id) {
				$results[$product_block_id] = Arr::get($extras, 'data.' . $product_block_id . '.php');
			}
		}

		return $results;
	}

	public function tabs($product) {
		$results = array();

		if (!$product) {
			return $results;
		}

		$extras = $this->index([
			'module_type' => 'product_tabs',
		]);

		$product_tab_ids = Arr::get($extras, 'all', array());

		foreach ($product_tab_ids as $product_tab_id) {
			$results[$product_tab_id] = Arr::get($extras, 'data.' . $product_tab_id . '.php');
		}

		$product_tab_ids = Arr::get($extras, 'custom.' . $product['product_id'], array());

		foreach ($product_tab_ids as $product_tab_id) {
			$results[$product_tab_id] = Arr::get($extras, 'data.' . $product_tab_id . '.php');
		}

		if ($product['special']) {
			$product_tab_ids = Arr::get($extras, 'special', array());

			foreach ($product_tab_ids as $product_tab_id) {
				$results[$product_tab_id] = Arr::get($extras, 'data.' . $product_tab_id . '.php');
			}
		}

		if ($product['quantity'] <= 0) {
			$product_tab_ids = Arr::get($extras, 'outofstock', array());

			foreach ($product_tab_ids as $product_tab_id) {
				$results[$product_tab_id] = Arr::get($extras, 'data.' . $product_tab_id . '.php');
			}
		}

		return $results;
	}

}

class_alias('ControllerJournal3ProductExtras', '\Opencart\Catalog\Controller\Journal3\ProductExtras');
