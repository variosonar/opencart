<?php

use Journal3\Opencart\MenuController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3FooterMenu extends MenuController {

	public function index($args) {
		$this->module_id = (int)Arr::get($args, 'module_id');
		$this->module_type = Arr::get($args, 'module_type');

		$cache = $this->journal3_cache->get('module.' . $this->module_type . '.' . $this->module_id);

		if ($cache === false) {
			$this->module_data = $this->model_journal3_module->get($this->module_id, $this->module_type);

			if (!$this->module_data) {
				return null;
			}

			$parser = new Parser('module/' . $this->module_type . '/general', Arr::get($this->module_data, 'general'), null, array());

			if ($parser->getSetting('status') === false) {
				return null;
			}

			$this->settings = array_merge_recursive(
				$parser->getPhp(),
				array(
					'grid_classes' => array('grid-rows'),
				),
				$this->parseGeneralSettings($parser, $this->module_id)
			);

			$this->css = $parser->getCss();
			$this->fonts = $parser->getFonts();

			$rows = array();
			$row_id = 0;

			foreach (Arr::get($this->module_data, 'rows', array()) as $row) {
				$row_id++;

				$parser = new Parser('module/' . $this->module_type . '/row', Arr::get($row, 'options'), null, array($row_id));

				if ($parser->getSetting('status') === false) {
					continue;
				}

				$this->css .= $parser->getCss();
				$fonts = $parser->getFonts();
				$this->fonts = Arr::merge($this->fonts, $fonts);

				$rows[$row_id] = array_merge_recursive(
					$parser->getPhp(),
					array(
						'classes' => array(
							'grid-row',
							'grid-row-' . $row_id,
							'fullwidth-row' => $parser->getSetting('fullwidth'),
							'align-content-row' => $parser->getSetting('contentAlign'),
                            $parser->getSetting('color_scheme'),
						),
						'columns' => array(),
					)
				);

				$column_id = 0;

				foreach (Arr::get($row, 'columns', array()) as $column) {
					$column_id++;

					$parser = new Parser('module/' . $this->module_type . '/column', Arr::get($column, 'options'), null, array($row_id, $column_id));

					if ($parser->getSetting('status') === false) {
						continue;
					}

					$this->css .= $parser->getCss();
					$fonts = $parser->getFonts();
					$this->fonts = Arr::merge($this->fonts, $fonts);

					$rows[$row_id]['columns'][$column_id] = array_merge_recursive(
						$parser->getPhp(),
						array(
							'classes' => array(
								'grid-col',
								'grid-col-' . $column_id,
							),
							'items'   => array(),
						)
					);

					$module_id = 0;

					foreach (Arr::get($column, 'items', array()) as $module) {
						$module_id++;

						$parser = new Parser('module/' . $this->module_type . '/module', Arr::get($module, 'options'), null, array($row_id, $column_id, $module_id));

						$custom_css = str_replace('%s', '.grid-module-footer-' . $row_id . '-' . $column_id . '-' . $module_id, $parser->getSetting('customCss') ?? '');
						$this->css .= $parser->getCss() . ' ' . $custom_css;
						$fonts = $parser->getFonts();
						$this->fonts = Arr::merge($this->fonts, $fonts);

						$rows[$row_id]['columns'][$column_id]['items'][$module_id] = array_merge_recursive(
							$parser->getPhp(),
							array(
								'classes' => array('grid-item', 'grid-module-footer-' . $row_id . '-' . $column_id . '-' . $module_id, $parser->getSetting('customClass')),
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

		if ($this->settings['footerType'] === 'reveal') {
			$this->journal3_document->addClass('footer-reveal');
		}

		$this->journal3->set('footer_color_scheme', $this->settings['color_scheme'] ?? "");

		return $this->load->controller('journal3/grid' . JOURNAL3_ROUTE_SEPARATOR . 'grid', $this->settings);
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
		return $this->parseItemSettings($parser, $index);
	}

}

class_alias('ControllerJournal3FooterMenu', '\Opencart\Catalog\Controller\Journal3\FooterMenu');
