<?php
/**
 * Class Subscription Status
 *
 * @package Catalog\Model\Localisation
 */
class ModelLocalisationSubscriptionStatus extends Model {
	/**
	 * Get Subscription Status
	 *
	 * @param int $subscription_status_id
	 *
	 * @return array<string, mixed>
	 */
	public function getSubscriptionStatus(int $subscription_status_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "subscription_status` WHERE `subscription_status_id` = '" . (int)$subscription_status_id . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	/**
	 * Get Subscription Statuses
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getSubscriptionStatuses(): array {
		$sql = "SELECT `subscription_status_id`, `name` FROM `" . DB_PREFIX . "subscription_status` WHERE `language_id` = '" . (int)$this->config->get('config_language_id') . "' ORDER BY `name`";

		$key = md5($sql);

		$subscription_status_data = $this->cache->get('subscription_status.' . $key);

		if (!$subscription_status_data) {
			$query = $this->db->query($sql);

			$subscription_status_data = $query->rows;

			$this->cache->set('subscription_status.' . $key, $subscription_status_data);
		}

		return $subscription_status_data;
	}
}
