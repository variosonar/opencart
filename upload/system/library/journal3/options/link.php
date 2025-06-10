<?php

namespace Journal3\Options;

use Journal3\Url;

class Link extends Option {

	/** @var Url */
	private static $url;

	protected static function parseValue($value, $data = null) {
		if (static::$url === null) {
			static::$url = \Journal3\Journal::getInstance()->getRegistry()->get('journal3_url');
		}

		return static::$url->getLink($value);
	}

}
