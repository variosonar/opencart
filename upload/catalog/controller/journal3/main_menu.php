<?php

use Journal3\Opencart\MenuController;
use Journal3\Options\Parser;

class ControllerJournal3MainMenu extends MenuController {

	private $label_id = 0;

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$display = $this->is_mobile ? 'accordion' : 'dropdown';

		$data = array(
			'edit'    => 'module_header/' . $this->module_type . '/edit/' . $this->module_id,
			'name'    => $parser->getSetting('name'),
			'classes' => array(
				'accordion-menu' => $display !== 'dropdown',
			),
			'display' => $display,
		);

		return $data;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		$is_open = false;

		if ($parser->getSetting('type') === 'flyout') {
			if ($parser->getSetting('mobileOpen') && $this->is_mobile) {
				$is_open = true;
			}

			if ($parser->getSetting('homeOpen')) {
				$is_open = true;
			}

			if ($parser->getSetting('pagesOpen')) {
				$is_open = true;
			}

			if ($parser->getSetting('stickyOpen')) {
				$is_open = true;
			}
		}

		$classes = array(
			'icon-only'              => $parser->getSetting('iconOnly'),
			'menu-fullwidth'         => ($parser->getSetting('type') === 'megamenu') && ($parser->getSetting('megaMenuBGFull')) && ($parser->getSetting('megaMenuLayout') === 'full'),
			'mega-fullwidth'         => ($parser->getSetting('type') === 'megamenu') && ($parser->getSetting('megaMenuLayout') === 'full'),
			'mega-custom'            => ($parser->getSetting('type') === 'megamenu') && ($parser->getSetting('megaMenuLayout') === 'custom'),
			'menu-fullwidth-content' => ($parser->getSetting('type') === 'megamenu') && ($parser->getSetting('megaMenuLayout') === 'full') && ($parser->getSetting('megaMenuBGFull')) && ($parser->getSetting('megaMenuContentFull')),
			'drop-menu'              => ($parser->getSetting('type') === 'multilevel') || ($parser->getSetting('type') === 'flyout'),
		);

		if ($parser->getSetting('label')) {
			$classes[] = 'has-label has-label-' . ++$this->label_id;
		}

		return array(
			'classes' => $classes,
			'isOpen'  => $is_open,
		);
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

class_alias('ControllerJournal3MainMenu', '\Opencart\Catalog\Controller\Journal3\MainMenu');
