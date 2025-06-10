<?php

use Journal3\Utils\Arr;

class ControllerJournal3Message extends Controller {

	public function __construct($registry) {
		parent::__construct($registry);
		$this->load->model('journal3/message');
		$this->load->model('tool/upload');
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

			$this->journal3_response->json('success', $this->model_journal3_message->all($filters));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function get() {
		try {
			$id = $this->journal3_request->get('id');

			$message = $this->model_journal3_message->get($id);

			if (is_array(Arr::get($message, 'fields'))) {
				foreach ($message['fields'] as &$field) {
					if ($field['type'] === 'file') {
						$field['code'] = Arr::get($field, 'code');

						// Prior to v.3.0.46, this field was not saved
						if (!$field['code']) {
							parse_str(htmlspecialchars_decode($field['url'], PHP_URL_QUERY), $results);
							$field['code'] = Arr::get($results, 'code');
						}

						$upload_info = $this->model_tool_upload->getUploadByCode($field['code']);

						$field['value'] = $upload_info['name'];

						if ($this->journal3_opencart->is_oc2) {
							$field['url'] = $this->url->link('tool/upload/download', 'token=' . $this->session->data['token'] . '&code=' . $upload_info['code'], true);
						} else {
							$field['url'] = $this->url->link('tool/upload' . JOURNAL3_ROUTE_SEPARATOR . 'download', 'user_token=' . $this->session->data['user_token'] . '&code=' . $upload_info['code'], true);
						}
					}
				}
			}

			$this->journal3_response->json('success', $message);
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

	public function remove() {
		try {
			if (!$this->user->hasPermission('modify', 'journal3/message')) {
				throw new Exception($this->language->get('text_permission'));
			}

			$id = $this->journal3_request->get('id');

			$this->journal3_response->json('success', $this->model_journal3_message->remove($id));
		} catch (Exception $e) {
			$this->journal3_response->json('error', $e->getMessage());
		}
	}

}

class_alias('ControllerJournal3Message', '\Opencart\Admin\Controller\Journal3\Message');
