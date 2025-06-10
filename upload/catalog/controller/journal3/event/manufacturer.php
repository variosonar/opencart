<?php

class ControllerJournal3EventManufacturer extends Controller {

	private static $manufacturers;

	public function model_catalog_manufacturer_getManufacturers_after(&$route, &$args, &$output) {
		self::$manufacturers = $output;
	}

	public function view_product_manufacturer_list_before(&$route, &$args) {
		if (self::$manufacturers) {
			$args['journal3_image_manufacturer_width'] = $this->journal3->get('image_dimensions_manufacturer.width');
			$args['journal3_image_manufacturer_height'] = $this->journal3->get('image_dimensions_manufacturer.height');
			$args['journal3_image_manufacturer_resize'] = $this->journal3->get('image_dimensions_manufacturer.resize');

			$categories = array();

			foreach (self::$manufacturers as $result) {
				if (is_numeric(\Journal3\Utils\Str::utf8_substr($result['name'], 0, 1))) {
					$key = '0 - 9';
				} else {
					$key = \Journal3\Utils\Str::utf8_substr(\Journal3\Utils\Str::utf8_strtoupper($result['name']), 0, 1);
				}

				if (!isset($categories[$key])) {
					$categories[$key]['name'] = $key;
				}

				if ($result['image']) {
					$image = $this->journal3_image->resize($result['image'], $args['journal3_image_manufacturer_width'], $args['journal3_image_manufacturer_height'], $args['journal3_image_manufacturer_resize']);
					$image2x = $this->journal3_image->resize($result['image'], $args['journal3_image_manufacturer_width'] * 2, $args['journal3_image_manufacturer_height'] * 2, $args['journal3_image_manufacturer_resize']);
				} else {
					$image = $this->journal3_image->resize($this->journal3->get('placeholder'), $args['journal3_image_manufacturer_width'], $args['journal3_image_manufacturer_height'], $args['journal3_image_manufacturer_resize']);
					$image2x = $this->journal3_image->resize($this->journal3->get('placeholder'), $args['journal3_image_manufacturer_width'] * 2, $args['journal3_image_manufacturer_height'] * 2, $args['journal3_image_manufacturer_resize']);
				}

				$categories[$key]['manufacturer'][] = array(
					'thumb'   => $image,
					'thumb2x' => $image2x,
				);
			}

			foreach ($categories as $key => $category) {
				foreach ($category['manufacturer'] as $index => $manufacturer) {
					$args['categories'][$key]['manufacturer'][$index] = array_merge($args['categories'][$key]['manufacturer'][$index], $manufacturer);
				}
			}
		}
	}

}

class_alias('ControllerJournal3EventManufacturer', '\Opencart\Catalog\Controller\Journal3\Event\Manufacturer');
