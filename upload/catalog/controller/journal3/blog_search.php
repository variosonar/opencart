<?php

use Journal3\Opencart\ModuleController;
use Journal3\Utils\Arr;

class ControllerJournal3BlogSearch extends ModuleController {

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		return [
			'edit'   => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'   => $parser->getSetting('name'),
			'search' => Arr::get($this->request->get, 'journal_blog_search'),
			'url'    => $this->journal3_url->link('journal3/blog', 'journal_blog_search='),
		];
	}

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		return [];
	}

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseSubitemSettings($parser, $index) {
		return [];
	}

	protected function afterRender() {
		$this->document->addScript('catalog/view/theme/journal3/js/blog_search.js', 'js-defer');
	}

}

class_alias('ControllerJournal3BlogSearch', '\Opencart\Catalog\Controller\Journal3\BlogSearch');
