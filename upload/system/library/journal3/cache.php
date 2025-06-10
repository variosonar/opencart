<?php

namespace Journal3;

/**
 * Class Cache is used to cache various elements for better performance
 *
 * It's similar to Opencart Cache class, but instead of json_encode / json_decode we use php var_export / include
 * which gives better performance
 *
 * Special thanks to https://github.com/shabeer-ali-m/php-super-cache
 *
 * @package Journal3
 */
class Cache extends Base {

	/**
	 * @var
	 */
	public $key;

	/**
	 * @var
	 */
	public $dynamic_key;

	/**
	 * @var
	 */
	public $cart_count;

	/**
	 * @var
	 */
	public $wishlist_count;

	/**
	 * @var
	 */
	public $compare_count;

	/**
	 * @var
	 */
	public $customer_firstname;

	/**
	 * @var
	 */
	public $customer_lastname;

	/**
	 * @var
	 */
	public $customer_token;

	/**
	 * @param $key
	 * @param $val
	 * @param bool $dynamic
	 */
	public function set($key, $val, $dynamic = true) {
		if (JOURNAL3_CACHE) {
			$key = $this->key($key, $dynamic);

			$val = var_export($val, true);

			// HHVM fails at __set_state, so just use object cast for now
			$val = str_replace('stdClass::__set_state', '(object)', $val);

			// Write to temp file first to ensure atomicity
			$tmp = DIR_CACHE . "$key." . uniqid('', true) . '.tmp';

			file_put_contents($tmp, '<?php $val = ' . $val . ';', LOCK_EX);
			rename($tmp, DIR_CACHE . $key);
		}
	}

	/**
	 * @param $key
	 * @param bool $dynamic
	 * @return false|mixed
	 */
	public function get($key, $dynamic = true) {
		if (JOURNAL3_CACHE) {
			$start = microtime(true);

			$key = $this->key($key, $dynamic);

			if (is_file(DIR_CACHE . "$key")) {
				@include DIR_CACHE . "$key";
			}

			$end = microtime(true);

			if (function_exists('clock')) {
				clock()->addCacheQuery('read', $key, isset($val) ? 'HIT' : false, ($end - $start) * 1000, null);
			}

			return isset($val) ? $val : false;
		}

		return false;
	}

	/**
	 * @param null $key
	 */
	public function delete($key = null) {
		if ($key === null) {
			$files = glob(DIR_CACHE . 'journal3.*');
		} else {
			$files = glob(DIR_CACHE . 'journal3.' . $key . '.*');
		}

		if ($files) {
			foreach ($files as $file) {
				if (is_file($file)) {
					@unlink($file);
				}
			}
		}
	}

	/**
	 * @param $data
	 * @return array|string|string[]
	 */
	public function update($data) {
		// update count badge
		$new_data = str_replace([
			'{{ $cart }}',
			'{{ $wishlist }}',
			'{{ $compare }}',
			'{{ $customer_firstname }}',
			'{{ $customer_lastname }}',
			'__customer_token__',
		], [
			$this->cart_count,
			$this->wishlist_count,
			$this->compare_count,
			$this->customer_firstname,
			$this->customer_lastname,
			$this->customer_token,
		], $data);

		if ($data !== $new_data) {
			if (!$this->cart_count) {
				$new_data = str_replace('count-badge cart-badge', 'count-badge cart-badge count-zero', $new_data);
			}

			if (!$this->wishlist_count) {
				$new_data = str_replace('count-badge wishlist-badge', 'count-badge wishlist-badge count-zero', $new_data);
			}

			if (!$this->compare_count) {
				$new_data = str_replace('count-badge compare-badge', 'count-badge compare-badge count-zero', $new_data);
			}
		}

		return $new_data;
	}

	/**
	 * @param $key
	 * @param $dynamic
	 * @return string
	 */
	private function key($key, $dynamic) {
		if ($this->key === null) {
			$keys = [
				"v" . JOURNAL3_VERSION . "-" . JOURNAL3_BUILD,
				"h" . substr(md5($this->journal3_request->host), 0, 10),
			];

			$this->key = implode(".", $keys);

			$dynamic_keys = [
				"s" . (int)$this->journal3_opencart->store_id,
				"l" . (int)$this->journal3_opencart->language_id,
				"c" . (int)$this->journal3_opencart->currency_id,
				"c" . (int)$this->journal3_opencart->is_customer,
				"g" . (int)$this->journal3_opencart->customer_group_id,
				"a" . (int)$this->journal3_opencart->is_admin,
				"w" . (int)$this->journal3_request->is_webp,
				$this->journal3->device,
				"v" . JOURNAL3_VERSION . "-" . JOURNAL3_BUILD,
				"h" . substr(md5($this->journal3_request->host), 0, 10),
			];

			$this->dynamic_key = implode(".", $dynamic_keys);
		}

		// Cache key
		if ($dynamic) {
			$key = 'journal3.' . $key . '.' . $this->dynamic_key;
		} else {
			$key = 'journal3.' . $key . '.' . $this->key;
		}

		return $key . '.cache';
	}

}
