<?php

use Journal3\Utils\Arr;
use Journal3\Utils\Str;

class ModelJournal3BlogPost extends Model {

	private static $SORTS = array(
		'name'         => 'pd.name',
		'date_created' => 'p.date_created',
		'date_updated' => 'p.date_updated',
		'views'        => 'p.views',
		'comments'     => 'comments',
	);

	public function all($filters = array()) {
		$filter_sql = "";

		if ($filter = Arr::get($filters, 'filter')) {
			$filter_sql .= " AND pd.`name` LIKE '%{$this->journal3_db->escape($filter)}%'";
		}

		$order_sql = "";

		if (($sort = Arr::get($filters, 'sort')) !== null) {
			$sort = Arr::get(static::$SORTS, $sort);

			if ($sort) {
				$order_sql .= " ORDER BY {$this->journal3_db->escape($sort)}";

				if (($sort = Arr::get($filters, 'order')) === 'desc') {
					$order_sql .= ' DESC';
				} else {
					$order_sql .= ' ASC';
				}
			}
		}

		$page = (int)Arr::get($filters, 'page');
		$limit = (int)Arr::get($filters, 'limit');

		if ($page || $limit) {
			if ($page < 1) {
				$page = 1;
			}

			if ($limit < 1) {
				$limit = 10;
			}

			$order_sql .= ' LIMIT ' . (($page - 1) * $limit) . ', ' . $limit;
		}

		$sql = "
			FROM
				`{$this->journal3_db->prefix('journal3_blog_post')}` p
			LEFT JOIN 
				`{$this->journal3_db->prefix('journal3_blog_post_description')}` pd ON p.post_id = pd.post_id
			WHERE
				(pd.`language_id` = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}' OR pd.`language_id` IS NULL)
				{$filter_sql}						
		";

		$count = (int)$this->db->query("SELECT COUNT(*) AS total {$sql}")->row['total'];

		$result = array();

		if ($count) {
			$query = $this->db->query("
				SELECT
					p.post_id,
					pd.name, 
					IF(p.views IS NULL, 0, p.views) AS views ,
                    (SELECT COUNT(*) FROM `{$this->journal3_db->prefix('journal3_blog_comments')}` WHERE post_id = p.post_id) AS comments 
				{$sql} 
				GROUP BY 
					p.`post_id`
				{$order_sql}
			");

			foreach ($query->rows as $row) {
				$result[] = array(
					'id'       => $row['post_id'],
					'name'     => $row['name'],
					'views'    => $row['views'],
					'comments' => $row['comments'],
				);
			}
		}

		return array(
			'count' => $count,
			'items' => $result,
		);
	}

	/**
	 * @throws Exception
	 */
	public function get($id) {
		$query1 = $this->db->query("
            SELECT
                image,
                comments,
                status,
                sort_order,
                date_created,
                author_id,
                post_data
            FROM 
            	`{$this->journal3_db->prefix('journal3_blog_post')}`
            WHERE 
            	`post_id` = '{$this->journal3_db->escapeInt($id)}'
        ");

		if ($query1->num_rows === 0) {
			throw new Exception('Post not found!');
		}

		$query2 = $this->db->query("
            SELECT
                language_id,
                name,
                description,
                meta_title,
                meta_keywords,
                meta_robots,
                meta_description,
                keyword,
                tags
            FROM
            	`{$this->journal3_db->prefix('journal3_blog_post_description')}`
            WHERE
            	`post_id` = '{$this->journal3_db->escapeInt($id)}'
        ");

		$query3 = $this->db->query("
            SELECT
                category_id
            FROM
            	`{$this->journal3_db->prefix('journal3_blog_post_to_category')}`
            WHERE
            	`post_id` = '{$this->journal3_db->escapeInt($id)}'
        ");

		$query4 = $this->db->query("
            SELECT
                p.product_id as product_id,
                pd.name as name
            FROM
            	`{$this->journal3_db->prefix('journal3_blog_post_to_product')}` p
			LEFT JOIN
            	`{$this->journal3_db->prefix('product_description')}` pd ON (pd.product_id = p.product_id)
            WHERE
            	p.`post_id` = '{$this->journal3_db->escapeInt($id)}'
            	AND pd.`language_id` = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
        ");

		$query5 = $this->db->query("
            SELECT
                store_id,
                layout_id
            FROM
            	`{$this->journal3_db->prefix('journal3_blog_post_to_layout')}`
            WHERE
            	`post_id` = '{$this->journal3_db->escapeInt($id)}'
        ");

		$query6 = $this->db->query("
            SELECT
                store_id
            FROM
            	`{$this->journal3_db->prefix('journal3_blog_post_to_store')}`
            WHERE
            	`post_id` = '{$this->journal3_db->escapeInt($id)}'
        ");

		$result = array(
			'name'             => array(),
			'description'      => array(),
			'meta_title'       => array(),
			'meta_keywords'    => array(),
			'meta_robots'      => array(),
			'meta_description' => array(),
			'keyword'          => array(),
			'tags'             => array(),
			'image'            => $query1->row['image'],
			'comments'         => Str::toBool($query1->row['comments']),
			'status'           => Str::toBool($query1->row['status']),
			'sort_order'       => $query1->row['sort_order'] ? $query1->row['sort_order'] : '',
			'date_created'     => $query1->row['date_created'],
			'author_id'        => $query1->row['author_id'],
			'post_data'        => $this->journal3_db->decode($query1->row['post_data'], true) ?: new stdClass(),
			'categories'       => array(),
			'products'         => array(),
		);

		foreach ($query2->rows as $row) {
			$result['name']['lang_' . $row['language_id']] = $row['name'];
			$result['description']['lang_' . $row['language_id']] = $row['description'];
			$result['meta_title']['lang_' . $row['language_id']] = $row['meta_title'];
			$result['meta_keywords']['lang_' . $row['language_id']] = $row['meta_keywords'];
			$result['meta_robots']['lang_' . $row['language_id']] = $row['meta_robots'];
			$result['meta_description']['lang_' . $row['language_id']] = $row['meta_description'];
			$result['keyword']['lang_' . $row['language_id']] = $row['keyword'];
			$result['tags']['lang_' . $row['language_id']] = $row['tags'];
		}

		foreach ($query3->rows as $row) {
			$result['categories'][] = $row['category_id'];
		}

		foreach ($query4->rows as $row) {
			$result['products'][] = $row['product_id'];
		}

		foreach ($query5->rows as $row) {
			$result['layouts']['store_' . $row['store_id']] = $row['layout_id'];
		}

		$this->load->model('setting/store');

		$stores = $this->model_setting_store->getStores();

		$result['stores']['store_0'] = 'false';

		foreach ($stores as $store) {
			$result['stores']['store_' . $store['store_id']] = 'false';
		}

		foreach ($query6->rows as $row) {
			$result['stores']['store_' . $row['store_id']] = 'true';
		}

		foreach ($result as $key => &$value) {
			if (!in_array($key, array('categories', 'products')) && is_array($value) && !$value) {
				$value = new stdClass();
			}
		}

		return $result;
	}

	public function add($data) {
		$date_created = Arr::get($data, 'date_created') ? "'{$this->journal3_db->escape(Arr::get($data, 'date_created'))}'" : "NOW()";

		$this->db->query("
            INSERT INTO `{$this->journal3_db->prefix('journal3_blog_post')}` (
            	`image`,
				`comments`,
				`status`,
				`sort_order`,
				`date_created`,
				`date_updated`,
				`author_id`,
				`post_data`
			) VALUES (
				'{$this->journal3_db->escape(Arr::get($data, 'image'))}',
				'{$this->journal3_db->escapeInt(Str::fromBool(Arr::get($data, 'comments')))}',
				'{$this->journal3_db->escapeInt(Str::fromBool(Arr::get($data, 'status')))}',
				'{$this->journal3_db->escape(Arr::get($data, 'sort_order'))}',
				{$date_created},
				NOW(),
				'{$this->journal3_db->escape(Arr::get($data, 'author_id'))}',
				'{$this->journal3_db->encode(Arr::get($data, 'post_data'), true)}'
			)
        ");

		$id = $this->db->getLastId();

		$this->_edit($id, $data);

		return $id;
	}

	public function edit($id, $data) {
		$this->db->query("
            UPDATE `{$this->journal3_db->prefix('journal3_blog_post')}`
            SET
                `image` = '{$this->journal3_db->escape(Arr::get($data, 'image'))}',
				`comments` = '{$this->journal3_db->escapeInt(Str::fromBool(Arr::get($data, 'comments')))}',
				`status` = '{$this->journal3_db->escapeInt(Str::fromBool(Arr::get($data, 'status')))}',
				`sort_order` = '{$this->journal3_db->escape(Arr::get($data, 'sort_order'))}',
				`date_created` = '{$this->journal3_db->escape(Arr::get($data, 'date_created'))}',
				`date_updated` = NOW(),
				`author_id` = '{$this->journal3_db->escape(Arr::get($data, 'author_id'))}',
                `post_data` = '{$this->journal3_db->encode(Arr::get($data, 'post_data'), true)}'
            WHERE
            	post_id = '{$this->journal3_db->escapeInt($id)}'
        ");

		$this->_edit($id, $data);

		return null;
	}

	private function _edit($id, $data) {
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_blog_post_description')}` WHERE post_id = '{$this->journal3_db->escapeInt($id)}'");
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_blog_post_to_category')}` WHERE post_id = '{$this->journal3_db->escapeInt($id)}'");
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_blog_post_to_product')}` WHERE post_id = '{$this->journal3_db->escapeInt($id)}'");
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_blog_post_to_layout')}` WHERE post_id = '{$this->journal3_db->escapeInt($id)}'");
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_blog_post_to_store')}` WHERE post_id = '{$this->journal3_db->escapeInt($id)}'");


		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();

		foreach ($languages as $language) {
			$this->db->query("
                INSERT INTO `{$this->journal3_db->prefix('journal3_blog_post_description')}` (
                	post_id,
                	language_id,
                	name,
                	description,
                	meta_title,
                	meta_keywords,
                	meta_robots,
                	meta_description,
                	keyword,
                	tags
				) VALUES (
                	'{$this->journal3_db->escapeInt($id)}',
                	'{$this->journal3_db->escapeInt($language['language_id'])}',
                	'{$this->journal3_db->escape(Arr::get($data, 'name.lang_' . $language['language_id']))}',
                	'{$this->journal3_db->escape(Arr::get($data, 'description.lang_' . $language['language_id']))}',
                	'{$this->journal3_db->escape(Arr::get($data, 'meta_title.lang_' . $language['language_id']))}',
                	'{$this->journal3_db->escape(Arr::get($data, 'meta_keywords.lang_' . $language['language_id']))}',
                	'{$this->journal3_db->escape(Arr::get($data, 'meta_robots.lang_' . $language['language_id']))}',
                	'{$this->journal3_db->escape(Arr::get($data, 'meta_description.lang_' . $language['language_id']))}',
                	'{$this->journal3_db->escape(Arr::get($data, 'keyword.lang_' . $language['language_id']))}',
                	'{$this->journal3_db->escape(Arr::get($data, 'tags.lang_' . $language['language_id']))}'
                )
            ");
		}

		$categories = array_unique(Arr::get($data, 'categories', array()));

		foreach ($categories as $category) {
			$this->db->query("
				INSERT INTO `{$this->journal3_db->prefix('journal3_blog_post_to_category')}` (
                	post_id,
                	category_id
				) VALUES (
                	'{$this->journal3_db->escapeInt($id)}',
                	'{$this->journal3_db->escapeInt($category)}'
                )
			");
		}

		$products = array_unique(Arr::get($data, 'products', array()));

		foreach ($products as $product) {
			$this->db->query("
				INSERT INTO `{$this->journal3_db->prefix('journal3_blog_post_to_product')}` (
                	post_id,
                	product_id
				) VALUES (
                	'{$this->journal3_db->escapeInt($id)}',
                	'{$this->journal3_db->escapeInt($product)}'
                )
			");
		}

		foreach (Arr::get($data, 'layouts', array()) as $store_id => $layout_id) {
			$store_id = str_replace('store_', '', $store_id);

			$this->db->query("
				INSERT INTO `{$this->journal3_db->prefix('journal3_blog_post_to_layout')}` (
					post_id,
					store_id,
					layout_id
				) VALUES (
					'{$this->journal3_db->escapeInt($id)}',
					'{$this->journal3_db->escapeInt($store_id)}',
					'{$this->journal3_db->escapeInt($layout_id)}'
				)
			");
		}

		foreach (Arr::get($data, 'stores', array()) as $store_id => $value) {
			if ($value === 'true') {
				$store_id = str_replace('store_', '', $store_id);

				$this->db->query("
                    INSERT INTO `{$this->journal3_db->prefix('journal3_blog_post_to_store')}` (
                    	post_id,
                    	store_id
					) VALUES (
						'{$this->journal3_db->escapeInt($id)}',
						'{$this->journal3_db->escapeInt($store_id)}'
					)
				");
			}
		}
	}

	/**
	 * @throws Exception
	 */
	public function copy($id) {
		$data = $this->get($id);

		foreach ($data['name'] as &$name) {
			$name .= ' Copy';
		}

		return $this->add($data);
	}

	public function remove($id) {
		$id = explode(',', $id);
		$id = $this->journal3_db->escapeInt($id);

		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_blog_post')}` WHERE post_id IN ({$id})");
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_blog_post_description')}` WHERE post_id IN ({$id})");
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_blog_post_to_category')}` WHERE post_id IN ({$id})");
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_blog_post_to_layout')}` WHERE post_id IN ({$id})");
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_blog_post_to_store')}` WHERE post_id IN ({$id})");
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_blog_post_to_product')}` WHERE post_id IN ({$id})");
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_blog_comments')}` WHERE post_id IN ({$id})");
	}

}

class_alias('ModelJournal3BlogPost', '\Opencart\Admin\Model\Journal3\BlogPost');
