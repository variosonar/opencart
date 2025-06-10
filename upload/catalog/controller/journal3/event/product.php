<?php

use Journal3\Opencart\EventResult;

class ControllerJournal3EventProduct extends Controller {

	private static $product_id;
	private static $product_info;
	private static $product_images;
	private static $product_tabs_blocks;
	private static $product_options_images;
	private static $product_options_datetime;

	private static $review;

	public function controller_product_product_before(&$route, &$args) {
		// load needed scripts
		$this->document->addScript('catalog/view/theme/journal3/lib/imagezoom/imagezoom.min.css', 'lib-imagezoom');
		$this->document->addScript('catalog/view/theme/journal3/lib/imagezoom/jquery.imagezoom.min.js', 'lib-imagezoom');

		$this->document->addScript('catalog/view/theme/journal3/js/gallery.js', 'js-defer');
		$this->document->addScript('catalog/view/theme/journal3/lib/lightgallery/css/lightgallery.css', 'lib-lightgallery');
		$this->document->addScript('catalog/view/theme/journal3/lib/lightgallery/css/lg-transitions.css', 'lib-lightgallery');
		$this->document->addScript('catalog/view/theme/journal3/lib/lightgallery/css/lg-fullscreen.css', 'lib-lightgallery');
		$this->document->addScript('catalog/view/theme/journal3/lib/lightgallery/css/lg-thumbnail.css', 'lib-lightgallery');
		$this->document->addScript('catalog/view/theme/journal3/lib/lightgallery/css/lg-video.css', 'lib-lightgallery');
		$this->document->addScript('catalog/view/theme/journal3/lib/lightgallery/css/lg-zoom.css', 'lib-lightgallery');
		$this->document->addScript('catalog/view/theme/journal3/lib/lightgallery/lightgallery.min.js', 'lib-lightgallery');
		$this->document->addScript('catalog/view/theme/journal3/lib/lightgallery/plugins/autoplay/lg-autoplay.min.js', 'lib-lightgallery');
		$this->document->addScript('catalog/view/theme/journal3/lib/lightgallery/plugins/fullscreen/lg-fullscreen.min.js', 'lib-lightgallery');
		$this->document->addScript('catalog/view/theme/journal3/lib/lightgallery/plugins/thumbnail/lg-thumbnail.min.js', 'lib-lightgallery');
		$this->document->addScript('catalog/view/theme/journal3/lib/lightgallery/plugins/video/lg-video.min.js', 'lib-lightgallery');
		$this->document->addScript('catalog/view/theme/journal3/lib/lightgallery/plugins/zoom/lg-zoom.min.js', 'lib-lightgallery');

		$this->document->addStyle('catalog/view/theme/journal3/lib/swiper/swiper-critical.min.css');
		$this->document->addScript('catalog/view/theme/journal3/js/carousel.js', 'js-defer');
		$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.css', 'lib-swiper');
		$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.js', 'lib-swiper');

		$this->document->addScript('catalog/view/theme/journal3/js/product.js', 'js-defer');

		// get product info
		$this->load->model('journal3/product');
		self::$product_id = (int)($this->request->get['product_id'] ?? 0);
		self::$product_info = $this->model_journal3_product->getProduct(self::$product_id)[self::$product_id] ?? null;

		if (!self::$product_info) {
			return;
		}

		// update recently viewed
		$this->model_journal3_product->addRecentlyViewedProduct(self::$product_id);

		// product tabs
		$product_tabs = [];

		$module_tabs = $this->load->controller('journal3/product_extras' . JOURNAL3_ROUTE_SEPARATOR . 'tabs', self::$product_info);

		foreach ($module_tabs as $module_id => $module_data) {
			if ($module_data['position'] === 'quickview' && $this->journal3->is_quickview_popup) {
				if ($tab = $this->load->controller('journal3/product_tabs', ['module_id' => $module_id, 'module_type' => 'product_tabs', 'product_info' => self::$product_info])) {
					$product_tabs['default'][] = $tab;
				}
			} else if ($module_data['position'] === 'quickview_details' && $this->journal3->is_quickview_popup) {
				if ($tab = $this->load->controller('journal3/product_tabs', ['module_id' => $module_id, 'module_type' => 'product_tabs', 'product_info' => self::$product_info])) {
					$product_tabs['bottom'][] = $tab;
				}
			} else if ($module_data['position'] === 'quickview_image' && $this->journal3->is_quickview_popup) {
				if ($tab = $this->load->controller('journal3/product_tabs', ['module_id' => $module_id, 'module_type' => 'product_tabs', 'product_info' => self::$product_info])) {
					$product_tabs['image'][] = $tab;
				}
			} else if (!$this->journal3->is_popup) {
				if ($tab = $this->load->controller('journal3/product_tabs', ['module_id' => $module_id, 'module_type' => 'product_tabs', 'product_info' => self::$product_info])) {
					$product_tabs[$module_data['position']][] = $tab;
				}
			}
		}

		foreach ($product_tabs as $position => &$items) {
			$_items = array();

			foreach ($items as $item) {
				$_items[$item['display']][] = $item;
			}

			foreach ($_items as $items__) {
				self::$product_tabs_blocks[$position][] = $this->load->controller('journal3/product_tabs' . JOURNAL3_ROUTE_SEPARATOR . 'tabs', ['items' => $items__, 'position' => $position]);
			}
		}

		// product blocks
		$module_blocks = $this->load->controller('journal3/product_extras' . JOURNAL3_ROUTE_SEPARATOR . 'blocks', self::$product_info);

		foreach ($module_blocks as $module_id => $module_data) {
			if ($module_data['position'] === 'quickview' && $this->journal3->is_quickview_popup) {
				if ($block = $this->load->controller('journal3/product_blocks', ['module_id' => $module_id, 'module_type' => 'product_blocks', 'product_info' => self::$product_info])) {
					self::$product_tabs_blocks['default'][] = $block;
				}
			} else if ($module_data['position'] === 'quickview_details' && $this->journal3->is_quickview_popup) {
				if ($block = $this->load->controller('journal3/product_blocks', ['module_id' => $module_id, 'module_type' => 'product_blocks', 'product_info' => self::$product_info])) {
					self::$product_tabs_blocks['bottom'][] = $block;
				}
			} else if ($module_data['position'] === 'quickview_image' && $this->journal3->is_quickview_popup) {
				if ($block = $this->load->controller('journal3/product_blocks', ['module_id' => $module_id, 'module_type' => 'product_blocks', 'product_info' => self::$product_info])) {
					self::$product_tabs_blocks['image'][] = $block;
				}
			} else if (!$this->journal3->is_popup) {
				if ($block = $this->load->controller('journal3/product_blocks', ['module_id' => $module_id, 'module_type' => 'product_blocks', 'product_info' => self::$product_info])) {
					self::$product_tabs_blocks[$module_data['position']][] = $block;
				}
			}
		}

		// product tabs / blocks
		if (self::$product_tabs_blocks) {
			foreach (self::$product_tabs_blocks as $position => &$block) {
				$block = '<div class="product-blocks blocks-' . $position . '">' . implode(' ', $block) . '</div>';
			}
		}
	}

	public function model_catalog_product_getProduct_after(&$route, &$args, &$output) {
		list($product_id) = $args;

		if (($product_id == self::$product_id) && $output) {
			if (self::$product_images === null) {
				self::$product_images = [
					['image' => $output['image']],
				];
			}

			// overwrite thumb to avoid unnecessary image resize
			// we add it to product_images to resize all in one
			// $output['image_'] = $output['image'];
			// $output['image'] = '';
		}
	}

	public function model_catalog_product_getProductImages_after(&$route, &$args, &$output) {
		static $first = true;

		if (!$first) {
			return;
		}

		$first = false;

		if (is_array(self::$product_images)) {
			self::$product_images = array_merge(self::$product_images, $output);
		}

		$output = [];
	}

	public function model_catalog_product_getProductRelated_before(&$route, &$args) {
		return new EventResult();
	}

	public function model_catalog_product_getProductOptions_after(&$route, &$args, &$output) {
		self::$product_options_datetime = false;
		self::$product_options_images = [];

		foreach ($output as &$option) {
			foreach ($option['product_option_value'] as &$option_value) {
				self::$product_options_images[$option_value['option_value_id']][$option_value['product_option_value_id']] = $option_value['image'];
				$option_value['image'] = '';
			}

			if (in_array($option['type'], ['date', 'time', 'datetime'])) {
				self::$product_options_datetime = true;
			}
		}
	}

	public function view_product_product_before(&$route, &$args) {
		if (!self::$product_info) {
			return;
		}

		// add to cart custom text
		$args['journal3_button_cart'] = $this->journal3->get('filterAddToCartStock') && self::$product_info['quantity'] <= 0 ? self::$product_info['stock_status'] : $this->language->get('button_cart');

		// add to cart popup quantity
		$args['journal3_product_quantity'] = (int)($this->request->get['product_quantity'] ?? 0);

		// view more url
		$args['journal3_view_more_url'] = $this->url->link('product/product', 'product_id=' . self::$product_id);

		// style prefix
		$args['stylePrefix'] = $this->journal3->is_quickview_popup ? 'quickviewPageStyle' : 'productPageStyle';

		// option price
		$args['optionPrice'] = $this->journal3->get($args['stylePrefix'] . 'OptionPrice');

		// product extras
		$args['journal3_product_labels'] = $this->journal3_product_extras->labels(self::$product_info);
		$args['journal3_product_classes'] = $this->journal3_product_extras->exclude_button(self::$product_info);
		$args['journal3_product_extra_buttons'] = $this->journal3_product_extras->extra_buttons(self::$product_info);
		$args['journal3_product_countdown'] = $this->journal3->get($this->journal3->is_quickview_popup ? 'quickviewCountdown' : 'countdownStatus') ? static::$product_info['special_date_end'] : null;

		// classes
		$args['journal3_product_classes']['out-of-stock'] = self::$product_info['quantity'] <= 0;
		$args['journal3_product_classes']['has-zero-price'] = (self::$product_info['special'] ?: self::$product_info['price']) <= 0;
		$args['journal3_product_classes']['has-countdown'] = (bool)$args['journal3_product_countdown'];
		$args['journal3_product_classes']['has-special'] = (bool)self::$product_info['special'];
		$args['journal3_product_classes']['has-extra-button'] = (bool)$args['journal3_product_extra_buttons'];

		// blocks / tabs
		if ($this->journal3_opencart->is_oc4 && is_array(self::$product_tabs_blocks)) {
			$review_token = $this->session->data['review_token'] ?? '';
			foreach (self::$product_tabs_blocks as $position => $block) {
				self::$product_tabs_blocks[$position] = str_replace('__REVIEW_TOKEN__', $review_token, $block);
			}
		}
		$args['journal3_product_tabs_blocks_content_top'] = self::$product_tabs_blocks['content_top'] ?? null;
		$args['journal3_product_tabs_blocks_top'] = self::$product_tabs_blocks['top'] ?? null;
		$args['journal3_product_tabs_blocks_details'] = self::$product_tabs_blocks['details'] ?? null;
		$args['journal3_product_tabs_blocks_bottom'] = self::$product_tabs_blocks['bottom'] ?? null;
		$args['journal3_product_tabs_blocks_image'] = self::$product_tabs_blocks['image'] ?? null;
		$args['journal3_product_tabs_blocks_default'] = self::$product_tabs_blocks['default'] ?? null;

		// product images
		if (self::$product_images) {
			$args['journal3_image_thumb_width'] = $this->journal3->get('image_dimensions_thumb.width');
			$args['journal3_image_thumb_height'] = $this->journal3->get('image_dimensions_thumb.height');
			$args['journal3_image_thumb_resize'] = $this->journal3->get('image_dimensions_thumb.resize');

			$args['journal3_image_popup_width'] = $this->journal3->get('image_dimensions_popup.width');
			$args['journal3_image_popup_height'] = $this->journal3->get('image_dimensions_popup.height');
			$args['journal3_image_popup_resize'] = $this->journal3->get('image_dimensions_popup.resize');

			if ($this->journal3->get('performanceLazyLoadImagesStatus')) {
				$args['journal3_image_placeholder'] = $this->journal3_image->transparent($args['journal3_image_thumb_width'], $args['journal3_image_thumb_height']);
			} else {
				$args['journal3_image_placeholder'] = null;
			}

			$args['images'] = [];

			foreach (self::$product_images as $product_image) {
				$args['images'][] = [
					'thumb'   => $this->journal3_image->resize($product_image['image'], $args['journal3_image_thumb_width'], $args['journal3_image_thumb_height'], $args['journal3_image_thumb_resize']),
					'thumb2x' => $this->journal3_image->resize($product_image['image'], $args['journal3_image_thumb_width'] * 2, $args['journal3_image_thumb_height'] * 2, $args['journal3_image_thumb_resize']),
					'popup'   => $this->journal3_image->resize($product_image['image'], $args['journal3_image_popup_width'], $args['journal3_image_popup_height'], $args['journal3_image_popup_resize']),
					'popup2x' => $this->journal3_image->resize($product_image['image'], $args['journal3_image_popup_width'] * 2, $args['journal3_image_popup_height'] * 2, $args['journal3_image_popup_resize']),
				];
			}

			$args['journal3_images_carousel'] = $this->journal3->carousel($this->journal3_document->getJs(), $args['stylePrefix'] . 'ImageCarouselStyle');
		} else {
			$args['images'] = false;
		}

		// product additional images
		$args['journal3_images_additional_position'] = $this->journal3->get($args['stylePrefix'] . 'AdditionalImagesPosition');
		$args['journal3_images_additional_direction'] = in_array($this->journal3->get($args['stylePrefix'] . 'AdditionalImagesPosition'), ['left', 'right']) ? 'vertical' : 'horizontal';

		$args['journal3_images_additional_carousel'] = $this->journal3->get($args['stylePrefix'] . 'AdditionalImagesCarousel') || $args['journal3_images_additional_direction'] === 'vertical';

		$args['journal3_images_additional_carousel_options'] = [
			'slidesPerView'       => 'auto',
			'spaceBetween'        => (int)$this->journal3->get($args['stylePrefix'] . 'AdditionalImagesSpacing'),
			'direction'           => $args['journal3_images_additional_direction'],
			'normalizeSlideIndex' => false,
			'slideToClickedSlide' => true,
			'freeMode'            => true,
		];

		if ($this->journal3->get($args['stylePrefix'] . 'AdditionalImagesStatus') && count(self::$product_images) > 1) {
			$args['journal3_image_additional_width'] = $this->journal3->get('image_dimensions_additional.width');
			$args['journal3_image_additional_height'] = $this->journal3->get('image_dimensions_additional.height');
			$args['journal3_image_additional_resize'] = $this->journal3->get('image_dimensions_additional.resize');

			$args['journal3_images_additional'] = [];

			foreach (self::$product_images as $product_image) {
				$args['journal3_images_additional'][] = [
					'additional'   => $this->journal3_image->resize($product_image['image'], $args['journal3_image_additional_width'], $args['journal3_image_additional_height'], $args['journal3_image_additional_resize']),
					'additional2x' => $this->journal3_image->resize($product_image['image'], $args['journal3_image_additional_width'] * 2, $args['journal3_image_additional_height'] * 2, $args['journal3_image_additional_resize']),
				];
			}
		} else {
			$args['journal3_images_additional'] = null;
		}

		// inline styles
		if ($args['journal3_images_additional'] && $args['journal3_images_additional_carousel'] && $args['journal3_images_additional_direction'] == 'vertical') {
			$args['journal3_images_style'] = 'style="width: calc(100% - ' . $args['journal3_image_additional_width'] . 'px)"';
			$args['journal3_images_additional_style'] = 'style="width: ' . $args['journal3_image_additional_width'] . 'px"';
		} else {
			$args['journal3_images_style'] = null;
			$args['journal3_images_additional_style'] = null;
		}

		// product images gallery
		if ($this->journal3->get($args['stylePrefix'] . 'GalleryStatus') && !$this->journal3->is_popup) {
			$args['journal3_image_popup_thumb_width'] = $this->journal3->get('image_dimensions_popup_thumb.width');
			$args['journal3_image_popup_thumb_height'] = $this->journal3->get('image_dimensions_popup_thumb.height');
			$args['journal3_image_popup_thumb_resize'] = $this->journal3->get('image_dimensions_popup_thumb.resize');

			$args['journal3_images_gallery_options'] = array(
				'addClass'          => 'lg-product-images',
				'thumbWidth'        => $args['journal3_image_popup_thumb_width'],
				'thumbHeight'       => $args['journal3_image_popup_thumb_height'] . 'px',
//				'mode'              => $this->journal3->get($args['stylePrefix'] . 'GalleryMode'),
//				'download'        => $this->journal3->get($args['stylePrefix'] . 'GalleryDownload'),
//				'fullScreen'      => $this->journal3->get($args['stylePrefix'] . 'GalleryFullScreen'),
				'allowMediaOverlap' => $this->journal3->get($args['stylePrefix'] . 'GalleryThumbToggleStatus'),
			);

			$args['journal3_images_gallery'] = [];

			foreach (self::$product_images as $product_image) {
				$src = $this->journal3_image->resize($product_image['image'], $args['journal3_image_popup_width'], $args['journal3_image_popup_height'], $args['journal3_image_popup_resize']);
				$src2x = $this->journal3_image->resize($product_image['image'], $args['journal3_image_popup_width'] * 2, $args['journal3_image_popup_height'] * 2, $args['journal3_image_popup_resize']);

				$args['journal3_images_gallery'][] = [
					'type'    => 'image',
					'src'     => $src,
					'srcset'  => $src === $src2x ? null : sprintf("%s 1x, %s 2x", $src, $src2x),
					'thumb'   => $this->journal3_image->resize($product_image['image'], $args['journal3_image_popup_thumb_width'], $args['journal3_image_popup_thumb_height'], $args['journal3_image_popup_thumb_resize']),
					'subHtml' => $args['heading_title'],
				];
			}
		} else {
			$args['journal3_images_gallery'] = null;
		}

		// trim product description
		if (!trim(strip_tags($args['description'], '<img><iframe>'))) {
			$args['description'] = '';
		}

		// quickview expand
		$args['quickviewExpand'] = !$this->journal3->get('quickviewExpandButton') || ($this->journal3->get('globalExpandCharactersLimit') > 0 && $args['description'] && \Journal3\Utils\Str::utf8_strlen($args['description']) <= $this->journal3->get('globalExpandCharactersLimit')) ? 'no-expand' : '';

		// stats
		$args['journal3_product_stats_position'] = $this->journal3->get($args['stylePrefix'] . 'Stats') && !$this->journal3->is_options_popup ? $this->journal3->get($args['stylePrefix'] . 'StatsPosition') : null;

		if ($args['journal3_product_stats_position']) {
			if (self::$product_info['quantity'] > 0 && !$this->config->get('config_stock_display')) {
				$stock = $this->journal3->get($args['stylePrefix'] . 'ProductInStockText') ?: $args['stock'];

				// some third party addons for in stock status
				if (!empty(self::$product_info['in_stock_status'])) {
					$stock = self::$product_info['in_stock_status'];
				}
			} else {
				$stock = $args['stock'];
			}

			$args['journal3_product_stock'] = $this->journal3->get($args['stylePrefix'] . 'ProductStock');
			$args['journal3_product_stock_text'] = $this->journal3->get($args['stylePrefix'] . 'ProductStockText');
			$args['journal3_product_stock_value'] = $stock;
			$args['journal3_product_stock_class'] = self::$product_info['quantity'] > 0 ? 'in-stock' : 'out-of-stock';

			$args['journal3_product_manufacturer'] = $this->journal3->get($args['stylePrefix'] . 'ProductManufacturer') && self::$product_info['manufacturer'];
			$args['journal3_product_manufacturer_display'] = $this->journal3->get($args['stylePrefix'] . 'ProductManufacturerDisplay');
			$args['journal3_product_manufacturer_text'] = $this->journal3->get($args['stylePrefix'] . 'ProductManufacturerText');
			$args['journal3_product_manufacturer_value'] = $args['manufacturer'];
			$args['journal3_product_manufacturer_href'] = $args['manufacturers'] ?: 'javascript:;';
			$args['journal3_product_manufacturer_image'] = false;

			$args['journal3_product_model'] = $this->journal3->get($args['stylePrefix'] . 'ProductModel') && self::$product_info['model'];
			$args['journal3_product_model_text'] = $this->journal3->get($args['stylePrefix'] . 'ProductModelText');
			$args['journal3_product_model_value'] = self::$product_info['model'];

			$args['journal3_product_weight'] = $this->journal3->get($args['stylePrefix'] . 'ProductWeight') && (float)self::$product_info['weight'];
			$args['journal3_product_weight_text'] = $this->journal3->get($args['stylePrefix'] . 'ProductWeightText');
			$args['journal3_product_weight_value'] = $this->weight->format(self::$product_info['weight'], self::$product_info['weight_class_id']);

			$args['journal3_product_dimensions'] = $this->journal3->get($args['stylePrefix'] . 'ProductDimension') && (float)self::$product_info['length'] && (float)self::$product_info['width'] && (float)self::$product_info['height'];
			$args['journal3_product_dimensions_text'] = $this->journal3->get($args['stylePrefix'] . 'ProductDimensionText');
			$args['journal3_product_dimensions_value'] = sprintf("%s x %s x %s", $this->length->format(self::$product_info['length'], self::$product_info['length_class_id']), $this->length->format(self::$product_info['width'], self::$product_info['length_class_id']), $this->length->format(self::$product_info['height'], self::$product_info['length_class_id']));

			$args['journal3_product_reward'] = $this->journal3->get($args['stylePrefix'] . 'ProductReward') && self::$product_info['reward'];
			$args['journal3_product_reward_text'] = $this->journal3->get($args['stylePrefix'] . 'ProductRewardText');
			$args['journal3_product_reward_value'] = self::$product_info['reward'];

			$args['journal3_product_sku'] = $this->journal3->get($args['stylePrefix'] . 'ProductSKU') && self::$product_info['sku'];
			$args['journal3_product_sku_text'] = $this->journal3->get($args['stylePrefix'] . 'ProductSKUText');
			$args['journal3_product_sku_value'] = self::$product_info['sku'];

			$args['journal3_product_upc'] = $this->journal3->get($args['stylePrefix'] . 'ProductUPC') && self::$product_info['upc'];
			$args['journal3_product_upc_text'] = $this->journal3->get($args['stylePrefix'] . 'ProductUPCText');
			$args['journal3_product_upc_value'] = self::$product_info['upc'];

			$args['journal3_product_ean'] = $this->journal3->get($args['stylePrefix'] . 'ProductEAN') && self::$product_info['ean'];
			$args['journal3_product_ean_text'] = $this->journal3->get($args['stylePrefix'] . 'ProductEANText');
			$args['journal3_product_ean_value'] = self::$product_info['ean'];

			$args['journal3_product_jan'] = $this->journal3->get($args['stylePrefix'] . 'ProductJAN') && self::$product_info['jan'];
			$args['journal3_product_jan_text'] = $this->journal3->get($args['stylePrefix'] . 'ProductJANText');
			$args['journal3_product_jan_value'] = self::$product_info['jan'];

			$args['journal3_product_isbn'] = $this->journal3->get($args['stylePrefix'] . 'ProductISBN') && self::$product_info['isbn'];
			$args['journal3_product_isbn_text'] = $this->journal3->get($args['stylePrefix'] . 'ProductISBNText');
			$args['journal3_product_isbn_value'] = self::$product_info['isbn'];

			$args['journal3_product_mpn'] = $this->journal3->get($args['stylePrefix'] . 'ProductMPN') && self::$product_info['mpn'];
			$args['journal3_product_mpn_text'] = $this->journal3->get($args['stylePrefix'] . 'ProductMPNText');
			$args['journal3_product_mpn_value'] = self::$product_info['mpn'];

			$args['journal3_product_location'] = $this->journal3->get($args['stylePrefix'] . 'ProductLocation') && self::$product_info['location'];
			$args['journal3_product_location_text'] = $this->journal3->get($args['stylePrefix'] . 'ProductLocationText');
			$args['journal3_product_location_value'] = self::$product_info['location'];

			if ($args['journal3_product_manufacturer'] && $args['journal3_product_manufacturer_display'] === 'image') {
				$this->load->model('catalog/manufacturer');

				$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer(self::$product_info['manufacturer_id']);

				if ($manufacturer_info && $manufacturer_info['image']) {
					$width = $this->journal3->get('image_dimensions_manufacturer_logo.width');
					$height = $this->journal3->get('image_dimensions_manufacturer_logo.height');
					$crop = $this->journal3->get('image_dimensions_manufacturer_logo.resize');

					$args['journal3_product_manufacturer_image'] = $this->journal3_image->resize($manufacturer_info['image'], $width, $height, $crop);
					$args['journal3_product_manufacturer_image2x'] = $this->journal3_image->resize($manufacturer_info['image'], $width * 2, $height * 2, $crop);
				}
			}

			if ($this->journal3->get($args['stylePrefix'] . 'CustomStats') && $this->journal3->get($args['stylePrefix'] . 'ProductSold')) {
				$args['journal3_product_sold'] = $this->journal3->getWithValue($args['stylePrefix'] . 'SoldText', '<span>' . $this->model_journal3_product->getProductsSold($this->request->get['product_id']) . '</span>');
			} else {
				$args['journal3_product_sold'] = false;
			}

			if ($this->journal3->get($args['stylePrefix'] . 'CustomStats') && $this->journal3->get($args['stylePrefix'] . 'ProductViews')) {
				$args['journal3_product_views'] = $this->journal3->getWithValue($args['stylePrefix'] . 'ViewsText', '<span>' . self::$product_info['viewed'] . '</span>');
			} else {
				$args['journal3_product_views'] = false;
			}
		}

		// product options
		if (!empty($args['options'])) {
			$args['journal3_image_options_width'] = $this->journal3->get('image_dimensions_options.width');
			$args['journal3_image_options_height'] = $this->journal3->get('image_dimensions_options.height');
			$args['journal3_image_options_resize'] = $this->journal3->get('image_dimensions_options.resize');

			foreach ($args['options'] as &$option) {
				if (in_array($option['type'], ['radio', 'checkbox'])) {
					foreach ($option['product_option_value'] as &$option_value) {
						if ($image = self::$product_options_images[$option_value['option_value_id']][$option_value['product_option_value_id']] ?? false) {
							if (is_file(DIR_IMAGE . $image)) {
								$option_value['image'] = $this->journal3_image->resize($image, $args['journal3_image_options_width'], $args['journal3_image_options_height'], $args['journal3_image_options_resize']);
								$option_value['image2x'] = $this->journal3_image->resize($image, $args['journal3_image_options_width'] * 2, $args['journal3_image_options_height'] * 2, $args['journal3_image_options_resize']);
							}
						}
					}
				}
			}
		}
	}

	public function view_product_compare_before(&$route, &$args) {
		$this->load->model('journal3/product');

		$product_info = $this->model_journal3_product->getProduct($args['products']);

		$args['image_width'] = $this->journal3->get('image_dimensions_compare.width');
		$args['image_height'] = $this->journal3->get('image_dimensions_compare.height');
		$args['image_resize'] = $this->journal3->get('image_dimensions_compare.resize');

		foreach ($args['products'] as &$product) {
			$result = $product_info[$product['product_id']];

			// image
			if ($result['image']) {
				$image = $this->journal3_image->resize($result['image'], $args['image_width'], $args['image_height'], $args['image_resize']);
				$image2x = $this->journal3_image->resize($result['image'], $args['image_width'] * 2, $args['image_height'] * 2, $args['image_resize']);
			} else {
				$image = $this->journal3_image->resize($this->journal3->get('placeholder'), $args['image_width'], $args['image_height'], $args['image_resize']);
				$image2x = $this->journal3_image->resize($this->journal3->get('placeholder'), $args['image_width'] * 2, $args['image_height'] * 2, $args['image_resize']);
			}

			// product extras
			$classes = $this->journal3_product_extras->exclude_button($result);

			// classes
			$classes['out-of-stock'] = $result['quantity'] <= 0;
			$classes['has-zero-price'] = ($result['special'] ? $result['special'] : $result['price']) <= 0;

			$product = array_merge($product, array(
				'thumb'   => $image,
				'thumb2x' => $image2x,
				'classes' => $classes,
			));
		}
	}

	public function controller_product_review_before(&$route, &$args) {
		if (!empty(static::$review)) {
			return static::$review;
		}
	}

	public function controller_product_review_after(&$route, &$args, &$output) {
		if (empty(static::$review)) {
			static::$review = $output;
		}
	}

	public static function getProductImages() {
		return static::$product_images;
	}

	public static function getProductInfo() {
		return static::$product_info;
	}

}

class_alias('ControllerJournal3EventProduct', '\Opencart\Catalog\Controller\Journal3\Event\Product');
