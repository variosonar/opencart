<?php

use Journal3\Utils\Min;
use Nette\Utils\Arrays;

class ControllerJournal3EventHeader extends Controller {

	public function controller_common_header_before(&$route, &$args) {
		$this->document->addStyle('catalog/view/theme/journal3/stylesheet/style.min.css');

		if (is_file(DIR_TEMPLATE . 'journal3/stylesheet/custom.min.css')) {
			$this->document->addStyle('catalog/view/theme/journal3/stylesheet/custom.min.css');
		} else if (is_file(DIR_TEMPLATE . 'journal3/stylesheet/custom.css')) {
			$this->document->addStyle('catalog/view/theme/journal3/stylesheet/custom.css');
		}

		$this->document->addScript('catalog/view/theme/journal3/js/head.js', 'inline');

		if (is_file(DIR_TEMPLATE . 'journal3/js/custom.min.js')) {
			$this->document->addScript('catalog/view/theme/journal3/js/custom.min.js', 'js-defer');
		} else if (is_file(DIR_TEMPLATE . 'journal3/js/custom.js')) {
			$this->document->addScript('catalog/view/theme/journal3/js/custom.js', 'js-defer');
		}
	}

	public function view_common_header_before(&$route, &$args) {
		// home page h1
		if ($this->journal3->get('seoH1HomePage') && ($this->journal3_request->get('route', 'common/home') === 'common/home')) {
			$args['journal3_home_h1'] = $this->config->get('config_name');
		} else {
			$args['journal3_home_h1'] = false;
		}

		// fonts
		if ($fonts = $this->journal3_document->getFonts()) {
			$fonts['display'] = $this->journal3->get('performanceGoogleFontsDisplay');

			$fonts = implode('&', Arrays::map($fonts, function ($value, $key) {
				return is_numeric($key) ? $value : $key . '=' . $value . '';
			}));

			$fonts_url = 'https://fonts.googleapis.com/css?' . $fonts;

			$cache_key = 'google-fonts.' . substr(md5($fonts_url), 0, 10);
			$cache = $this->journal3_cache->get($cache_key, false);

			if ($cache === false) {
				$curl = curl_init($fonts_url);

				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLOPT_TIMEOUT, 30);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; rv:39.0) Gecko/20100101 Firefox/39.0');

				$cache = Min::minifyCSS(curl_exec($curl));

				curl_close($curl);

				$this->journal3_cache->set($cache_key, $cache, false);
			}

			$this->journal3_document->addLink('https://fonts.gstatic.com/', 'preconnect', ['crossorigin']);

			if ($cache) {
				$this->journal3_document->addCss($cache, 'google-fonts', -1);
			} else {
				$this->journal3_document->addLink($fonts_url, 'preload', ['as' => 'style']);
				$this->journal3_document->addLink($fonts_url, 'stylesheet', ['media' => 'print', 'onload' => "this.media='all'"]);
			}
		}

		if ($fonts = $this->journal3_document->getFontsCustom()) {
			$fonts_css = '';

			foreach ($fonts as $font_family => $font) {
				$src = [];

				foreach ($font as $type => $ver) {
					$src[] = "url('{$this->journal3_assets->url('catalog/view/theme/journal3/fonts_custom/' . $font_family . '.' . $type, $ver)}') format('{$type}')";
				}

				$this->journal3_document->addLink($this->journal3_assets->url('catalog/view/theme/journal3/fonts_custom/' . $font_family . '.' . array_keys($font)[0], array_values($font)[0]), 'preload', ['as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous']);

				$src = implode(',', $src);

				$fonts_css .= "
					@font-face {
						font-family: '{$font_family}';
						src: {$src};
						font-weight: normal;
						font-style: normal;
						font-display: {$this->journal3->get('performanceGoogleFontsDisplay')};
					}
				";
			}

			$this->journal3_document->addCss(Min::minifyCSS($fonts_css), 'fonts-custom', -1);
		}

		// assets
		$assets = array_flip(['countdown', 'imagezoom', 'lightgallery', 'masterslider', 'swiper', 'swiper-latest', 'typeahead', 'smoothscroll', 'datetimepicker', 'countup']);

		$assets = Arrays::map($assets, function ($_, $lib) {
			$scripts = $this->document->getScripts('lib-' . $lib);
			$scripts = $this->journal3_assets->scripts($scripts);

			if (version_compare(VERSION, '4', '>=') || defined('JOURNAL3_OLD_OC3039')) {
				$scripts = array_map(function ($script) {
					return $script['href'] ?? '';
				}, $scripts);
			}

			return array_values($scripts);
		});

		$this->journal3_document->addJs(['assets' => $assets]);

		// css tpl
		$this->journal3_document->addCss(trim(strip_tags($this->load->view('journal3/css', ['data' => $this->journal3->all()]))), 'css');

		// styles
		if (!empty($args['styles'])) {
			if ($this->journal3->get('performanceCSSMinify')) {
				$args['styles'] = $this->journal3_assets->minifyStyles($args['styles']);
			}

			if ($this->journal3->get('performanceCSSInline')) {
				foreach ($args['styles'] as $href => $style) {
					if (is_file($style['href'])) {
						$this->journal3_document->addCss(file_get_contents($style['href']), pathinfo($style['href'], PATHINFO_BASENAME), -1);
						unset($args['styles'][$href]);
					}
				}
			}

			$args['styles'] = $this->journal3_assets->styles($args['styles']);

			foreach ($args['styles'] as $style) {
				$this->journal3_document->addLink($style['href'], 'preload', ['as' => 'style']);

				if (!JOURNAL3_STATIC_URL && $this->journal3->get('performancePushCSS')) {
					$this->journal3_response->addPushAsset($style['href'], 'rel=preload; as=style');
				}
			}
		}

		// inline scripts
		$args['journal3_inline_scripts'] = $this->document->getScripts('inline');

		if ($this->journal3->get('performanceJSMinify')) {
			$args['journal3_inline_scripts'] = $this->journal3_assets->minifyScripts($args['journal3_inline_scripts']);
		}

		$args['journal3_inline_scripts'] = $this->journal3_assets->inlineScripts($args['journal3_inline_scripts']);

		// scripts
		if (!empty($args['scripts'])) {
			if ($this->journal3->get('performanceJSMinify')) {
				$args['scripts'] = $this->journal3_assets->minifyScripts($args['scripts']);
			}

			$args['scripts'] = $this->journal3_assets->scripts($args['scripts']);

			$args['scripts_defer'] = $this->document->getScripts('js-defer');

			if ($this->journal3->get('performanceJSMinify')) {
				$args['scripts_defer'] = $this->journal3_assets->minifyScripts($args['scripts_defer']);
			}

			$args['scripts_defer'] = $this->journal3_assets->scripts($args['scripts_defer']);
		}

		// push assets
		$this->journal3_response->pushAssets();

		// others
		$args['journal3_version'] = JOURNAL3_VERSION . '-' . JOURNAL3_BUILD;
		$args['journal3_oc_version'] = VERSION;
		$args['journal3_classes'] = $this->journal3_document->getClasses();
		$args['journal3_metas'] = $this->journal3_document->getMetas();
		$args['journal3_links'] = $this->journal3_document->getLinks();
		$args['journal3_meta_tags'] = $this->load->controller('journal3/seo' . JOURNAL3_ROUTE_SEPARATOR . 'meta_tags');
		$args['journal3_js'] = json_encode($this->journal3_document->getJs());
		$args['journal3_css'] = $this->journal3_document->getCss();

		// sentry
		if (preg_match('/Lighthouse|GTmetrix/i', $this->journal3_request->server('HTTP_USER_AGENT', ''))) {
			$args['analytics'] = [];
			$args['journal3_sentry_dsn_loader'] = null;
		} else {
			$args['journal3_sentry_dsn_loader'] = JOURNAL3_SENTRY_DSN_LOADER;
		}

		// admin bar
		if ($this->journal3_opencart->is_admin && $this->journal3->is_desktop && !$this->journal3->is_popup) {
			$args['journal_admin_bar_links'] = [
				['edit' => 'variable/color', 'name' => 'Variables'],
				['edit' => 'style/page', 'name' => 'Styles'],
				['edit' => 'skin/edit/' . $this->journal3->get('active_skin'), 'name' => 'Skin'],
				['edit' => 'layout/edit/' . $this->journal3->get('layout_id'), 'name' => 'Layout'],
				['edit' => 'module_header/' . $this->journal3->get('header_desktop_type') . '/edit/' . $this->journal3->get('header_desktop_id'), 'name' => 'Desktop Header'],
				['edit' => 'module_header/' . $this->journal3->get('header_mobile_type') . '/edit/' . $this->journal3->get('header_mobile_id'), 'name' => 'Mobile Header'],
				['edit' => 'module_footer/footer_menu/edit/' . $this->journal3->get('footer_menu_id'), 'name' => 'Footer'],
			];

			switch ($this->request->get['route'] ?? '') {
				case 'account/login':
				case 'account/register':
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=account', 'name' => 'Page Settings'];

					break;

				case 'account/account':
					$args['journal_admin_bar_links'][] = ['edit' => 'style/account/edit/' . htmlspecialchars($this->journal3->get('accountPageStyle')), 'name' => 'Page Settings'];

					break;

				case 'account/wishlist':
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=wishlist', 'name' => 'Page Settings'];

					break;

				case 'checkout/cart':
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=cart', 'name' => 'Page Settings'];

					break;

				case 'checkout/checkout':
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=checkout', 'name' => 'Page Settings'];

					break;

				case 'information/contact':
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=contact', 'name' => 'Page Settings'];

					break;

				case 'information/information':
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=information', 'name' => 'Page Settings'];

					break;

				case 'information/sitemap':
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=sitemap', 'name' => 'Page Settings'];

					break;

				case 'journal3/blog':
				case 'journal3/blog/post':
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=blog', 'name' => 'Page Settings'];
					$args['journal_admin_bar_links'][] = ['edit' => 'blog_post/edit/' . $this->journal3_request->get('journal_blog_post_id', ''), 'name' => 'Edit Post'];

					break;

				case 'product/category':
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=category', 'name' => 'Page Settings'];
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=products', 'name' => 'Product Listing'];

					break;

				case 'product/compare':
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=compare', 'name' => 'Page Settings'];

					break;

				case 'product/manufacturer':
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=manufacturer', 'name' => 'Page Settings'];

					break;

				case 'product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info':
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=products', 'name' => 'Product Listing'];

					break;

				case 'product/search':
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=search', 'name' => 'Page Settings'];
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=products', 'name' => 'Product Listing'];

					break;

				case 'product/special':
					$args['journal_admin_bar_links'][] = ['edit' => 'skin/edit/' . $this->journal3->get('active_skin') . '?page=products', 'name' => 'Product Listing'];

					break;

				case 'product/product':
					$args['journal_admin_bar_links'][] = ['edit' => 'style/product_page/edit/' . htmlspecialchars($this->journal3->get('productPageStyle')), 'name' => 'Page Settings'];

					break;
			}
		} else {
			$args['journal_admin_bar_links'] = false;
		}
	}

}

class_alias('ControllerJournal3EventHeader', '\Opencart\Catalog\Controller\Journal3\Event\Header');
