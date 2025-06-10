<?php

class ControllerJournal3Profiler extends Controller {

	private static $start_view;

	public function before_view(&$route, &$args) {
		static::$start_view = microtime(true);
	}

	public function after_view(&$route, &$args, &$output) {
		clock()->addView($route, $args, [
			'time'     => static::$start_view,
			'duration' => microtime(true) - static::$start_view,
		]);
	}

	public function before_controller(&$route, &$args) {
		$module_id = !empty($args['module_id']) ? '/' . $args['module_id'] : '';

		clock()->event($route . $module_id)->name($route . $module_id)->begin();
	}

	public function after_controller(&$route, &$args, &$output) {
		$module_id = !empty($args['module_id']) ? '/' . $args['module_id'] : '';

		clock()->event($route . $module_id)->end();
	}

	public function clockwork() {
		if (function_exists('clock') && !empty($this->request->get['request'])) {
			clock()->returnMetadata($this->request->get['request']);
		}
	}

}

class_alias('ControllerJournal3Profiler', '\Opencart\Catalog\Controller\Journal3\Profiler');
