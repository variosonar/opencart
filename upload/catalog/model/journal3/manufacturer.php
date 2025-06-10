<?php

class ModelJournal3Manufacturer extends Model {

	private static $results;

	public function getManufacturer($manufacturer_id) {
		if (static::$results === null) {
			$cache_key = "catalog.manufacturer.s{$this->config->get('config_store_id')}";

			static::$results = $this->journal3_cache->get($cache_key, false);

			if (static::$results === false) {
				static::$results = [];

				$query = $this->db->query("
					SELECT
						m.manufacturer_id,
						md.name,
						m.image
					FROM " . DB_PREFIX . "manufacturer m 
					LEFT JOIN " . DB_PREFIX . "manufacturer_description md ON (m.manufacturer_id = md.manufacturer_id)
					LEFT JOIN " . DB_PREFIX . "manufacturer_to_store m2s ON (m.manufacturer_id = m2s.manufacturer_id) 
					WHERE 
						m2s.store_id = '" . (int)$this->config->get('config_store_id') . "'
					GROUP BY m.manufacturer_id
				");

				foreach ($query->rows as $row) {
					static::$results[$row['manufacturer_id']] = [
						'manufacturer_id' => $row['manufacturer_id'],
						'name'            => $row['name'],
						'image'           => $row['image'],
						'link'            => [
							'href' => $this->journal3_url->link('product/manufacturer' . JOURNAL3_ROUTE_SEPARATOR . 'info', 'manufacturer_id=' . $row['manufacturer_id']),
						],
					];
				}

				$this->journal3_cache->set($cache_key, static::$results, false);
			}
		}

		return static::$results[$manufacturer_id] ?? null;
	}

}

class_alias('ModelJournal3Manufacturer', '\Opencart\Catalog\Model\Journal3\Manufacturer');
