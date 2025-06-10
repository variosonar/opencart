<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;

class ControllerJournal3Image extends ModuleController {

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$width = $parser->getSetting('imageDimensions.width');
		$height = $parser->getSetting('imageDimensions.height');
		$resize = $parser->getSetting('imageDimensions.resize');

		$data = array(
			'edit'         => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'         => $parser->getSetting('name'),
			'image_width'  => $width,
			'image_height' => $height,
			'image_resize' => $resize,
		);

		switch ($parser->getSetting('type')) {
			case 'image':
				$image = $parser->getSetting('image');

				$data['image'] = $this->journal3_image->resize($image, $width, $height, $resize);
				$data['image2x'] = $this->journal3_image->resize($image, $width * 2, $height * 2, $resize);

				break;

			case 'logo':
				$image = $this->journal3->get('logo2x') ?: $this->journal3->get('logo') ?: $this->config->get('config_logo');

				$data['image'] = $this->journal3_image->resize($image, $width, $height, $resize);
				$data['image2x'] = $this->journal3_image->resize($image, $width * 2, $height * 2, $resize);

				break;

			case 'logo_alternate':
				$image = $this->journal3->get('logo2xAlternate') ?: $this->journal3->get('logoAlternate') ?: $this->journal3->get('logo') ?: $this->config->get('config_logo');

				$data['image'] = $this->journal3_image->resize($image, $width, $height, $resize);
				$data['image2x'] = $this->journal3_image->resize($image, $width * 2, $height * 2, $resize);

				break;

			case 'brand':
				$data['has_brand_image'] = true;

				break;

			case 'page':
				$data['has_page_image'] = true;

				break;
		}

		if ($this->journal3->get('performanceLazyLoadImagesStatus') && $parser->getSetting('lazyLoad')) {
			$data['dummy_image'] = $this->journal3_image->transparent($width, $height);
		}

		return $data;
	}

	protected function parseItemSettings($parser, $index) {
		return array();
	}

	protected function parseSubitemSettings($parser, $index) {
		return array();
	}

	protected function beforeRender() {
		if (!empty($this->settings['has_page_image'])) {
			$image = $this->load->controller('journal3/seo' . JOURNAL3_ROUTE_SEPARATOR . 'getTags', array('image'));

			$this->settings['image'] = $this->journal3_image->resize($image, $this->settings['image_width'], $this->settings['image_height'], $this->settings['image_resize']);
			$this->settings['image2x'] = $this->journal3_image->resize($image, $this->settings['image_width'] * 2, $this->settings['image_height'] * 2, $this->settings['image_resize']);

			switch ($this->journal3_document->getPageRoute()) {
				case 'product/category':
					$path = $this->request->get['path'];
					$path = explode('_', $path);
					$path = array_map('intval', $path);
					$path = implode('_', $path);
					$this->settings['link']['href'] = $this->journal3_url->link('product/category', 'path=' . $path);
					break;
				case 'product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info':
					$this->settings['link']['href'] = $this->journal3_url->link('product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info', 'manufacturer_id=' . $this->journal3_document->getPageId());
					break;
			}
		}

		if (!empty($this->settings['has_brand_image']) && $this->request->get['route'] === 'product/product') {
			$id = (int)$this->request->get['product_id'];

			if ($id) {
				$product_info = $this->model_catalog_product->getProduct($id);

				$this->load->model('catalog/manufacturer');

				$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($product_info['manufacturer_id']);

				if (!empty($manufacturer_info['image'])) {
					$this->settings['link']['href'] = $this->journal3_url->link('product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info', 'manufacturer_id=' . $product_info['manufacturer_id']);
					$this->settings['image'] = $this->journal3_image->resize($manufacturer_info['image'], $this->settings['image_width'], $this->settings['image_height'], $this->settings['image_resize']);
					$this->settings['image2x'] = $this->journal3_image->resize($manufacturer_info['image'], $this->settings['image_width'] * 2, $this->settings['image_height'] * 2, $this->settings['image_resize']);;
				} else {
					$this->settings = null;
				}
			}
		}
	}

}

class_alias('ControllerJournal3Image', '\Opencart\Catalog\Controller\Journal3\Image');
