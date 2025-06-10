<?php

class ControllerJournal3EventCache extends Controller {

	public function model_checkout_order_addOrderHistory_after(&$route, &$args) {
		$order_id = $args[0] ?? 0;

		$this->journal3_cache->delete('module');

		$bestsellers = new \Journal3\Opencart\Bestseller($this->registry);
		$bestsellers->init();
		$bestsellers->update($order_id);
	}

}

class_alias('ControllerJournal3EventCache', '\Opencart\Catalog\Controller\Journal3\Event\Cache');
