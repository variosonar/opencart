<?php

class ControllerJournal3BlocksExample extends Controller {

	public function index($args) {
		return 'Dynamic Content for module_id = ' . $args['module_id'];
	}

}

class_alias('ControllerJournal3BlocksExample', '\Opencart\Catalog\Controller\Journal3\BlocksExample');
