<?php
/**
 * Class Subscription
 *
 * @package Admin\Model\Extension\Report
 */
class ModelExtensionReportSubscription extends Model {
	/**
	 * getSubscriptions
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function getSubscriptions(array $data = []): array {
		$implode = [];

		$sql = "SELECT MIN(`s`.`date_added`) AS date_start, MAX(`s`.`date_added`) AS date_end, COUNT(*) AS `subscriptions`, SUM((SELECT SUM(`ot`.`value`) FROM `" . DB_PREFIX . "order_total` ot WHERE ot.`order_id` = `s`.`order_id` AND ot.`code` = 'tax' GROUP BY ot.`order_id`)) AS tax, SUM(`s`.`quantity`) AS `products`, SUM(`s`.`price`) AS `total` FROM `" . DB_PREFIX . "subscription` `s`";

		if (isset($data['filter_subscription_status_id']) && $data['filter_subscription_status_id'] != '') {
			$implode[] = "`s`.`subscription_status_id` = '" . (int)$data['filter_subscription_status_id'] . "'";
		} else {
			$implode[] = "`s`.`subscription_status_id` > '0'";
		}

		if (!empty($data['filter_date_start'])) {
			$implode[] = "DATE(`s`.`date_added`) >= DATE('" . $this->db->escape((string)$data['filter_date_start']) . "')";
		}

		if (!empty($data['filter_date_end'])) {
			$implode[] = "DATE(`s`.`date_added`) <= DATE('" . $this->db->escape((string)$data['filter_date_end']) . "')";
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		if (!empty($data['filter_group'])) {
			$group = $data['filter_group'];
		} else {
			$group = 'week';
		}

		switch ($group) {
			case 'day':
				$sql .= " GROUP BY YEAR(`s`.`date_added`), MONTH(`s`.`date_added`), DAY(`s`.`date_added`)";
				break;
			default:
			case 'week':
				$sql .= " GROUP BY YEAR(`s`.`date_added`), WEEK(`s`.`date_added`)";
				break;
			case 'month':
				$sql .= " GROUP BY YEAR(`s`.`date_added`), MONTH(`s`.`date_added`)";
				break;
			case 'year':
				$sql .= " GROUP BY YEAR(`s`.`date_added`)";
				break;
		}

		$sql .= " ORDER BY `s`.`date_added` DESC";

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
	 * getTotalSubscriptions
	 *
	 * @param array $data
	 *
	 * @return int
	 */
	public function getTotalSubscriptions(array $data = []): int {
		$implode = [];

		if (!empty($data['filter_group'])) {
			$group = $data['filter_group'];
		} else {
			$group = 'week';
		}

		switch ($group) {
			case 'day':
				$sql = "SELECT COUNT(DISTINCT YEAR(`date_added`), MONTH(`date_added`), DAY(`date_added`)) AS `total` FROM `" . DB_PREFIX . "subscription`";
				break;
			default:
			case 'week':
				$sql = "SELECT COUNT(DISTINCT YEAR(`date_added`), WEEK(`date_added`)) AS `total` FROM `" . DB_PREFIX . "subscription`";
				break;
			case 'month':
				$sql = "SELECT COUNT(DISTINCT YEAR(`date_added`), MONTH(`date_added`)) AS `total` FROM `" . DB_PREFIX . "subscription`";
				break;
			case 'year':
				$sql = "SELECT COUNT(DISTINCT YEAR(`date_added`)) AS `total` FROM `" . DB_PREFIX . "subscription`";
				break;
		}

		if (!empty($data['filter_subscription_status_id'])) {
			$implode[] = "`subscription_status_id` = '" . (int)$data['filter_subscription_status_id'] . "'";
		} else {
			$implode[] = "`subscription_status_id` > '0'";
		}

		if (!empty($data['filter_date_start'])) {
			$implode[] = "DATE(`date_added`) >= DATE('" . $this->db->escape((string)$data['filter_date_start']) . "')";
		}

		if (!empty($data['filter_date_end'])) {
			$implode[] = "DATE(`date_added`) <= DATE('" . $this->db->escape((string)$data['filter_date_end']) . "')";
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}
}
