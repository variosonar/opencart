<?php

class ModelJournal3Newsletter extends Model {

	public function isSubscribed($email) {
		$sql = "
			SELECT COUNT(*) AS total
			FROM `{$this->journal3_db->prefix('journal3_newsletter')}`
			WHERE email = '{$this->journal3_db->escape($email)}'
		";

		return $this->db->query($sql)->row['total'] > 0;
	}

	public function subscribe($email, $name = '') {
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

		$sql = "
			INSERT INTO `{$this->journal3_db->prefix('journal3_newsletter')}` (
				name,
				email,
				ip,
				store_id
			) VALUES (
				'{$this->journal3_db->escape($name)}',
				'{$this->journal3_db->escape($email)}',
				'{$this->journal3_db->escape($this->request->server['REMOTE_ADDR'])}',
				'{$this->journal3_db->escapeInt($this->config->get('config_store_id'))}'
			)
		";

		return $this->db->query($sql);
	}

	public function unsubscribe($email) {
		$this->db->query("DELETE FROM `{$this->journal3_db->prefix('journal3_newsletter')}` WHERE email = '{$this->journal3_db->escape($email)}'");
	}

}

class_alias('ModelJournal3Newsletter', '\Opencart\Catalog\Model\Journal3\Newsletter');
