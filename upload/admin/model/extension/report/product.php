<?php
/**
 * Class Product
 *
 * @package Admin\Model\Extension\Report
 */
class ModelExtensionReportProduct extends Model {
	/**
	 * getProductsViewed
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array
	 */
	public function getProductsViewed(array $data = []): array {
		$sql = "SELECT `pd`.`name`, `p`.`model`, `p`.`viewed` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id`) WHERE `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND `p`.`viewed` > '0' ORDER BY `p`.`viewed` DESC";

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
	 * getTotalProductViews
	 *
	 * @return int
	 */
	public function getTotalProductViews(): int {
		$query = $this->db->query("SELECT SUM(`viewed`) AS `total` FROM `" . DB_PREFIX . "product`");

		if ($query->num_rows) {
			return (int)$query->row['total'];
		} else {
			return 0;
		}
	}

	/**
	 * getTotalProductsViewed
	 *
	 * @return int
	 */
	public function getTotalProductsViewed(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "product` WHERE `viewed` > '0'");

		return (int)$query->row['total'];
	}

	/**
	 * Reset
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "product` SET `viewed` = '0'");
	}

	/**
	 * getPurchased
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array
	 */
	public function getPurchased(array $data = []): array {
		$implode = [];

		$sql = "SELECT `op`.`name`, `op`.`model`, SUM(`op`.`quantity`) AS `quantity`, SUM((`op`.`price` + `op`.`tax`) * `op`.`quantity`) AS `total` FROM `" . DB_PREFIX . "order_product` `op` LEFT JOIN `" . DB_PREFIX . "order` `o` ON (`op`.`order_id` = `o`.`order_id`)";

		if (!empty($data['filter_order_status_id'])) {
			$implode[] = "`o`.`order_status_id` = '" . (int)$data['filter_order_status_id'] . "'";
		} else {
			$implode[] = "`o`.`order_status_id` > '0'";
		}

		if (!empty($data['filter_date_start'])) {
			$implode[] = "DATE(`o`.`date_added`) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
		}

		if (!empty($data['filter_date_end'])) {
			$implode[] = "DATE(`o`.`date_added`) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$sql .= " GROUP BY `op`.`product_id` ORDER BY total DESC";

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
	 * getTotalPurchased
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function getTotalPurchased(array $data): int {
		$implode = [];

		$sql = "SELECT COUNT(DISTINCT `op`.`product_id`) AS `total` FROM `" . DB_PREFIX . "order_product` `op` LEFT JOIN `" . DB_PREFIX . "order` `o` ON (`op`.`order_id` = `o`.`order_id`)";

		if (!empty($data['filter_order_status_id'])) {
			$implode[] = "`o`.`order_status_id` = '" . (int)$data['filter_order_status_id'] . "'";
		} else {
			$implode[] = "`o`.`order_status_id` > '0'";
		}

		if (!empty($data['filter_date_start'])) {
			$implode[] = "DATE(`o`.`date_added`) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
		}

		if (!empty($data['filter_date_end'])) {
			$implode[] = "DATE(`o`.`date_added`) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}
}
