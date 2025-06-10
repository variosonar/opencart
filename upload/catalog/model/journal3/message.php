<?php

use Journal3\Utils\Arr;

class ModelJournal3Message extends Model {

	public function addMessage($data) {
		$name = Arr::get($data, 'name', '');
		$email = Arr::get($data, 'email', '');
		$fields = Arr::get($data, 'items', array());
		$url = Arr::get($data, 'url', '');

		$sql = "
            INSERT INTO `{$this->journal3_db->prefix('journal3_message')}` (
            	name,
            	email,
            	fields,
            	store_id,
            	url,
            	date
			) VALUES (
				'{$this->journal3_db->escape($name)}',
				'{$this->journal3_db->escape($email)}',
				'{$this->journal3_db->escape($this->journal3_db->encode($fields, true))}',
				'{$this->journal3_db->escapeInt($this->config->get('config_store_id'))}',
				'{$this->journal3_db->escape($url)}',
				NOW()
			)
        ";

		$this->db->query($sql);
	}

}

class_alias('ModelJournal3Message', '\Opencart\Catalog\Model\Journal3\Message');
