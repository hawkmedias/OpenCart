<?php
/**
 * Class Squareup
 *
 * @package Catalog\Model\Extension\CreditCard
 */
class ModelExtensionCreditCardSquareup extends Model {
	/**
	 * addCustomer
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function addCustomer(array $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "squareup_customer` SET `customer_id` = '" . (int)$data['customer_id'] . "', `sandbox` = '" . (int)$data['sandbox'] . "', `square_customer_id` = '" . $this->db->escape($data['square_customer_id']) . "'");
	}

	/**
	 * getCustomer
	 *
	 * @param int $customer_id
	 * @param int $sandbox
	 *
	 * @return array
	 */
	public function getCustomer(int $customer_id, int $sandbox): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "squareup_customer` WHERE `customer_id` = '" . (int)$customer_id . "' AND `sandbox` = '" . (int)$sandbox . "'");

		return $query->row;
	}

	/**
	 * addCard
	 *
	 * @param int                  $customer_id
	 * @param int                  $sandbox
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function addCard(int $customer_id, int $sandbox, array $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "squareup_token` SET `customer_id` = '" . (int)$customer_id . "', `sandbox` = '" . (int)$sandbox . "', `token` = '" . $this->db->escape($data['id']) . "', `brand` = '" . $this->db->escape($data['card_brand']) . "', `ends_in` = '" . (int)$data['last_4'] . "', `date_added` = NOW()");
	}

	/**
	 * getCard
	 *
	 * @param int $squareup_token_id
	 *
	 * @return array
	 */
	public function getCard(int $squareup_token_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "squareup_token` WHERE `squareup_token_id` = '" . (int)$squareup_token_id . "'");

		return $query->row;
	}

	/**
	 * getCards
	 *
	 * @param int $customer_id
	 * @param int $sandbox
	 *
	 * @return array
	 */
	public function getCards(int $customer_id, int $sandbox): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "squareup_token` WHERE `customer_id` = '" . (int)$customer_id . "' AND `sandbox` = '" . (int)$sandbox . "'");

		return $query->rows;
	}

	/**
	 * cardExists
	 *
	 * @param int                  $customer_id
	 * @param array<string, mixed> $data
	 *
	 * @return bool
	 */
	public function cardExists(int $customer_id, array $data): bool {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "squareup_token` WHERE `customer_id` = '" . (int)$customer_id . "' AND `brand` = '" . $this->db->escape($data['card_brand']) . "' AND `ends_in` = '" . (int)$data['last_4'] . "'");

		if ($query->num_rows) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * verifyCardCustomer
	 *
	 * @param int $squareup_token_id
	 * @param int $customer_id
	 *
	 * @return bool
	 */
	public function verifyCardCustomer(int $squareup_token_id, int $customer_id): bool {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "squareup_token` WHERE `squareup_token_id` = '" . (int)$squareup_token_id . "' AND `customer_id` = '" . (int)$customer_id . "'");

		if ($query->num_rows) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * deleteCard
	 *
	 * @param int $squareup_token_id
	 *
	 * @return void
	 */
	public function deleteCard(int $squareup_token_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "squareup_token` WHERE `squareup_token_id` = '" . (int)$squareup_token_id . "'");
	}
}
