<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3Faq extends ModuleController {

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$data = array(
			'edit' => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name' => $parser->getSetting('name'),
		);

		$default = $parser->getSetting('default');

		$data['default_index'] = -1;

		if ($default) {
			foreach (Arr::get($this->module_data, 'items') as $index => $item) {
				if ($default === Arr::get($item, 'id')) {
					$data['default_index'] = $index + 1;
					break;
				}
			}
		}

		return $data;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		return array(
			'panel_classes' => array(
				'panel-collapse',
				'collapse',
				'in' => $index === $this->settings['default_index'],
			),
			'classes'       => array(
				'panel',
				'panel-active' => $index === $this->settings['default_index'],
			),
			'has_icon'      => (bool)$parser->getSetting('icon'),
		);
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseSubitemSettings($parser, $index) {
		return array();
	}

	protected function beforeRender() {
		if (!empty($this->settings['items'][$this->settings['default_index']])) {
			$this->settings['items'][$this->settings['default_index']]['active'] = true;
			$this->settings['items'][$this->settings['default_index']]['classes'][] = 'active';
			$this->settings['items'][$this->settings['default_index']]['panel_classes'][] = 'in';
		}

		if ($this->journal3->get('seoGoogleRichSnippetsStatus')) {
			$json = [
				"@context"   => "https://schema.org",
				"@type"      => "FAQPage",
				"mainEntity" => [],
			];

			foreach ($this->settings['items'] as $item) {
				$json['mainEntity'][] = [
					"@type"          => "Question",
					"name"           => $item['title'],
					"acceptedAnswer" => [
						"@type" => "Answer",
						"text"  => $item['content'],
					],
				];
			}

			$this->settings['faq_schema'] = '<script type="application/ld+json">' . json_encode($json) . '</script>';
		} else {
			$this->settings['faq_schema'] = null;
		}
	}

}

class_alias('ControllerJournal3Faq', '\Opencart\Catalog\Controller\Journal3\Faq');
