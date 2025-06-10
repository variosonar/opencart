<?php

use Journal3\Opencart\ModuleController;

class ControllerJournal3BlogTags extends ModuleController {

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$this->load->model('journal3/blog');

		$results = $this->model_journal3_blog->getTags($parser->getSetting('limit'));

		$items = [];

		foreach ($results as $result) {
			$items[] = [
				'name' => $result,
				'href' => $this->journal3_url->link('journal3/blog', 'journal_blog_tag=' . $result),
			];
		}

		return [
			'edit'  => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'  => $parser->getSetting('name'),
			'items' => $items,
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

}

class_alias('ControllerJournal3BlogTags', '\Opencart\Catalog\Controller\Journal3\BlogTags');
