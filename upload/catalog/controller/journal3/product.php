<?php

class ControllerJournal3Product extends Controller {

	public function index() {
		$this->request->get['route'] = 'product/product';

		return $this->load->controller('product/product');
	}

}

class_alias('ControllerJournal3Product', '\Opencart\Catalog\Controller\Journal3\Product');
