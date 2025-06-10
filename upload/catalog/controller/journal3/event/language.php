<?php

class ControllerJournal3EventLanguage extends Controller {

	private static $languages;

	public function model_localisation_language_after(&$route, &$args, &$output) {
		if (is_array($output)) {
			foreach ($output as $language) {
				static::$languages[$language['code']] = $language;
			}
		}
	}

	public function view_common_language_before(&$route, &$args) {
		if ($args['languages']) {
			$images = [];

			foreach ($args['languages'] as &$language) {
				$language_extension = static::$languages[$language['code']]['extension'] ?? null;

				if ($language_extension) {
					$image = "extension/{$language_extension}/catalog/language/{$language['code']}/{$language['code']}.png";
				} else {
					$image = "catalog/language/{$language['code']}/{$language['code']}.png";
				}

				$images[$language['code']] = $image;

				if (is_file($image)) {
					$language['journal3_language_image'] = $this->journal3_image->base64($image);
				} else {
					$language['journal3_language_image'] = '';
				}
			}

			if (!empty($images[$args['code']]) && is_file($images[$args['code']])) {
				[$width, $height] = getimagesize($images[$args['code']]);
			} else {
				$width = 16;
				$height = 16;
			}

			$args['journal3_language_image_width'] = $width;
			$args['journal3_language_image_height'] = $height;

			$args['journal3_language_image_placeholder'] = $this->journal3_image->transparent($width * 2, $height * 2);

			if (!empty($images[$args['code']]) && is_file($images[$args['code']])) {
				$args['journal3_language_image'] = $this->journal3_image->base64($images[$args['code']]);
			} else {
				$args['journal3_language_image'] = $args['journal3_language_image_placeholder'];
			}
		}
	}

}

class_alias('ControllerJournal3EventLanguage', '\Opencart\Catalog\Controller\Journal3\Event\Language');

