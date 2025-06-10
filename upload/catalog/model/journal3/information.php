<?php

class ModelJournal3Information extends Model {

	private static $results;

	public function getInformation($information_id) {
		if (static::$results === null) {
			$cache_key = "catalog.information.l{$this->config->get('config_language_id')}.s{$this->config->get('config_store_id')}";

			static::$results = $this->journal3_cache->get($cache_key, false);

			if (static::$results === false) {
				static::$results = [];

				$query = $this->db->query("
					SELECT
						i.information_id,
				   		id.title
					FROM " . DB_PREFIX . "information i 
					LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) 
					LEFT JOIN " . DB_PREFIX . "information_to_store i2s ON (i.information_id = i2s.information_id) 
					WHERE
						id.language_id = '" . (int)$this->config->get('config_language_id') . "' 
						AND i2s.store_id = '" . (int)$this->config->get('config_store_id') . "' 
						AND i.status = '1'
					GROUP BY i.information_id
				");

				foreach ($query->rows as $row) {
					static::$results[$row['information_id']] = [
						'information_id' => $row['information_id'],
						'title'          => $row['title'],
						'link'           => [
							'href'  => $this->journal3_url->link('information/information', 'information_id=' . $row['information_id']),
							'agree' => $this->journal3_url->link('information/information' . JOURNAL3_ROUTE_SEPARATOR . ($this->journal3_opencart->is_oc4 ? 'info' : 'agree'), 'information_id=' . $row['information_id']),
						],
					];
				}

				$this->load->language('account/register');

				static::$results['text_agree'] = $this->language->get('text_agree');
				static::$results['error_agree'] = $this->language->get('error_agree');

				$this->journal3_cache->set($cache_key, static::$results, false);
			}
		}

		if (empty(static::$results[$information_id])) {
			return null;
		}

		$information_info = static::$results[$information_id];

		$information_info['text'] = sprintf(static::$results['text_agree'], $information_info['link']['agree'], $information_info['title'], $information_info['title']);
		$information_info['error'] = sprintf(static::$results['error_agree'], $information_info['title']);

		return $information_info;
	}

}

class_alias('ModelJournal3Information', '\Opencart\Catalog\Model\Journal3\Information');
