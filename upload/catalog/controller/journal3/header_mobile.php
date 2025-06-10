<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3HeaderMobile extends ModuleController {

	public function index($args) {
		$this->module_id = (int)Arr::get($args, 'module_id');
		$this->module_type = Arr::get($args, 'module_type');
		$this->module_args = Arr::get($args, 'module_args');

		$cache = $this->journal3_cache->get('module.' . $this->module_type . '.' . $this->module_id);

		if ($cache === false) {
			$this->module_data = $this->model_journal3_module->get($this->module_id, $this->module_type);

			if (!$this->module_data) {
				return null;
			}

			$files = glob(DIR_SYSTEM . 'library/journal3/data/settings/module/header_mobile/{*,*/*}.json', GLOB_BRACE);

			foreach ($files as &$file) {
				$file = str_replace(DIR_SYSTEM . 'library/journal3/data/settings/', '', $file);
				$file = str_replace('.json', '', $file);
			}

			$parser = new Parser($files, Arr::get($this->module_data, 'general'));

			$this->settings = array_merge_recursive(
				$parser->getPhp(),
				array(
					'mobileHeaderClasses' => array(
						'module-' . $this->module_type . '-' . $this->module_id,
						$parser->getSetting('customClass'),
					),
				),
				$this->parseGeneralSettings($parser, $this->module_id)
			);

			$custom_css = str_replace('%s', '.module-' . $this->module_type . '-' . $this->module_id, $parser->getSetting('customCss') ?? '');
			$this->css .= $parser->getCss() . ' ' . $custom_css;
			$this->js = $parser->getJs();
			$this->fonts = $parser->getFonts();

			$cache = array(
				'css'      => $this->css,
				'js'       => $this->js,
				'fonts'    => $this->fonts,
				'settings' => $this->settings,
			);

			$this->journal3_cache->set('module.' . $this->module_type . '.' . $this->module_id, $cache);
		} else {
			$this->css = $cache['css'];
			$this->js = $cache['js'];
			$this->fonts = $cache['fonts'];
			$this->settings = $cache['settings'];
		}

		$this->beforeRender();

		if ($this->settings === null) {
			return null;
		}

		return [
			'css'      => $this->css,
			'js'       => $this->js,
			'fonts'    => $this->fonts,
			'settings' => $this->settings,
		];
	}

	protected function parseGeneralSettings($parser, $module_id) {
		$logo = $this->journal3->get('logoMobile2x') ?: $this->journal3->get('logo2x') ?: $this->journal3->get('logo') ?: $this->config->get('config_logo');
		$logo2x = $logo;

		$logo_width = null;
		$logo_height = null;

		if ($logo && is_file(DIR_IMAGE . $logo)) {
			list ($logo_width, $logo_height) = getimagesize(DIR_IMAGE . $logo);

			$logo = $this->journal3_image->resize($logo, round($logo_width / 2), round($logo_height / 2));
			$logo2x = $this->journal3_image->resize($logo2x);
		} else {
			$logo = false;
			$logo2x = false;
		}

		$logoAlternate = $this->journal3->get('logoMobile2xAlternate') ?: $this->journal3->get('logo2xAlternate') ?: $this->config->get('config_logo');
		$logo2xAlternate = $logoAlternate;

		$logoAlternate_width = null;
		$logoAlternate_height = null;

		if ($logoAlternate && is_file(DIR_IMAGE . $logoAlternate)) {
			list ($logoAlternate_width, $logoAlternate_height) = getimagesize(DIR_IMAGE . $logoAlternate);

			$logoAlternate = $this->journal3_image->resize($logoAlternate, round($logoAlternate_width / 2), round($logoAlternate_height / 2));
			$logo2xAlternate = $this->journal3_image->resize($logo2xAlternate);
		} else {
			$logoAlternate = false;
			$logo2xAlternate = false;
		}

		return [
			'mobileHeaderType'             => str_replace('header_mobile_', '', $this->module_type),
			'mobile_logo_src'              => $logo,
			'mobile_logo_src2x'            => $logo2x,
			'mobile_logo_width'            => $logo_width,
			'mobile_logo_height'           => $logo_height,
			'mobile_logo_alternate_src'    => $logoAlternate,
			'mobile_logo_alternate_src2x'  => $logo2xAlternate,
			'mobile_logo_alternate_width'  => $logoAlternate_width,
			'mobile_logo_alternate_height' => $logoAlternate_height,
		];
	}

	protected function parseItemSettings($parser, $index) {
	}

	protected function parseSubitemSettings($parser, $index) {
	}

}

class_alias('ControllerJournal3HeaderMobile', '\Opencart\Catalog\Controller\Journal3\HeaderMobile');
