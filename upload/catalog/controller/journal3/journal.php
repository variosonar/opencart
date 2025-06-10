<?php

use Journal3\Utils\Arr;

class ControllerJournal3Journal extends Controller {

	public function image_tools() {
		try {
			if (!function_exists('exec')) {
				throw new \Exception('exec function is not enabled!');
			}

			if (in_array(strtolower(ini_get('safe_mode')), array('on', '1'), true)) {
				throw new \Exception('safe_mode is on');
			}

			$disabled_functions = explode(',', ini_get('disable_functions'));

			if (in_array('exec', $disabled_functions)) {
				throw new \Exception('exec function is in disable_functions');
			}

			$this->journal3_response->json('success', $this->journal3_image->canOptimise());
		} catch (\Exception $e) {
			$this->journal3_response->json('success', array(
				'error' => $e->getMessage(),
			));
		}
	}

	public function device_detect() {
		try {
			$device = $this->journal3_request->post('device');
			$session_device = Arr::get($this->session->data, 'journal3_device');

			$reload = false;

			if ($device !== $session_device) {
				$this->session->data['journal3_device'] = $device;
				$reload = true;
			}

			$this->journal3_response->json('success', array(
				'device' => $device,
				'reload' => $reload,
			));
		} catch (\Exception $e) {
			$this->journal3_response->json('success', array(
				'error' => $e->getMessage(),
			));
		}
	}

}

class_alias('ControllerJournal3Journal', '\Opencart\Catalog\Controller\Journal3\Journal');
