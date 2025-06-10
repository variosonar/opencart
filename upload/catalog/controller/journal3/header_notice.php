<?php

use Journal3\Opencart\MenuController;
use Journal3\Options\Parser;
use Journal3\Options\Range;
use Journal3\Utils\Arr;

class ControllerJournal3HeaderNotice extends MenuController {

	private $ajax = false;

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

			$custom_css = str_replace('%s', '.module-' . $this->module_type . '-' . $this->module_id, $parser->getSetting('customCss') ?? '');
			$this->css = $parser->getCss() . ' ' . $custom_css;

			$rows = array();
			$row_id = 0;

			$this->settings = array_merge_recursive(
				$parser->getPhp(),
				array(
					'status'       => $parser->getSetting('status'),
					'id'           => uniqid($this->module_type . '-'),
					'module_id'    => $this->module_id,
					'classes'      => array(
						'module',
						'module-' . $this->module_type,
						'module-' . $this->module_type . '-' . $this->module_id,
						$parser->getSetting('color_scheme'),
						$parser->getSetting('customClass'),
					),
					'grid_classes' => array('grid-rows'),
				),
				$this->parseGeneralSettings($parser, $this->module_id)
			);

			if ($parser->getSetting('contentType') === 'grid') {
				foreach (Arr::get($this->module_data, 'rows', array()) as $row) {
					$row_id++;

					$parser = new Parser('module/' . $this->module_type . '/row', Arr::get($row, 'options'), null, array($this->module_id, $row_id));

					if ($parser->getSetting('status') === false) {
						continue;
					}

					$custom_css = str_replace('%s', '.grid-row-' . $row_id, $parser->getSetting('customCss') ?? '');
					$this->css .= $parser->getCss() . ' ' . $custom_css;

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

						$rows[$row_id]['columns'][$column_id] = array_merge_recursive(
							$parser->getPhp(),
							array(
								'classes' => array(
									'grid-col',
									'grid-col-' . $column_id,
									$parser->getSetting('customClass')
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
			}

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

		if ($this->settings['status'] === false) {
			return null;
		}

		if (!Range::inRange(Arr::get($this->settings, 'schedule'))) {
			return null;
		}

		if (Arr::get($this->settings, 'scheduledStatus') === false) {
			return null;
		}

		if ($this->css) {
			$this->journal3_document->addCss($this->css, "{$this->module_type}-{$this->module_id}");
		}

		$this->journal3_document->addJs(array('header_notice' => array(
			'm' => $this->module_id,
			'c' => $this->settings['cookie'],
			'o' => $this->settings['options'],
		)));

		if ($this->settings['contentType'] === 'grid') {
			$this->settings['content'] = $this->load->controller('journal3/grid' . JOURNAL3_ROUTE_SEPARATOR . 'grid', $this->settings);
		}

		return $this->load->view('journal3/module/header_notice', $this->settings);
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$data = array(
			'edit'    => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'    => $parser->getSetting('name'),
			'options' => $parser->getJs(),
			'classes' => [
				'notice-builder' => $parser->getSetting('contentType') === 'grid',
			]
		);

		return $data;
	}

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		return array();
	}

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseSubitemSettings($parser, $index) {
		return array();
	}

}

class_alias('ControllerJournal3HeaderNotice', '\Opencart\Catalog\Controller\Journal3\HeaderNotice');
