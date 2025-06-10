<?php

use Journal3\Opencart\ModuleController;

class ControllerJournal3BlogComments extends ModuleController {

	/**
	 * @param \Journal3\Options\Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$this->load->model('journal3/blog');

		$results = $this->model_journal3_blog->getLatestComments();

		$items = [];

		foreach ($results as $result) {
			$items[] = [
				'classes'  => [
					'module-item',
				],
				'title'    => $result['post'],
				'avatar'   => md5(strtolower(trim($result['email']))),
				'href'     => $this->journal3_url->link('journal3/blog' . JOURNAL3_ROUTE_SEPARATOR . 'post', 'journal_blog_post_id=' . $result['post_id']) . '#c' . $result['comment_id'],
				'subtitle' => $result['name'],
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

class_alias('ControllerJournal3BlogComments', '\Opencart\Catalog\Controller\Journal3\BlogComments');
