<?php

namespace Journal3;

use Journal3\Utils\Arr;

/**
 * Class ProductExtras is used to generate product extras
 * It's used instead an Opencart model for better performance
 *
 * @package Journal3
 */
class ProductExtras extends Base {

	/**
	 * @var
	 */
	private $extras;

	/**
	 * @param $extras
	 */
	public function setExtras($extras) {
		$this->extras = $extras;
	}

	/**
	 * @param $product
	 * @return int[]|string[]
	 */
	public function exclude_button($product) {
		$results = array();

		$extra_button_ids = Arr::get($this->extras, 'product_exclude_button.all', array());

		foreach ($extra_button_ids as $extra_button_id) {
			$data = Arr::get($this->extras, 'product_exclude_button.data.' . $extra_button_id . '.php');

			if ($data) {
				if ($data['excludeCart']) {
					$results['hide-cart'] = true;
				}

				if ($data['excludeWishlist']) {
					$results['hide-wishlist'] = true;
				}

				if ($data['excludeCompare']) {
					$results['hide-compare'] = true;
				}
			}
		}

		$extra_button_ids = Arr::get($this->extras, 'product_exclude_button.custom.' . $product['product_id'], array());

		foreach ($extra_button_ids as $extra_button_id) {
			$data = Arr::get($this->extras, 'product_exclude_button.data.' . $extra_button_id . '.php');

			if ($data) {
				if ($data['excludeCart']) {
					$results['hide-cart'] = true;
				}

				if ($data['excludeWishlist']) {
					$results['hide-wishlist'] = true;
				}

				if ($data['excludeCompare']) {
					$results['hide-compare'] = true;
				}
			}
		}

		if ($product['special']) {
			$extra_button_ids = Arr::get($this->extras, 'product_exclude_button.special', array());

			foreach ($extra_button_ids as $extra_button_id) {
				$data = Arr::get($this->extras, 'product_exclude_button.data.' . $extra_button_id . '.php');

				if ($data) {
					if ($data['excludeCart']) {
						$results['hide-cart'] = true;
					}

					if ($data['excludeWishlist']) {
						$results['hide-wishlist'] = true;
					}

					if ($data['excludeCompare']) {
						$results['hide-compare'] = true;
					}
				}
			}
		}

		if ($product['quantity'] <= 0) {
			$extra_button_ids = Arr::get($this->extras, 'product_exclude_button.outofstock', array());

			foreach ($extra_button_ids as $extra_button_id) {
				$data = Arr::get($this->extras, 'product_exclude_button.data.' . $extra_button_id . '.php');

				if ($data) {
					if ($data['excludeCart']) {
						$results['hide-cart'] = true;
					}

					if ($data['excludeWishlist']) {
						$results['hide-wishlist'] = true;
					}

					if ($data['excludeCompare']) {
						$results['hide-compare'] = true;
					}
				}
			}
		}

		return array_keys($results);
	}

	/**
	 * @param $product
	 * @return array
	 */
	public function labels($product) {
		$results = array();

		$label_ids = Arr::get($this->extras['product_label'], 'all', array());

		foreach ($label_ids as $label_id) {
			$results[$label_id] = Arr::get($this->extras['product_label'], 'data.' . $label_id . '.php');
		}

		$label_ids = Arr::get($this->extras['product_label'], 'custom.' . $product['product_id'], array());

		foreach ($label_ids as $label_id) {
			$results[$label_id] = Arr::get($this->extras['product_label'], 'data.' . $label_id . '.php');
		}

		if ($product['special']) {
			$label_ids = Arr::get($this->extras['product_label'], 'special', array());

			foreach ($label_ids as $label_id) {
				$label = Arr::get($this->extras['product_label'], 'data.' . $label_id . '.php');

				if ((float)$product['price']) {
					$label['label'] = '-' . round(($product['price'] - $product['special']) / $product['price'] * 100) . '%';
				} else {
					$label['label'] = '-100' . '%';
				}

				$results[$label_id] = $label;
			}
		}

		if ($product['quantity'] <= 0) {
			$label_ids = Arr::get($this->extras['product_label'], 'outofstock', array());

			foreach ($label_ids as $label_id) {
				$label = Arr::get($this->extras['product_label'], 'data.' . $label_id . '.php');

				$label['label'] = $product['stock_status'] ?? '';

				$results[$label_id] = $label;
			}
		}

		$labels = [];

		foreach ($results as $label_id => $label) {
			if ($label['display'] === 'default' && ($label['positionDefault'] === 'group_outside' || $label['positionDefault'] === 'price')) {
				$labels[$label['positionDefault']][$label_id] = $label;
			} else {
				$labels['default'][$label_id] = $label;
			}
		}

		return $labels;
	}

	/**
	 * @param $product
	 * @return array
	 */
	public function extra_buttons($product) {
		$results = array();

		$extra_button_ids = Arr::get($this->extras, 'product_extra_button.all', array());

		foreach ($extra_button_ids as $extra_button_id) {
			$results[$extra_button_id] = Arr::get($this->extras, 'product_extra_button.data.' . $extra_button_id . '.php');
		}

		$extra_button_ids = Arr::get($this->extras, 'product_extra_button.custom.' . $product['product_id'], array());

		foreach ($extra_button_ids as $extra_button_id) {
			$results[$extra_button_id] = Arr::get($this->extras, 'product_extra_button.data.' . $extra_button_id . '.php');
		}

		if ($product['special']) {
			$extra_button_ids = Arr::get($this->extras, 'product_extra_button.special', array());

			foreach ($extra_button_ids as $extra_button_id) {
				$results[$extra_button_id] = Arr::get($this->extras, 'product_extra_button.data.' . $extra_button_id . '.php');
			}
		}

		if ($product['quantity'] <= 0) {
			$extra_button_ids = Arr::get($this->extras, 'product_extra_button.outofstock', array());

			foreach ($extra_button_ids as $extra_button_id) {
				$results[$extra_button_id] = Arr::get($this->extras, 'product_extra_button.data.' . $extra_button_id . '.php');
			}
		}

		return array_slice($results, 0, 2, true);
	}

	/**
	 * @param $product
	 * @param $stat
	 * @return array|null
	 */
	public function stat($product, $stat) {
		$label = null;
		$text = null;

		switch ($stat) {
			case 'brand':
				if (!$product['manufacturer']) {
					return null;
				}

				$label = $this->journal3->get('productPageStyleProductManufacturerText');
				$text = sprintf('<a href="%s">%s</a>', $this->journal3_url->link('product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info', 'manufacturer_id=' . $product['manufacturer_id']), $product['manufacturer']);
				break;

			case 'model':
				$label = $this->journal3->get('productPageStyleProductModelText');
				$text = $product['model'];
				break;

			case 'sku':
				$label = $this->journal3->get('productPageStyleProductSKUText');
				$text = $product['sku'];
				break;

			case 'upc':
				$label = $this->journal3->get('productPageStyleProductUPCText');
				$text = $product['upc'];
				break;

			case 'ean':
				$label = $this->journal3->get('productPageStyleProductEANText');
				$text = $product['ean'];
				break;

			case 'jan':
				$label = $this->journal3->get('productPageStyleProductJANText');
				$text = $product['jan'];
				break;

			case 'isbn':
				$label = $this->journal3->get('productPageStyleProductISBNText');
				$text = $product['isbn'];
				break;

			case 'mpn':
				$label = $this->journal3->get('productPageStyleProductMPNText');
				$text = $product['mpn'];
				break;

			case 'location':
				$label = $this->journal3->get('productPageStyleProductLocationText');
				$text = $product['location'];
				break;

			case 'weight':
				$label = $this->journal3->get('productPageStyleProductWeightText');
				$text = $this->weight->format($product['weight'], $product['weight_class_id'], $this->language->get('decimal_point'), $this->language->get('thousand_point'));
				break;

			case 'dimension':
				$length = $this->length->format($product['length'], $product['length_class_id']);
				$width = $this->length->format($product['width'], $product['length_class_id']);
				$height = $this->length->format($product['height'], $product['length_class_id']);

				$label = $this->journal3->get('productPageStyleProductDimensionText');
				$text = "{$length} x {$width} x {$height}";
				break;

			case 'reward':
				$label = $this->journal3->get('productPageStyleProductRewardText');
				$text = $product['reward'];
				break;

			case 'points':
				$label = $this->journal3->get('productPageStyleProductPointsText');
				$text = $product['points'];
				break;

			case 'stock':
				$label = $this->journal3->get('productPageStyleProductStockText');

				if ($product['quantity'] > 0) {
					if (!empty($product['in_stock_status'])) {
						$text = $product['in_stock_status'];
					} else {
						$text = $this->journal3->get('productPageStyleProductInStockText');
					}
				} else {
					$text = $product['stock_status'];
				}
				break;

			case 'quantity':
				$label = $this->journal3->get('productPageStyleProductStockText');
				$text = $product['quantity'];
				break;

		}

		if ($label && $text) {
			return [
				'label' => $label,
				'text'  => $text,
			];
		}

		return null;
	}

}
