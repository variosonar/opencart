<?php

class ControllerJournal3EventMaintenance extends Controller {

	public function view_common_maintenance_before(&$route, &$args) {
		$this->document->setTitle($this->journal3->get('maintenanceMetaTitle'));
	}

}

class_alias('ControllerJournal3EventMaintenance', '\Opencart\Catalog\Controller\Journal3\Event\Maintenance');
