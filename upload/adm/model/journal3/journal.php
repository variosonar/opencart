<?php

use Journal3\Opencart\Tables;

class ModelJournal3Journal extends Model {

	public function __construct($registry) {
		parent::__construct($registry);

		if (!$this->registry->has('journal3_db')) {
			$this->registry->set('journal3_db', new \Journal3\DB($this->registry));
		}
	}

	public function database() {
		foreach (Tables::TABLES as $name => $sql) {
			$this->db->query(sprintf($sql, $this->journal3_db->prefix($name)));
		}

		// variable label
		$query = $this->db->query("DESCRIBE `{$this->journal3_db->prefix('journal3_variable')}`");

		$found = false;

		foreach ($query->rows as $row) {
			if ($row['Field'] === 'variable_label') {
				$found = true;
				break;
			}
		}

		if (!$found) {
			$this->db->query("
				ALTER TABLE `{$this->journal3_db->prefix('journal3_variable')}`
				ADD `variable_label` VARCHAR(64) NOT NULL AFTER `variable_name`
			");

			$this->db->query("
				UPDATE `{$this->journal3_db->prefix('journal3_variable')}`
				SET `variable_label` = `variable_name`
			");
		}

		// style label
		$query = $this->db->query("DESCRIBE `{$this->journal3_db->prefix('journal3_style')}`");

		$found = false;

		foreach ($query->rows as $row) {
			if ($row['Field'] === 'style_label') {
				$found = true;
				break;
			}
		}

		if (!$found) {
			$this->db->query("
				ALTER TABLE `{$this->journal3_db->prefix('journal3_style')}`
				ADD `style_label` VARCHAR(64) NOT NULL AFTER `style_name`
			");

			$this->db->query("
				UPDATE `{$this->journal3_db->prefix('journal3_style')}`
				SET `style_label` = `style_name`
			");
		}

		// style table fixes
		$query = $this->db->query("DESCRIBE `{$this->journal3_db->prefix('journal3_style')}`");

		foreach ($query->rows as $row) {
			if ($row['Field'] === 'style_value' && strtolower($row['Type']) !== 'mediumtext') {
				$this->db->query("
					ALTER TABLE `{$this->journal3_db->prefix('journal3_style')}` 
					CHANGE `style_value` `style_value` MEDIUMTEXT
				");
			}
		}

		// newsletter ip log fix
		$query = $this->db->query("DESCRIBE `{$this->journal3_db->prefix('journal3_newsletter')}`");

		$found = false;

		foreach ($query->rows as $row) {
			if ($row['Field'] === 'ip') {
				$found = true;
				break;
			}
		}

		if (!$found) {
			$this->db->query("
				ALTER TABLE `{$this->journal3_db->prefix('journal3_newsletter')}`
				ADD `ip` VARCHAR(40) NOT NULL AFTER `email`
			");
		}

		// blog category meta robots
		$query = $this->db->query("DESCRIBE `{$this->journal3_db->prefix('journal3_blog_category_description')}`");

		$found = false;

		foreach ($query->rows as $row) {
			if ($row['Field'] === 'meta_robots') {
				$found = true;
				break;
			}
		}

		if (!$found) {
			$this->db->query("
				ALTER TABLE `{$this->journal3_db->prefix('journal3_blog_category_description')}`
				ADD `meta_robots` VARCHAR(256) NOT NULL AFTER `meta_keywords`
			");
		}

		// blog post meta robots
		$query = $this->db->query("DESCRIBE `{$this->journal3_db->prefix('journal3_blog_post_description')}`");

		$found = false;

		foreach ($query->rows as $row) {
			if ($row['Field'] === 'meta_robots') {
				$found = true;
				break;
			}
		}

		if (!$found) {
			$this->db->query("
				ALTER TABLE `{$this->journal3_db->prefix('journal3_blog_post_description')}`
				ADD `meta_robots` VARCHAR(256) NOT NULL AFTER `meta_keywords`
			");
		}

		// blog post post details
		$query = $this->db->query("DESCRIBE `{$this->journal3_db->prefix('journal3_blog_post')}`");

		$found = false;

		foreach ($query->rows as $row) {
			if ($row['Field'] === 'post_data') {
				$found = true;
				break;
			}
		}

		if (!$found) {
			$this->db->query("
				ALTER TABLE `{$this->journal3_db->prefix('journal3_blog_post')}`
				ADD `post_data` MEDIUMTEXT NOT NULL AFTER `views`
			");
		}

		// enable theme on all stores
		$this->load->model('setting/store');

		$stores = $this->model_setting_store->getStores();

		array_unshift($stores, array(
			'store_id' => '0',
			'name'     => $this->config->get('config_name'),
		));

		$query = $this->db->query("SELECT * FROM `{$this->journal3_db->prefix('setting')}` WHERE `key` = 'theme_journal3_status'");

		$current = array();

		foreach ($query->rows as $row) {
			$current[$row['store_id']] = (int)$row['setting_id'];
		}

		foreach ($stores as $store) {
			if (isset($current[$store['store_id']])) {
				$this->db->query("UPDATE `{$this->journal3_db->prefix('setting')}` SET `value` = '1' WHERE `setting_id` = '{$current[$store['store_id']]}'");
			} else {
				$this->db->query("
					INSERT INTO `{$this->journal3_db->prefix('setting')}` (
						`store_id`,
						`code`,
						`key`,
						`value`,
						`serialized`
					) VALUES (
						'{$this->journal3_db->escapeInt($store['store_id'])}',
						'theme_journal3',
						'theme_journal3_status',
						'1',
						'0'
					)
				");
			}
		}
	}

	public function isInstalled() {
		return $this->db->query(str_replace('_', '\_', "SHOW TABLES LIKE '{$this->journal3_db->prefix('journal3_')}%'"))->num_rows >= count(Tables::TABLES);
	}

	public function install() {
		$this->database();

		$this->load->model('user/user_group');

		$files = glob(DIR_APPLICATION . 'controller/journal3/*.php');
		$data = $this->model_user_user_group->getUserGroup($this->user->getGroupId());

		foreach ($files as $file) {
			$file = 'journal3/' . str_replace('.php', '', basename($file));

			$data['permission']['access'][] = $file;
			$data['permission']['modify'][] = $file;
		}

		$data['permission']['access'] = array_unique($data['permission']['access']);
		$data['permission']['modify'] = array_unique($data['permission']['modify']);

		$this->model_user_user_group->editUserGroup($this->user->getGroupId(), $data);
	}

	public function uninstall() {
		foreach (Tables::TABLES as $name => $sql) {
			$this->db->query("DROP TABLE IF EXISTS `{$this->journal3_db->prefix($name)}`");
		}
	}

	private function _getSearchCondition($field_name, $field_id, $keyword, $id) {
		if ($id) {
			return "{$field_id} = '{$this->journal3_db->escapeInt($id)}'";
		}

		return "{$field_name} LIKE '%{$this->journal3_db->escape($keyword)}%'";
	}

	public function getProducts($keyword, $id) {
		return $this->db->query("
			SELECT
				p.product_id AS id,
			 	pd.name AS name
			FROM 
				`{$this->journal3_db->prefix('product')}` p 
			LEFT JOIN 
				`{$this->journal3_db->prefix('product_description')}` pd ON (p.product_id = pd.product_id) 
			WHERE 
				pd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
				AND {$this->_getSearchCondition('pd.name', 'p.product_id', $keyword, $id)}
			GROUP BY 
				p.product_id
			ORDER BY 
				pd.name ASC	
		")->rows;
	}

	public function getCategories($keyword, $id) {
		return $this->db->query("
			SELECT
				cp.category_id AS id,
			 	GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') AS name
			FROM `{$this->journal3_db->prefix('category_path')}` cp 
			LEFT JOIN `{$this->journal3_db->prefix('category')}` c1 ON (cp.category_id = c1.category_id) 
			LEFT JOIN `{$this->journal3_db->prefix('category')}` c2 ON (cp.path_id = c2.category_id) 
			LEFT JOIN `{$this->journal3_db->prefix('category_description')}` cd1 ON (cp.path_id = cd1.category_id) 
			LEFT JOIN `{$this->journal3_db->prefix('category_description')}` cd2 ON (cp.category_id = cd2.category_id)
			WHERE cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' 
				AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "' 
				AND {$this->_getSearchCondition('cd2.name', 'cp.category_id', $keyword, $id)}
			GROUP BY 
				cp.category_id
			ORDER BY 
				cd1.name ASC	
		")->rows;
	}

	public function getManufacturers($keyword, $id) {
		return $this->db->query("
			SELECT
				m.manufacturer_id AS id,
			 	m.name AS name
			FROM 
				`{$this->journal3_db->prefix('manufacturer')}` m
			WHERE 
				{$this->_getSearchCondition('name', 'manufacturer_id', $keyword, $id)}
			GROUP BY 
				m.manufacturer_id
			ORDER BY 
				m.name ASC	
		")->rows;
	}

	public function getInformations($keyword, $id) {
		return $this->db->query("
			SELECT
				i.information_id AS id,
			 	id.title AS name
			FROM 
				`{$this->journal3_db->prefix('information')}` i
			LEFT JOIN 
				`{$this->journal3_db->prefix('information_description')}` id ON (i.information_id = id.information_id) 
			WHERE 
				id.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
				AND {$this->_getSearchCondition('id.title', 'i.information_id', $keyword, $id)}
			GROUP BY 
				i.information_id
			ORDER BY 
				id.title ASC	
		")->rows;
	}

	public function getAttributes($keyword, $id = null) {
		$sql = "
			SELECT
				'attribute' as `type`,
				concat(a.attribute_id, '_', pa.text) as `id`,
				CONCAT(agd.name, ' > ', ad.name, ' > ', pa.text) as `name`
			FROM 
				`{$this->journal3_db->prefix('attribute')}` a
			LEFT JOIN 
				`{$this->journal3_db->prefix('attribute_description')}` ad ON (a.attribute_id = ad.attribute_id)
			LEFT JOIN
				`{$this->journal3_db->prefix('attribute_group')}` ag ON (a.attribute_group_id = ag.attribute_group_id)
			LEFT JOIN
				`{$this->journal3_db->prefix('attribute_group_description')}` agd ON (a.attribute_group_id = agd.attribute_group_id)
			LEFT JOIN
				`{$this->journal3_db->prefix($this->journal3->get('filterAttributeValuesSeparator') ? 'journal3_product_attribute' : 'product_attribute')}` pa ON (a.attribute_id = pa.attribute_id) 
			WHERE 
				ad.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
				AND agd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
				AND pa.text LIKE '%{$this->journal3_db->escape($keyword)}%'
			GROUP BY 
				id
			ORDER BY 
				agd.name, ad.name		
		";

		return $this->db->query($sql)->rows;
	}

	public function getAllAttributes() {
		$sql = "
			SELECT
				'attribute' as `type`,
				a.attribute_id as `id`,
				CONCAT(agd.name, ' > ', ad.name) as `name`
			FROM 
				`{$this->journal3_db->prefix('attribute')}` a
			LEFT JOIN 
				`{$this->journal3_db->prefix('attribute_description')}` ad ON (a.attribute_id = ad.attribute_id)
			LEFT JOIN
				`{$this->journal3_db->prefix('attribute_group')}` ag ON (a.attribute_group_id = ag.attribute_group_id)
			LEFT JOIN
				`{$this->journal3_db->prefix('attribute_group_description')}` agd ON (a.attribute_group_id = agd.attribute_group_id) 
			WHERE 
				ad.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
				AND agd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
			GROUP BY 
				a.attribute_id
			ORDER BY 
				agd.name, ad.name	
		";

		return $this->db->query($sql)->rows;
	}

	public function getOptions($keyword, $id = null) {
		$sql = "
			SELECT			  
				concat(o.option_id, '_', ov.option_value_id) as `id`,			  
				concat(od.name, ' > ', ovd.name) as `name`
			FROM
				`{$this->journal3_db->prefix('option')}` o
			LEFT JOIN 
				`{$this->journal3_db->prefix('option_value')}` ov ON (o.option_id = ov.option_id)
			LEFT JOIN 
				`{$this->journal3_db->prefix('option_description')}` od ON (o.option_id = od.option_id)
			LEFT JOIN 
				`{$this->journal3_db->prefix('option_value_description')}` ovd ON (ov.option_value_id = ovd.option_value_id)
			WHERE 
				ovd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
				AND ovd.name LIKE '%{$this->journal3_db->escape($keyword)}%'
			GROUP BY 
				id
			ORDER BY 
				od.name, ovd.name ASC	
		";

		return $this->db->query($sql)->rows;
	}

	public function getAllOptions() {
		$sql = "
			SELECT
				'option' as `type`,
				o.option_id as `id`,
				od.name as `name`
			FROM 
				`{$this->journal3_db->prefix('option')}` o
			LEFT JOIN 
				`{$this->journal3_db->prefix('option_description')}` od ON (o.option_id = od.option_id)		  
			WHERE 
				od.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
				AND o.type IN ('checkbox', 'radio', 'select')
			GROUP BY 
				o.option_id
			ORDER BY
				od.name	
		";

		return $this->db->query($sql)->rows;
	}


	public function getFilters($keyword, $id = null) {
		$sql = "
			SELECT			  
				concat(fg.filter_group_id, '_', f.filter_id) as `id`,			  
				concat(fgd.name, ' > ', fd.name) as `name`
			FROM
				`{$this->journal3_db->prefix('filter_group')}` fg
			LEFT JOIN 
				`{$this->journal3_db->prefix('filter_group_description')}` fgd ON (fg.filter_group_id = fgd.filter_group_id)
			LEFT JOIN 
				`{$this->journal3_db->prefix('filter')}` f ON (f.filter_group_id = fg.filter_group_id)
			LEFT JOIN 
				`{$this->journal3_db->prefix('filter_description')}` fd ON (f.filter_id = fd.filter_id)
			WHERE 
				fgd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
				AND fd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
				AND fd.name LIKE '%{$this->journal3_db->escape($keyword)}%'
			GROUP BY 
				id
			ORDER BY 
				fgd.name, fd.name ASC	
		";

		return $this->db->query($sql)->rows;
	}

	public function getAllFilters() {
		$sql = "
			SELECT
				'filter' as `type`,
				fg.filter_group_id as `id`,
				fgd.name as `name`
			FROM 
				`{$this->journal3_db->prefix('filter_group')}` fg
			LEFT JOIN
				`{$this->journal3_db->prefix('filter_group_description')}` fgd ON (fg.filter_group_id = fgd.filter_group_id) 
			WHERE 
				fgd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
			GROUP BY 
				fg.filter_group_id	
		";

		return $this->db->query($sql)->rows;
	}

	public function getOutOfStockStatuses($keyword) {
		return array();
	}

	public function getModules() {
		// Journal Modules
		$query = $this->db->query("SELECT module_id, module_type, module_name FROM `{$this->journal3_db->prefix('journal3_module')}` ORDER BY module_name ASC");

		$results = array();

		foreach ($query->rows as $row) {
			$results[$row['module_type']][$row['module_id']] = array(
				'id'    => $row['module_id'],
				'value' => $row['module_name'],
			);
		}

		// Opencart Modules
		foreach ($this->getOpencartModules() as $module) {
			$results['opencart'][$module['id']] = array(
				'id'    => $module['id'],
				'value' => $module['name'],
			);
		}

		return $results;
	}

	public function getOpencartModules() {
		$results = array();

		ob_start();

		if ($this->journal3_opencart->is_oc2) {
			$this->load->model('extension/extension');
			$this->load->model('extension/module');

			// Get a list of installed modules
			$extensions = $this->model_extension_extension->getInstalled('module');

			// Add all the modules which have multiple settings for each module
			foreach ($extensions as $code) {
				$this->load->language('extension/module/' . $code);

				$modules = $this->model_extension_module->getModulesByCode($code);

				$items = false;

				foreach ($modules as $module) {
					$items = true;

					$results[] = array(
						'name' => strip_tags($this->language->get('heading_title')) . ' - ' . strip_tags($module['name']),
						'id'   => $code . '/' . $module['module_id'],
					);
				}

				if (!$items && $this->config->has($code . '_status')) {
					$results[] = array(
						'name' => strip_tags($this->language->get('heading_title')),
						'id'   => $code,
					);
				}
			}
		} else {
			$this->load->model('setting/extension');
			$this->load->model('setting/module');

			if ($this->journal3_opencart->is_oc4) {
				// Get a list of installed modules
				$extensions = $this->model_setting_extension->getExtensionsByType('module');

				// Add all the modules which have multiple settings for each module
				foreach ($extensions as $extension) {
					$this->load->language('extension/' . $extension['extension'] . '/module/' . $extension['code'], $extension['code']);

					$modules = $this->model_setting_module->getModulesByCode($extension['extension'] . '.' . $extension['code']);

					$items = false;

					foreach ($modules as $module) {
						$items = true;

						$results[] = array(
							'name' => strip_tags($module['name']),
							'id'   => $extension['extension'] . '.' . $extension['code'] . '.' . $module['module_id'],
						);
					}

					if (!$items && $this->config->has('module_' . $extension['code'] . '_status')) {
						$results[] = array(
							'name' => strip_tags($this->language->get($extension['code'] . '_heading_title')),
							'id'   => $extension['extension'] . '.' . $extension['code'],
						);
					}
				}
			} else {
				// Get a list of installed modules
				if (version_compare(VERSION, '4', '>=') || defined('JOURNAL3_OLD_OC3039')) {
					$extensions = $this->model_setting_extension->getExtensionsByType('module');
				} else {
					$extensions = $this->model_setting_extension->getInstalled('module');
				}

				// Add all the modules which have multiple settings for each module
				foreach ($extensions as $code) {
					$this->load->language('extension/module/' . $code);

					$modules = $this->model_setting_module->getModulesByCode($code);

					$items = false;

					foreach ($modules as $module) {
						$items = true;

						$results[] = array(
							'name' => strip_tags($this->language->get('heading_title')) . ' - ' . strip_tags($module['name']),
							'id'   => $code . '/' . $module['module_id'],
						);
					}

					if (!$items && $this->config->has('module_' . $code . '_status')) {
						$results[] = array(
							'name' => strip_tags($this->language->get('heading_title')),
							'id'   => $code,
						);
					}
				}
			}
		}

		ob_get_clean();

		return $results;
	}

	public function getVariables() {
		$query = $this->db->query("SELECT * FROM `{$this->journal3_db->prefix('journal3_variable')}` ORDER BY variable_label ASC");

		$results = array();

		foreach ($query->rows as $row) {
			$results[$row['variable_type']][$row['variable_name']] = array(
				'name'  => $row['variable_name'],
				'label' => $row['variable_label'] ? $row['variable_label'] : $row['variable_name'],
				'value' => $this->journal3_db->decode($row['variable_value'], $row['serialized']),
			);
		}

		return $results;
	}

	public function getStyles() {
		$query = $this->db->query("SELECT * FROM `{$this->journal3_db->prefix('journal3_style')}` ORDER BY style_label ASC");

		$results = array();

		foreach ($query->rows as $row) {
			$results[$row['style_type']][$row['style_name']] = array(
				'name'  => $row['style_name'],
				'label' => $row['style_label'] ? $row['style_label'] : $row['style_name'],
			);

			// $results[$row['style_type']][$row['style_name']] = $this->journal3_db->decode($row['style_value'], $row['serialized']);
		}

		return $results;
	}

	public function getBlogCategories($keyword, $id) {
		return $this->db->query("
			SELECT
				c.category_id AS id,
			 	cd.name AS name
			FROM 
				`{$this->journal3_db->prefix('journal3_blog_category')}` c 
			LEFT JOIN 
				`{$this->journal3_db->prefix('journal3_blog_category_description')}` cd ON (c.category_id = cd.category_id) 
			WHERE 
				cd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
				AND {$this->_getSearchCondition('cd.name', 'c.category_id', $keyword, $id)}
			GROUP BY 
				c.category_id
			ORDER BY 
				cd.name ASC	
		")->rows;
	}

	public function getBlogPosts($keyword, $id) {
		return $this->db->query("
			SELECT
				p.post_id AS id,
			 	pd.name AS name
			FROM 
				`{$this->journal3_db->prefix('journal3_blog_post')}` p 
			LEFT JOIN 
				`{$this->journal3_db->prefix('journal3_blog_post_description')}` pd ON (p.post_id = pd.post_id) 
			WHERE 
				pd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
				AND {$this->_getSearchCondition('pd.name', 'p.post_id', $keyword, $id)}
			GROUP BY 
				p.post_id
			ORDER BY 
				pd.name ASC	
		")->rows;
	}

	public function authors() {
		return $this->db->query("
            SELECT
                user_id,
                username,
                firstname,
                lastname
            FROM `{$this->journal3_db->prefix('user')}`
        ")->rows;
	}

	public function getPaymentMethods() {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "extension` WHERE `type` = 'payment' ORDER BY code");

		$results = array();

		foreach ($query->rows as $row) {
			$this->load->language('extension/payment/' . $row['code']);

			$results[] = array(
				'code'  => $row['code'],
				'title' => $this->language->get('heading_title'),
			);
		}

		return $results;
	}

	public function fixSeoUrls() {
		// oc4 missing seo urls
		if ($this->journal3_opencart->is_oc4) {
			$this->load->model('localisation/language');
			$this->load->model('setting/store');

			$languages = $this->model_localisation_language->getLanguages();

			$stores = $this->model_setting_store->getStores();

			array_unshift($stores, ['store_id' => 0]);

			$seo_urls = [
				['key' => 'language', 'value' => 'en-gb', 'keyword' => 'en-gb', 'sort_order' => -2,],
				['key' => 'route', 'value' => 'information/information', 'keyword' => 'information', 'sort_order' => -1,],
				['key' => 'route', 'value' => 'product/product', 'keyword' => 'product', 'sort_order' => -1,],
				['key' => 'route', 'value' => 'product/category', 'keyword' => 'category', 'sort_order' => -1,],
				['key' => 'route', 'value' => 'product/manufacturer', 'keyword' => 'manufacturers', 'sort_order' => -1,],
				['key' => 'route', 'value' => 'product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info', 'keyword' => 'manufacturer', 'sort_order' => -1,],
			];

			$seo_urls = array_reduce($seo_urls, function ($result, $item) use ($stores, $languages) {
				foreach ($stores as $store) {
					foreach ($languages as $language) {
						$key = "{$store['store_id']}___{$language['language_id']}___{$item['key']}___{$item['value']}";

						$result[$key] = [
							'store_id'    => $store['store_id'],
							'language_id' => $language['language_id'],
							'key'         => $item['key'],
							'value'       => $item['value'],
							'keyword'     => $item['keyword'],
							'sort_order'  => $item['sort_order'],
						];
					}
				}

				return $result;
			}, []);

			foreach ($this->db->query("SELECT * FROM `{$this->journal3_db->prefix('seo_url')}` WHERE `key` IN ('language', 'route')")->rows as $seo_url) {
				$key = "{$seo_url['store_id']}___{$seo_url['language_id']}___{$seo_url['key']}___{$seo_url['value']}";

				unset($seo_urls[$key]);
			}

			foreach ($seo_urls as $seo_url) {
				try {
					$this->db->query("
						INSERT INTO `{$this->journal3_db->prefix('seo_url')}` (
							`store_id`,
							`language_id`,
							`key`,
							`value`,
							`keyword`,
							`sort_order`
						) VALUES (
							'{$seo_url['store_id']}',
							'{$seo_url['language_id']}',
							'{$seo_url['key']}',
							'{$seo_url['value']}',
							'{$seo_url['keyword']}',
							'{$seo_url['sort_order']}'
						)
					");
				} catch (Exception $e) {
				}
			}
		}
	}

}

class_alias('ModelJournal3Journal', '\Opencart\Admin\Model\Journal3\Journal');
