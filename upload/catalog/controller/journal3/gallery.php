<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;

class ControllerJournal3Gallery extends ModuleController {

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$data = array(
			'edit'            => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'            => $parser->getSetting('name'),
			'swiper_carousel' => $parser->getSetting('gridType') === 'ipr' && $parser->getSetting('carousel'),
			'classes'         => [
				'carousel-mode'    => $parser->getSetting('gridType') === 'ipr' && $parser->getSetting('carousel'),
				'align-to-content' => $parser->getSetting('gridType') === 'auto' && $parser->getSetting('autoGridContainerAlignToContent'),
			],
			'images'          => array(),
			'options'         => array(
				'addClass'          => 'lg-' . $this->module_id,
				'colorSchemeClass'  => $parser->getSetting('color_scheme'),
				'thumbWidth'        => $parser->getSetting('popupThumbDimensions.width'),
				'thumbHeight'       => $parser->getSetting('popupThumbDimensions.height') . 'px',
//				'mode'              => $parser->getSetting('moduleGalleryMode'),
//				'download'        => $parser->getSetting('moduleGalleryDownload'),
//				'fullScreen'      => $parser->getSetting('moduleGalleryFullScreen'),
				'allowMediaOverlap' => $parser->getSetting('moduleGalleryThumbToggleStatus'),
			),
			'carouselOptions' => $this->journal3->carousel($parser->getJs(), 'carouselStyle'),
		);

		if ($this->journal3->get('performanceLazyLoadImagesStatus')) {
			$data['dummy_image'] = $this->journal3_image->transparent($parser->getSetting('thumbDimensions.width'), $parser->getSetting('thumbDimensions.height'));
		}

		return $data;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		$data = array(
			'classes' => array(
				'swiper-slide' => $this->settings['swiper_carousel'],
			),
			'alt'     => $parser->getSetting('title'),
		);

		if ($parser->getSetting('type') == 'link') {
			$link = $parser->getSetting('link');

			$data['thumb'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['thumbDimensions']['width'], $this->settings['thumbDimensions']['height'], $this->settings['thumbDimensions']['resize']);
			$data['thumb2x'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['thumbDimensions']['width'] * 2, $this->settings['thumbDimensions']['height'] * 2, $this->settings['thumbDimensions']['resize']);
			$data['popup'] = $link['href'];
		} else {
			if ($parser->getSetting('type') === 'image') {
				$data['thumb'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['thumbDimensions']['width'], $this->settings['thumbDimensions']['height'], $this->settings['thumbDimensions']['resize']);
				$data['thumb2x'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['thumbDimensions']['width'] * 2, $this->settings['thumbDimensions']['height'] * 2, $this->settings['thumbDimensions']['resize']);
				$data['popup'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['popupImageDimensions']['width'], $this->settings['popupImageDimensions']['height'], $this->settings['popupThumbDimensions']['resize']);
				$width = $this->journal3_image->width;
				$height = $this->journal3_image->height;
				if ($this->settings['popupImageDimensions']['width'] && $this->settings['popupImageDimensions']['height']) {
					$data['popup2x'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['popupImageDimensions']['width'] * 2, $this->settings['popupImageDimensions']['height'] * 2, $this->settings['popupThumbDimensions']['resize']);
				} else {
					$data['popup2x'] = null;
				}
				$data['popupThumb'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['popupThumbDimensions']['width'] * 2, $this->settings['popupThumbDimensions']['height'] * 2, $this->settings['popupThumbDimensions']['resize']);

				$this->settings['images'][] = array(
					'type'    => 'image',
					'src'     => $data['popup'],
					'srcset'  => $data['popup2x'] ? sprintf("%s 1x, %s 2x", $data['popup'], $data['popup2x']) : null,
					'width'   => $width,
					'height'  => $height,
					'thumb'   => $data['popupThumb'],
					'subHtml' => $parser->getSetting('title'),
				);
			} else {
				$data['thumb'] = $this->journal3_image->resize($parser->getSetting('videoImage'), $this->settings['thumbDimensions']['width'], $this->settings['thumbDimensions']['height'], $this->settings['thumbDimensions']['resize']);
				$data['thumb2x'] = $this->journal3_image->resize($parser->getSetting('videoImage'), $this->settings['thumbDimensions']['width'] * 2, $this->settings['thumbDimensions']['height'] * 2, $this->settings['thumbDimensions']['resize']);
				$data['popup2x'] = $this->journal3_image->resize($parser->getSetting('videoImage'), $this->settings['popupImageDimensions']['width'] * 2, $this->settings['popupImageDimensions']['height'] * 2, $this->settings['popupThumbDimensions']['resize']);
				$data['popupThumb'] = $this->journal3_image->resize($parser->getSetting('videoImage'), $this->settings['popupThumbDimensions']['width'] * 2, $this->settings['popupThumbDimensions']['height'] * 2, $this->settings['popupThumbDimensions']['resize']);

				switch ($parser->getSetting('videoType')) {
					case 'html5':
						$this->settings['images'][] = array(
							'type'    => 'html5video',
							'video'   => [
								'source' => [
									[
										'src'  => $parser->getSetting('videoHtml5Url'),
										'type' => 'video/' . pathinfo($parser->getSetting('videoHtml5Url'), PATHINFO_EXTENSION),
									],
								],
							],
							'thumb'   => $data['popupThumb'],
							'subHtml' => $parser->getSetting('title'),
						);

						break;

					case 'youtube':
						$this->settings['images'][] = array(
							'type'    => 'video',
							'src'     => $parser->getSetting('videoYoutubeUrl'),
							'thumb'   => $data['popupThumb'],
							'subHtml' => $parser->getSetting('title'),
						);

						break;

					case 'vimeo':
						$this->settings['vimeo'] = true;
						$this->settings['images'][] = array(
							'type'    => 'video',
							'src'     => $parser->getSetting('videoVimeoUrl'),
							'thumb'   => $data['popupThumb'],
							'subHtml' => $parser->getSetting('title'),
						);

						break;
				}
			}
		}

		return $data;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseSubitemSettings($parser, $index) {
		return array();
	}

	protected function beforeRender() {
		if ($this->settings['dynamic']) {
			$this->settings['items'] = [];
			$this->settings['images'] = [];

			if ($this->settings['dynamicPath'] && is_dir(DIR_IMAGE . 'catalog/' . $this->settings['dynamicPath'])) {
				$files = glob(DIR_IMAGE . 'catalog/' . $this->settings['dynamicPath'] . '/*');

				natsort($files);

				foreach ($files as $file) {
					$pathinfo = pathinfo($file);

					if (empty($pathinfo['extension']) || !in_array(strtolower($pathinfo['extension']), ['png', 'jpg', 'jpeg', 'gif'])) {
						continue;
					}

					$image = str_replace(DIR_IMAGE, '', $file);
					$video = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'] . '.mp4';

					$this->settings['items'][] = [
						'classes' => array(
							'module-item',
							'swiper-slide' => $this->settings['swiper_carousel'],
						),
						'alt'     => $pathinfo['filename'],
						'thumb'   => $this->journal3_image->resize($image, $this->settings['thumbDimensions']['width'], $this->settings['thumbDimensions']['height'], $this->settings['thumbDimensions']['resize']),
						'thumb2x' => $this->journal3_image->resize($image, $this->settings['thumbDimensions']['width'] * 2, $this->settings['thumbDimensions']['height'] * 2, $this->settings['thumbDimensions']['resize']),
					];

					if (is_file($video)) {
						$video = JOURNAL3_STATIC_URL . str_replace(DIR_IMAGE, 'image/', $video);

						$this->settings['images'][] = array(
							'type'    => 'html5video',
							'src'     => $video,
							'thumb'   => $this->journal3_image->resize($image, $this->settings['popupThumbDimensions']['width'] * 2, $this->settings['popupThumbDimensions']['height'] * 2, $this->settings['popupThumbDimensions']['resize']),
							'subHtml' => $pathinfo['filename'],
						);
					} else {
						$src = $this->journal3_image->resize($image, $this->settings['popupImageDimensions']['width'], $this->settings['popupImageDimensions']['height'], $this->settings['popupThumbDimensions']['resize']);
						$width = $this->journal3_image->width;
						$height = $this->journal3_image->height;

						if ($this->settings['popupImageDimensions']['width'] && $this->settings['popupImageDimensions']['height']) {
							$src2x = $this->journal3_image->resize($image, $this->settings['popupImageDimensions']['width'] * 2, $this->settings['popupImageDimensions']['height'] * 2, $this->settings['popupThumbDimensions']['resize']);
						} else {
							$src2x = null;
						}

						$this->settings['images'][] = array(
							'type'    => 'image',
							'src'     => $src,
							'srcset'  => $src2x ? sprintf("%s 1x, %s 2x", $src, $src2x) : null,
							'width'   => $width,
							'height'  => $height,
							'thumb'   => $this->journal3_image->resize($image, $this->settings['popupThumbDimensions']['width'] * 2, $this->settings['popupThumbDimensions']['height'] * 2, $this->settings['popupThumbDimensions']['resize']),
							'subHtml' => $pathinfo['filename'],
						);
					}
				}
			}
		}

		if ($this->settings['productImages']) {
			$this->settings['items'] = [];
			$this->settings['images'] = [];

			if ($this->journal3_document->getPageRoute() === 'product/product') {
				$images = ControllerJournal3EventProduct::getProductImages();
				$product_info = ControllerJournal3EventProduct::getProductInfo();

				foreach ($images as $image) {
					$image = $image['image'] ?? '';

					$this->settings['items'][] = [
						'classes' => array(
							'module-item',
							'swiper-slide' => $this->settings['swiper_carousel'],
						),
						'alt'     => $product_info['name'] ?? '',
						'thumb'   => $this->journal3_image->resize($image, $this->settings['thumbDimensions']['width'], $this->settings['thumbDimensions']['height'], $this->settings['thumbDimensions']['resize']),
						'thumb2x' => $this->journal3_image->resize($image, $this->settings['thumbDimensions']['width'] * 2, $this->settings['thumbDimensions']['height'] * 2, $this->settings['thumbDimensions']['resize']),
					];

					$src = $this->journal3_image->resize($image, $this->settings['popupImageDimensions']['width'], $this->settings['popupImageDimensions']['height'], $this->settings['popupThumbDimensions']['resize']);
					$width = $this->journal3_image->width;
					$height = $this->journal3_image->height;

					if ($this->settings['popupImageDimensions']['width'] && $this->settings['popupImageDimensions']['height']) {
						$src2x = $this->journal3_image->resize($image, $this->settings['popupImageDimensions']['width'] * 2, $this->settings['popupImageDimensions']['height'] * 2, $this->settings['popupThumbDimensions']['resize']);
					} else {
						$src2x = null;
					}

					$this->settings['images'][] = array(
						'type'    => 'image',
						'src'     => $src,
						'srcset'  => $src2x ? sprintf("%s 1x, %s 2x", $src, $src2x) : null,
						'width'   => $width,
						'height'  => $height,
						'thumb'   => $this->journal3_image->resize($image, $this->settings['popupThumbDimensions']['width'] * 2, $this->settings['popupThumbDimensions']['height'] * 2, $this->settings['popupThumbDimensions']['resize']),
						'subHtml' => $product_info['name'] ?? '',
					);
				}
			}
		}

		if ($this->settings['thumbsLimit'] === '') {
			$this->settings['thumbsLimit'] = count($this->settings['items']);
		}

		if (!$this->settings['items']) {
			$this->settings = null;
		}
	}

	protected function afterRender() {
		$this->document->addScript('catalog/view/theme/journal3/js/carousel.js', 'js-defer');

		if ($this->settings['swiper_carousel']) {
			$this->document->addStyle('catalog/view/theme/journal3/lib/swiper/swiper-critical.min.css');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.css', 'lib-swiper');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.js', 'lib-swiper');
		}

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

		if (!empty($this->settings['vimeo'])) {
			$this->document->addScript('https://player.vimeo.com/api/player.js', 'lib-lightgallery');
		}
	}

}

class_alias('ControllerJournal3Gallery', '\Opencart\Catalog\Controller\Journal3\Gallery');
