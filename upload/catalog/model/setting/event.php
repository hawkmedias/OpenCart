<?php
/**
 * Class Event
 *
 * @package Catalog\Model\Setting
 */
class ModelSettingEvent extends Model {
	/**
	 * Get Events
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getEvents(): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "event` WHERE `trigger` LIKE 'catalog/%' AND `status` = '1' ORDER BY `sort_order` ASC");

		return $query->rows;
	}
}
