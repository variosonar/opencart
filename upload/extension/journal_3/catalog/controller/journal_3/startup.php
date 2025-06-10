<?php

namespace Opencart\Catalog\Controller\Extension\Journal3\Journal3;

use Journal3\Utils\Str;

class Startup extends \Opencart\System\Engine\Controller {

	public function index() {
		if (!$this->config->get('theme_journal_3_status') || $this->config->get('config_theme') !== 'journal_3') {
			return;
		}

		if (!empty($this->session->data['user_id']) && !empty($this->request->get['j3dt'])) {
			return;
		}

		if (!defined('JOURNAL3_INSTALLED')) {
			$json = json_decode(file_get_contents(DIR_EXTENSION . 'journal_3/install.json'), true);

			define('JOURNAL3_INSTALLED', $json['version'] ?? '3.2.5');

			if (version_compare(VERSION, '4.0.2.0', '>=')) {
				define('JOURNAL3_ROUTE_SEPARATOR', '.');
			} else {
				define('JOURNAL3_ROUTE_SEPARATOR', '|');
			}

			if (!defined('JOURNAL3_EVENT_SEPARATOR')) {
				if (version_compare(VERSION, '4.1.0.0', '>=')) {
					define('JOURNAL3_EVENT_SEPARATOR', '.');
				} else {
					define('JOURNAL3_EVENT_SEPARATOR', '/');
				}
			}

			class_alias('\Opencart\System\Engine\Action', '\Action', false);
			class_alias('\Opencart\System\Engine\Controller', '\Controller', false);
			class_alias('\Opencart\System\Engine\Model', '\Model', false);
		}

		spl_autoload_register(function ($class) {
			$file = DIR_SYSTEM . 'library/' . str_replace('\\', '/', strtolower($class)) . '.php';

			if (is_file($file)) {
				include_once($file);

				return true;
			} else {
				return false;
			}
		});

		$this->autoloader->register('Opencart\Catalog\Controller\Api\Journal3', DIR_APPLICATION . 'controller/api/journal3/');
		$this->autoloader->register('Opencart\Catalog\Controller\Journal3', DIR_APPLICATION . 'controller/journal3/');
		$this->autoloader->register('Opencart\Catalog\Controller\Journal3\Event', DIR_APPLICATION . 'controller/journal3/event/');
		$this->autoloader->register('Opencart\Catalog\Model\Journal3', DIR_APPLICATION . 'model/journal3/');

		$this->template->addPath('journal3', DIR_APPLICATION . 'view/theme/journal3/template/');

		$this->decode_blog_url();
		$this->event->register('view/*/before', new \Opencart\System\Engine\Action('extension/journal3/journal3/startup' . JOURNAL3_ROUTE_SEPARATOR . 'event'));

		// paypal fix?!?
		$this->event->register('controller/common/footer/before', new \Opencart\System\Engine\Action('extension/paypal/payment/paypal' . JOURNAL3_ROUTE_SEPARATOR . 'content_top_before'));

		$this->load->controller('journal3/startup');
	}

	public function event(string &$route, array &$args, mixed &$output): void {
		if (str_starts_with($route, 'extension/opencart')) {
			$_route = substr($route, strlen('extension/opencart/'));

			if (is_file(DIR_EXTENSION . 'journal_3/catalog/view/template/' . $_route . '.twig')) {
				$route = 'extension/journal_3/' . $_route;

				return;
			}
		}

		if (version_compare(VERSION, '4.1.0.0', '>=') && is_file(DIR_EXTENSION . 'journal_3/catalog/view/template/' . $route . '_41.twig')) {
			$route = 'extension/journal_3/' . $route . '_41';

			return;
		}

		if (is_file(DIR_EXTENSION . 'journal_3/catalog/view/template/' . $route . '.twig')) {
			$route = 'extension/journal_3/' . $route;

			return;
		}

		if (is_file(DIR_APPLICATION . 'view/theme/journal3/template/' . $route . '.twig')) {
			$route = 'journal3/' . $route;

			return;
		}
	}

	private function getBlogKeyword() {
		static $blog_keyword;

		if ($blog_keyword === null) {
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "journal3_setting` WHERE `setting_name` = 'blogPageKeyword' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "'");
			$setting_value = json_decode($query->row['setting_value'] ?? '{}', true);
			$blog_keyword = $setting_value['lang_' . (int)$this->config->get('config_language_id')] ?? '';
		}

		return $blog_keyword;
	}

	public function decode_blog_url() {
		if ($this->config->get('config_seo_url')) {
			$this->event->register('model/design/seo_url/getSeoUrlByKeyValue/after', new \Opencart\System\Engine\Action('extension/journal3/journal3/startup' . JOURNAL3_ROUTE_SEPARATOR . 'rewrite_blog_url'));

			if (isset($this->request->get['_route_'])) {
				$parts = explode('/', $this->request->get['_route_']);

				// remove any empty arrays from trailing
				if (Str::utf8_strlen(end($parts)) == 0) {
					array_pop($parts);
				}

				$is_blog = false;
				$journal_blog_category_id = 0;
				$journal_blog_post_id = 0;

				foreach ($parts as $part) {
					if ($part === $this->getBlogKeyword()) {
						$is_blog = true;
					} else {
						$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "journal3_blog_post_description` WHERE `keyword` = '" . $this->db->escape($part) . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1");

						if ($query->row) {
							$journal_blog_post_id = $query->row['post_id'];
						} else {
							$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "journal3_blog_category_description` WHERE `keyword` = '" . $this->db->escape($part) . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1");

							if ($query->row) {
								$journal_blog_category_id = $query->row['category_id'];
							}
						}
					}
				}

				if ($journal_blog_post_id) {
					$this->request->get['route'] = 'journal3/blog' . JOURNAL3_ROUTE_SEPARATOR . 'post';
					$this->request->get['journal_blog_post_id'] = $journal_blog_post_id;

					if ($journal_blog_category_id) {
						$this->request->get['journal_blog_category_id'] = $journal_blog_category_id;
					}
				} else if ($journal_blog_category_id) {
					$this->request->get['route'] = 'journal3/blog';
					$this->request->get['journal_blog_category_id'] = $journal_blog_category_id;
				} else if ($is_blog) {
					$this->request->get['route'] = 'journal3/blog';
				}
			}
		}
	}

	public function rewrite_blog_url(string &$route, array &$args, &$output) {
		$key = $args[0] ?? null;
		$value = $args[1] ?? null;

		if ($key === 'route' && Str::startsWith($value, 'journal3/blog')) {
			if ($this->getBlogKeyword()) {
				$output = [
					'sort_order' => 1,
					'keyword'    => $this->getBlogKeyword(),
				];
			}
		} elseif ($key === 'journal_blog_category_id') {
			$query = $this->db->query("SELECT keyword, '100' as sort_order FROM `" . DB_PREFIX . "journal3_blog_category_description` WHERE `category_id` = '" . $this->db->escape($value) . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "'");

			$output = $query->row;
		} elseif ($key === 'journal_blog_post_id') {
			$query = $this->db->query("SELECT keyword, '200' as sort_order FROM `" . DB_PREFIX . "journal3_blog_post_description` WHERE `post_id` = '" . $this->db->escape($value) . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "'");

			$output = $query->row;
		}
	}

}
