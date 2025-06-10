<?php

namespace Journal3;

/**
 * Class Response
 *
 * It's similar to Opencart Response class but with additional methods
 *
 * @package Journal3
 */
class Response extends Base {

	private $push_assets = [];

	/**
	 * @param $status
	 * @param array $response
	 */
	public function json($status, $response = []) {
		$output = json_encode([
			'status'   => $status,
			'response' => $response,
		]);

		$output = str_replace('&amp;', '&', $output);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput($output);
	}

	public function addPushAsset(string $link, string $args) {
		$this->push_assets[] = [
			'link' => $link,
			'args' => $args,
		];
	}

	public function pushAssets() {
		if ($this->journal3_request->is_https && !$this->journal3_request->is_ajax && !$this->journal3_request->is_post && !headers_sent()) {
			$path = parse_url($this->config->get('config_ssl'), PHP_URL_PATH);

			foreach ($this->push_assets as $push_asset) {
				header('Link: <' . $path . $push_asset['link'] . '>; ' . $push_asset['args'], false);
			}
		}
	}

}
