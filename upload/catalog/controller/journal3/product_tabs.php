<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3ProductTabs extends ModuleController {

	private static $PRODUCT_INFO;

	public function index($args) {
		$this->module_id = (int)Arr::get($args, 'module_id');
		$this->module_type = Arr::get($args, 'module_type');

		$cache = $this->journal3_cache->get('module.' . $this->module_type . '.' . $this->module_id);

		if ($cache === false) {
			$this->module_data = $this->model_journal3_module->get($this->module_id, $this->module_type);

			if (!$this->module_data) {
				return null;
			}

			$parser = new Parser('module/' . $this->module_type . '/general', Arr::get($this->module_data, 'general'), null, array($this->module_id));

			$custom_css = str_replace('%s', '.product_extra-' . $this->module_id, $parser->getSetting('customCss') ?? '');
			$this->css = $parser->getCss() . ' ' . $custom_css;

			$rows = array();
			$row_id = 0;

			$this->settings = array_merge_recursive(
				$parser->getPhp(),
				array(
					'status'        => $parser->getSetting('status'),
					'id'            => uniqid($this->module_type . '-'),
					'classes'       => array(
						$parser->getSetting('customClass'),
					),
					'popup_classes' => array(
						'popup-block-' . $this->module_id,
					),
					'grid_classes'  => array(
						'product-blocks-' . $this->module_data['general']['position'],
						'product-blocks-' . $this->module_id,
						'grid-rows',
					),
					'rows'          => $rows,
				),
				$this->parseGeneralSettings($parser, $this->module_id)
			);

			if ($this->settings['tabType'] === 'grid') {
				foreach (Arr::get($this->module_data, 'rows', array()) as $row) {
					$row_id++;

					$parser = new Parser('module/' . $this->module_type . '/row', Arr::get($row, 'options'), null, array($this->module_id, $row_id));

					if ($parser->getSetting('status') === false) {
						continue;
					}

					$this->css .= $parser->getCss();

					$rows[$row_id] = array_merge_recursive(
						$parser->getPhp(),
						array(
							'classes' => array(
								'grid-row',
								'grid-row-' . $this->module_id . '-' . $row_id,
								$parser->getSetting('color_scheme'),
							),
							'columns' => array(),
						)
					);

					$column_id = 0;

					foreach (Arr::get($row, 'columns', array()) as $column) {
						$column_id++;

						$parser = new Parser('module/' . $this->module_type . '/column', Arr::get($column, 'options'), null, array($this->module_id, $row_id, $column_id));

						if ($parser->getSetting('status') === false) {
							continue;
						}

						$this->css .= $parser->getCss();

						$rows[$row_id]['columns'][$column_id] = array_merge_recursive(
							$parser->getPhp(),
							array(
								'classes' => array(
									'grid-col',
									'grid-col-' . $this->module_id . '-' . $row_id . '-' . $column_id,
								),
								'items'   => array(),
							)
						);

						$module_id = 0;

						foreach (Arr::get($column, 'items', array()) as $module) {
							$module_id++;

							$parser = new Parser('module/' . $this->module_type . '/module', Arr::get($module, 'options'), null, array($this->module_id, $row_id, $column_id, $module_id));

							$custom_css = str_replace('%s', '.grid-module-' . $this->module_type . '-' . $this->module_id . '-' . $row_id . '-' . $column_id . '-' . $module_id, $parser->getSetting('customCss') ?? '');
							$this->css .= $parser->getCss() . ' ' . $custom_css;
							$fonts = $parser->getFonts();
							$this->fonts = Arr::merge($this->fonts, $fonts);

							$rows[$row_id]['columns'][$column_id]['items'][$module_id] = array_merge_recursive(
								$parser->getPhp(),
								array(
									'classes' => array('grid-item', 'grid-module-' . $this->module_type . '-' . $this->module_id . '-' . $row_id . '-' . $column_id . '-' . $module_id, $parser->getSetting('customClass')),
									'item'    => Arr::get($module, 'item'),
								),
							);
						}
					}

				}

				$this->settings['rows'] = $rows;

				$cache = array(
					'css'      => $this->css,
					'js'       => $this->js,
					'fonts'    => $this->fonts,
					'settings' => $this->settings,
				);

				$this->journal3_cache->set('module.' . $this->module_type . '.' . $this->module_id, $cache);
			}
		} else {
			$this->css = $cache['css'];
			$this->js = $cache['js'];
			$this->fonts = $cache['fonts'];
			$this->settings = $cache['settings'];
		}

		if ($this->settings['status'] === false) {
			return null;
		}

		if (Arr::get($this->settings, 'scheduledStatus') === false) {
			return null;
		}

		switch ($this->settings['tabType']) {
			case 'grid':
				$this->settings['content'] = $this->load->controller('journal3/grid' . JOURNAL3_ROUTE_SEPARATOR . 'grid', $this->settings);
				break;

			default:
				// dynamic
				if (Arr::get($this->settings, 'contentType') === 'dynamic' && Arr::get($this->settings, 'dynamic')) {
					$this->settings['content'] = $this->load->controller(Arr::get($this->settings, 'dynamic'), array(
						'module_id' => $this->module_id,
						'settings'  => $this->settings,
					));
				}
		}

		if ($this->css) {
			$this->journal3_document->addCss($this->css, "{$this->module_type}-{$this->module_id}");
		}

		return $this->settings;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$data = array(
			'tab_classes'   => array(
				$this->module_type . '-' . $this->module_id . '-tab'
			),
			'panel_classes' => array(
				'panel-collapse',
				'collapse',
				'in' => $parser->getSetting('display') === 'accordion' && $parser->getSetting('accordionOpen'),
			),
			'classes'       => array(
				'product-extra-' . $parser->getSetting('contentType'),
				'product_extra-' . $this->module_id,
				'tab-pane'            => $parser->getSetting('display') === 'tabs',
				'panel'               => $parser->getSetting('display') === 'accordion',
				'product-extra-popup' => $parser->getSetting('popup'),
				'panel-active'        => $parser->getSetting('display') === 'accordion' && $parser->getSetting('accordionOpen'),
			),
		);

		switch ($parser->getSetting('contentType')) {
			case 'description':
			case 'short_description':
			case 'attributes':
			case 'reviews':
				$data['content'] = $this->productContent($parser->getSetting('contentType'), $parser->getSetting('shortDescriptionLimit'), $parser->getSetting('shortDescriptionReadMore'));
				break;

			case 'image':
				$src = $this->journal3_image->resize($parser->getSetting('image'), $parser->getSetting('imageDimensions.width'), $parser->getSetting('imageDimensions.height'), $parser->getSetting('imageDimensions.resize'));
				$data['content'] = "<img src=\"{$src}\" alt=\"\" width=\"{$parser->getSetting('imageDimensions.width')}\" height=\"{$parser->getSetting('imageDimensions.height')}\"/>";
				break;

			default:
				if ($parser->getSetting('tabType') === 'link') {
					$data['content'] = true;
				} else {
					$data['content'] = $parser->getSetting('content');
				}
		}

		return $data;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		return array();
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseSubitemSettings($parser, $index) {
		return array();
	}

	private function productContent($type, $short_description_limit, $short_description_read_more = '') {
		if (static::$PRODUCT_INFO === null) {
			$this->load->model('catalog/product');

			$product_info = $this->model_catalog_product->getProduct($this->request->get['product_id']);

			if (!$product_info) {
				return null;
			}

			// desc
			static::$PRODUCT_INFO['description'] = html_entity_decode(Arr::get($product_info, 'description', ''), ENT_QUOTES, 'UTF-8');

			if (!trim(strip_tags(static::$PRODUCT_INFO['description'], '<img><iframe>'))) {
				static::$PRODUCT_INFO['description'] = '';
			}

			// attrs
			if (version_compare(VERSION, '4', '>=') || defined('JOURNAL3_OLD_OC3039')) {
				$data['attribute_groups'] = $this->model_catalog_product->getAttributes($this->request->get['product_id']);
			} else {
				$data['attribute_groups'] = $this->model_catalog_product->getProductAttributes($this->request->get['product_id']);
			}

			static::$PRODUCT_INFO['attributes'] = $this->load->view('journal3/module/product_blocks_attributes', $data);

			// reviews
			if ($this->journal3_opencart->is_oc4) {
				static::$PRODUCT_INFO['reviews'] = $this->load->controller('product/review');
			} else {
				$this->load->language('product/product');

				$data['text_write'] = $this->language->get('text_write');
				$data['entry_name'] = $this->language->get('entry_name');
				$data['entry_review'] = $this->language->get('entry_review');
				$data['text_note'] = $this->language->get('text_note');
				$data['entry_rating'] = $this->language->get('entry_rating');
				$data['entry_bad'] = $this->language->get('entry_bad');
				$data['entry_good'] = $this->language->get('entry_good');
				$data['text_loading'] = $this->language->get('text_loading');
				$data['button_continue'] = $this->language->get('button_continue');

				$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true));
				$data['tab_review'] = sprintf($this->language->get('tab_review'), Arr::get($product_info, 'reviews'));

				$data['review_status'] = $this->config->get('config_review_status');

				if ($this->config->get('config_review_guest') || $this->customer->isLogged()) {
					$data['review_guest'] = true;
				} else {
					$data['review_guest'] = false;
				}

				if ($this->customer->isLogged()) {
					$data['customer_name'] = $this->customer->getFirstName() . '&nbsp;' . $this->customer->getLastName();
				} else {
					$data['customer_name'] = '';
				}

				$data['reviews'] = sprintf($this->language->get('text_reviews'), (int)Arr::get($product_info, 'reviews'));
				$data['rating'] = (int)$product_info['rating'];

				// Captcha
				if ($this->journal3_opencart->is_oc2) {
					if ($this->config->get($this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
						$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
					} else {
						$data['captcha'] = '';
					}
				} else if ($this->journal3_opencart->is_oc3) {
					if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
						$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
					} else {
						$data['captcha'] = '';
					}
				} else {
					$this->load->model('setting/extension');

					$extension_info = $this->model_setting_extension->getExtensionByCode('captcha', $this->config->get('config_captcha'));

					if ($extension_info && $this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
						$data['captcha'] = $this->load->controller('extension/' . $extension_info['extension'] . '/captcha/' . $extension_info['code']);
					} else {
						$data['captcha'] = '';
					}
				}

				static::$PRODUCT_INFO['reviews'] = $this->load->view('journal3/module/product_blocks_reviews', $data);
			}
		}

		static::$PRODUCT_INFO['short_description'] = \Journal3\Utils\Str::utf8_substr(strip_tags(html_entity_decode(static::$PRODUCT_INFO['description'], ENT_QUOTES, 'UTF-8')), 0, (int)$short_description_limit) . '... ' . '<a id="read-more-desc" role="button">' . $short_description_read_more . '</a>';

		return static::$PRODUCT_INFO[$type];
	}

	public function tabs($data) {
		$data['items'] = array_filter(Arr::get($data, 'items'), function ($item) {
			return (bool)trim($item['content'] ?: '');
		});

		if (!$data['items']) {
			return null;
		}

		$sort_order = array();

		foreach ($data['items'] as $key => $value) {
			$sort_order[$key] = $value['sort'];
		}

		array_multisort($sort_order, SORT_ASC, $data['items']);

		$data['display'] = $data['items']['0']['display'];

		$data['classes'] = array(
			'product_extra',
			'product_' . $data['display'],
			'product_' . $data['display'] . '-' . $data['position'],
		);

		switch ($data['display']) {
			case 'tabs':
				$data['items']['0']['tab_classes'][] = 'active';
				$data['items']['0']['classes'][] = 'active';

				break;
		}

		return $this->load->view('journal3/module/product_tabs', $data);
	}

}

class_alias('ControllerJournal3ProductTabs', '\Opencart\Catalog\Controller\Journal3\ProductTabs');
