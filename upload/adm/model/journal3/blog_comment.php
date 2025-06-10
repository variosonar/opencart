<?php

use Journal3\Utils\Arr;
use Journal3\Utils\Str;

class ModelJournal3BlogComment extends Model {

	private static $SORTS = array(
		'name' => 'pd.name',
	);

	public function all($filters = array()) {
		$filter_sql = "";

		if ($filter = Arr::get($filters, 'filter')) {
			$filter_sql .= " AND (
				pd.`name` LIKE '%{$this->journal3_db->escape($filter)}%'
				OR bc.`name` LIKE '%{$this->journal3_db->escape($filter)}%'
				OR bc.`email` LIKE '%{$this->journal3_db->escape($filter)}%'
			)
			";
		}

		$order_sql = " ORDER BY date DESC";

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
				`{$this->journal3_db->prefix('journal3_blog_comments')}` bc
			LEFT JOIN 
				`{$this->journal3_db->prefix('journal3_blog_post')}` p ON p.post_id = bc.post_id
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
					bc.comment_id,
                    bc.name as author,
                    pd.name as post_name,
                    bc.parent_id as parent_id,
                    bc.status as status 
				{$sql} 
				GROUP BY 
					bc.`comment_id`
				{$order_sql}
			");

			foreach ($query->rows as $row) {
				$result[] = array(
					'id'     => $row['comment_id'],
					'name'   => $row['author'] ? $row['author'] . ' @ ' . $row['post_name'] : $row['post_name'],
					'status' => (int)$row['status'],
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
		$query = $this->db->query("
            SELECT
                *
            FROM 
            	`{$this->journal3_db->prefix('journal3_blog_comments')}`
            WHERE 
            	`comment_id` = '{$this->journal3_db->escapeInt($id)}'
        ");

		if ($query->num_rows === 0) {
			throw new Exception('Comment not found!');
		}


		$result = array(
			'name'    => $query->row['name'],
			'email'   => $query->row['email'],
			'website' => $query->row['website'],
			'comment' => $query->row['comment'],
			'status'  => Str::toBool($query->row['status']),
		);

		return $result;
	}

	public function edit($id, $data) {
		$this->db->query("
            UPDATE `{$this->journal3_db->prefix('journal3_blog_comments')}`
            SET
            	name = '{$this->journal3_db->escape(Arr::get($data, 'name', ''))}',
                email = '{$this->journal3_db->escape(Arr::get($data, 'email', ''))}',
                website = '{$this->journal3_db->escape(Arr::get($data, 'website', ''))}',
                comment = '{$this->journal3_db->escape(Arr::get($data, 'comment', ''))}',
                status = '{$this->journal3_db->escape(Str::fromBool(Arr::get($data, 'status')))}'
            WHERE
            	comment_id = '{$this->journal3_db->escapeInt($id)}'
        ");

		return null;
	}

	public function remove($id) {
		$id = explode(',', $id);
		$id = $this->journal3_db->escapeInt($id);

		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_blog_comments')}` WHERE comment_id IN ({$id})");
	}

}

class_alias('ModelJournal3BlogComment', '\Opencart\Admin\Model\Journal3\BlogComment');
