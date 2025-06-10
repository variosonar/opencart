<?php

use Journal3\Options\Option;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3Settings extends Controller {

	public function index() {
		Parser::setConfig('admin', $this->journal3_opencart->is_admin);
		Parser::setConfig('language_id', $this->journal3_opencart->language_id);
		Parser::setConfig('currency_id', $this->journal3_opencart->currency_id);
		Parser::setConfig('rtl', $this->journal3->is_rtl);
		Parser::setConfig('device', $this->journal3->device);
		Parser::setConfig('customer', $this->journal3_opencart->is_customer);
		Parser::setConfig('customer_group_id', $this->journal3_opencart->customer_group_id);
		Parser::setConfig('store_id', $this->journal3_opencart->store_id);
		Parser::setConfig('default_language_id', $this->journal3_opencart->default_language_id);
		Parser::setConfig('fallback_language_id', $this->journal3_opencart->fallback_language_id);

		$cache = $this->journal3_cache->get('variables.all', false);

		if ($cache === false) {
			$this->load->model('journal3/settings');

			$cache = [
				'variables' => $this->model_journal3_settings->getVariables(),
                'css'       => '',
			];

			$css = [];

			foreach ($cache['variables']['color'] ?? [] as $key => $value) {
				$key = str_replace(["__VAR__", " "], ["--", '-'], $key);
				$key = strtolower($key);

				if (is_array($value)) {
					$value = $value['color'] ?? '';
				}

				if ($value === '' || $value === null) {
					continue;
				}

//				list($r, $g, $b, $a) = sscanf($value ?? '', "rgba(%d, %d, %d, %f)");
//
//				$value = "rgba($r, $g, $b, var($key-alpha, $a))";

				$css[] = "{$key}: {$value}";
			}

			foreach ($cache['variables']['gap'] ?? [] as $key => $value) {
				$key = str_replace(["__VAR__", " "], ["--j-", '-'], $key);
				$key = strtolower($key);

				$value = $value['value'] ?? $value ?? '';

				if ($value === '' || $value === null) {
					continue;
				}

				$css[] = "{$key}: {$value}";
			}

			$cache['css'] .= ':root { ' . implode('; ', $css) . ' }';

			$this->journal3_cache->set('variables.all', $cache, false);
		}

		$this->journal3_document->addCss($cache['css'], 'variables');

		Option::setVariables($cache['variables']);

		$cache = $this->journal3_cache->get('settings');

		if ($cache === false) {
			$this->load->model('journal3/settings');

			$settings = $this->model_journal3_settings->getSettings();

			$cache = array(
				'php'   => array(),
				'js'    => array(),
				'fonts' => array(),
				'css'   => '',
			);

			// settings
			$files = array(
				'dashboard/dashboard',

				'system/system',

				'settings/active_skin',
				'settings/blog',
				'settings/custom_code',
				'settings/general',
				'settings/performance',
				'settings/seo',
			);

			$parser = new Parser($files, $settings);

			$cache['php'] += $parser->getPhp();
			$cache['js'] += $parser->getJs();
			$fonts = $parser->getFonts();
			$cache['fonts'] = Arr::merge($cache['fonts'], $fonts);
			$cache['css'] .= $parser->getCss();

			// image optimisations
			if ($parser->getSetting('performanceCompressImagesWebpStatus') && !$this->journal3_image->canOptimise('cwebp')) {
				$cache['php']['performanceCompressImagesWebpStatus'] = false;
			}

			if ($parser->getSetting('performanceCompressImagesJpegStatus') && !$this->journal3_image->canOptimise('jpeg')) {
				$cache['php']['performanceCompressImagesJpegStatus'] = false;
			}

			if ($parser->getSetting('performanceCompressImagesPngStatus') && !$this->journal3_image->canOptimise('png')) {
				$cache['php']['performanceCompressImagesPngStatus'] = false;
			}

			// static assets url
			if ($parser->getSetting('performanceCDNStatus')) {
				if ($this->journal3_request->is_https) {
					$cache['php']['staticAssetsUrl'] = $parser->getSetting('performanceCDNHttps');
				} else {
					$cache['php']['staticAssetsUrl'] = $parser->getSetting('performanceCDNHttp');
				}
			}

			// images url
			if (!empty($cache['php']['staticAssetsUrl'])) {
				$cache['php']['staticImagesUrl'] = $cache['php']['staticAssetsUrl'];
			} else if ($this->journal3_request->is_https) {
				$cache['php']['staticImagesUrl'] = $this->config->get('config_ssl');
			} else {
				$cache['php']['staticImagesUrl'] = $this->config->get('config_url');
			}

			// set cache
			$this->journal3_cache->set('settings', $cache);
		}

		$this->journal3->load($cache['php']);
		$this->journal3_document->addJs($cache['js']);
		$this->journal3_document->addFonts($cache['fonts']);
		$this->journal3_document->addCss($cache['css'], 'settings');

		define('JOURNAL3_SEO_URL_ENGINE', $this->journal3->get('performanceSeoUrlEngine'));
		define('JOURNAL3_STATIC_URL', $this->journal3->get('staticAssetsUrl'));
		define('JOURNAL3_STATIC_IMAGES_URL', $this->journal3->get('staticImagesUrl') . 'image/');

		if (!$this->journal3->is_popup && $this->journal3_opencart->is_admin && $this->journal3->get('adminEditor')) {
			$this->journal3_document->addClass('is-editor');
		}
	}

}

class_alias('ControllerJournal3Settings', '\Opencart\Catalog\Controller\Journal3\Settings');
