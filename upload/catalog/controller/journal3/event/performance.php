<?php

use Journal3\Opencart\EventResult;
use Journal3\Utils\Str;

class ControllerJournal3EventPerformance extends Controller {

	/**
	 *
	 * Disable unneeded catalog/controller/common/menu execution, we use our own menu builder
	 *
	 * @param $eventRoute
	 * @param $data
	 * @return EventResult
	 */
	public function controller_common_menu_before(&$eventRoute, &$data) {
		return new EventResult();
	}

	/**
	 *
	 * Disable unneeded call to catalog/model/catalog/category/getCategories, we use or own menu builder
	 *
	 * @param $route
	 * @param $args
	 */
	public function model_catalog_category_getCategories_after(&$route, &$args, &$output) {
		if ($this->journal3_opencart->is_oc2) {
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

			foreach ($backtrace as $trace) {
				if (!empty($trace['class']) && $trace['class'] === 'ControllerCommonHeader') {
					$output = [];
					break;
				}
			}
		}
	}

	/**
	 *
	 * Disable unneeded catalog/model/information/getInformations query in footer, we use our own footer builder
	 *
	 * @param $route
	 * @param $args
	 * @return EventResult
	 */
	public function model_catalog_information_getInformations_before(&$route, &$args) {
		if (!Str::contains($this->request->get['route'] ?? '', 'sitemap')) {
			return new EventResult();
		}
	}

}

class_alias('ControllerJournal3EventPerformance', '\Opencart\Catalog\Controller\Journal3\Event\Performance');
