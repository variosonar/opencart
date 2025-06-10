<?php

namespace Journal3\Opencart;

/**
 * Class EventResult is used to return a falsy result in Opencart events
 *
 * Default Opencart event engine does not allow to stop execution if an event returns false value in system/engine/loader.php
 *
 * @package Journal3\Opencart
 */
class EventResult {

	/**
	 * @return string
	 */
	public function __toString() {
		return '';
	}

}
