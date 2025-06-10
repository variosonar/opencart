<?php

class ControllerJournal3EventNotFound extends Controller {

	public function view_error_not_found_before(&$route, &$args) {
		$this->load->language('error/not_found');
	}

}

class_alias('ControllerJournal3EventNotFound', '\Opencart\Catalog\Controller\Journal3\Event\NotFound');
