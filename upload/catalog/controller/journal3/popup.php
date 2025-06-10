<?php

use Journal3\Opencart\MenuController;
use Journal3\Options\Parser;
use Journal3\Options\Range;
use Journal3\Utils\Arr;

class ControllerJournal3Popup extends MenuController {

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
					'edit'         => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
					'name'         => $parser->getSetting('name'),
					'status'       => $parser->getSetting('status'),
					'id'           => uniqid($this->module_type . '-'),
					'module_id'    => $this->module_id,
					'classes'      => array(
						'module',
						'module-' . $this->module_type,
						'module-' . $this->module_type . '-' . $this->module_id,
						'popup-iframe' => $this->ajax && $parser->getSetting('contentType') === 'grid',
						$parser->getSetting('color_scheme'),
						$parser->getSetting('customClass')
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

		$this->journal3_document->addJs(array('popup' => array(
			'm' => $this->module_id,
			'c' => $this->settings['cookie'],
			'o' => $this->settings['options'],
		)));

		if ($this->settings['contentType'] === 'grid') {
			$this->settings['content'] = $this->load->controller('journal3/grid' . JOURNAL3_ROUTE_SEPARATOR . 'grid', $this->settings);
		}

		$this->settings['iframe'] = Arr::get($args, 'iframe');
		$this->settings['iframe_src'] = $this->journal3_url->link('journal3/popup' . JOURNAL3_ROUTE_SEPARATOR . 'page', 'module_id=' . $this->module_id . '&popup=module');

		if ($this->settings['iframe']) {
			$this->journal3_document->addClass('module-popup-' . $this->module_id);
			$this->journal3_document->addJs(array(
				'modulePopupId' => $this->module_id,
			));
		}

		return $this->load->view('journal3/module/popup', $this->settings);
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$data = array(
			'ajax'    => $this->ajax,
			'options' => $parser->getJs(),
		);

		if ($parser->getSetting('contentType') === 'image') {
			$data['image'] = $this->journal3_image->resize($parser->getSetting('image'), $parser->getSetting('imageDimensions.width'), $parser->getSetting('imageDimensions.height'), $parser->getSetting('imageDimensions.resize'));
		}

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

	public function get() {
		try {
			$this->ajax = true;

			$module_id = (int)$this->journal3_request->get('module_id');

			$data['content'] = $this->index(array('module_id' => $module_id, 'module_type' => 'popup',));
			$data['css'] = $this->css;

			$this->response->setOutput($this->load->view('journal3/module/popup_page', $data));
		} catch (Exception $e) {
			die('Invalid module_id!');
		}
	}

	public function page() {
		try {
			$module_id = (int)$this->journal3_request->get('module_id');

			$this->journal3_document->addJs(array('popupModuleId' => $module_id));

			$data['content'] = $this->load->controller('journal3/popup', array('module_id' => $module_id, 'module_type' => 'popup', 'iframe' => true));
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('journal3/module/popup_content', $data));
		} catch (Exception $e) {
			die('Invalid module_id!');
		}
	}

}

class_alias('ControllerJournal3Popup', '\Opencart\Catalog\Controller\Journal3\Popup');
