<?php

use Journal3\Utils\Arr;

class ControllerJournal3Journal extends Controller {

	public function __construct($registry) {
		parent::__construct($registry);

		$this->load->model('journal3/journal');
		$this->load->model('journal3/setting');
		$this->load->model('journal3/module');

		$this->load->language('error/permission');
	}

	public function index() {
		// Opencart modifications refreshed check
		if (!defined('JOURNAL3_INSTALLED') || (JOURNAL3_INSTALLED !== JOURNAL3_VERSION)) {
			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');

			$data['error_title'] = 'Journal Installation Error';

			if (version_compare(VERSION, '4', '>=')) {
				$data['error_message'] = 'journal_3.ocmod.zip extension is not installed or updated to the latest version.';
			} else {
				$data['error_message'] = 'Make sure you have refreshed Opencart Modifications.';
			}

			$this->response->setOutput($this->load->view('journal3/error', $data));

			return;
		}

		if (!$this->model_journal3_journal->isInstalled()) {
			$this->model_journal3_journal->install();
		} else {
			$this->model_journal3_journal->database();
		}

		// language
		$this->load->language('journal3/journal3');

		// title
		$this->document->setTitle($this->language->get('Journal'));

		// summernote / ckeditor
		// define('JOURNAL3_CKEDITOR', '//cdn.ckeditor.com/4.10.0/standard/ckeditor.js');
		// define('JOURNAL3_CKEDITOR', '//cdn.ckeditor.com/4.10.0/basic/ckeditor.js');
		// define('JOURNAL3_CKEDITOR', '//cdn.ckeditor.com/4.10.0/full/ckeditor.js');
		if (defined('JOURNAL3_CKEDITOR')) {
			$this->document->addScript(JOURNAL3_CKEDITOR);
		} else {
			if ($this->journal3_opencart->is_oc4) {
				$this->document->addScript('view/javascript/ckeditor/ckeditor.js');
				$this->document->addScript('view/javascript/ckeditor/adapters/jquery.js');
			} else {
				$this->document->addStyle('view/javascript/summernote/summernote.css');
				$this->document->addScript('view/javascript/summernote/summernote.js');
				if ($this->journal3_opencart->is_oc3) {
					$this->document->addScript('view/javascript/summernote/summernote-image-attributes.js');
				}
				$this->document->addScript('view/javascript/summernote/opencart.js');
			}
		}

		// font loader
		$this->document->addScript('https://ajax.googleapis.com/ajax/libs/webfont/1.4.7/webfont.js');
		$this->document->addStyle('https://fonts.googleapis.com/css?family=Montserrat:300,400,600');

		// codemirror
		$this->document->addStyle('view/javascript/journal3/lib/codemirror/lib/codemirror.css');
		$this->document->addStyle('view/javascript/journal3/lib/codemirror/addon/dialog/dialog.css');
		$this->document->addScript('view/javascript/journal3/lib/codemirror/lib/codemirror.js');
		$this->document->addScript('view/javascript/journal3/lib/codemirror/mode/css/css.js');
		$this->document->addScript('view/javascript/journal3/lib/codemirror/mode/javascript/javascript.js');
		$this->document->addScript('view/javascript/journal3/lib/codemirror/mode/xml/xml.js');
		$this->document->addScript('view/javascript/journal3/lib/codemirror/mode/htmlmixed/htmlmixed.js');
		$this->document->addScript('view/javascript/journal3/lib/codemirror/addon/dialog/dialog.js');
		$this->document->addScript('view/javascript/journal3/lib/codemirror/addon/search/searchcursor.js');
		$this->document->addScript('view/javascript/journal3/lib/codemirror/addon/search/search.js');

		// custom fonts
		$custom_fonts = [];

		$files = glob(DIR_CATALOG . 'view/theme/journal3/fonts_custom/*.{woff,woff2}', GLOB_BRACE);

		foreach ($files as $file) {
			$font = pathinfo($file, PATHINFO_FILENAME);

			if (is_file(DIR_CATALOG . 'view/theme/journal3/fonts_custom/' . $font . '.woff2')) {
				$custom_fonts[$font]['woff2'] = substr(md5(DIR_CATALOG . 'view/theme/journal3/fonts_custom/' . $font . '.woff2'), 0, 10);
			}

			if (is_file(DIR_CATALOG . 'view/theme/journal3/fonts_custom/' . $font . '.woff')) {
				$custom_fonts[$font]['woff'] = substr(md5(DIR_CATALOG . 'view/theme/journal3/fonts_custom/' . $font . '.woff'), 0, 10);
			}
		}

		$fonts_css = '';

		foreach ($custom_fonts as $font_family => $font) {
			$src = [];

			foreach ($font as $type => $ver) {
				$src[] = "url('../catalog/view/theme/journal3/fonts_custom/{$font_family}.{$type}?{$ver}') format('{$type}')";
			}

			$src = implode(',', $src);

			$fonts_css .= "
					@font-face {
						font-family: '{$font_family}';
						src: {$src};
						font-weight: normal;
						font-style: normal;
						font-display: block;
					}
				";
		}

		$data['journal3_custom_fonts_css'] = $fonts_css;

		// icons
		if (is_file(DIR_CATALOG . 'view/theme/journal3/icons_custom/selection.json')) {
			$icons_folder = '../catalog/view/theme/journal3/icons_custom';
		} else {
			$icons_folder = '../catalog/view/theme/journal3/icons';
		}

		$icons_ver = substr(md5_file($icons_folder . '/selection.json'), 0, 10);

		$this->document->addStyle($icons_folder . '/style.css?ver=' . $icons_ver);

		// admin icons
		$this->document->addStyle('view/javascript/journal3/icons/style.css?v=' . (JOURNAL3_DEBUG ? time() : JOURNAL3_BUILD));

		// journal3
		if (!empty($this->request->get['j_edit'])) {
			$url = '&j_edit=1';
		} else {
			$url = '';
		}

		$this->document->addScript($this->journal3_url->admin_link('journal3/journal' . JOURNAL3_ROUTE_SEPARATOR . 'js', $url));

		// journal3 assets
		$this->document->addStyle('view/javascript/journal3/dist/journal.css?v=' . (JOURNAL3_DEBUG ? time() : JOURNAL3_BUILD));
		$this->document->addScript('view/javascript/journal3/dist/journal.js?v=' . (JOURNAL3_DEBUG ? time() : JOURNAL3_BUILD));

		// version
		$data['j3v'] = JOURNAL3_VERSION;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('journal3/journal3', $data));
	}

	public function js() {
		$data = array();

		// admin options
		if ($this->journal3_opencart->is_oc4) {
			$data['j3limit'] = (int)$this->config->get('config_pagination_admin');
		} else {
			$data['j3limit'] = (int)$this->config->get('config_limit_admin');
		}

		$data['j3ltr'] = defined('JOURNAL3_LTR_ADMIN') && JOURNAL3_LTR_ADMIN === true;

		// php options
		$data['php_version'] = PHP_VERSION;
		$data['php_opcache'] = $this->opcache();
		$data['php_ini'] = str_replace('\\', '/', php_ini_loaded_file());
		$data['php_upload_max_filesize'] = ini_get('upload_max_filesize');
		$data['php_post_max_size'] = ini_get('post_max_size');

		// cache
		if (version_compare(VERSION, '3', '>=') && version_compare(VERSION, '4', '<')) {
			$data['template_cache'] = $this->config->get('developer_theme') ? 'Yes' : 'No';
		} else {
			$data['template_cache'] = false;
		}

		// older vqmod version warning (v.2.6.4 or lower) - it affects performance if DIR_STORAGE is outside public_html
		if (
			version_compare(VERSION, '3', '>=')
			&& version_compare(VERSION, '4', '<')
			&& class_exists('\VQMod')
			&& version_compare(\VQMod::$_vqversion, '2.6.4', '<=')
			&& !(strpos(DIR_STORAGE, realpath(DIR_SYSTEM . '../')) === 0)
		) {
			$data['vqmod_warning'] = true;
		} else {
			$data['vqmod_warning'] = false;
		}

		// version
		$data['ocv'] = VERSION;
		$data['j3ver'] = JOURNAL3_VERSION . '-' . JOURNAL3_BUILD;
		$data['j3v'] = JOURNAL3_VERSION;
		$data['j3ov'] = $this->journal3_opencart->ver * 10;
		$data['j3env'] = JOURNAL3_ENV;
		$data['j3export'] = JOURNAL3_EXPORT;
		$data['j3sep'] = JOURNAL3_ROUTE_SEPARATOR;

		// webpack PORT
		$data['PORT'] = $_ENV['PORT'] ?? 4444;

		// base url
		if (!empty($this->request->get['j_edit'])) {
			$url = '&j_edit=1';
		} else {
			$url = '';
		}

		$data['base'] = str_replace('&amp;', '&', $this->journal3_url->admin_link('journal3/journal', $url));

		// session token, needed for ajax calls
		if ($this->journal3_opencart->is_oc2) {
			$data['token'] = $this->session->data['token'];
		} else {
			$data['token'] = $this->session->data['user_token'];
		}

		// available stores
		$dashboard = (array)$this->model_journal3_setting->get(0, array('dashboard'));
		$this->load->model('setting/store');
		$stores = $this->model_setting_store->getStores();
		$stores = array_map(function ($store) {
			$store['domain'] = $this->domain($store);

			unset($store['url']);
			unset($store['ssl']);

			return $store;
		}, $stores);

		array_unshift($stores, array(
			'store_id'       => '0',
			'name'           => $this->config->get('config_name'),
			'domain'         => parse_url(HTTPS_CATALOG ?: HTTP_CATALOG, PHP_URL_HOST),
			'dashboard_user' => $dashboard['dashboard']['dashboard_user_0'] ?? '',
			'dashboard_key'  => $dashboard['dashboard']['dashboard_key_0'] ?? '',
		));

		$data['stores'] = $stores;

		// domain id
		if (function_exists('php_uname')) {
			$data['domain_id'] = substr(md5(php_uname('a')), 0, 16);
		} else {
			$data['domain_id'] = substr(md5(HTTPS_CATALOG ?: HTTP_CATALOG), 0, 16);
		}

		// custom fields
		$this->load->model('customer/custom_field');
		$custom_fields = $this->model_customer_custom_field->getCustomFields();

		$data['custom_fields'] = array(
			'account' => array(),
			'address' => array(),
		);

		foreach ($custom_fields as $custom_field) {
			$data['custom_fields'][$custom_field['location']][] = array(
				'label' => $custom_field['name'],
				'value' => $custom_field['custom_field_id'],
			);
		}

		// customer groups
		$this->load->model('customer/customer_group');
		$customer_groups = $this->model_customer_customer_group->getCustomerGroups();

		$data['customer_groups'] = array();
		foreach ($customer_groups as $customer_group) {
			$data['customer_groups'][] = array(
				'customer_group_id' => $customer_group['customer_group_id'],
				'name'              => $customer_group['name'],
			);
		}

		// available languages
		$this->load->model('localisation/language');
		$data['languages'] = array_map(function ($language) {
			$language_extension = $language['extension'] ?? null;

			if ($language_extension) {
				$image = "extension/{$language_extension}/catalog/language/{$language['code']}/{$language['code']}.png";
			} else {
				$image = "catalog/language/{$language['code']}/{$language['code']}.png";
			}

			$language['image'] = $image;

			return $language;
		}, array_values($this->model_localisation_language->getLanguages()));
		$data['default_language'] = $this->config->get('config_language_id');

		// tax classes
		$this->load->model('localisation/tax_class');

		$tax_classes = $this->model_localisation_tax_class->getTaxClasses();

		array_unshift($tax_classes, array(
			'tax_class_id' => '',
			'title'        => 'None',
		));

		$data['tax_classes'] = $tax_classes;

		// custom fonts
		$custom_fonts = [];

		$files = glob(DIR_CATALOG . 'view/theme/journal3/fonts_custom/*.{woff,woff2}', GLOB_BRACE);

		foreach ($files as $file) {
			$font = pathinfo($file, PATHINFO_FILENAME);

			$custom_fonts[$font] = [
				'family' => $font,
			];
		}

		// fonts
		$data['fonts']['system'] = json_decode(file_get_contents(DIR_SYSTEM . 'library/journal3/data/fonts/system.json'), true);
		$data['fonts']['google'] = json_decode(file_get_contents(DIR_SYSTEM . 'library/journal3/data/fonts/google.json'), true);
		$data['fonts']['custom'] = ['fonts' => array_values($custom_fonts), 'css' => ''];

		// icons
		if (is_file(DIR_CATALOG . 'view/theme/journal3/icons_custom/style.css')) {
			$icons = 'icons_custom';
		} else {
			$icons = 'icons';
		}

		$data['icons'] = array();

		if (is_file(DIR_CATALOG . 'view/theme/journal3/' . $icons . '/selection.json')) {
			$selection = json_decode(file_get_contents(DIR_CATALOG . 'view/theme/journal3/' . $icons . '/selection.json'), true);

			foreach (Arr::get($selection, 'icons', array()) as $icon) {
				$classes = explode(',', $icon['properties']['name']);
				$name = trim($classes[0]);
				$code = $icon['properties']['code'];

				if ($name !== 'youtube22') {
					$data['icons'][] = array(
						'name' => $name,
						'code' => dechex($code),
					);
				}
			}
		}

		// variables
		$data['variables'] = $this->model_journal3_journal->getVariables();

		// styles
		$data['styles'] = $this->model_journal3_journal->getStyles();

		// modules
		$data['modules'] = $this->model_journal3_journal->getModules();

		// filters
		$data['attributes'] = $this->model_journal3_journal->getAllAttributes();
		$data['options'] = $this->model_journal3_journal->getAllOptions();
		$data['filters'] = $this->model_journal3_journal->getAllFilters();

		// authors
		$data['authors'] = $this->model_journal3_journal->authors();

		// payments
		$data['payment_methods'] = $this->model_journal3_journal->getPaymentMethods();

		// response
		$this->response->addHeader('Content-Type: application/javascript');
		$this->response->setOutput($this->load->view('journal3/js', array('data' => $data)));
	}

	public function get_variable() {
		return $this->journal3_response->json('success', $this->model_journal3_journal->getVariables());
	}

	public function get_style() {
		return $this->journal3_response->json('success', $this->model_journal3_journal->getStyles());
	}

	public function get_module() {
		return $this->journal3_response->json('success', $this->model_journal3_journal->getModules());
	}

	public function get_skins() {
		$this->load->model('journal3/skin');

		return $this->journal3_response->json('success', $this->model_journal3_skin->all());
	}

	public function search() {
		// load system settings for attribute separator
		$this->load->model('journal3/setting');

		$settings = $this->model_journal3_setting->get(0, array('system'));

		$this->journal3->load(Arr::get($settings, 'system', array()));

		try {
			$type = $this->journal3_request->get('type');
			$keyword = $this->journal3_request->get('keyword', '');
			$id = $this->journal3_request->get('id', '');

			$results = array();

			switch ($type) {
				case 'product':
					$results = $this->model_journal3_journal->getProducts($keyword, $id);
					break;

				case 'category':
					$results = $this->model_journal3_journal->getCategories($keyword, $id);
					break;

				case 'manufacturer':
					$results = $this->model_journal3_journal->getManufacturers($keyword, $id);
					break;

				case 'information':
					$results = $this->model_journal3_journal->getInformations($keyword, $id);
					break;

				case 'attribute':
					$results = $this->model_journal3_journal->getAttributes($keyword, $id);
					break;

				case 'option':
					$results = $this->model_journal3_journal->getOptions($keyword, $id);
					break;

				case 'filter':
					$results = $this->model_journal3_journal->getFilters($keyword, $id);
					break;

				case 'blog_category':
					$results = $this->model_journal3_journal->getBlogCategories($keyword, $id);
					break;

				case 'blog_post':
					$results = $this->model_journal3_journal->getBlogPosts($keyword, $id);
					break;

				default:
					if ($id) {
						$result = $this->model_journal3_module->get($id);
						$results = array(
							array(
								'id'   => $id,
								'name' => Arr::get($result, 'general.name'),
							),
						);
					} else {
						$result = $this->model_journal3_module->all(array(
							'type' => $type,
							'name' => $keyword,
						));
						$results = $result['items'];
					}
					break;
			}

			array_walk($results, function (&$result) {
				$result['name'] = strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'));
			});

			if ($id && !$results) {
//				throw new \Exception(sprintf("ID %s not found!", $id));
			}

			$this->journal3_response->json('success', $results);
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function layouts() {
		$this->load->model('design/layout');

		$layouts = array_map(function ($layout) {
			return array('id' => $layout['layout_id'], 'name' => $layout['name']);
		}, $this->model_design_layout->getLayouts());

		return $this->journal3_response->json('success', $layouts);
	}

	public function clear_cache() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/journal')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$this->journal3_cache->delete();

			$this->journal3_assets->clearCache();

			$this->journal3_response->json('success');
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function export_icons() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/journal')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$type = $this->journal3_request->get('type');

			// get used icons

			$icons = [];

			switch ($type) {
				case 'admin':
					$directory = new RecursiveDirectoryIterator('view/javascript/journal3/src');
					$iterator = new RecursiveIteratorIterator($directory);

					foreach ($iterator as $file) {
						if (!$file->isDir()) {
							preg_match_all('/"icon icon-(\S+)"/', file_get_contents($file->getPathname()), $matches);

							$matches = $matches[1] ?? [];

							foreach ($matches as $match) {
								$match = strtolower($match);
								$icons[$match] = $match;
							}

							preg_match_all('/content:\s?"\\\(.+)"/', file_get_contents($file->getPathname()), $matches);

							$matches = $matches[1] ?? [];

							foreach ($matches as $match) {
								$match = strtolower($match);
								$icons[$match] = $match;
							}
						}
					}

					break;

				case 'demos':
					$files = glob('../system/library/journal3/data/import_export/*.sql', GLOB_BRACE);

					$icons = [];

					foreach ($files as $file) {
						preg_match_all('/code":"(\w+)","name/', file_get_contents($file), $matches);

						$matches = $matches[1] ?? [];

						foreach ($matches as $match) {
							$match = strtolower($match);
							$icons[$match] = $match;
						}
					}

					break;
			}

			// trim selection.json based on used icons

			$selection = json_decode(file_get_contents('../catalog/view/theme/journal3/icons_old/selection.json'));

			foreach ($selection->icons as $key => $icon) {
				if (!isset($icons[dechex($icon->properties->code)]) && !isset($icons[$icon->properties->name])) {
					unset($selection->icons[$key]);
				}
			}

			$selection->icons = array_values($selection->icons);

			if ($type === 'admin') {
				$selection->metadata->name = 'icomoon-admin';
				$selection->preferences->fontPref->prefix = 'icon-admin-';
				$selection->preferences->fontPref->metadata->fontFamily = 'icomoon-admin';
				$selection->preferences->fontPref->classSelector = '.icon-admin';
				$selection->preferences->imagePref->prefix = 'icon-admin-';
				$selection->preferences->imagePref->classSelector = '.icon-admin';
				$selection->preferences->imagePref->name = 'icomoon-admin';
			}

			// download new selection.json

			$filename = date('Y-m-d_H-i-s', time());

			if ($this->journal3_opencart->is_oc2) {
				$file = DIR_CACHE . $filename . '.sql';
			} else {
				$file = DIR_STORAGE . 'cache/' . $filename . '.sql';
			}

			file_put_contents($file, json_encode($selection));

			if (!headers_sent()) {
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="selection_' . $type . '.json"');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file));

				if (ob_get_level()) {
					ob_end_clean();
				}

				readfile($file, 'rb');

				unlink($file);

				exit;
			} else {
				throw new Exception('Error: Headers already sent out!');
			}
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function get() {
		$this->load->model('setting/store');
		$stores = $this->model_setting_store->getStores();

		array_unshift($stores, array(
			'store_id' => '0',
			'name'     => $this->config->get('config_name'),
			'url'      => HTTP_CATALOG,
			'ssl'      => HTTPS_CATALOG,
		));

		$data = [];

		$dashboard = (array)$this->model_journal3_setting->get(0, array('dashboard'));

		foreach ($stores as $store) {
			$data['stores'][] = [
				'store_id'  => $store['store_id'],
				'name'      => $store['name'],
				'domain'    => $this->domain($store),
				'dashboard' => [
					'dashboard_user' => $dashboard['dashboard']['dashboard_user_' . $store['store_id']] ?? '',
					'dashboard_key'  => $dashboard['dashboard']['dashboard_key_' . $store['store_id']] ?? '',
				],
			];
		}

		$this->journal3_response->json('success', $data);
	}

	public function edit() {
		$this->load->model('setting/store');
		$stores = $this->model_setting_store->getStores();

		array_unshift($stores, array(
			'store_id' => '0',
			'name'     => $this->config->get('config_name'),
			'url'      => HTTP_CATALOG,
			'ssl'      => HTTPS_CATALOG,
		));

		try {
			if (!$this->user->hasPermission('modify', 'journal3/journal')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$data = $this->journal3_request->post('data');

			$dashboard = [];

			foreach ($data['stores'] ?? [] as $store) {
				$dashboard['dashboard_user_' . $store['store_id']] = $store['dashboard']['dashboard_user'];
				$dashboard['dashboard_key_' . $store['store_id']] = $store['dashboard']['dashboard_key'];
			}

			$this->model_journal3_setting->edit(0, array('dashboard' => $dashboard));

			$this->journal3_cache->delete();

			$this->journal3_response->json('success');
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function print_error($title, $error, $footer = '') {
		echo "
			<style>
				body {
					font-family: sans-serif;
					padding: 30px;
				}
				b {
					color: red;
				}
			</style>
			<div class=\"content\">
				<p><h2>{$title}</h2></p>
				<p>{$error}</p>
				<p>{$footer}</p>
			</div>
		";

		exit;
	}

	private function opcache() {
		$status = false;

		if (function_exists('opcache_get_status')) {
			if (ini_get('opcache.restrict_api')) {
				return 'Unknown';
			}

			$status = opcache_get_status();
		}

		return $status ? 'Yes' : 'No';
	}

	private function domain($store) {
		if (!empty($store['ssl'])) {
			$domain = parse_url($store['ssl'], PHP_URL_HOST);

			if ($domain) {
				return $domain;
			}
		}

		if (!empty($store['url'])) {
			$domain = parse_url($store['url'], PHP_URL_HOST);

			if ($domain) {
				return $domain;
			}
		}

		return null;
	}

}

class_alias('ControllerJournal3Journal', '\Opencart\Admin\Controller\Journal3\Journal');
