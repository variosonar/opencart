<?php

namespace Journal3;

/**
 * Class Opencart is used to access various Opencart variables like version, customer_id, language_id, etc...
 *
 * @package Journal3
 */
class Opencart extends Base {

	/**
	 * @var int
	 */
	public $ver;

	/**
	 * @var bool
	 */
	public $is_oc2;

	/**
	 * @var bool
	 */
	public $is_oc3;

	/**
	 * @var bool
	 */
	public $is_oc4;

	/**
	 * @var bool|mixed
	 */
	public $is_admin;

	/**
	 * @var bool|mixed
	 */
	public $is_customer;

	/**
	 * @var int
	 */
	public $store_id;

	/**
	 * @var int
	 */
	public $language_id;

	/**
	 * @var int
	 */
	public $default_language_id;

	/**
	 * @var int
	 */
	public $fallback_language_id;

	/**
	 * @var mixed|null
	 */
	public $currency_id;

	/**
	 * @var int
	 */
	public $customer_group_id;

	/**
	 * Opencart constructor.
	 * @param $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->ver = (int)explode('.', VERSION)[0];
		$this->is_oc2 = $this->ver === 2;
		$this->is_oc3 = $this->ver === 3;
		$this->is_oc4 = $this->ver === 4;

		$this->is_admin = $this->session->data['user_id'] ?? null > 0;
		$this->is_customer = $this->session->data['customer_id'] ?? null > 0;

		$this->store_id = (int)$this->config->get('config_store_id');
		$this->language_id = (int)$this->config->get('config_language_id');
		$this->currency_id = $this->session->data['currency'] ?? null ?: $this->config->get('config_currency');
		$this->customer_group_id = (int)($this->is_customer ? $this->customer->getGroupId() : $this->config->get('config_customer_group_id'));

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language`");

		foreach ($query->rows as $row) {
			if ($row['code'] == $this->config->get('config_language')) {
				$this->default_language_id = $row['language_id'];
			}

			if ($row['language_id'] == 1) {
				$this->fallback_language_id = 1;
			}
		}
	}

}

