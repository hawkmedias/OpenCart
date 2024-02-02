<?php
/**
 * Class Translation
 *
 * @package Catalog\Model\Design
 */
class ModelDesignTranslation extends Model {
	/**
	 * Get Translations
	 *
	 * @param string $route
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getTranslations(string $route): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "translation` WHERE `store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "' AND `route` = '" . $this->db->escape($route) . "'");

		return $query->rows;
	}
}
