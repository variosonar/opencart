<?php

namespace Journal3\Options;

use Journal3\Utils\Str;
use Nette\Utils\Strings;

class ColorScheme extends Option {

    protected static function parseValue($value, $data = null) {
        if (!$value) {
            return '';
        }

        return static::scheme($value);
    }

    public static function scheme($value) {
        return 'color-scheme-' . Str::handleize($value);
    }

}
