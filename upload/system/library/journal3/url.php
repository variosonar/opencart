<?php

namespace Journal3;

use Journal3\Utils\Arr;
use Journal3\Utils\Str;

/**
 * Class Url
 *
 * It's similar to Opencart Url class but with additional methods
 *
 * @package Journal3
 */
class Url extends Base {

	/**
	 * @var
	 */
	private static $keywords;

	/**
	 * @var
	 */
	private static $links;

	/**
	 * @var mixed|null
	 */
	private $config_seo_url;

	/**
	 * @var mixed|null
	 */
	private $config_ssl;

	/**
	 * @var mixed|null
	 */
	private $config_url;

	private $customer_token;

	/**
	 * Url constructor.
	 * @param $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->config_seo_url = $this->config->get('config_seo_url');
		$this->config_ssl = $this->config->get('config_ssl');
		$this->config_url = $this->config->get('config_url');
		$this->customer_token = $this->journal3_opencart->is_oc4 && !$this->journal3_opencart->is_admin ? $this->session->data['customer_token'] ?? '' : '';
	}

	/**
	 * @param $route
	 * @param string $args
	 * @param false $secure
	 * @return mixed|string
	 */
	public function link($route, $args = '', $secure = false) {
		if ($this->registry->get('journal3_opencart')->is_oc4) {
			if ($args) {
				$args = 'language=' . $this->config->get('config_language') . '&' . $args;
				$args = str_replace('&&', '&', $args);
			} else {
				$args = 'language=' . $this->config->get('config_language');
			}

			return $this->url->link($route, $args);
		}

		if (JOURNAL3_SEO_URL_ENGINE === 'none') {
			return $this->url->link($route, $args, $secure);
		}

		if (JOURNAL3_SEO_URL_ENGINE === 'blog' && !Str::startsWith($route, 'journal3/blog')) {
			return $this->url->link($route, $args, $secure);
		}

		if ($this->config_ssl && $secure) {
			$link = $this->config_ssl . 'index.php?route=' . $route;
		} else {
			$link = $this->config_url . 'index.php?route=' . $route;
		}

		if ($args) {
			if (is_array($args)) {
				$link .= '&amp;' . http_build_query($args);
			} else {
				$link .= str_replace('&', '&amp;', '&' . ltrim($args, '&'));
			}
		}

		if (!$this->config_seo_url) {
			return $link;
		}

		if ((static::$keywords === null) && (JOURNAL3_SEO_URL_ENGINE === 'all')) {
			if (function_exists('clock')) {
				clock()->event('SEO Url')->name('seo_url')->begin();
			}

			if ($this->registry->get('journal3_opencart')->is_oc2) {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "url_alias");
			} else {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");
			}

			static::$keywords = [];

			foreach ($query->rows as $row) {
				if ($this->registry->get('journal3_opencart')->is_oc4) {
					static::$keywords[$row['key']][$row['value']] = $row['keyword'];
				} else {
					$query = explode('=', $row['query']);

					if (isset($query[1]) && $row['keyword']) {
						static::$keywords[$query[0]][$query[1]] = $row['keyword'];
					} else {
						static::$keywords['route'][$row['query']] = $row['keyword'];
					}
				}
			}

			if (function_exists('clock')) {
				clock()->event('seo_url')->end();
			}
		}

		if (!isset(static::$links[$link])) {
			$url_info = parse_url(str_replace('&amp;', '&', $link));

			$url = '';

			$data = [];

			parse_str($url_info['query'], $data);

			$is_blog_url = false;

			foreach ($data as $key => $value) {
				if (isset($data['route'])) {
					if (($data['route'] == 'product/product' && $key == 'product_id') || (($data['route'] == 'product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info' || $data['route'] == 'product/product') && $key == 'manufacturer_id') || ($data['route'] == 'information/information' && $key == 'information_id')) {
						$keyword = static::$keywords[$key][$value] ?? '';

						if ($keyword) {
							$url .= '/' . $keyword;

							unset($data[$key]);
						}
					} elseif ($key == 'path') {
						$categories = explode('_', $value);

						foreach ($categories as $category) {
							$keyword = static::$keywords['category_id'][$category] ?? '';

							if ($keyword) {
								$url .= '/' . $keyword;
							} else {
								$url = '';

								break;
							}
						}

						unset($data[$key]);
					} else if ($key == 'journal_blog_post_id') {
						$is_blog_url = true;

						if ($journal_blog_keyword = $this->model_journal3_blog->rewritePost($value)) {
							$url .= '/' . $journal_blog_keyword;
							unset($data[$key]);
						}
					} elseif ($key == 'journal_blog_category_id') {
						$is_blog_url = true;

						if ($journal_blog_keyword = $this->model_journal3_blog->rewriteCategory($value)) {
							$url .= '/' . $journal_blog_keyword;
							unset($data[$key]);
						}
					} elseif ($key === 'route') {
						if ($value === 'common/home') {
							$url .= '/';
						} else if ($value === 'journal3/blog') {
							$is_blog_url = true;
						} else if (!empty(static::$keywords['route'][$value])) {
							$url .= '/' . static::$keywords['route'][$value];
						}
					}
				}
			}

			if ($is_blog_url && $this->model_journal3_blog->getBlogKeyword()) {
				$url = '/' . $this->model_journal3_blog->getBlogKeyword() . $url;
			}

			if ($url) {
				unset($data['route']);

				$query = '';

				if ($data) {
					foreach ($data as $key => $value) {
						$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
					}

					if ($query) {
						$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
					}
				}

				static::$links[$link] = $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
			} else {
				static::$links[$link] = $link;
			}
		}

		return static::$links[$link];
	}

	/**
	 * @param $route
	 * @param string $args
	 * @return string
	 */
	public function admin_link($route, $args = '') {
		if ($this->journal3_opencart->is_oc2) {
			$token = 'token=' . $this->journal3_request->session('token');
		} else {
			$token = 'user_token=' . $this->journal3_request->session('user_token');
		}

		return $this->url->link($route, $token . $args, true);
	}

	/**
	 * @param $link
	 * @return array
	 * @throws \Exception
	 */
	public function getLink($link) {
		$result = [
			'type'    => Arr::get($link, 'type'),
			'id'      => Arr::get($link, 'id'),
			'href'    => '',
			'name'    => '',
			'total'   => null,
			'attrs'   => [],
			'classes' => [],
		];

		if (Arr::get($link, 'target') === 'true') {
			$result['attrs']['target'] = 'target="_blank"';
		}

		if ($rel = Arr::get($link, 'rel')) {
			$result['attrs'][$rel] = 'rel="' . $rel . '"';
		}

		switch ($result['type']) {
			case 'none':
				$result['href'] = '';
				break;

			case 'custom':
				$result['href'] = Arr::get($link, 'url');
				break;

			case 'page':
				$page = Arr::get($link, 'page');

				if ($page) {
					switch (true) {
						case $this->journal3_opencart->is_oc2:
							switch ($page) {
								case 'account/return/insert';
									$page = 'account/return/add';
									break;
							}
							break;

						case $this->journal3_opencart->is_oc3;
							switch ($page) {
								case 'affiliate/account';
									$page = 'affiliate/login';
									break;
								case 'account/return/insert';
									$page = 'account/return/add';
							}
							break;

						case $this->journal3_opencart->is_oc4;
							switch ($page) {
								case 'affiliate/account':
									$page = 'account/affiliate';
									break;
								case 'account/voucher';
									$page = 'checkout/voucher';
									break;
								case 'account/return';
									$page = 'account/returns';
									break;
								case 'account/return/insert';
									$page = 'account/returns' . JOURNAL3_ROUTE_SEPARATOR . 'add';
									break;
							}
					}

					if ($this->journal3_opencart->is_oc4 && Str::startsWith($page, 'account/') && $this->journal3_opencart->is_customer) {
						$result['href'] = $this->link($page, 'customer_token=__customer_token__', $this->journal3_request->is_https);
					} else {
						$result['href'] = $this->link($page, '', $this->journal3_request->is_https);
					}

					switch ($page) {
						case 'common/home':
							$result['href'] = str_replace('index.php?route=common/home', '', $result['href']);
							break;

						case 'checkout/cart':
							$result['classes'][] = 'cart-badge';
							$result['total'] = '{{ $cart }}';
							break;

						case 'account/wishlist':
							$result['classes'][] = 'wishlist-badge';
							$result['total'] = '{{ $wishlist }}';
							break;

						case 'product/compare':
							$result['classes'][] = 'compare-badge';
							$result['total'] = '{{ $compare }}';
							break;

						case 'product/catalog_latest':
							$result['href'] = $this->link('product/catalog', 'sort=p.date_added&order=DESC', $this->journal3_request->is_https);
							break;
					}
				}

				break;

			case 'category':
				$this->load->model('catalog/category');

				$category_info = $this->model_catalog_category->getCategory((int)$result['id']);

				if ($category_info) {
					$result['name'] = $category_info['name'];
					$result['href'] = $this->link('product/category', 'path=' . $result['id'], $this->journal3_request->is_https);
				}

				break;

			case 'product':
				$this->load->model('catalog/product');

				$product_info = $this->model_catalog_product->getProduct((int)$result['id']);

				if ($product_info) {
					$result['name'] = $product_info['name'];
					$result['href'] = $this->link('product/product', 'product_id=' . $result['id'], $this->journal3_request->is_https);
				}

				break;

			case 'manufacturer':
				$this->load->model('catalog/manufacturer');

				$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer((int)$result['id']);

				if ($manufacturer_info) {
					$result['name'] = $manufacturer_info['name'];
					$result['href'] = $this->link('product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info', 'manufacturer_id=' . $result['id'], $this->journal3_request->is_https);
				}

				break;

			case 'information':
				$this->load->model('catalog/information');

				$information_info = $this->model_catalog_information->getInformation((int)$result['id']);

				if ($information_info) {
					$result['name'] = $information_info['title'];
					$result['href'] = $this->link('information/information', 'information_id=' . $result['id'], $this->journal3_request->is_https);
				}

				break;

			case 'blog_home':
				$result['href'] = $this->link('journal3/blog', '', $this->journal3_request->is_https);
				break;

			case 'blog_post':
				$result['href'] = $this->link('journal3/blog' . JOURNAL3_ROUTE_SEPARATOR . 'post', 'journal_blog_post_id=' . $result['id'], $this->journal3_request->is_https);
				break;

			case 'blog_category':
				$result['href'] = $this->link('journal3/blog', 'journal_blog_category_id=' . $result['id'], $this->journal3_request->is_https);
				break;

			case 'popup':
				$result['href'] = 'javascript:open_popup(' . (int)$result['id'] . ')';
				unset($result['attrs']['target']);
				break;

			case 'login_popup':
				$result['href'] = 'javascript:open_login_popup()';
				unset($result['attrs']['target']);
				break;

			case 'register_popup':
				$result['href'] = 'javascript:open_register_popup()';
				unset($result['attrs']['target']);
				break;

			case 'scroll':
				$result['href'] = 'javascript:$(\'html, body\').animate({ scrollTop: ' . (int)Arr::get($link, 'scroll') . ' });';
				break;

			case 'quickview':
				$result['href'] = 'javascript:quickview(' . (int)$result['id'] . ')';

				break;
		}

		return $result;
	}

}
