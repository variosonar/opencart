<?php

use Journal3\Opencart\MenuController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3Grid extends MenuController {

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

			if ($parser->getSetting('status') === false) {
				return null;
			}

			$custom_css = str_replace('%s', '.module-' . $this->module_type . '-' . $this->module_id, $parser->getSetting('customCss') ?? '');
			$this->css = $parser->getCss() . ' ' . $custom_css;
			$this->fonts = $parser->getFonts();

			$rows = array();
			$row_id = 0;

			$this->settings = array_merge_recursive(
				$parser->getPhp(),
				array(
					'grid_classes' => array(
						'module',
						'module-' . $this->module_type,
						'module-' . $this->module_type . '-' . $this->module_id,
						'grid-rows',
						$parser->getSetting('customClass'),
					),
					'rows'         => $rows,
				),
				$this->parseGeneralSettings($parser, $this->module_id)
			);

			foreach (Arr::get($this->module_data, 'rows', array()) as $row) {
				$row_id++;

				$parser = new Parser('module/' . $this->module_type . '/row', Arr::get($row, 'options'), null, array($this->module_id, $row_id));

				if ($parser->getSetting('status') === false) {
					continue;
				}

				$custom_css = str_replace('%s', '.grid-row-' . $row_id, $parser->getSetting('customCss') ?? '');
				$this->css .= $parser->getCss() . ' ' . $custom_css;
				$fonts = $parser->getFonts();
				$this->fonts = Arr::merge($this->fonts, $fonts);

				$rows[$row_id] = array_merge_recursive(
					$parser->getPhp(),
					array(
						'classes' => array(
							'grid-row',
							'grid-row-' . $row_id,
							$parser->getSetting('customClass'),
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

					$custom_css = str_replace('%s', '.grid-col-' . $column_id, $parser->getSetting('customCss') ?? '');
					$this->css .= $parser->getCss() . ' ' . $custom_css;
					$fonts = $parser->getFonts();
					$this->fonts = Arr::merge($this->fonts, $fonts);

					$rows[$row_id]['columns'][$column_id] = array_merge_recursive(
						$parser->getPhp(),
						array(
							'classes' => array(
								'grid-col',
								'grid-col-' . $column_id,
								$parser->getSetting('customClass'),
								$parser->getSetting('color_scheme'),
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
				'fonts'    => $this->fonts,
				'settings' => $this->settings,
			);

			$this->journal3_cache->set('module.' . $this->module_type . '.' . $this->module_id, $cache);
		} else {
			$this->css = $cache['css'];
			$this->fonts = $cache['fonts'];
			$this->settings = $cache['settings'];
		}

		if ($this->css) {
			$this->journal3_document->addCss($this->css, "{$this->module_type}-{$this->module_id}");
		}

		if ($this->fonts) {
			$this->journal3_document->addFonts($this->fonts);
		}

		return $this->load->controller('journal3/grid' . JOURNAL3_ROUTE_SEPARATOR . 'grid', $this->settings);
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		return array(
			'edit' => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name' => $parser->getSetting('name'),
		);
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

	public function grid($data) {
		$module_args = array();

		if (Arr::get($data, 'link.type') === 'category') {
			$module_args['category_prefix'] = Arr::get($data, 'link.id');
		}

		if (isset($data['rows'])) {
			foreach ($data['rows'] as $rk => &$row) {
				foreach ($row['columns'] as $ck => &$column) {
					foreach ($column['items'] as $ik => &$item) {
						$id = Arr::get($item, 'item.id');
						$type = Arr::get($item, 'item.type');

						$item['item'] = null;

						if ($id) {
							switch ($type) {
								case 'opencart':
									if ($this->journal3_opencart->is_oc2) {
										$part = explode('/', $id);

										if (isset($part[0]) && $this->config->get($part[0] . '_status')) {
											$item['item'] = $this->load->controller('extension/module/' . $part[0]);
										}

										if (isset($part[1])) {
											$this->load->model('extension/module');
											$setting_info = $this->model_extension_module->getModule($part[1]);

											if ($setting_info && $setting_info['status']) {
												$item['item'] = $this->load->controller('extension/module/' . $part[0], $setting_info);
											}
										}
									} else if ($this->journal3_opencart->is_oc3)  {
										$part = explode('/', $id);

										if (isset($part[0]) && $this->config->get('module_' . $part[0] . '_status')) {
											$item['item'] = $this->load->controller('extension/module/' . $part[0]);
										}

										if (isset($part[1])) {
											$this->load->model('setting/module');
											$setting_info = $this->model_setting_module->getModule($part[1]);

											if ($setting_info && $setting_info['status']) {
												$item['item'] = $this->load->controller('extension/module/' . $part[0], $setting_info);
											}
										}
									} else {
										$part = explode('.', $id);

										if (isset($part[1]) && $this->config->get('module_' . $part[1] . '_status')) {
											$module_data = $this->load->controller('extension/' .  $part[0] . '/module/' . $part[1]);

											if ($module_data) {
												$item['item'] = $module_data;
											}
										}

										if (isset($part[2])) {
											$this->load->model('setting/module');
											$setting_info = $this->model_setting_module->getModule($part[2]);

											if ($setting_info && $setting_info['status']) {
												$output = $this->load->controller('extension/' .  $part[0] . '/module/' . $part[1], $setting_info);

												if ($output) {
													$item['item'] = $output;
												}
											}
										}
									}

									break;

								default:
									$item['item'] = $this->load->controller('journal3/' . $type, array(
										'module_id'   => $id,
										'module_type' => $type,
										'module_args' => $module_args,
									));

									if (empty($item['item'])) {
										unset($column['items'][$ik]);
									}

							}
						}
					}

					if (!$column['items']) {
						unset($row['columns'][$ck]);
					}
				}

				if (!$row['columns']) {
					unset($data['rows'][$rk]);
				}
			}
		}

		if (empty($data['rows'])) {
			return null;
		}

		return $this->load->view('journal3/grid', $data);
	}

}

class_alias('ControllerJournal3Grid', '\Opencart\Catalog\Controller\Journal3\Grid');
