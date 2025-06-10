<?php

use Journal3\Opencart\ModuleController;
use Journal3\Utils\Arr;
use Journal3\Utils\Str;

class ControllerJournal3Slider extends ModuleController {

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$image = Arr::get($this->module_data, 'items.0.image.lang_' . $this->config->get('config_language_id'));

		if (is_file(DIR_IMAGE . $image)) {
			list($width, $height) = @getimagesize('image/' . $image);
		} else {
			$width = null;
			$height = null;
		}

		if ($parser->getSetting('imageDimensions.width')) {
			$width = $parser->getSetting('imageDimensions.width');
		}

		if ($parser->getSetting('imageDimensions.height')) {
			$height = $parser->getSetting('imageDimensions.height');
		}

		$data = array(
			'edit'     => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'     => $parser->getSetting('name'),
			'width'    => $width,
			'height'   => $height,
			'options'  => array_merge_recursive(
				array(),
				$parser->getJs()
			),
			'classes'  => array(),
			'syncWith' => $parser->getSetting('syncWith') ? '.module-slider-' . $parser->getSetting('syncWith') : '',
		);

		if (!$data['syncWith'] && $parser->getSetting('autoplay')) {
			$data['options']['autoplay'] = [
				'delay'                => $parser->getSetting('autoplayDelay'),
				'disableOnInteraction' => false,
				'pauseOnMouseEnter'    => $parser->getSetting('pauseOnMouseEnter'),
			];
		}

		if ($parser->getSetting('pagination') !== 'none') {
			$data['options']['pagination']['type'] = $parser->getSetting('pagination');
		}

		$data['lazyload_placeholder'] = $this->journal3_image->transparent($width, $height);
		$data['lazyload_thumb_placeholder'] = $this->journal3_image->transparent($parser->getSetting('thumbnailsDimensions.width'), $parser->getSetting('thumbnailsDimensions.height'));

		return $data;
	}

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		$width = Arr::get($this->settings, 'width');
		$height = Arr::get($this->settings, 'height');

		$data = array(
			'classes'       => array(
				'swiper-slide',
			),
			'thumb_classes' => array(
				'swiper-slide',
				'thumb-item-' . $index,
			),
		);

		$slide_image = $parser->getSetting('image');
		$thumb_image = $parser->getSetting('thumbImage') ?: $slide_image;

		// slide image
		switch ($parser->getSetting('type')) {
			case 'category':
				$this->settings['has_category'] = true;
				$data['image'] = false;
				$data['image2x'] = false;
				$data['thumb'] = false;
				$thumb_image = false;
				break;

			case 'product':
				$this->settings['has_product'] = true;
				$data['image'] = false;
				$data['image2x'] = false;
				$data['thumb'] = false;
				$thumb_image = false;
				break;

			case 'image':
				if ($slide_image && is_file(DIR_IMAGE . $slide_image)) {
					$data['image'] = $this->journal3_image->resize($slide_image, $width, $height, $this->settings['imageDimensions']['resize']);
					$data['image2x'] = $this->journal3_image->resize($slide_image, $width * 2, $height * 2, $this->settings['imageDimensions']['resize']);
				} else {
					$data['image'] = $this->journal3_image->transparent($width, $height);
					$data['image2x'] = $this->journal3_image->transparent($width * 2, $height * 2);
				}
				break;

			case 'video':
				if ($parser->getSetting('videoHtml5Poster')) {
					$data['videoPoster'] = $this->journal3_image->resize($parser->getSetting('videoHtml5Poster'));
				} else {
					$data['videoPoster'] = null;
				}
				$data['videoSrc'] = $parser->getSetting('videoHtml5Url');
				break;

			default:
		}

		// slide thumb
		if (Arr::get($this->settings, 'thumbnails')) {
			$thumb_width = $this->settings['thumbnailsDimensions']['width'];
			$thumb_height = $this->settings['thumbnailsDimensions']['height'];

			if ($thumb_image) {
				$data['thumb'] = $this->journal3_image->resize($thumb_image, $thumb_width, $thumb_height, $this->settings['thumbnailsDimensions']['resize']);
				$data['thumb2x'] = $this->journal3_image->resize($thumb_image, $thumb_width * 2, $thumb_height * 2, $this->settings['thumbnailsDimensions']['resize']);
			} else {
				$data['thumb'] = $this->journal3_image->transparent($thumb_width, $thumb_height);
				$data['thumb2x'] = $this->journal3_image->transparent($thumb_width * 2, $thumb_height * 2);
			}
		}

		return $data;
	}

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseSubitemSettings($parser, $index) {
		$data = array(
			'classes' => array(
				'slide-' . $parser->getSetting('type'),
			),
		);

		// image
		$image = $parser->getSetting('image');

		// text
		$text = $parser->getSetting('text');

		// category
		if ($parser->getSetting('category')) {
			$this->load->model('catalog/category');

			$category = $this->model_catalog_category->getCategory((int)$parser->getSetting('category'));

			if ($category) {
				$image = $category['image'];

				if ($text) {
					$text = html_entity_decode($category[$text] ?? '', ENT_QUOTES, 'UTF-8');
				}
			}
		}

		switch ($parser->getSetting('type')) {
			case 'image':
				$data['width'] = $parser->getSetting('imageDimensions.width') ?: $this->settings['slidesLayersImageDimensions']['width'];
				$data['height'] = $parser->getSetting('imageDimensions.height') ?: $this->settings['slidesLayersImageDimensions']['height'];
				$data['image'] = $this->journal3_image->resize($image, $data['width'], $data['height'], $parser->getSetting('imageDimensions.resize'));
				$data['image2x'] = $this->journal3_image->resize($image, $data['width'] * 2, $data['height'] * 2, $parser->getSetting('imageDimensions.resize'));

				break;
			case 'text':
				$data['text'] = $text;

				if ($text === 'category_description') {
					$this->settings['has_category_description'] = true;
				}

				if ($text === 'page_title') {
					$this->settings['has_page_title'] = true;
				}

				break;
			case 'video':
				switch ($parser->getSetting('videoType')) {
					case 'html5':
						$data['videoSrc'] = $parser->getSetting('videoHtml5Url');
						break;

					case 'youtube':
						$data['videoSrc'] = Str::YoutubeId($parser->getSetting('videoYoutubeUrl'));
						break;

					case 'vimeo':
						$data['videoSrc'] = Str::VimeoId($parser->getSetting('videoVimeoUrl'));
						break;
				}
				break;
		}

		return $data;
	}

	protected function beforeRender() {

		if (!empty($this->settings['items']) && $this->settings['loop'] && $this->settings['slidesPerView'] === 'auto') {
			$this->settings['options']['loopedSlides'] = count($this->settings['items']);
		}

		if ($this->settings['dynamic']) {
			$this->settings['items'] = [];
			$this->settings['images'] = [];

			if ($this->settings['dynamicPath'] && is_dir(DIR_IMAGE . 'catalog/' . $this->settings['dynamicPath'])) {
				$files = glob(DIR_IMAGE . 'catalog/' . $this->settings['dynamicPath'] . '/*');

				natsort($files);

				foreach ($files as $index => $file) {
					$pathinfo = pathinfo($file);

					if (empty($pathinfo['extension']) || !in_array(strtolower($pathinfo['extension']), ['png', 'jpg', 'jpeg', 'gif'])) {
						continue;
					}

					$image = str_replace(DIR_IMAGE, '', $file);

					$this->settings['items'][] = [
						'type'          => 'image',
						'classes'       => array(
							'swiper-slide',
						),
						'thumb_classes' => array(
							'swiper-slide',
							'thumb-item-' . $index,
						),
						'alt'           => $pathinfo['filename'],
						'image'         => $this->journal3_image->resize($image, $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']),
						'image2x'       => $this->journal3_image->resize($image, $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']),
						'thumb'         => $this->journal3_image->resize($image, $this->settings['thumbnailsDimensions']['width'], $this->settings['thumbnailsDimensions']['height'], $this->settings['thumbnailsDimensions']['resize']),
						'thumb2x'       => $this->journal3_image->resize($image, $this->settings['thumbnailsDimensions']['width'] * 2, $this->settings['thumbnailsDimensions']['height'] * 2, $this->settings['thumbnailsDimensions']['resize']),
						'items'         => [],
					];
				}
			}
		}

		if (!$this->settings['items']) {
			$this->settings = null;
		}

		// title
		if (Arr::get($this->settings, 'has_page_title')) {
			$route = Arr::get($this->request->get, 'route');

			switch ($route) {
				case 'product/catalog':
					$page_title = $this->journal3->get('allProductsPageTitle');

					break;

				case 'information/information':
				case 'product/category':
				case 'product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info':
				case 'product/product':
				case 'journal3/blog':
				case 'journal3/blog/post':
					$page_title = $this->load->controller('journal3/seo' . JOURNAL3_ROUTE_SEPARATOR . 'getTags', array('title'));

					break;

				case 'product/special':
					$this->load->language('product/special');
					$page_title = $this->language->get('heading_title');

					break;

				default:
					$page_title = $this->language->get('heading_title');
			}

			if ($route === 'checkout/checkout' && $this->journal3->get('activeCheckout') === 'journal') {
				$page_title = $this->journal3->get('checkoutTitle');
			}

			foreach ($this->settings['items'] as $index => &$item) {
				foreach ($item['items'] as &$subitem) {
					if ($subitem['text'] === 'page_title') {
						$subitem['text'] = $page_title;
					}
				}
			}
		}

		if (Arr::get($this->settings, 'staticTextType') === 'title' || Arr::get($this->settings, 'static2TextType') === 'title') {
			$route = Arr::get($this->request->get, 'route');
			$title = null;

			switch ($route) {
				case 'product/catalog':
					$title = $this->journal3->get('allProductsPageTitle');

					break;

				case 'information/information':
				case 'product/category':
				case 'product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info':
				case 'product/product':
				case 'journal3/blog':
				case 'journal3/blog/post':
					$title = $this->load->controller('journal3/seo' . JOURNAL3_ROUTE_SEPARATOR . 'getTags', array('title'));

					break;

				case 'product/special':
					$this->load->language('product/special');
					$title = $this->language->get('heading_title');

					break;

				default:
					$title = $this->language->get('heading_title');
			}

			if ($route === 'checkout/checkout' && $this->journal3->get('activeCheckout') === 'journal') {
				$title = $this->journal3->get('checkoutTitle');
			}

			if ($title) {
				if (Arr::get($this->settings, 'staticTextType') === 'title') {
					$this->settings['staticText'] = $title;
				}

				if (Arr::get($this->settings, 'static2TextType') === 'title') {
					$this->settings['static2Text'] = $title;
				}
			}

		}


		// slide image
		if (Arr::get($this->settings, 'has_category') || Arr::get($this->settings, 'has_product')) {
			$image = $this->load->controller('journal3/seo' . JOURNAL3_ROUTE_SEPARATOR . 'getTags', array('image'));

			foreach ($this->settings['items'] as $index => &$item) {
				if ($item['type'] === 'category') {
					$item['image'] = $this->journal3_image->resize($image, $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']);
					$item['image2x'] = $this->journal3_image->resize($image, $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']);

					// slide thumb
					if (Arr::get($this->settings, 'thumbnails')) {
						$item['thumb'] = $this->journal3_image->resize($image, $this->settings['thumbnailsDimensions']['width'], $this->settings['thumbnailsDimensions']['height'], $this->settings['thumbnailsDimensions']['resize']);
						$item['thumb2x'] = $this->journal3_image->resize($image, $this->settings['thumbnailsDimensions']['width'] * 2, $this->settings['thumbnailsDimensions']['height'] * 2, $this->settings['thumbnailsDimensions']['resize']);
					} else {
						$item['thumb'] = false;
					}
				}

				if ($item['type'] === 'product') {
					$item['image'] = $this->journal3_image->resize($image, $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']);
					$item['image2x'] = $this->journal3_image->resize($image, $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']);

					// slide thumb
					if (Arr::get($this->settings, 'thumbnails')) {
						$item['thumb'] = $this->journal3_image->resize($image, $this->settings['thumbnailsDimensions']['width'], $this->settings['thumbnailsDimensions']['height'], $this->settings['thumbnailsDimensions']['resize']);
						$item['thumb2x'] = $this->journal3_image->resize($image, $this->settings['thumbnailsDimensions']['width'] * 2, $this->settings['thumbnailsDimensions']['height'] * 2, $this->settings['thumbnailsDimensions']['resize']);
					} else {
						$item['thumb'] = false;
					}
				}
			}
		}

		// category description
		if (Arr::get($this->settings, 'has_category_description')) {
			$category_description = $this->load->controller('journal3/seo' . JOURNAL3_ROUTE_SEPARATOR . 'getTags', array('category_description'));

			foreach ($this->settings['items'] as $index => &$item) {
				foreach ($item['items'] as &$subitem) {
					if ($subitem['text'] === 'category_description') {
						$subitem['text'] = $category_description;
					}
				}
			}
		}

		if (empty($this->settings) || empty($this->settings['items'])) {
			return null;
		}

		$this->settings['items'] = array_map(function ($item) {
			if (!empty($item['items'])) {
				$item['items_left'] = array_filter($item['items'], function ($subitem) {
					return $subitem['position'] === 'left' && (($subitem['type'] !== 'icon') || empty($subitem['iconAbsolute']));
				});

				$item['items_right'] = array_filter($item['items'], function ($subitem) {
					return $subitem['position'] === 'right' && (($subitem['type'] !== 'icon') || empty($subitem['iconAbsolute']));
				});

				$item['items_absolute'] = array_filter($item['items'], function ($subitem) {
					return !(($subitem['type'] !== 'icon') || empty($subitem['iconAbsolute']));
				});
			}

			return $item;
		}, $this->settings['items']);

		if ($this->settings['shuffle']) {
			shuffle($this->settings['items']);
		}

		if ($this->settings['width'] || $this->settings['height']) {
			$this->css .= ' .module-slider-' . $this->module_id . ' { --image-width: ' . $this->settings['width'] . '; --image-height: ' . $this->settings['height'] . '}';
			$this->css .= ' .module-slider-' . $this->module_id . ' .swiper-slide::after { --image-width: ' . $this->settings['width'] . '; --image-height: ' . $this->settings['height'] . '}';
		}
	}

	protected function afterRender() {
		$this->document->addStyle('catalog/view/theme/journal3/lib/swiper-latest/swiper-bundle-critical.min.css');
		$this->document->addScript('catalog/view/theme/journal3/js/slider.js', 'js-defer');
		$this->document->addScript('catalog/view/theme/journal3/lib/swiper-latest/swiper-bundle.min.css', 'lib-swiper-latest');
		$this->document->addScript('catalog/view/theme/journal3/lib/swiper-latest/swiper-bundle.min.js', 'lib-swiper-latest');
	}

}

class_alias('ControllerJournal3Slider', '\Opencart\Catalog\Controller\Journal3\Slider');
