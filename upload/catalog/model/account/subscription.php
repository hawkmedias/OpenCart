<?php
/**
 * Class Subscription
 *
 * @package Catalog\Model\Account
 */
class ModelAccountSubscription extends Model {
	/**
	 * getSubscription
	 *
	 * @param int $subscription_id
	 *
	 * @return array
	 */
	public function getSubscription(int $subscription_id): array {
		$subscription_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "subscription` `s` WHERE `subscription_id` = '" . (int)$subscription_id . "' AND `customer_id` = '" . (int)$this->customer->getId() . "'");

		if ($query->num_rows) {
			$subscription_data = $query->row;

			$subscription_data['option'] = ($query->row['option'] ? json_decode($query->row['option'], true) : '');
			$subscription_data['payment_method'] = ($query->row['payment_method'] ? json_decode($query->row['payment_method'], true) : '');
			$subscription_data['shipping_method'] = ($query->row['shipping_method'] ? json_decode($query->row['shipping_method'], true) : '');
		}

		return $subscription_data;
	}

	/**
	 * getSubscriptionByOrderProductId
	 *
	 * @param int $order_id
	 * @param int $order_product_id
	 *
	 * @return array
	 */
	public function getSubscriptionByOrderProductId(int $order_id, int $order_product_id): array {
		$subscription_data = [];

		$query = $this->db->query("SELECT * FROM  `" . DB_PREFIX . "subscription` WHERE `order_id` = '" . (int)$order_id . "' AND `order_product_id` = '" . (int)$order_product_id . "' AND `customer_id` = '" . (int)$this->customer->getId() . "'");

		if ($query->num_rows) {
			$subscription_data = $query->row;

			$subscription_data['option'] = ($query->row['option'] ? json_decode($query->row['option'], true) : '');
			$subscription_data['payment_method'] = ($query->row['payment_method'] ? json_decode($query->row['payment_method'], true) : '');
			$subscription_data['shipping_method'] = ($query->row['shipping_method'] ? json_decode($query->row['shipping_method'], true) : '');
		}

		return $subscription_data;
	}

	/**
	 * getSubscriptions
	 *
	 * @param int $start
	 * @param int $limit
	 *
	 * @return array
	 */
	public function getSubscriptions(int $start = 0, int $limit = 20): array {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 1;
		}

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "subscription` WHERE `customer_id` = '" . (int)$this->customer->getId() . "' AND `subscription_status_id` > '0' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "' ORDER BY `subscription_id` DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	/**
	 * getTotalSubscriptions
	 *
	 * @return int
	 */
	public function getTotalSubscriptions(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "subscription` WHERE `customer_id` = '" . (int)$this->customer->getId() . "' AND `subscription_status_id` > '0' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "'");

		return (int)$query->row['total'];
	}

	/**
	 * getTotalSubscriptionByShippingAddressId
	 *
	 * @param int $address_id
	 *
	 * @return int
	 */
	public function getTotalSubscriptionByShippingAddressId(int $address_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "subscription` WHERE `customer_id` = '" . (int)$this->customer->getId() . "' AND `shipping_address_id` = '" . (int)$address_id . "'");

		return (int)$query->row['total'];
	}

	/**
	 * getTotalSubscriptionByPaymentAddressId
	 *
	 * @param int $address_id
	 *
	 * @return int
	 */
	public function getTotalSubscriptionByPaymentAddressId(int $address_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "subscription` WHERE `customer_id` = '" . (int)$this->customer->getId() . "' AND `payment_address_id` = '" . (int)$address_id . "'");

		return (int)$query->row['total'];
	}

	/**
	 * getHistories
	 *
	 * @param int $subscription_id
	 * @param int $start
	 * @param int $limit
	 *
	 * @return array
	 */
	public function getHistories(int $subscription_id, int $start = 0, int $limit = 10): array {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 10;
		}

		$query = $this->db->query("SELECT `sh`.`date_added`, `ss`.`name` AS `status`, `sh`.`comment`, `sh`.`notify` FROM `" . DB_PREFIX . "subscription_history` `sh` LEFT JOIN `" . DB_PREFIX . "subscription_status` `ss` ON `sh`.`subscription_status_id` = `ss`.`subscription_status_id` WHERE `sh`.`subscription_id` = '" . (int)$subscription_id . "' AND `ss`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' ORDER BY `sh`.`date_added` DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	/**
	 * getTotalHistories
	 *
	 * @param int $subscription_id
	 *
	 * @return int
	 */
	public function getTotalHistories(int $subscription_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "subscription_history` WHERE `subscription_id` = '" . (int)$subscription_id . "'");

		return (int)$query->row['total'];
	}
}
