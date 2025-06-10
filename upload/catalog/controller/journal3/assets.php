<?php

use Journal3\Utils\Min;

class ControllerJournal3Assets extends Controller {

	public function index() {
		// jquery
		if ($this->journal3->get('performanceJQuery')) {
			$jquery = 'catalog/view/theme/journal3/lib/jquery/jquery-2.2.4.min.js';
		} else {
			$jquery = 'catalog/view/javascript/jquery/jquery-2.1.1.min.js';
		}

		$this->document->addScript($jquery);

		// font awesome
		if ($this->journal3->get('performanceFontAwesome')) {
			$font_awesome = 'catalog/view/theme/journal3/lib/font-awesome/css/font-awesome.min.css';
		} else {
			$font_awesome = 'catalog/view/javascript/font-awesome/css/font-awesome.min.css';
		}

		if ($this->journal3->get('performanceCSSDefer')) {
			$this->document->addStyle('catalog/view/theme/journal3/lib/font-awesome/css/font-awesome-critical.min.css');
			$this->journal3_document->addLink($font_awesome, 'preload', ['as' => 'style']);
			$this->journal3_document->addLink($font_awesome, 'stylesheet', ['media' => 'print', 'onload' => "this.media='all'"]);
		} else {
			$this->document->addStyle($font_awesome);
		}

		// bootstrap
		if ($this->journal3->get('performanceBootstrap')) {
			$bootstrap_css = 'catalog/view/theme/journal3/lib/bootstrap/css/bootstrap.min.css';
			$bootstrap_js = 'catalog/view/theme/journal3/lib/bootstrap/js/bootstrap.min.js';
		} else {
			$bootstrap_css = 'catalog/view/javascript/bootstrap/css/bootstrap.min.css';
			$bootstrap_js = 'catalog/view/javascript/bootstrap/js/bootstrap.min.js';
		}

		if ($this->journal3->get('performanceCSSDefer')) {
			$this->document->addStyle('catalog/view/theme/journal3/lib/bootstrap/css/bootstrap-critical.min.css');
			$this->journal3_document->addLink($bootstrap_css, 'preload', ['as' => 'style']);
			$this->journal3_document->addLink($bootstrap_css, 'stylesheet', ['media' => 'print', 'onload' => "this.media='all'"]);
		} else {
			$this->document->addStyle($bootstrap_css);
		}

		$this->document->addScript($bootstrap_js);

		// bootstrap rtl
		if ($this->language->get('direction') === 'rtl') {
			$bootstrap_rtl_css = 'catalog/view/theme/journal3/lib/bootstrap-rtl/bootstrap-rtl.min.css';

			if ($this->journal3->get('performanceCSSDefer')) {
				$this->document->addStyle('catalog/view/theme/journal3/lib/bootstrap/css/bootstrap-critical.min.css');
				$this->journal3_document->addLink($bootstrap_rtl_css, 'stylesheet', ['media' => 'print', 'onload' => "this.media='all'"]);
			} else {
				$this->document->addStyle($bootstrap_rtl_css);
			}
		}

		// oc4 datetimepicker
		if ($this->journal3_opencart->is_oc4) {
			if (version_compare(VERSION, '4.1.0.0', '<')) {
				$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment.min.js');
				$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment-with-locales.min.js');
				$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/daterangepicker.js');
				$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/daterangepicker.css');
			}
		}

		// icons
		$icons_folder = 'catalog/view/theme/journal3/icons';

		if ($this->journal3_opencart->is_oc4) {
			if (is_file(DIR_APPLICATION . 'view/theme/journal3/icons_custom/selection.json')) {
				$icons_folder = 'catalog/view/theme/journal3/icons_custom';
			}
		} else {
			if (is_file(DIR_TEMPLATE . 'journal3/icons_custom/selection.json')) {
				$icons_folder = 'catalog/view/theme/journal3/icons_custom';
			}
		}

		$icons_ver = substr(md5_file($icons_folder . '/selection.json'), 0, 10);

		$cache_key = 'icons.' . substr(md5($icons_folder), 0, 10);
		$cache = $this->journal3_cache->get($cache_key, false);

		if ($cache === false) {
			$types = ['woff2' => 'woff2', 'woff' => 'woff', 'ttf' => 'truetype', 'svg' => 'svg'];
			$src = [];

			foreach ($types as $type => $format) {
				if (is_file($icons_folder . '/fonts/icomoon.' . $type)) {
					$src[] = "url('{$this->journal3_assets->url($icons_folder . '/fonts/icomoon.' . $type, $icons_ver)}') format('{$format}')";
				}
			}

			$src = implode(',', $src);

			$cache = Min::minifyCSS("
				@font-face {
					font-family: 'icomoon';
					src: {$src};
					font-weight: normal;
					font-style: normal;
					font-display: block;
				}
				
				.icon {
					/* use !important to prevent issues with browser extensions that change fonts */
					font-family: 'icomoon' !important;
					speak: never;
					font-style: normal;
					font-weight: normal;
					font-variant: normal;
					text-transform: none;
					line-height: 1;
					-webkit-font-smoothing: antialiased;
					-moz-osx-font-smoothing: grayscale;
				}
			");

			$this->journal3_cache->set($cache_key, $cache, false);
		}

		$this->journal3_document->addCss($cache, 'icons', -2);

		if (is_file($icons_folder . '/fonts/icomoon.woff2')) {
			$icons_font = $icons_folder . '/fonts/icomoon.woff2';
		} else {
			$icons_font = $icons_folder . '/fonts/icomoon.woff';
		}

		$this->journal3_document->addLink($this->journal3_assets->url($icons_font, $icons_ver), 'preload', ['as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous']);

		if (!JOURNAL3_STATIC_URL && $this->journal3->get('performancePushIcons')) {
			$this->journal3_response->addPushAsset($this->journal3_assets->url($icons_font, $icons_ver), 'rel=preload; as=font; crossorigin');
		}

		// opencart common.js
		$this->document->addScript('catalog/view/javascript/common.js');

		// hover intent
		$this->document->addScript('catalog/view/theme/journal3/lib/hoverintent/jquery.hoverIntent.min.js', 'js-defer');

		// smoothscroll
		$this->document->addScript('catalog/view/theme/journal3/lib/smoothscroll/smoothscroll.min.js', 'lib-smoothscroll');

		// journal common.js
		$this->document->addScript('catalog/view/theme/journal3/js/common.js', 'js-defer');

		// journal.js
		$this->document->addScript('catalog/view/theme/journal3/js/journal.js', 'js-defer');

		// stepper
		$this->document->addScript('catalog/view/theme/journal3/js/stepper.js', 'js-defer');

		// countdown
		$this->document->addScript('catalog/view/theme/journal3/js/countdown.js', 'js-defer');
		$this->document->addScript('catalog/view/theme/journal3/lib/countdown/jquery.countdown.min.js', 'lib-countdown');

		// search
		$this->document->addScript('catalog/view/theme/journal3/js/search.js', 'js-defer');
		$this->document->addScript('catalog/view/theme/journal3/lib/typeahead/typeahead.jquery.min.js', 'lib-typeahead');

		// lozad
		$this->document->addScript('catalog/view/theme/journal3/lib/lozad/lozad.min.js', 'inline');

		// loadjs
		$this->document->addScript('catalog/view/theme/journal3/lib/loadjs/loadjs.min.js', 'inline');
	}

}

class_alias('ControllerJournal3Assets', '\Opencart\Catalog\Controller\Journal3\Assets');
