<?php

namespace Journal3;

use Journal3\Utils\Arr;
use Nette\Utils\Strings;

/**
 * Class Request
 *
 * It's similar to Opencart Request class but with additional methods
 *
 * @package Journal3
 */
class Request extends Base {

	/**
	 * @var string
	 */
	public $host;

	/**
	 * @var string
	 */
	public $url;

	/**
	 * @var bool
	 */
	public $is_get;

	/**
	 * @var bool
	 */
	public $is_post;

	/**
	 * @var bool
	 */
	public $is_ajax;

	/**
	 * @var bool
	 */
	public $is_https;

	/**
	 * @var bool
	 */
	public $is_webp;

	/**
	 * Request constructor.
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->is_get = strtolower($this->server('REQUEST_METHOD') ?? '') === 'get';
		$this->is_post = strtolower($this->server('REQUEST_METHOD') ?? '') === 'post';
		$this->is_ajax = strtolower($this->server('HTTP_X_REQUESTED_WITH') ?? '') === 'xmlhttprequest';
		$this->is_https = (bool)$this->server('HTTPS');
		$this->is_webp = Strings::contains($this->server('HTTP_ACCEPT') ?? '', 'image/webp') || Strings::contains($this->server('HTTP_USER_AGENT') ?? '', ' Chrome/');

		$this->host = ($this->is_https ? 'https' : 'http') . '://' . $this->server('HTTP_HOST');
		$this->url = $this->host . $this->server('REQUEST_URI');
	}

	/**
	 * @param $key
	 * @param null $default
	 * @return mixed|null
	 */
	public function cookie($key, $default = null) {
		return $this->request->cookie[$key] ?? $default;
	}

	/**
	 * @param $key
	 * @param null $default
	 * @return mixed
	 * @throws \Exception
	 */
	public function get($key, $default = null) {
		return $this->param('GET', $key, $default);
	}

	/**
	 * @param $key
	 * @param null $default
	 * @return mixed
	 * @throws \Exception
	 */
	public function file($key, $default = null) {
		return $this->param('FILE', $key, $default);
	}

	/**
	 * @param $key
	 * @param null $default
	 * @return mixed
	 * @throws \Exception
	 */
	public function post($key, $default = null) {
		return $this->param('POST', $key, $default);
	}

	/**
	 * @param $key
	 * @param null $default
	 * @return mixed|null
	 */
	public function server($key, $default = null) {
		return $this->request->server[$key] ?? $default;
	}

	/**
	 * @param $key
	 * @param null $default
	 * @return mixed|null
	 */
	public function session($key, $default = null) {
		return $this->session->data[$key] ?? $default;
	}

	/**
	 * @param $method
	 * @param $variable
	 * @param null $default
	 * @return mixed
	 * @throws \Exception
	 */
	protected function param($method, $variable, $default = null) {
		$value = null;

		if ($method === 'GET') {
			$value = Arr::get($this->request->get, $variable);
		}

		if ($method === 'POST') {
			$value = Arr::get($this->request->post, $variable);
		}

		if ($method === 'FILE') {
			$value = Arr::get($this->request->files, $variable);
		}

		if ($value === null && $default !== null) {
			$value = $default;
		}

		if ($value === null) {
			throw new \Exception(sprintf("%s: Parameter `%s` is not found!", $method, $variable));
		}

		return $value;
	}

}
