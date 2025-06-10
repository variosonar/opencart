<?php

namespace Journal3;

use Journal3\Opencart\Autocomplete;

/**
 * Class Base is used as base class for all Journal system classes
 * It has the same behaviour as Opencart Registry class
 *
 * @package Journal3
 */
abstract class Base implements Autocomplete {

	/**
	 * @var \Registry
	 */
	protected $registry;

	/**
	 * @param $registry
	 */
	public function __construct($registry) {
		$this->registry = $registry;
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function __get($name) {
		return $this->registry->get($name);
	}

}

