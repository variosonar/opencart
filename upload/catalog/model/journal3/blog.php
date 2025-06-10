<?php

use Journal3\Utils\Arr;

class ModelJournal3Blog extends Model {

	private static $BLOG_KEYWORD = null;
	private static $BLOG_KEYWORDS = null;

	public function isEnabled() {
		return $this->journal3->get('blogStatus');
	}

	public function getCategories() {
		$query = $this->db->query("
            SELECT
                c.category_id,
                cd.name
            FROM
            	`{$this->journal3_db->prefix('journal3_blog_category')}` c
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_category_description')}` cd ON c.category_id = cd.category_id
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_category_to_store')}` c2s ON c.category_id = c2s.category_id
            WHERE
            	c.status = 1
            	AND cd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}' 
            	AND c2s.store_id = '{$this->journal3_db->escapeInt($this->config->get('config_store_id'))}'
            ORDER BY
            	c.sort_order
        ");

		return $query->rows;
	}

	public function getCategoriesByPostId($post_id) {
		$query = $this->db->query("
            SELECT
                c.category_id,
                cd.name
            FROM 
            	`{$this->journal3_db->prefix('journal3_blog_category')}` c
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_category_description')}` cd ON c.category_id = cd.category_id
			LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_category_to_store')}` c2s ON c.category_id = c2s.category_id
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_post_to_category')}` p2c ON c.category_id = p2c.category_id
            WHERE
            	c.status = 1
            	AND p2c.post_id = '{$this->journal3_db->escapeInt($post_id)}'
            	AND cd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}' 
            	AND c2s.store_id = '{$this->journal3_db->escapeInt($this->config->get('config_store_id'))}'            	
        ");

		return $query->rows;
	}

	public function getCategory($category_id) {
		$query = $this->db->query("
            SELECT
                *,
                c.category_id,
                cd.name,
                cd.description,
                cd.meta_title,
                cd.meta_keywords,
                cd.meta_description,
                cd.keyword
            FROM
            	`{$this->journal3_db->prefix('journal3_blog_category')}` c
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_category_description')}` cd ON c.category_id = cd.category_id
            WHERE
            	c.status = 1
            	AND c.category_id = '{$this->journal3_db->escapeInt($category_id)}'
            	AND cd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}' 
        ");

		return $query->row;
	}

	public function getPost($post_id) {
		$query = $this->db->query("
            SELECT
			    *,
                p.post_id,
                p.image,
                p.comments,
                p.date_created,
                p.views,
                pd.name,
                pd.description,
                pd.meta_title,
                pd.meta_keywords,
                pd.meta_description,
                pd.keyword,
                pd.tags,
                a.username,
                a.firstname,
                a.lastname,
                a.email
            FROM
            	`{$this->journal3_db->prefix('journal3_blog_post')}` p
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_post_description')}` pd ON p.post_id = pd.post_id
            LEFT JOIN
            	`{$this->journal3_db->prefix('user')}` a ON p.author_id = a.user_id
            WHERE
            	p.status = 1
            	AND p.post_id = '{$this->journal3_db->escapeInt($post_id)}'
            	AND pd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}' 
            	AND p.date_created <= NOW()
        ");

		$result = $query->row;

		if ($result) {
			$result['post_data'] = $this->journal3_db->decode($result['post_data'] ?? '{}', true);
		}

		return $result;
	}

	public function getPosts($data = array()) {
		$sql = "
            SELECT
                p.post_id,
                p.image,
                p.date_created as date,
                p.views,
                pd.name,
                pd.description,
                a.username,
                a.firstname,
                a.lastname,
                a.email,
                (
                    SELECT count(*)
                    FROM `{$this->journal3_db->prefix('journal3_blog_comments')}` bc
                    WHERE
                    	bc.status = 1                    
                    	AND bc.post_id = p.post_id
                    	AND bc.parent_id = 0
                ) as comments
            FROM
            	`{$this->journal3_db->prefix('journal3_blog_post')}` p
		";

		if (Arr::get($data, 'category_id') || Arr::get($data, 'categories')) {
			$sql .= " LEFT JOIN `{$this->journal3_db->prefix('journal3_blog_post_to_category')}` p2c ON p.post_id = p2c.post_id";
		}

		$sql .= "
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_post_description')}` pd ON p.post_id = pd.post_id
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_post_to_store')}` p2s ON p.post_id = p2s.post_id
            LEFT JOIN
            	`{$this->journal3_db->prefix('user')}` a ON p.author_id = a.user_id
            WHERE 
            	pd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}' 
            	AND p2s.store_id = '{$this->journal3_db->escapeInt($this->config->get('config_store_id'))}'
            	AND p.date_created <= NOW()
        ";

		if (Arr::get($data, 'category_id')) {
			$sql .= " AND p2c.category_id = " . (int)$data['category_id'];
		}

		if (Arr::get($data, 'categories')) {
			$sql .= " AND p2c.category_id IN (" . implode(',', array_map('intval', $data['categories'])) . ")";
		}

		if (isset($data['tag']) && $data['tag']) {
			$sql .= " AND pd.tags LIKE '%" . $this->db->escape($data['tag']) . "%'";
		}

		if (isset($data['search']) && $data['search']) {
			$temp_1 = array();
			$temp_2 = array();

			$words = explode(' ', trim(preg_replace('/\s\s+/', ' ', $data['search'])));

			foreach ($words as $word) {
				$temp_1[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
				$temp_2[] = "pd.description LIKE '%" . $this->db->escape($word) . "%'";
			}

			if ($temp_1) {
				$sql .= ' AND ((' . implode(" AND ", $temp_1) . ') OR (' . implode(" AND ", $temp_2) . '))';
			}
		}

		if (Arr::get($data, 'post_ids')) {
			$sql .= ' AND p.post_id IN (' . implode(',', array_map('intval', $data['post_ids'])) . ')';
		}

		$sql .= ' AND p.status = 1';

		$sql .= ' GROUP BY p.post_id';

		if (isset($data['sort']) && ($data['sort'] === 'newest' || $data['sort'] === 'latest')) {
			$sql .= ' ORDER BY p.date_created DESC';
		}

		if (isset($data['sort']) && $data['sort'] === 'oldest') {
			$sql .= ' ORDER BY p.date_created ASC';
		}

		if (isset($data['sort']) && $data['sort'] === 'comments') {
			$sql .= ' ORDER BY comments DESC';
		}

		if (isset($data['sort']) && $data['sort'] === 'views') {
			$sql .= ' ORDER BY p.views DESC';
		}

		$start = (int)Arr::get($data, 'start', 0);
		$limit = (int)Arr::get($data, 'limit', 0);

		if ($limit) {
			$sql .= " LIMIT {$this->journal3_db->escapeNat($start)}, {$this->journal3_db->escapeNat($limit)}";
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getPostsTotal($data = array()) {
		$sql = "
            SELECT
                COUNT(*) AS total
            FROM
            	`{$this->journal3_db->prefix('journal3_blog_post')}` p
        ";

		if (isset($data['category_id']) && $data['category_id']) {
			$sql .= " LEFT JOIN `{$this->journal3_db->prefix('journal3_blog_post_to_category')}` p2c ON p.post_id = p2c.post_id";
		}

		$sql .= "
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_post_description')}` pd ON p.post_id = pd.post_id
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_post_to_store')}` p2s ON p.post_id = p2s.post_id
            WHERE
            	pd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
            	AND p2s.store_id = '{$this->journal3_db->escapeInt($this->config->get('config_store_id'))}'
            	AND p.date_created <= NOW()
        ";

		if (isset($data['category_id']) && $data['category_id']) {
			$sql .= " AND p2c.category_id = " . (int)$data['category_id'];
		}

		if (isset($data['category_ids']) && $data['category_ids']) {
			$sql .= " AND p2c.category_id IN " . $this->journal3_db->escapeInt($data['category_id']);
		}

		if (isset($data['tag']) && $data['tag']) {
			$sql .= " AND pd.tags LIKE '%" . $this->db->escape($data['tag']) . "%'";
		}

		if (isset($data['search']) && $data['search']) {
			$temp_1 = array();
			$temp_2 = array();

			$words = explode(' ', trim(preg_replace('/\s\s+/', ' ', $data['search'])));

			foreach ($words as $word) {
				$temp_1[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
				$temp_2[] = "pd.description LIKE '%" . $this->db->escape($word) . "%'";
			}

			if ($temp_1) {
				$sql .= ' AND ((' . implode(" AND ", $temp_1) . ') OR (' . implode(" AND ", $temp_2) . '))';
			}
		}

		$sql .= ' AND p.status = 1';

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getRelatedPosts($product_id, $limit = 5) {
		$sql = "
            SELECT
                p.post_id,
                p.image,
                p.date_created as date,
                p.views,
                pd.name,
                pd.description,
                a.username,
                a.firstname,
                a.lastname,
                (
                    SELECT count(*)
                    FROM `{$this->journal3_db->prefix('journal3_blog_comments')}` bc
                    WHERE bc.post_id = p.post_id AND bc.status = 1 AND bc.parent_id = 0
                ) as comments
            FROM `{$this->journal3_db->prefix('journal3_blog_post')}` p
            LEFT JOIN `{$this->journal3_db->prefix('journal3_blog_post_to_product')}` p2p ON p.post_id = p2p.post_id
            LEFT JOIN `{$this->journal3_db->prefix('journal3_blog_post_description')}` pd ON p.post_id = pd.post_id
            LEFT JOIN `{$this->journal3_db->prefix('user')}` a ON p.author_id = a.user_id
            WHERE 
            	pd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}' 
            	AND p2p.product_id = {$this->journal3_db->escapeInt($product_id)}
            	AND p.date_created <= NOW() 
            	AND p.status = 1
            ORDER BY pd.name ASC
        ";

		if ($limit) {
			$sql .= " LIMIT 0, {$this->journal3_db->escapeNat($limit)}";
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getRelatedProducts($post_id, $limit = 5) {
		$sql = "
			SELECT 
				bp2p.product_id
			FROM `{$this->journal3_db->prefix('journal3_blog_post_to_product')}` bp2p 
			LEFT JOIN `{$this->journal3_db->prefix('product')}` p ON (bp2p.product_id = p.product_id) 
			LEFT JOIN `{$this->journal3_db->prefix('product_to_store')}` p2s ON (p.product_id = p2s.product_id) 
			WHERE 
				bp2p.post_id = '{$this->journal3_db->escapeInt($post_id)}' 
				AND p.status = '1' 
				AND p.date_available <= NOW() 
				AND p2s.store_id = '{$this->journal3_db->escapeInt($this->config->get('config_store_id'))}'			
		";

		if ($this->journal3->get('filterCheckQuantityRelated')) {
			$sql .= ' AND p.quantity > 0';
		}

		if ($limit) {
			$sql .= " LIMIT 0, {$this->journal3_db->escapeNat($limit)}";
		}

		$query = $this->db->query($sql);

		return $this->model_journal3_product->getProduct($query->rows);
	}

	public function getCommentsStatus($post_info) {
		if ($post_info['comments'] === '2') {
			return $this->journal3->get('blogPostComments');
		}

		return (bool)$post_info['comments'];
	}

	public function createComment($data) {
		$parent_id = (int)Arr::get($data, 'parent_id', 0);
		$post_id = (int)Arr::get($data, 'post_id', 0);
		$name = Arr::get($data, 'name', '');
		$email = Arr::get($data, 'email', '');
		$website = Arr::get($data, 'website', '');
		$comment = Arr::get($data, 'comment', '');
		$status = (int)$this->journal3->get('blogPostApproveComments');

		if (version_compare(VERSION, '2.1', '<')) {
			$this->load->library('user');
		}

		if (version_compare(VERSION, '4', '>=')) {
			$this->user = new \Opencart\System\Library\Cart\User($this->registry);
		} else if (version_compare(VERSION, '2.2', '>=')) {
			$this->user = new \Cart\User($this->registry);
		} else {
			$this->user = new User($this->registry);
		}

		if ($this->user->isLogged()) {
			$customer_id = 0;
			$author_id = $this->user->getId();
		} else if ($this->customer->isLogged()) {
			$customer_id = $this->customer->getId();
			$author_id = 0;
		} else {
			$customer_id = 0;
			$author_id = 0;
		}

		$sql = "
            INSERT INTO `{$this->journal3_db->prefix('journal3_blog_comments')}` (
            	parent_id,
            	post_id,
            	customer_id,
            	author_id,
            	name,
            	email,
            	website,
            	comment,
            	status,
            	date
			) VALUES (
				'{$this->journal3_db->escapeInt($parent_id)}',
				'{$this->journal3_db->escapeInt($post_id)}',
				'{$this->journal3_db->escapeInt($customer_id)}',
				'{$this->journal3_db->escapeInt($author_id)}',
				'{$this->journal3_db->escape($name)}',
				'{$this->journal3_db->escape($email)}',
				'{$this->journal3_db->escape($website)}',
				'{$this->journal3_db->escape($comment)}',
				'{$this->journal3_db->escapeInt($status)}',
				NOW()
			)
        ";

		$this->db->query($sql);

		if (!$status) {
			return [
				'comment_id' => $this->db->getLastId()
			];
		}

		return $this->getComment($this->db->getLastId());
	}

	public function getTotalComments($data) {
		$post_id = $data['post_id'] ?? 0;

		$query = $this->db->query("
            SELECT
                count(*) as total
            FROM
            	`{$this->journal3_db->prefix('journal3_blog_comments')}` bc
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_post')}` p ON p.post_id = bc.post_id
            WHERE
            	bc.post_id = {$this->journal3_db->escapeInt($post_id)}
            	AND bc.parent_id = 0
            	AND bc.status = 1
            	AND p.status = 1
        ");

		return $query->row['total'];
	}

	public function getComments($data) {
		$post_id = $data['post_id'] ?? 0;

		$sql = "
            SELECT
                *
            FROM
            	`{$this->journal3_db->prefix('journal3_blog_comments')}` bc
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_post')}` p ON p.post_id = bc.post_id
            WHERE
            	bc.post_id = {$this->journal3_db->escapeInt($post_id)}
            	AND bc.parent_id = 0
            	AND bc.status = 1
            	AND p.status = 1
            ORDER BY 
            	bc.date ASC
        ";


		$start = (int)Arr::get($data, 'start', 0);
		$limit = (int)Arr::get($data, 'limit', 0);

		if ($limit) {
			$sql .= " LIMIT {$this->journal3_db->escapeNat($start)}, {$this->journal3_db->escapeNat($limit)}";
		}

		$query = $this->db->query($sql);

		$comments = $query->rows;

		$replies = array();

		$comment_ids = array();

		foreach ($query->rows as $row) {
			$comment_ids[] = $this->journal3_db->escapeInt($row['comment_id']);
		}

		if ($comment_ids) {
			$comment_ids = implode(',', $comment_ids);
			$query = $this->db->query("
                SELECT
                    *
                FROM
                	`{$this->journal3_db->prefix('journal3_blog_comments')}` bc
                WHERE
                	bc.post_id = {$this->journal3_db->escapeInt($post_id)}
                	AND parent_id IN ({$comment_ids})
                	AND status = 1
            ");

			foreach ($query->rows as $row) {
				if (!isset($replies[$row['parent_id']])) {
					$replies[$row['parent_id']] = array();
				}
				$replies[$row['parent_id']][] = $row;
			}

		}

		foreach ($comments as &$comment) {
			$comment['comment'] = nl2br($comment['comment']);

			if ($comment['website']) {
				$comment['website'] = trim($comment['website']);
				$comment['website'] = trim($comment['website'], '/');
				$comment['website'] = parse_url($comment['website'], PHP_URL_SCHEME) !== null ? $comment['website'] : ('http://' . $comment['website']);
			}

			$comment['avatar'] = md5(strtolower(trim($comment['email'])));

			$comment['replies'] = isset($replies[$comment['comment_id']]) ? $replies[$comment['comment_id']] : array();
		}

		return $comments;
	}

	public function getComment($comment_id) {
		$query = $this->db->query("
            SELECT
                comment_id,
                website,
                name,
                email,
                comment,
                date
            FROM
            	`{$this->journal3_db->prefix('journal3_blog_comments')}`
            WHERE
            	status = 1
            	AND comment_id = {$this->journal3_db->escapeInt($comment_id)}
		");

		if ($query->num_rows) {
			$query->row['comment'] = nl2br($query->row['comment']);
		}

		return $query->row;
	}

	public function getTags($limit = 5) {
		$sql = "
            SELECT
                pd.tags as tags
            FROM
            	`{$this->journal3_db->prefix('journal3_blog_post')}` p
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_post_description')}` pd ON p.post_id = pd.post_id
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_post_to_store')}` p2s ON p.post_id = p2s.post_id
            WHERE
            	p.status = 1
            	AND pd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
            	AND  p2s.store_id = '{$this->journal3_db->escapeInt($this->config->get('config_store_id'))}'
		";

		$query = $this->db->query($sql);

		$tags = array();

		foreach ($query->rows as $row) {
			foreach (explode(',', $row['tags']) as $tag) {
				$tag = trim($tag);

				if (!$tag) continue;

				$tags[$tag] = $tag;

				if (count($tags) == $limit) {
					return $tags;
				}
			}
		}

		return $tags;
	}

	public function getLatestComments($limit = 5) {
		$sql = "
            SELECT
                bc.comment_id,
                bc.email,
                bc.comment,
                bc.post_id,
                bc.name,
                pd.name as post
            FROM
            	`{$this->journal3_db->prefix('journal3_blog_comments')}` bc
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_post_description')}` pd ON bc.post_id = pd.post_id
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_post')}` p ON p.post_id = bc.post_id
            LEFT JOIN
            	`{$this->journal3_db->prefix('journal3_blog_post_to_store')}` p2s ON p.post_id = p2s.post_id
            WHERE
            	p.status = 1
            	AND bc.status = 1
            	AND bc.parent_id = 0
            	AND pd.language_id = '{$this->journal3_db->escapeInt($this->config->get('config_language_id'))}'
            	AND p2s.store_id = '{$this->journal3_db->escapeInt($this->config->get('config_store_id'))}'
            ORDER BY
            	date DESC
        ";

		if ($limit) {
			$sql .= " LIMIT 0, {$this->journal3_db->escapeNat($limit)}";
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getBlogCategoryLayoutId($category_id) {
		$query = $this->db->query("
			SELECT
				*
			FROM
				`{$this->journal3_db->prefix('journal3_blog_category_to_layout')}`
			WHERE
				category_id = '{$this->journal3_db->escapeInt($category_id)}'
				AND store_id = '{$this->journal3_db->escapeInt($this->config->get('config_store_id'))}'
		");

		return Arr::get($query->row, 'layout_id', false);
	}

	public function getBlogPostLayoutId($post_id) {
		$query = $this->db->query("
			SELECT
				*
			FROM
				`{$this->journal3_db->prefix('journal3_blog_post_to_layout')}`
			WHERE
				post_id = '{$this->journal3_db->escapeInt($post_id)}'
				AND store_id = '{$this->journal3_db->escapeInt($this->config->get('config_store_id'))}'
		");

		return Arr::get($query->row, 'layout_id', false);
	}

	public function getAdminInfo($user_id) {
		$query = $this->db->query("
			SELECT
				* 
			FROM
				`{$this->journal3_db->prefix('user')}`
			WHERE
				user_id = '{$this->journal3_db->escapeInt($user_id)}'
				AND status = '1'
		");

		return $query->row;
	}

	public function getAuthorName($post_info) {
		switch ($this->journal3->get('blogAuthorName')) {
			case 'username':
				return $post_info['username'];

			case 'firstname':
				return $post_info['firstname'];

			case 'fullname':
				return $post_info['lastname'] . ' ' . $post_info['firstname'];
		}

		return '';
	}

	public function updateViews($post_id) {
		$this->db->query("
			UPDATE
				`{$this->journal3_db->prefix('journal3_blog_post')}`
			SET 
				views = if (views IS NULL, 0, views) + 1
			WHERE
				post_id = {$this->journal3_db->escapeInt($post_id)}
		");
	}

	public function getBlogKeyword() {
		if (self::$BLOG_KEYWORD === null) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "journal3_setting WHERE store_id = '" . (int)$this->config->get('config_store_id') . "' AND `setting_name` = 'blogPageKeyword'");
			if (!$query->num_rows) {
				self::$BLOG_KEYWORD = false;
			} else {
				$keywords = json_decode($query->row['setting_value'], true);
				self::$BLOG_KEYWORD = Arr::get($keywords, 'lang_' . $this->config->get('config_language_id'));
			}
		}

		return self::$BLOG_KEYWORD;
	}

	public function getBlogKeywords() {
		if (self::$BLOG_KEYWORDS === null) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "journal3_setting WHERE store_id = '" . (int)$this->config->get('config_store_id') . "' AND `setting_name` = 'blogPageKeyword'");
			if (!$query->num_rows) {
				self::$BLOG_KEYWORDS = false;
			} else {
				self::$BLOG_KEYWORDS = array();
				$keywords = json_decode($query->row['setting_value'], true);
				if ($keywords) {
					foreach ($keywords as $keyword) {
						self::$BLOG_KEYWORDS[$keyword] = $keyword;
						self::$BLOG_KEYWORDS[$keyword . '/'] = $keyword . '/';
					}
				}
			}
		}

		return self::$BLOG_KEYWORDS;
	}

	public function rewriteCategory($category_id) {
		return Arr::get($this->getCategory($category_id), 'keyword');
	}

	public function rewritePost($post_id) {
		return Arr::get($this->getPost($post_id), 'keyword');
	}

}

class_alias('ModelJournal3Blog', '\Opencart\Catalog\Model\Journal3\Blog');
