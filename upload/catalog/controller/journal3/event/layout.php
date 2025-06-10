<?php

use Journal3\Opencart\EventResult;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3EventLayout extends Controller {

	private static $MODULES = array(
		'popup',
		'notification',
		'header_notice',
		'bottom_menu',
		'side_menu',
		'background_slider',
	);

	private static $POSITIONS = array(
		'column_left',
		'column_right',
		'content_top',
		'content_bottom',
		'top',
		'bottom',
		'footer_top',
		'footer_bottom',
	);

	private static $layout;
	private static $layout_id;

	public function controller_common_position_before(&$eventRoute, &$args) {
		$position = str_replace('common/', '', $eventRoute);

		// layout_id
		if (static::$layout_id === null) {
			$this->load->model('design/layout');
			$this->load->model('journal3/blog');

			if (isset($this->request->get['route'])) {
				$route = (string)$this->request->get['route'];
			} else {
				$route = 'common/home';
			}

			// quickview fix
			if ($route === 'journal3/product') {
				$route = 'product/product';
			}

			$this->journal3_document->addClass('route-' . str_replace(['/', '.'], '-', $route));
			$this->journal3_document->setPageRoute($route);

			static::$layout_id = 0;

			if ($route == 'product/category' && isset($this->request->get['path'])) {
				$path = explode('_', (string)$this->request->get['path']);
				$category_id = end($path);

				$this->load->model('catalog/category');

				if (version_compare(VERSION, '4', '>=') || defined('JOURNAL3_OLD_OC3039')) {
					static::$layout_id = $this->model_catalog_category->getLayoutId($category_id);
				} else {
					static::$layout_id = $this->model_catalog_category->getCategoryLayoutId($category_id);
				}

				$this->journal3_document->setPageId($category_id);

				$this->journal3_document->addClass('category-' . $category_id);
			}

			if ($route == 'product/product' && isset($this->request->get['product_id'])) {
				$product_id = $this->request->get['product_id'];

				$this->load->model('catalog/product');

				if (version_compare(VERSION, '4', '>=') || defined('JOURNAL3_OLD_OC3039')) {
					static::$layout_id = $this->model_catalog_product->getLayoutId($product_id);
				} else {
					static::$layout_id = $this->model_catalog_product->getProductLayoutId($product_id);
				}

				$this->journal3_document->setPageId($product_id);

				$this->journal3_document->addClass('product-' . $product_id);
			}

			if ($route == 'product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info' && isset($this->request->get['manufacturer_id'])) {
				$manufacturer_id = $this->request->get['manufacturer_id'];

				if (version_compare(VERSION, '4', '>=') || defined('JOURNAL3_OLD_OC3039')) {
					$this->load->model('catalog/manufacturer');

					static::$layout_id = $this->model_catalog_manufacturer->getLayoutId($manufacturer_id);
				}

				$this->journal3_document->setPageId($manufacturer_id);

				$this->journal3_document->addClass('manufacturer-' . $manufacturer_id);
			}

			if ($route == 'information/information' && isset($this->request->get['information_id'])) {
				$information_id = $this->request->get['information_id'];

				$this->load->model('catalog/information');

				if (version_compare(VERSION, '4', '>=') || defined('JOURNAL3_OLD_OC3039')) {
					static::$layout_id = $this->model_catalog_information->getLayoutId($information_id);
				} else {
					static::$layout_id = $this->model_catalog_information->getInformationLayoutId($information_id);
				}

				$this->journal3_document->setPageId($information_id);

				$this->journal3_document->addClass('information-' . $information_id);
			}

			if ($route == 'journal3/blog' && isset($this->request->get['journal_blog_category_id'])) {
				$journal_blog_category_id = $this->request->get['journal_blog_category_id'];

				static::$layout_id = $this->model_journal3_blog->getBlogCategoryLayoutId($journal_blog_category_id);

				$this->journal3_document->setPageId($journal_blog_category_id);

				$this->journal3_document->addClass('blog-category-' . $journal_blog_category_id);
			}

			if ($route == 'journal3/blog' . JOURNAL3_ROUTE_SEPARATOR . 'post' && isset($this->request->get['journal_blog_post_id'])) {
				$journal_blog_post_id = $this->request->get['journal_blog_post_id'];

				static::$layout_id = $this->model_journal3_blog->getBlogPostLayoutId($journal_blog_post_id);

				$this->journal3_document->setPageId($journal_blog_post_id);

				$this->journal3_document->addClass('blog-post-' . $journal_blog_post_id);
			}

			if (!static::$layout_id) {
				static::$layout_id = $this->model_design_layout->getLayout($route);
			}

			if (!static::$layout_id) {
				static::$layout_id = $this->config->get('config_layout_id');
			}

			$this->journal3->set('layout_id', static::$layout_id);
			$this->journal3_document->addClass('layout-' . static::$layout_id);
		}

		// disable layout modules in popups
		if ($this->journal3->is_popup) {
			return self::result();
		}

		// layout data
		if (static::$layout === null) {
			$cache = $this->journal3_cache->get('layout.' . static::$layout_id);

			if ($cache === false) {
				$this->load->model('journal3/layout');

				$layout_data = $this->model_journal3_layout->get(static::$layout_id);

				$layout_positions = Arr::get($layout_data, 'enabledPositions', array());

				$cache = array(
					'settings' => array(),
					'php'      => array(),
					'js'       => array(),
					'fonts'    => array(),
					'css'      => '',
				);

				$parser = new Parser('layout/general', Arr::get($layout_data, 'general'), null, array(static::$layout_id));

				$cache['php'] += $parser->getPhp();
				$cache['css'] .= $parser->getCss();

				foreach (static::$POSITIONS as $POSITION) {
					$data = array(
						'rows'         => array(),
						'grid_classes' => array('grid-rows'),
					);

					$cache['settings'][$POSITION] = $data;

					if (!in_array($POSITION, $layout_positions)) {
						continue;
					}

					$prefix = str_replace('_', '-', $POSITION);

					$row_id = 0;

					foreach (Arr::get($layout_data, 'positions.' . $POSITION . '.rows', array()) as $row) {
						$row_id++;

						$parser = new Parser('layout/row', Arr::get($row, 'options'), null, Arr::trim(array($prefix, $row_id)));

						if ($parser->getSetting('status') === false) {
							continue;
						}

						$custom_css = str_replace('%s', '.grid-row-' . $prefix . '-' . $row_id, $parser->getSetting('customCss') ?? '');
						$cache['css'] .= $parser->getCss() . ' ' . $custom_css;
						$fonts = $parser->getFonts();
						$cache['fonts'] = Arr::merge($cache['fonts'], $fonts);

						$data['rows'][$row_id] = array_merge_recursive(
							$parser->getPhp(),
							array(
								'classes' => array(
									'grid-row',
									'grid-row-' . $prefix . '-' . $row_id,
									'align-content-row' => $parser->getSetting('contentAlign'),
									'fullwidth-row'     => $parser->getSetting('fullwidth'),
									$parser->getSetting('customClass'),
									$parser->getSetting('color_scheme'),
								),
								'columns' => array(),
							)
						);

						$column_id = 0;

						foreach (Arr::get($row, 'columns', array()) as $column) {
							$column_id++;

							$parser = new Parser('layout/column', Arr::get($column, 'options'), null, Arr::trim(array($prefix, $row_id, $column_id)));

							if ($parser->getSetting('status') === false) {
								continue;
							}

							$custom_css = str_replace('%s', '.grid-col-' . $prefix . '-' . $row_id . '-' . $column_id, $parser->getSetting('customCss') ?? '');
							$cache['css'] .= $parser->getCss() . ' ' . $custom_css;
							$fonts = $parser->getFonts();
							$cache['fonts'] = Arr::merge($cache['fonts'], $fonts);

							$data['rows'][$row_id]['columns'][$column_id] = array_merge_recursive(
								$parser->getPhp(),
								array(
									'classes' => array(
										'grid-col',
										'grid-col-' . $prefix . '-' . $row_id . '-' . $column_id,
										$parser->getSetting('customClass'),
										$parser->getSetting('color_scheme'),
									),
									'items'   => array(),
								)
							);

							$module_id = 0;

							foreach (Arr::get($column, 'items', array()) as $module) {
								$module_id++;

								$parser = new Parser('layout/module', Arr::get($module, 'options'), null, Arr::trim(array($prefix, $row_id, $column_id, $module_id)));

								$custom_css = str_replace('%s', '.grid-module-' . $prefix . '-' . $row_id . '-' . $column_id . '-' . $module_id, $parser->getSetting('customCss') ?? '');
								$cache['css'] .= $parser->getCss() . ' ' . $custom_css;
								$fonts = $parser->getFonts();
								$cache['fonts'] = Arr::merge($cache['fonts'], $fonts);

								$data['rows'][$row_id]['columns'][$column_id]['items'][$module_id] = array_merge_recursive(
									$parser->getPhp(),
									array(
										'classes' => array('grid-item', 'grid-module-' . $prefix . '-' . $row_id . '-' . $column_id . '-' . $module_id, $parser->getSetting('customClass')),
										'item'    => Arr::get($module, 'item'),
									)
								);
							}
						}

					}

					$cache['settings'][$POSITION] = $data;
				}

				foreach (static::$MODULES as $MODULE) {
					if (Arr::get($layout_data, 'positions.absolute.' . $MODULE)) {
						$module_id = Arr::get($layout_data, 'positions.absolute.' . $MODULE);

						if ($module_id) {
							$cache['settings']['absolute'][] = array(
								'module_id'   => $module_id,
								'module_type' => $MODULE,
							);
						}
					} else {
						$module_id = Arr::get($layout_data, 'positions.global.' . $MODULE);

						if ($module_id) {
							$cache['settings']['global'][] = array(
								'module_id'   => $module_id,
								'module_type' => $MODULE,
							);
						}
					}
				}

				$this->journal3_cache->set('layout.' . static::$layout_id, $cache);
			}

			switch (Arr::get($cache['php'], 'pageStyleBoxedLayout')) {
				case 'boxed':
					$this->journal3_document->addClass('boxed-layout');
					break;
			}

			$this->journal3_document->addCss($cache['css'], 'layout');
			$this->journal3_document->addFonts($cache['fonts']);

			foreach (static::$POSITIONS as $POSITION) {
				$data = $cache['settings'][$POSITION];

				$grid = $this->load->controller('journal3/grid' . JOURNAL3_ROUTE_SEPARATOR . 'grid', $data);

				$data['modules'] = array();

				if ($grid) {
					$data['modules'][] = $grid;
				}

				if ($data['modules']) {
					self::$layout[$POSITION] = $this->load->view('common/' . $POSITION, $data);
				} else {
					self::$layout[$POSITION] = null;
				}
			}

			foreach (Arr::get($cache['settings'], 'global', array()) as $module) {
				$result = $this->load->controller('journal3/' . $module['module_type'], $module);

				if ($result) {
					self::$layout[$module['module_type']] = $result;
				}
			}

			foreach (Arr::get($cache['settings'], 'absolute', array()) as $module) {
				$result = $this->load->controller('journal3/' . $module['module_type'], $module);

				if ($result) {
					self::$layout[$module['module_type']] = $result;
				}
			}

			if (self::$layout['column_left'] && self::$layout['column_right']) {
				$this->journal3_document->addClass('two-column');
				$this->journal3_document->addJs(array('columnsCount' => 2));
				$this->journal3->set('columnsCount', 2);
			} else if (self::$layout['column_left'] || self::$layout['column_right']) {
				$this->journal3_document->addClass('one-column');
				$this->journal3_document->addJs(array('columnsCount' => 1));
			} else {
				$this->journal3_document->addJs(array('columnsCount' => 0));
			}

			if (self::$layout['column_left'] && self::$layout['column_right']) {
				$this->journal3_document->addClass('column-left column-right');
			} else if (self::$layout['column_left']) {
				$this->journal3_document->addClass('column-left');
			} else if (self::$layout['column_right']) {
				$this->journal3_document->addClass('column-right');
			}

			if ($cache['php']['headerDesktop'] ?? null) {
				$this->journal3->set('headerDesktop', $cache['php']['headerDesktop']);
			}

			if ($cache['php']['headerMobile'] ?? null) {
				$this->journal3->set('headerMobile', $cache['php']['headerMobile']);
			}

			if ($cache['php']['footerMenu'] ?? null) {
				$this->journal3->set('footerMenu', $cache['php']['footerMenu']);
			}

			if ($cache['php']['footerMenuPhone'] ?? null) {
				$this->journal3->set('footerMenuPhone', $cache['php']['footerMenuPhone']);
			}
		}

		if (!$this->journal3->is_popup && !empty(static::$layout[$position])) {
			return self::result($position);
		}

		return self::result();
	}

	public function controller_common_header_before(&$eventRoute, &$args) {
		// disable header in popups
		if ($this->journal3->is_popup) {
			return;
		}

		if (!empty($this->journal3_request->server('HTTP_FILTER_MODULE'))) {
			return;
		}

		// active header
		$mobile_header_active = $this->journal3->is_phone || ($this->journal3->is_tablet && $this->journal3->get('mobileHeaderTablet'));

		$this->journal3->set('mobile_header_active', $mobile_header_active);
		$this->journal3_document->addClass($mobile_header_active ? 'mobile-header-active' : 'desktop-header-active');
		$this->journal3_document->addJs(['mobile_header_active' => $mobile_header_active]);

		// desktop header
		$desktop_module_id = null;
		$desktop_module_type = null;
		$desktop_settings = null;

		if ($desktop_module = $this->journal3->get('headerDesktop')) {
			list($desktop_module_id, $desktop_module_type) = explode('/', $desktop_module);
		}

		if ($desktop_module_id && $desktop_module_type) {
			$this->journal3->set('header_desktop_type', $desktop_module_type);
			$this->journal3->set('header_desktop_id', $desktop_module_id);

			$desktop_settings = $this->load->controller('journal3/header_desktop', array(
				'module_type' => $desktop_module_type,
				'module_id'   => $desktop_module_id,
			));
		}

		// mobile header
		$mobile_module_id = null;
		$mobile_module_type = null;
		$mobile_settings = null;

		if ($mobile_module = $this->journal3->get('headerMobile')) {
			list($mobile_module_id, $mobile_module_type) = explode('/', $mobile_module);
		}

		if ($mobile_module_id && $mobile_module_type) {
			$this->journal3->set('header_mobile_type', $mobile_module_type);
			$this->journal3->set('header_mobile_id', $mobile_module_id);

			$mobile_settings = $this->load->controller('journal3/header_mobile', array(
				'module_type' => $mobile_module_type,
				'module_id'   => $mobile_module_id,
			));
		}

		// header settings
		$this->journal3_document->addCss($desktop_settings['css'] ?? null, "header_desktop");
		$this->journal3_document->addCss($mobile_settings['css'] ?? null, "header_mobile");

		$this->journal3_document->addFonts($desktop_settings['fonts'] ?? null);
		$this->journal3_document->addFonts($mobile_settings['fonts'] ?? null);

		if ($mobile_header_active) {
			$this->journal3->load($desktop_settings['settings'] ?? null);
			$this->journal3->load($mobile_settings['settings'] ?? null);

			$this->journal3_document->addJs($desktop_settings['js'] ?? null);
			$this->journal3_document->addJs($mobile_settings['js'] ?? null);
		} else {
			$this->journal3->load($mobile_settings['settings'] ?? null);
			$this->journal3->load($desktop_settings['settings'] ?? null);

			$this->journal3_document->addJs($mobile_settings['js'] ?? null);
			$this->journal3_document->addJs($desktop_settings['js'] ?? null);
		}

		// desktop menus
		if ($desktop_settings && !$mobile_header_active) {
			$this->journal3_document->addClass(str_replace('_', '-', $desktop_module_type));

			if ($this->journal3->get('mobileMenu1')) {
				$this->journal3_document->addClass('menu-1-off-canvas');
			}

			if ($this->journal3->get('mobileMenu2')) {
				$this->journal3_document->addClass('menu-2-off-canvas');
			}

			if ($this->journal3->get('headerMainMenuDisplay') === 'scroll') {
				$this->journal3_document->addClass('menu-1-scroll');
			}

			if ($this->journal3->get('headerMainMenuDisplay2') === 'scroll') {
				$this->journal3_document->addClass('menu-2-scroll');
			}

			if ($this->journal3->get('headerMainMenu2')) {
				$this->journal3_document->addClass('has-menu-bar');
			}

			$this->journal3->set('desktop_main_menu', $this->load->controller('journal3/main_menu', array(
				'module_type' => 'main_menu',
				'module_id'   => $this->journal3->get('headerMainMenu'),
				'id'          => 'main-menu',
				'is_mobile'   => $this->journal3->get('mobileMenu1'),
			)));

			$this->journal3->set('desktop_main_menu_2', $this->load->controller('journal3/main_menu', array(
				'module_type' => 'main_menu',
				'module_id'   => $this->journal3->get('headerMainMenu2'),
				'id'          => 'main-menu-2',
				'is_mobile'   => $this->journal3->get('mobileMenu2'),
			)));

			$this->journal3->set('desktop_top_menu', $this->load->controller('journal3/top_menu', array(
				'module_type' => 'top_menu',
				'module_id'   => $this->journal3->get('headerTopMenu'),
			)));

			$this->journal3->set('desktop_top_menu_2', $this->load->controller('journal3/top_menu', array(
				'module_type' => 'top_menu',
				'module_id'   => $this->journal3->get('headerTopMenu2'),
			)));

			$this->journal3->set('desktop_top_menu_3', $this->load->controller('journal3/top_menu', array(
				'module_type' => 'top_menu',
				'module_id'   => $this->journal3->get('headerTopMenu3'),
			)));

			$this->journal3->set('headerDesktopMobileMenuBuilder', $this->load->controller('journal3/grid', array(
				'module_type' => 'grid',
				'module_id'   => $this->journal3->get('mobileMenuDesktopBuilder'),
			)));

			$this->journal3->set('headerDesktopMobileMenuAccordion', $this->load->controller('journal3/accordion_menu', array(
				'module_type' => 'accordion_menu',
				'module_id'   => $this->journal3->get('mobileMenuDesktopAccordion'),
			)));
		}

		// mobile menus
		if ($mobile_settings) {
			$this->journal3_document->addClass(str_replace('_', '-', $mobile_module_type));

			$this->journal3->set('mobile_main_menu', $this->load->controller('journal3/main_menu', array(
				'module_type' => 'main_menu',
				'module_id'   => $this->journal3->get('headerMobileMainMenu'),
				'is_mobile'   => true,
				'id'          => 'main-menu-mobile',
			)));

			$this->journal3->set('mobile_top_menu', $this->load->controller('journal3/top_menu', array(
				'module_type' => 'top_menu',
				'module_id'   => $this->journal3->get('headerMobileTopMenu'),
			)));

			$this->journal3->set('mobile_top_menu_2', $this->load->controller('journal3/top_menu', array(
				'module_type' => 'top_menu',
				'module_id'   => $this->journal3->get('headerMobileTopMenu2'),
			)));

			$this->journal3->set('mobile_secondary_menu', $this->load->controller('journal3/top_menu', array(
				'module_type' => 'top_menu',
				'module_id'   => $this->journal3->get('headerMobileSecondaryMenu'),
			)));

			$this->journal3->set('mobile_bottom_menu', $this->load->controller('journal3/bottom_menu', array(
				'module_type' => 'bottom_menu',
				'module_id'   => $this->journal3->get('headerMobileBottomMenu'),
			)));

			$this->journal3->set('headerMobileMenuBuilder', $this->load->controller('journal3/grid', array(
				'module_type' => 'grid',
				'module_id'   => $this->journal3->get('headerMobileBuilder'),
			)));

			$this->journal3->set('headerMobileMenuAccordion', $this->load->controller('journal3/accordion_menu', array(
				'module_type' => 'accordion_menu',
				'module_id'   => $this->journal3->get('headerMobileAccordion'),
			)));
		}

		// desktop sticky type
		if ($this->journal3->get('stickyStatus')) {
			if (in_array($this->journal3->get('headerType'), ['classic', 'mega'])) {
				$this->journal3_document->addClass('sticky-' . $this->journal3->get('stickyLayout'));
			} else {
				$this->journal3_document->addClass('sticky-' . $this->journal3->get('stickyLayoutCompact'));
			}
		}
		if ($this->journal3->get('stickyStatus') && $this->journal3->get('stickyFullHomePadding')) {
			$this->journal3_document->addClass('over-content');
		}
		if ($this->journal3->get('stickyStatus') && $this->journal3->get('stickyFullHomePaddingAll')) {
			$this->journal3_document->addClass('over-content-all');
		}
		// mobile sticky type
		if ($this->journal3->get('headerMobileStickyStatus')) {
			$this->journal3_document->addClass('mobile-sticky-' . $this->journal3->get('stickyMobileLayout'));
		}

		// search page
		if ($this->journal3->get('headerMiniSearchDisplay') === 'page') {
			$this->journal3_document->addClass('search-page');
		}

		// bottom menu
		if (static::$layout['bottom_menu'] ?? null) {
			$this->journal3_document->addClass('has-bottom-menu');
		}

		// maintenance page
		if ($this->config->get('config_maintenance') && !$this->journal3_opencart->is_admin) {
			$this->journal3->set('maintenanceGrid', $this->load->controller('journal3/grid', array(
				'module_type' => 'grid',
				'module_id'   => $this->journal3->get('maintenanceGridModule'),
			)));
		}
	}

	public function view_common_header_before(&$eventRoute, &$data) {
		$data['journal3_popup'] = static::$layout['popup'] ?? null;
		$data['journal3_header_notice'] = static::$layout['header_notice'] ?? null;
		$data['journal3_bottom_menu'] = static::$layout['bottom_menu'] ?? null;
		$data['journal3_side_menu'] = static::$layout['side_menu'] ?? null;
		$data['journal3_background_slider'] = static::$layout['background_slider'] ?? null;
		$data['journal3_notification'] = static::$layout['notification'] ?? null;

		// desktop header
		if (!$this->journal3->is_popup && !$this->journal3->get('mobile_header_active') && $this->journal3->get('headerType')) {
			$data['journal3_header_desktop'] = $this->load->view('journal3/headers/desktop/' . $this->journal3->get('headerType'), $data);
			$data['journal3_header_desktop'] = $this->journal3_cache->update($data['journal3_header_desktop']);
		} else {
			$data['journal3_header_desktop'] = false;
		}

		// mobile header
		if (!$this->journal3->is_popup && $this->journal3->get('mobileHeaderType')) {
			$data['journal3_header_mobile'] = $this->load->view('journal3/headers/mobile/header_mobile_' . $this->journal3->get('mobileHeaderType'), $data);
			$data['journal3_header_mobile'] = $this->journal3_cache->update($data['journal3_header_mobile']);
		} else {
			$data['journal3_header_mobile'] = false;
		}
	}

	public function controller_common_footer_before(&$eventRoute, &$data) {
		// disable footer in popups
		if ($this->journal3->is_popup) {
			return;
		}

		if (!empty($this->journal3_request->server('HTTP_FILTER_MODULE'))) {
			return;
		}

		$footer_menu = $this->journal3->get('footerMenu');

		if ($this->journal3->is_phone && $this->journal3->get('footerMenuPhone')) {
			$footer_menu = $this->journal3->get('footerMenuPhone');
		}

		if (!$this->journal3->is_popup && $footer_menu) {
			$this->journal3->set('footer_menu_id', $footer_menu);

			static::$layout['footer_menu'] = $this->load->controller('journal3/footer_menu', array(
				'module_type' => 'footer_menu',
				'module_id'   => $footer_menu,
			));
		}
	}

	public function view_common_footer_before(&$eventRoute, &$data) {
		$data['journal3_bottom'] = static::$layout['bottom'] ?? null;
		$data['journal3_footer_menu'] = static::$layout['footer_menu'] ?? null;
	}

	public function view_before(&$route, &$args) {
		$args['journal3_top'] = static::$layout['top'] ?? null;

		if (!empty($args['content_top'])) {
			$args['content_top'] = (string)$args['content_top'];
		}

		if (!empty($args['content_bottom'])) {
			$args['content_bottom'] = (string)$args['content_bottom'];
		}

		if (!empty($args['column_left'])) {
			$args['column_left'] = (string)$args['column_left'];
		}

		if (!empty($args['column_right'])) {
			$args['column_right'] = (string)$args['column_right'];
		}
	}

	public static function result($position = '') {
		if (version_compare(VERSION, '4.1.0.0', '>=')) {
			return self::$layout[$position] ?? '';
		}

		if (version_compare(VERSION, '4.0.2.0', '>=')) {
			if ($position && method_exists(self::class, $position)) {
				return new \Opencart\System\Engine\Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . $position);
			}

			return new \Opencart\System\Engine\Action('journal3/event/layout' . JOURNAL3_ROUTE_SEPARATOR . 'empty');
		} else {
			if ($position && !empty(static::$layout[$position])) {
				return static::$layout[$position];
			}

			return new EventResult();
		}
	}

	public function column_left() {
		return self::$layout['column_left'] ?? '';
	}

	public function column_right() {
		return self::$layout['column_right'] ?? '';
	}

	public function content_top() {
		return self::$layout['content_top'] ?? '';
	}

	public function content_bottom() {
		return self::$layout['content_bottom'] ?? '';
	}

	public function empty() {
		return '';
	}

	public function view_common_position_before(&$route, &$args, &$output) {
		$position = str_replace('journal3/common/', '', $route);

		$output = static::$layout[$position] ?? '';
	}

	public function model_design_layout_getModules_after(&$route, &$args, &$output) {
		$output = [];
	}

}

class_alias('ControllerJournal3EventLayout', '\Opencart\Catalog\Controller\Journal3\Event\Layout');
