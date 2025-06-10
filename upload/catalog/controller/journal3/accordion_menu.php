<?php

use Journal3\Opencart\MenuController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3AccordionMenu extends MenuController {

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		return array(
			'edit' => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name' => $parser->getSetting('name'),
		);
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		$data = [
			'isOpen' => !$parser->getSetting('collapsed'),
		];

		return $data;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseSubitemSettings($parser, $index) {
		return $this->parseItemSettings($parser, $index);
	}

	protected function beforeRender() {
		if (isset($this->request->get['path'])) {
			$category_ids = explode('_', $this->request->get['path']);

			if ($category_ids) {
				$this->parse($category_ids, $this->settings['items'], 0);
			}
		}

		$url_params = [
			'product'       => 'product_id',
			'information'   => 'information_id',
			'blog_post'     => 'journal_blog_post_id',
			'blog_category' => 'journal_blog_category_id',
		];

		foreach ($url_params as $url_type => $url_param) {
			$url_id = $this->request->get[$url_param] ?? null;

			if ($url_id) {
				foreach ($this->settings['items'] as &$item) {
					$type = $item['link']['type'] ?? null;
					$id = $item['link']['id'] ?? null;

					if ($url_type === $type && $url_id == $id) {
						$item['classes'][] = 'open active';
						$item['isOpen'] = true;
					}
				}

				break;
			}
		}

		if ($this->config->get('config_product_count')) {
			foreach ($this->settings['items'] as &$item) {
				$item['link']['total'] = 0;

				foreach ($item['items'] as $subitem) {
					$item['link']['total'] += $subitem['total'] ?? 0;
				}
			}
		}
	}

	private function parse($category_ids, &$items, $index) {
		if (isset($category_ids[$index])) {
			foreach ($items as &$item) {
				if ($index === 0) {
					$category_id = Arr::get($item, 'link.id');
				} else {
					$category_id = Arr::get($item, 'category_id');
				}

				if ($category_id === $category_ids[$index]) {
					$item['classes'][] = 'open active';
					$item['isOpen'] = true;
				}

				if (isset($item['items'])) {
					$this->parse($category_ids, $item['items'], $index + 1);
				}
			}
		}
	}

}

class_alias('ControllerJournal3AccordionMenu', '\Opencart\Catalog\Controller\Journal3\AccordionMenu');
