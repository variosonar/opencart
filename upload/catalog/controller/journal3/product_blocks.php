<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3ProductBlocks extends ModuleController {

	public function index($args) {
		$this->module_id = (int)Arr::get($args, 'module_id');
		$this->module_type = Arr::get($args, 'module_type');

		$cache = $this->journal3_cache->get('module.' . $this->module_type . '.' . $this->module_id);

		if ($cache === false) {
			$this->module_data = $this->model_journal3_module->get($this->module_id, $this->module_type);

			if (!$this->module_data) {
				return null;
			}

			if (Arr::get($this->module_data, 'general.blockType') === 'custom') {
				return $this->load->controller('journal3/blocks', array(
					'module_id'   => $this->module_id,
					'module_type' => $this->module_type,
				));
			}

			$parser = new Parser('module/' . $this->module_type . '/' . $this->module_type, Arr::get($this->module_data, 'general'), null, array($this->module_id));

			$this->css = $parser->getCss();

			$rows = array();
			$row_id = 0;

			$this->settings = array_merge_recursive(
				$parser->getPhp(),
				array(
					'status'       => $parser->getSetting('status'),
					'grid_classes' => array(
						'product-blocks-' . $this->module_data['general']['position'],
						'product-blocks-' . $this->module_id,
						'grid-rows',
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

		if ($this->css) {
			$this->journal3_document->addCss($this->css, "{$this->module_type}-{$this->module_id}");
		}

		$output = $this->load->controller('journal3/grid' . JOURNAL3_ROUTE_SEPARATOR . 'grid', $this->settings);

		if (!$output) {
			return null;
		}

		return $output;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		return array();
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

}

class_alias('ControllerJournal3ProductBlocks', '\Opencart\Catalog\Controller\Journal3\ProductBlocks');
