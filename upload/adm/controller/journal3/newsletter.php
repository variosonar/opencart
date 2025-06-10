<?php

class ControllerJournal3Newsletter extends Controller {

	public function __construct($registry) {
		parent::__construct($registry);
		$this->load->model('journal3/newsletter');
		$this->load->language('error/permission');
	}

	public function all() {
		try {
			$filters = array(
				'filter' => $this->journal3_request->get('filter', ''),
				'sort'   => $this->journal3_request->get('sort', ''),
				'order'  => $this->journal3_request->get('order', ''),
				'page'   => $this->journal3_request->get('page', '1'),
				'limit'  => $this->journal3_request->get('limit', '10'),
			);

			$this->journal3_response->json('success', $this->model_journal3_newsletter->all($filters));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function remove() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/newsletter')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = $this->journal3_request->get('id');

			$this->journal3_response->json('success', $this->model_journal3_newsletter->remove($id));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function export() {
		header('Pragma: public');
		header('Expires: 0');
		header('Content-Description: File Transfer');
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename=' . date('Y-m-d_H-i-s', time()) . '_newsletter_list.csv');
		header('Content-Transfer-Encoding: binary');

		echo 'Name,Customer,Store' . PHP_EOL;

		$subscribers = $this->model_journal3_newsletter->all();

		foreach ($subscribers['items'] as $subscriber) {
			echo "{$subscriber['name']},{$subscriber['email']},{$subscriber['store_id']}" . PHP_EOL;
		}

		exit();
	}

}

class_alias('ControllerJournal3Newsletter', '\Opencart\Admin\Controller\Journal3\Newsletter');
