<?php

class ControllerJournal3EventFooter extends Controller {

	public function controller_common_footer_before(&$route, &$args) {
		if (JOURNAL3_LIVERELOAD) {
			$this->document->addScript('catalog/view/theme/journal3/stylesheet/style.min.js', 'footer');
		}
	}

	public function view_common_footer_before(&$route, &$args) {
		if (!empty($args['styles'])) {
			if ($this->journal3->get('performanceCSSMinify')) {
				$args['styles'] = $this->journal3_assets->minifyStyles($args['styles']);
			}

			$args['styles'] = $this->journal3_assets->styles($args['styles']);
		}

		if (!empty($args['scripts'])) {
			if ($this->journal3->get('performanceJSMinify')) {
				$args['scripts'] = $this->journal3_assets->minifyScripts($args['scripts']);
			}

			$args['scripts'] = $this->journal3_assets->scripts($args['scripts']);
		}
	}

}

class_alias('ControllerJournal3EventFooter', '\Opencart\Catalog\Controller\Journal3\Event\Footer');
