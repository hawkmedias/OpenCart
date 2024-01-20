<?php
/**
 * Class Reward
 *
 * @package Catalog\Model\Extension\Total
 */
class ModelExtensionTotalReward extends Model {
	/**
	 * getTotal
	 *
	 * @param array $total
	 */
	public function getTotal(array $total): void {
		if (isset($this->session->data['reward'])) {
			$this->load->language('extension/total/reward', 'reward');

			$points = $this->customer->getRewardPoints();

			if ($this->session->data['reward'] <= $points) {
				$discount_total = 0;

				$points_total = 0;

				foreach ($this->cart->getProducts() as $product) {
					if ($product['points']) {
						$points_total += $product['points'];
					}
				}

				$points = min($points, $points_total);

				foreach ($this->cart->getProducts() as $product) {
					$discount = 0;

					if ($product['points']) {
						$discount = $product['total'] * ($this->session->data['reward'] / $points_total);

						if ($product['tax_class_id']) {
							$tax_rates = $this->tax->getRates($product['total'] - ($product['total'] - $discount), $product['tax_class_id']);

							foreach ($tax_rates as $tax_rate) {
								if ($tax_rate['type'] == 'P') {
									$total['taxes'][$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
								}
							}
						}
					}

					$discount_total += $discount;
				}

				$total['totals'][] = [
					'code'       => 'reward',
					'title'      => sprintf($this->language->get('reward')->get('text_reward'), $this->session->data['reward']),
					'value'      => -$discount_total,
					'sort_order' => $this->config->get('total_reward_sort_order')
				];

				$total['total'] -= $discount_total;
			}
		}
	}

	/**
	 * Confirm
	 *
	 * @param array $order_info
	 * @param array $order_total
	 */
	public function confirm(array $order_info, array $order_total): int {
		$this->load->language('extension/opencart/total/reward');

		$points = 0;

		$start = strpos($order_total['title'], '(') + 1;
		$end = strrpos($order_total['title'], ')');

		if ($start && $end) {
			$points = substr($order_total['title'], $start, $end - $start);
		}

		// Customers
		$this->load->model('account/customer');

		if ($order_info['customer_id'] && $this->model_account_customer->getRewardTotal($order_info['customer_id']) >= $points) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "customer_reward` SET `customer_id` = '" . (int)$order_info['customer_id'] . "', `order_id` = '" . (int)$order_info['order_id'] . "', `description` = '" . $this->db->escape(sprintf($this->language->get('text_order_id'), (int)$order_info['order_id'])) . "', `points` = '" . -(float)$points . "', `date_added` = NOW()");
		} else {
			return $this->config->get('config_fraud_status_id');
		}

		return 0;
	}

	/**
	 * Unconfirm
	 *
	 * @param int $order_id
	 */
	public function unconfirm(int $order_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "customer_reward` WHERE `order_id` = '" . (int)$order_id . "' AND `points` < '0'");
	}
}
