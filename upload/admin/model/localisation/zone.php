<?php
/**
 * Class Zone
 *
 * @package Admin\Model\Localisation
 */
class ModelLocalisationZone extends Model {
	/**
	 * addZone
	 *
	 * @param array $data
	 *
	 * @return int
	 */
	public function addZone(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "zone` SET `status` = '" . (int)$data['status'] . "', `name` = '" . $this->db->escape($data['name']) . "', `code` = '" . $this->db->escape($data['code']) . "', `country_id` = '" . (int)$data['country_id'] . "'");

		$this->cache->delete('zone');

		return $this->db->getLastId();
	}

	/**
	 * editZone
	 *
	 * @param int   $zone_id
	 * @param array $data
	 *
	 * @return void
	 */
	public function editZone(int $zone_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "zone` SET `status` = '" . (int)$data['status'] . "', `name` = '" . $this->db->escape($data['name']) . "', `code` = '" . $this->db->escape($data['code']) . "', `country_id` = '" . (int)$data['country_id'] . "' WHERE `zone_id` = '" . (int)$zone_id . "'");

		$this->cache->delete('zone');
	}

	/**
	 * deleteZone
	 *
	 * @param int $zone_id
	 *
	 * @return void
	 */
	public function deleteZone(int $zone_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "zone` WHERE `zone_id` = '" . (int)$zone_id . "'");

		$this->cache->delete('zone');
	}

	/**
	 * getZone
	 *
	 * @param int $zone_id
	 *
	 * @return array
	 */
	public function getZone(int $zone_id): array {
		$query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "zone` WHERE `zone_id` = '" . (int)$zone_id . "'");

		return $query->row;
	}

	/**
	 * getZones
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function getZones(array $data = []): array {
		$sql = "SELECT *, `z`.`name`, `c`.`name` AS `country` FROM `" . DB_PREFIX . "zone` `z` LEFT JOIN `" . DB_PREFIX . "country` `c` ON (`z`.`country_id` = `c`.`country_id`)";

		$sort_data = [
			'c.name',
			'z.name',
			'z.code'
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY `c`.`name`";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * getZonesByCountryId
	 *
	 * @param int $country_id
	 *
	 * @return array
	 */
	public function getZonesByCountryId(int $country_id): array {
		$zone_data = $this->cache->get('zone.' . (int)$country_id);

		if (!$zone_data) {
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE `country_id` = '" . (int)$country_id . "' AND `status` = '1' ORDER BY `name`");

			$zone_data = $query->rows;

			$this->cache->set('zone.' . (int)$country_id, $zone_data);
		}

		return $zone_data;
	}

	/**
	 * getTotalZones
	 *
	 * @return int
	 */
	public function getTotalZones(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "zone`");

		return (int)$query->row['total'];
	}

	/**
	 * getTotalZonesByCountryId
	 *
	 * @param int $country_id
	 *
	 * @return int
	 */
	public function getTotalZonesByCountryId(int $country_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "zone` WHERE `country_id` = '" . (int)$country_id . "'");

		return (int)$query->row['total'];
	}
}
