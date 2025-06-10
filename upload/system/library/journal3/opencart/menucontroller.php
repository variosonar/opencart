<?php

namespace Journal3\Opencart;

use Journal3\Options\Parser;
use Journal3\Utils\Arr;

/**
 * Class MenuController is used as base class for menu based modules (main_menu, top_menu, etc)
 *
 * @package Journal3\Opencart
 */
abstract class MenuController extends \Controller {

	protected $item_id;
	protected $module_id;
	protected $module_type;
	protected $module_data;
	protected $settings;
	protected $css;
	protected $fonts = array();
	protected $is_mobile;

	public function index($args) {
		$this->module_id = (int)Arr::get($args, 'module_id');
		$this->module_type = Arr::get($args, 'module_type');
		$this->item_id = 1;
		$this->is_mobile = $this->journal3->is_phone || ($args['is_mobile'] ?? false);

		if (!$this->module_id || !$this->module_type) {
			return null;
		}

		$cache = $this->journal3_cache->get('module.' . $this->module_type . '.' . $this->module_id . '.x' . (int)$this->is_mobile);

		if ($cache === false) {
			$this->load->model('journal3/module');

			$this->module_data = $this->model_journal3_module->get($this->module_id, $this->module_type);

			if (!$this->module_data) {
				return null;
			}

			$parser = new Parser('module/' . $this->module_type . '/general', Arr::get($this->module_data, 'general'), null, array($this->module_id));

			$module_type = str_replace('_', '-', $this->module_type);

			$custom_css = str_replace('%s', '.' . $module_type . '-' . $this->module_id, $parser->getSetting('customCss') ?? '' ?? '');
			$this->css = $parser->getCss() . ' ' . $custom_css;
			$this->fonts = $parser->getFonts();

			$this->settings = array_merge_recursive(
				$parser->getPhp(),
				array(
					'status'       => $parser->getSetting('status'),
					'module_id'    => $this->module_id,
					'classes'      => array(
						$module_type,
						$module_type . '-' . $this->module_id,
						$parser->getSetting('customClass'),
						$parser->getSetting('color_scheme'),
					),
					'image_width'  => $parser->getSetting('imageDimensions.width'),
					'image_height' => $parser->getSetting('imageDimensions.height'),
					'image_resize' => $parser->getSetting('imageDimensions.resize'),
				),
				$this->parseGeneralSettings($parser, $this->module_id)
			);

			if ($parser->getSetting('status') !== false && Arr::get($this->settings, 'items') === null) {
				$this->settings['items'] = array();

				$items = Arr::get($this->module_data, 'items', array());

				foreach ($items as $item_index => $item) {
					$item_id = $this->item_id++;

					$parser = new Parser('module/' . $this->module_type . '/item', $item, null, array($this->module_id, $item_id));

					if ($parser->getSetting('status') === false) {
						continue;
					}

					$custom_css = str_replace('%s', '.' . $module_type . '-item-' . $item_id, $parser->getSetting('customCss') ?? '');
					$this->css .= $parser->getCss() . ' ' . $custom_css;
					$fonts = $parser->getFonts();
					$this->fonts = Arr::merge($this->fonts, $fonts);

					switch ($parser->getSetting('type')) {
						case 'megamenu':
							$item_data = array(
								'classes'      => array(
									'dropdown',
									'mega-menu',
								),
								'grid_classes' => array(
									'grid-rows',
								),
								'rows'         => $this->generateMegaMenu($item, $item_id),
							);

							break;

						case 'flyout':
							$item_data['items'] = $parser->getSetting('flyout');

							$item_data['classes'] = array(
								'dropdown',
								'flyout',
							);

							break;

						default:
							// none in main menu and flyout menu ignores possible subitems
							if (($parser->getSetting('type') === '') && (($this->module_type === 'main_menu') || ($this->module_type === 'flyout_menu'))) {
								$item_data = [
									'items' => [],
								];
							} else {
								$item_data = $this->generateMultiLevelMenu($item, $parser);
							}

							$item_data['classes'] = array(
								'multi-level' => ($this->module_type === 'main_menu') || ($this->module_type === 'flyout_menu'),
								'dropdown'    => (bool)$item_data['items'],
								'drop-menu'   => (bool)$item_data['items'] && ($this->module_type === 'top_menu'),
							);
					}

					$show_category_image = $parser->getSetting('categoryImage') ?? ($this->settings['categoryImage'] ?? false);

					if ($show_category_image && ($item['link']['type'] ?? '' === 'category')) {
						$this->load->model('journal3/category');

						$category = $this->model_journal3_category->getCategory($item['link']['id'] ?? '');

						$item_data['image'] = $category['image'] ?? '' ?: $this->journal3->get('placeholder');
					}

					if ($image = ($parser->getSetting('image') ?: ($item_data['image'] ?? ''))) {
						$item_data['thumb'] = $this->journal3_image->resize($image, $this->settings['image_width'], $this->settings['image_height'], $this->settings['image_resize']);
						$item_data['thumb2x'] = $this->journal3_image->resize($image, $this->settings['image_width'] * 2, $this->settings['image_height'] * 2, $this->settings['image_resize']);
					} else {
						$item_data['thumb'] = '';
						$item_data['thumb2x'] = '';
					}

					$this->settings['items'][$item_id] = array_merge_recursive(
						$parser->getPhp(),
						array(
							'classes' => array(
								'menu-item',
								$module_type . '-item',
								$module_type . '-item-' . $item_id,
								'has-image' => (bool)$item_data['thumb'],
								$parser->getSetting('color_scheme'),
								$parser->getSetting('customClass'),
							),
						),
						$item_data,
						$this->parseItemSettings($parser, $item_id)
					);
				}
			}

			$cache = array(
				'css'      => $this->css,
				'fonts'    => $this->fonts,
				'settings' => $this->settings,
			);

			$this->journal3_cache->set('module.' . $this->module_type . '.' . $this->module_id . '.x' . (int)$this->is_mobile, $cache);
		} else {
			$this->css = $cache['css'];
			$this->fonts = $cache['fonts'];
			$this->settings = $cache['settings'];
		}

		$this->settings['id'] = uniqid($this->module_type . '-');

		if (!empty($args['id'])) {
			$this->settings['id'] = $args['id'];
		}

		if ($this->settings['status'] === false) {
			return null;
		}

		if (Arr::get($this->settings, 'scheduledStatus') === false) {
			return null;
		}

		if (isset($this->settings['items']) && in_array($this->module_type, array('main_menu', 'flyout_menu'))) {
			foreach ($this->settings['items'] as &$setting) {
				switch (Arr::get($setting, 'type')) {
					case 'megamenu':
						$setting['items'] = $this->load->controller('journal3/grid' . JOURNAL3_ROUTE_SEPARATOR . 'grid', $setting);
						break;

					case 'flyout':
						$setting['items'] = $this->load->controller('journal3/flyout_menu', array(
							'module_id'   => $setting['items'],
							'module_type' => 'flyout_menu',
							'is_mobile'   => $this->is_mobile,
						));
						break;
				}
			}
		}

		$this->settings['is_mobile'] = $this->is_mobile;

		$this->beforeRender();

		if ($this->settings === null) {
			return null;
		}

		$output = $this->load->view('journal3/module/' . ($this->module_type === 'flyout_menu' ? 'main_menu' : $this->module_type), $this->settings);
		$output = $this->journal3_cache->update($output);

		if (!$output) {
			return null;
		}

		$this->afterRender();

		if ($this->css) {
			$this->journal3_document->addCss($this->css, "{$this->module_type}-{$this->module_id}");
		}

		if ($this->fonts) {
			$this->journal3_document->addFonts($this->fonts);
		}

		return $output;
	}

	protected abstract function parseGeneralSettings($parser, $module_id);

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected abstract function parseItemSettings($parser, $index);

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected abstract function parseSubitemSettings($parser, $index);

	/**
	 * @param array $item
	 * @param number $item_id
	 * @return array
	 */
	protected final function generateMegaMenu($item, $item_id) {
		$rows = array();
		$row_id = 0;

		foreach (Arr::get($item, 'rows', array()) as $row) {
			$row_id++;

			$parser = new Parser('module/' . $this->module_type . '/row', Arr::get($row, 'options'), null, array($this->module_id, $item_id, $row_id));

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
						$parser->getSetting('customClass'),
						$parser->getSetting('color_scheme'),
					),
					'columns' => array(),
				)
			);

			$column_id = 0;

			foreach (Arr::get($row, 'columns', array()) as $column) {
				$column_id++;

				$parser = new Parser('module/' . $this->module_type . '/column', Arr::get($column, 'options'), null, array($this->module_id, $item_id, $row_id, $column_id));

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
							$parser->getSetting('customClass'),
						),
						'items'   => array(),
					)
				);

				$module_id = 0;

				foreach (Arr::get($column, 'items', array()) as $module) {
					$module_id++;

					$parser = new Parser('module/' . $this->module_type . '/module', Arr::get($module, 'options'), null, array($this->module_id, $item_id, $row_id, $column_id, $module_id));

					$custom_css = str_replace('%s', '.grid-module-' . $this->module_type . '-' . $this->module_id . '-' . $item_id . '-' . $row_id . '-' . $column_id . '-' . $module_id, $parser->getSetting('customCss') ?? '');
					$this->css .= $parser->getCss() . ' ' . $custom_css;
					$fonts = $parser->getFonts();
					$this->fonts = Arr::merge($this->fonts, $fonts);

					$rows[$row_id]['columns'][$column_id]['items'][$module_id] = array_merge_recursive(
						$parser->getPhp(),
						array(
							'classes' => array('grid-item', 'grid-module-' . $this->module_type . '-' . $this->module_id . '-' . $item_id . '-' . $row_id . '-' . $column_id . '-' . $module_id, $parser->getSetting('customClass')),
							'item'    => Arr::get($module, 'item'),
						),
					);
				}
			}

		}

		return $rows;
	}

	/**
	 * @param array $item
	 * @param Parser $parser
	 * @return array
	 */
	protected final function generateMultiLevelMenu($item, $parser) {
		$module_type = str_replace('_', '-', $this->module_type);

		$item_data = array();

		$items = array();

		$link = $this->journal3_url->getLink($parser->getSetting('link'));

		if ($link['type'] === 'category' && $parser->getSetting('subcategories')) {
			$this->load->model('journal3/category');

			$category = $this->model_journal3_category->getCategoryTree($link['id'], (int)$parser->getSetting('subcategoriesMaxLevels'));

			$items = Arr::get($category, 'items', array());

			$this->links($items, $link['id']);

			$show_category_image = $parser->getSetting('categoryImage') ?? ($this->settings['categoryImage'] ?? false);

			if ($show_category_image) {
				$item_data['image'] = $category['image'] ?? '' ?: $this->journal3->get('placeholder');
			}

			if ($parser->getSetting('subcategoriesImages')) {
				$this->images($items);
			}

			$link['total'] = Arr::get($category, 'link.total');
		} else {
			$subitems = Arr::get($item, 'items', array());

			foreach ($subitems as $subitem) {
				$subitem_id = $this->item_id++;

				$parser = new Parser('module/' . $this->module_type . '/subitem', $subitem, null, array($this->module_id, $subitem_id));

				if ($parser->getSetting('status') === false) {
					continue;
				}

				$this->css .= $parser->getCss();
				$fonts = $parser->getFonts();
				$this->fonts = Arr::merge($this->fonts, $fonts);

				$item_data = $this->generateMultiLevelMenu($subitem, $parser);

				if ($image = $parser->getSetting('image')) {
					$item_data['thumb'] = $this->journal3_image->resize($image, $this->settings['image_width'], $this->settings['image_height'], $this->settings['image_resize']);
					$item_data['thumb2x'] = $this->journal3_image->resize($image, $this->settings['image_width'] * 2, $this->settings['image_height'] * 2, $this->settings['image_resize']);
				} else {
					$item_data['thumb'] = '';
					$item_data['thumb2x'] = '';
				}

				$items[$subitem_id] = array_merge_recursive(
					$parser->getPhp(),
					array(
						'classes' => array(
							'menu-item',
							$module_type . '-item-' . $subitem_id,
							'dropdown' => (bool)$item_data['items'],
						),
					),
					$item_data,
					$this->parseSubitemSettings($parser, $subitem_id)
				);
			}
		}

		$item_data['items'] = $items;

		return $item_data;
	}

	/**
	 * Called before view is rendered
	 */
	protected function beforeRender() {
	}

	/**
	 * Called after view is rendered,
	 */
	protected function afterRender() {
	}

	private function links(&$categories, $path = '') {
		foreach ($categories as $key => &$category) {
			if ($path) {
				$href = $path . '_' . $category['category_id'];
			} else {
				$href = $category['category_id'];
			}

			$category['title'] = $category['name'];

			$category['classes'] = [
				'menu-item menu-item-c' . $category['category_id'],
			];

			$category['link']['href'] = $this->journal3_url->link('product/category', 'path=' . $href, $this->journal3_request->is_https);
			$category['link']['total'] = $category['total'] ?? 0;
			$category['link']['classes'] = [];

			if (!empty($category['items'])) {
				$category['classes']['dropdown'] = true;
				$this->links($category['items'], $href);
			}
		}
	}

	private function images(&$categories) {
		foreach ($categories as $key => &$category) {
			$image = $category['image'] ?: $this->journal3->get('placeholder');

			if ($image) {
				$category['thumb'] = $this->journal3_image->resize($image, $this->settings['image_width'], $this->settings['image_height'], $this->settings['image_resize']);
				$category['thumb2x'] = $this->journal3_image->resize($image, $this->settings['image_width'] * 2, $this->settings['image_height'] * 2, $this->settings['image_resize']);
				$category['classes'][] = 'has-image';
			} else {
				$category['thumb'] = '';
				$category['thumb2x'] = '';
			}

			if (!empty($category['items'])) {
				$this->images($category['items']);
			}
		}
	}

}

