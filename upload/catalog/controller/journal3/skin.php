<?php

use Journal3\Options\Color;
use Journal3\Options\ColorScheme;
use Journal3\Options\Option;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3Skin extends Controller {

	public function index() {
		$cache = $this->journal3_cache->get('skin');

		if ($cache === false) {
			$this->load->model('journal3/settings');

			$settings = $this->model_journal3_settings->getSettings();

			$cache = array(
				'php'   => array(),
				'js'    => array(),
				'fonts' => array(),
				'css'   => '',
			);

			$files = array(
				'skin/blog/post',
				'skin/blog/posts',

				'skin/footer/general',

				'skin/global/countdown',
				'skin/global/general',
				'skin/global/notification',
				'skin/global/stepper',
				'skin/global/quickview',
				'skin/global/ripple',

				'skin/header/general',

				'skin/page/account',
				'skin/page/cart',
				'skin/page/category',
				'skin/page/checkout',
				'skin/page/compare',
				'skin/page/contact',
				'skin/page/information',
				'skin/page/maintenance',
				'skin/page/manufacturers',
				'skin/page/search',
				'skin/page/sitemap',
				'skin/page/wishlist',

				'skin/product/general',

				'skin/products/general',

				'skin/image_dimensions',

				'skin/catalog_mode',
			);

			$parser = new Parser($files, $settings);

			$cache['php'] += $parser->getPhp();
			$cache['js'] += $parser->getJs();
			$fonts = $parser->getFonts();
			$cache['fonts'] = Arr::merge($cache['fonts'], $fonts);
			$cache['css'] .= $parser->getCss();

			// font_size and gaps
			$__vars = ['font_size', 'gap'];

			$all_variables = Option::getVariables();

			foreach ($__vars as $__var) {
				$fonts = $all_variables[$__var] ?? [];
				$css = [];

				foreach ($fonts as $name => $font) {
					$name = Option::varName($__var, str_replace('__VAR__', '', $name));

					if (is_scalar($font)) {
						$value = trim($font);

						if (strlen($value) > 0 && is_numeric($value)) {
							$value = $value . 'px';
						}

						$css['_'][] = $name . ': ' . $value;
					} else {
						$value = trim($font['value'] ?? '');

						if (strlen($value) > 0 && is_numeric($value)) {
							$value = $value . 'px';
						}

						$css['_'][] = $name . ': ' . $value;

						foreach ($font['value_multi'] ?? [] as $value) {
							$min = Option::parseBreakpoint(Arr::get($value, 'min'));
							$max = Option::parseBreakpoint(Arr::get($value, 'max'));
							$value = Arr::get($value, 'value');

							if (strlen($value) > 0 && is_numeric($value)) {
								$value = $value . 'px';
							}

							if ($value && ($min || $max)) {
								$css[$min . '_' . $max][] = $name . ': ' . $value;
							}
						}
					}
				}

				uksort($css, function ($a, $b) {
					$a = explode('_', $a)[1] ?: 999999;
					$b = explode('_', $b)[1] ?: 999999;

					if ($a == $b) {
						return 0;
					}

					return $a < $b ? 1 : -1;
				});

				foreach ($css as $media => $value) {
					$media = explode('_', $media);

					$value = ":root {" . implode("; ", $value) . "}";

					if ($media[0] && $media[1]) {
						$value = "@media (min-width: {$media[0]}px) and (max-width: {$media[1]}px) {{$value}}";
					} else if ($media[0]) {
						$value = "@media (min-width: {$media[0]}px) {{$value}}";
					} else if ($media[1]) {
						$value = "@media (max-width: {$media[1]}px) {{$value}}";
					}

					$cache['css'] .= $value . PHP_EOL;
				}
			}

			// color scheme
			$all_variables = Option::getVariables();
			$color_schemes = $all_variables['color_scheme'] ?? [];
			$color_scheme = $parser->getSetting('color_scheme') ?: ($color_schemes ? ColorScheme::scheme(str_replace('__VAR__', '', array_key_first($color_schemes))) : '');

			$color_scheme_css = '';

			foreach ($color_schemes as $mode => $colors) {
				$css = [];
				$mode = ColorScheme::scheme(str_replace('__VAR__', '', $mode));

				foreach ($colors as $var => $color) {
					$clr = Color::colorValue($color);

					if ($clr) {
						list($r, $g, $b, $a) = sscanf($clr ?? '', "rgba(%d, %d, %d, %f)");
						$clr = \Journal3\Options\RGBAtoHSL([
							'r' => $r,
							'g' => $g,
							'b' => $b,
							'a' => $a,
						]);
						$css[] = Option::varName('color-scheme', $var) . '-h: ' . $clr['h'];
						$css[] = Option::varName('color-scheme', $var) . '-s: ' . $clr['s'] . '%';
						$css[] = Option::varName('color-scheme', $var) . '-l: ' . $clr['l'] . '%';
						$css[] = Option::varName('color-scheme', $var) . '-a: ' . $a;
					}
				}

				if ($mode === $color_scheme) {
					$color_scheme_css .= ':root, .dropdown-menu:not([class*="color-scheme-"]):not(.dropdown-menu[class*="color-scheme-"] .dropdown-menu), .tt-menu' . ', .' . $mode . ' { ' . implode('; ', $css) . ' }' . PHP_EOL;
				} else {
					$color_scheme_css .= '.' . $mode . ' { ' . implode('; ', $css) . ' }' . PHP_EOL;
				}
			}

			$cache['css'] .= $color_scheme_css;

			// placeholder
			$cache['php']['placeholder'] = $parser->getSetting('placeholder');

			// footer mobile
			if ($this->journal3->is_mobile) {
				if ($data = Arr::get($cache, 'php.footerMenuPhone')) {
					$cache['php']['footerMenu'] = $data;
				}
			}

			// account link
			$cache['js']['loginUrl'] = $this->url->link('account/login', '', true);

			// checkout link
			$cache['js']['checkoutUrl'] = $this->url->link('checkout/checkout', '', true);

			// set cache
			$this->journal3_cache->set('skin', $cache);
		}

		$this->journal3->load($cache['php']);
		$this->journal3_document->addJs($cache['js']);
		$this->journal3_document->addFonts($cache['fonts']);
		$this->journal3_document->addCss($cache['css'], 'settings');

		// image dimensions
		$image_dimensions = array_keys(json_decode(file_get_contents(DIR_SYSTEM . 'library/journal3/data/settings/skin/image_dimensions.json'), true));

		foreach ($image_dimensions as $image_dimension) {
			$dimensions = $this->journal3->get($image_dimension);

			$this->config->set(str_replace('image_dimensions_', 'theme_journal3_image_', $image_dimension) . '_width', (int)$dimensions['width']);
			$this->config->set(str_replace('image_dimensions_', 'theme_journal3_image_', $image_dimension) . '_height', (int)$dimensions['height']);
		}

		// other settings
		$this->config->set('theme_journal3_product_limit', $this->journal3->get('productLimit'));
		$this->config->set('theme_journal3_product_description_length', $this->journal3->get('productDescriptionLimit'));

		$this->config->set('config_pagination', (int)$this->journal3->get('productLimit'));
		$this->config->set('config_product_description_length', (int)$this->journal3->get('productDescriptionLimit'));

		// active skin
		$this->journal3_document->addClass('skin-' . $this->journal3->get('active_skin'));

		// boxed layout
		if ($this->journal3->get('globalPageBoxedLayout') === 'boxed') {
			$this->journal3_document->addClass('boxed-layout');
		}

		// default view
		if (isset($this->request->cookie['view'])) {
			$this->journal3->set('globalProductView', $this->request->cookie['view']);
		}

		// old browser
		if ($this->journal3->get('oldBrowserStatus') && in_array('ie', $this->journal3->browser_classes)) {
			$this->journal3->set('oldBrowserChrome', $this->journal3_image->resize('catalog/journal3/misc/chrome.png'));
			$this->journal3->set('oldBrowserFirefox', $this->journal3_image->resize('catalog/journal3/misc/firefox.png'));
			$this->journal3->set('oldBrowserEdge', $this->journal3_image->resize('catalog/journal3/misc/edge.png'));
			$this->journal3->set('oldBrowserOpera', $this->journal3_image->resize('catalog/journal3/misc/opera.png'));
			$this->journal3->set('oldBrowserSafari', $this->journal3_image->resize('catalog/journal3/misc/safari.png'));
		} else {
			$this->journal3->set('oldBrowserStatus', false);
		}

		// catalog mode
		if (!$this->journal3->get('catalogLanguageStatus')) {
			$this->journal3_document->addClass('no-language');
		}

		if (!$this->journal3->get('catalogCurrencyStatus')) {
			$this->journal3_document->addClass('no-currency');
		}

		if (!$this->journal3->get('catalogSearchStatus')) {
			$this->journal3_document->addClass('no-search');
		}

		if (!$this->journal3->get('catalogMiniCartStatus')) {
			$this->journal3_document->addClass('no-mini-cart');
		}

		if (!$this->journal3->get('catalogCartStatus')) {
			$this->journal3_document->addClass('no-cart');
		}

		if (!$this->journal3->get('catalogWishlistStatus')) {
			$this->journal3_document->addClass('no-wishlist');
		}

		if (!$this->journal3->get('catalogCompareStatus')) {
			$this->journal3_document->addClass('no-compare');
		}

		$this->journal3_document->addClass($this->journal3->get('color_scheme'));
	}

}

class_alias('ControllerJournal3Skin', '\Opencart\Catalog\Controller\Journal3\Skin');
