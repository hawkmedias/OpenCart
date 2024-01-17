<?php
/**
 * Class Worldpay
 *
 * @package Catalog\Model\Extension\Payment
 */
class ModelExtensionPaymentWorldpay extends Model {
	/**
	 * getMethod
	 *
	 * @param array $address
	 *
	 * @return array
	 */
	public function getMethod(array $address): array {
		$this->load->language('extension/payment/worldpay');

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_worldpay_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

		if (!$this->config->get('payment_worldpay_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = [];

		if ($status) {
			$method_data = [
				'code'       => 'worldpay',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_worldpay_sort_order')
			];
		}

		return $method_data;
	}

	/**
	 * getCards
	 *
	 * @param int $customer_id
	 *
	 * @return array
	 */
	public function getCards(int $customer_id): array {
		$card_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "worldpay_card` WHERE `customer_id` = '" . (int)$customer_id . "'");

		// Addresses
		$this->load->model('account/address');

		foreach ($query->rows as $row) {

			$card_data[] = [
				'card_id'     => $row['card_id'],
				'customer_id' => $row['customer_id'],
				'token'       => $row['token'],
				'digits'      => $row['digits'],
				'expiry'      => $row['expiry'],
				'type'        => $row['type'],
			];
		}

		return $card_data;
	}

	/**
	 * addCard
	 *
	 * @param int   $order_id
	 * @param array $card_data
	 *
	 * @return void
	 */
	public function addCard(int $order_id, array $card_data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "worldpay_card` SET `customer_id` = '" . (int)$card_data['customer_id'] . "', `order_id` = '" . (int)$order_id . "', `digits` = '" . $this->db->escape($card_data['Last4Digits']) . "', `expiry` = '" . $this->db->escape($card_data['ExpiryDate']) . "', `type` = '" . $this->db->escape($card_data['CardType']) . "', `token` = '" . $this->db->escape($card_data['Token']) . "'");
	}

	/**
	 * deleteCard
	 *
	 * @param string $token
	 *
	 * @return bool
	 */
	public function deleteCard(string $token): bool {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "worldpay_card` WHERE `customer_id` = '" . $this->customer->isLogged() . "' AND `token` = '" . $this->db->escape($token) . "'");

		if ($this->db->countAffected() > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * addOrder
	 *
	 * @param array  $order_info
	 * @param string $order_code
	 *
	 * @return int
	 */
	public function addOrder(array $order_info, string $order_code): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "worldpay_order` SET `order_id` = '" . (int)$order_info['order_id'] . "', `order_code` = '" . $this->db->escape($order_code) . "', `date_added` = NOW(), `date_modified` = NOW(), `currency_code` = '" . $this->db->escape($order_info['currency_code']) . "', `total` = '" . $this->currency->format($order_info['total'], $order_info['currency_code'], false, false) . "'");

		return $this->db->getLastId();
	}

	/**
	 * getOrder
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function getOrder(int $order_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "worldpay_order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

		if ($query->num_rows) {
			$order = $query->row;
			$order['transactions'] = $this->getTransactions($order['worldpay_order_id']);

			return $order;
		} else {
			return [];
		}
	}

	/**
	 * addTransaction
	 *
	 * @param int    $worldpay_order_id
	 * @param string $type
	 * @param array  $order_info
	 *
	 * @return void
	 */
	public function addTransaction(int $worldpay_order_id, string $type, array $order_info): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "worldpay_order_transaction` SET `worldpay_order_id` = '" . (int)$worldpay_order_id . "', `date_added` = NOW(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . $this->currency->format($order_info['total'], $order_info['currency_code'], false, false) . "'");
	}

	/**
	 * getTransactions
	 *
	 * @param int $worldpay_order_id
	 *
	 * @return array
	 */
	public function getTransactions(int $worldpay_order_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "worldpay_order_transaction` WHERE `worldpay_order_id` = '" . (int)$worldpay_order_id . "'");

		if ($query->num_rows) {
			return $query->rows;
		} else {
			return [];
		}
	}

	/**
	 * recurringPayment
	 *
	 * @param array  $item
	 * @param string $order_id_rand
	 * @param string $token
	 *
	 * @return void
	 */
	public function recurringPayment(array $item, string $order_id_rand, string $token): void {
		// Subscriptions
		$this->load->model('checkout/subscription');

		// Worldpay
		$this->load->model('extension/payment/worldpay');

		// Trial information
		if ($item['subscription']['trial_status'] == 1) {
			$price = $item['subscription']['trial_price'];
			$trial_amt = $this->currency->format($this->tax->calculate($item['subscription']['trial_price'], $item['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'], false, false) * $item['quantity'] . ' ' . $this->session->data['currency'];
			$trial_text = sprintf($this->language->get('text_trial'), $trial_amt, $item['subscription']['trial_cycle'], $item['subscription']['trial_frequency'], $item['subscription']['trial_duration']);
		} else {
			$price = $item['subscription']['price'];
			$trial_text = '';
		}

		$subscription_amt = $this->currency->format($this->tax->calculate($item['subscription']['price'], $item['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'], false, false) * $item['quantity'] . ' ' . $this->session->data['currency'];

		$subscription_description = $trial_text . sprintf($this->language->get('text_subscription'), $subscription_amt, $item['subscription']['cycle'], $item['subscription']['frequency']);

		if ($item['subscription']['duration'] > 0) {
			$subscription_description .= sprintf($this->language->get('text_length'), $item['subscription']['duration']);
		}

		$item['subscription']['description'] = $subscription_description;

		$subscription_id = $this->model_checkout_subscription->addSubscription($this->session->data['order_id'], $item['subscription']);

		$this->model_checkout_subscription->editReference($subscription_id, $order_id_rand);

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$order = [
			'token'             => $token,
			'orderType'         => 'RECURRING',
			'amount'            => (int)($price * 100),
			'currencyCode'      => $order_info['currency_code'],
			'name'              => $order_info['firstname'] . ' ' . $order_info['lastname'],
			'orderDescription'  => $order_info['store_name'] . ' - ' . date('Y-m-d H:i:s'),
			'customerOrderCode' => 'orderRecurring-' . $subscription_id
		];

		$this->model_extension_payment_worldpay->logger($order);

		$response_data = $this->model_extension_payment_worldpay->sendCurl('orders', $order);

		$this->model_extension_payment_worldpay->logger($response_data);

		$next_payment = new \DateTime('now');
		$trial_end = new \DateTime('now');
		$subscription_end = new \DateTime('now');

		if ($item['subscription']['trial_status'] == 1 && $item['subscription']['trial_duration'] != 0) {
			$next_payment = $this->calculateSchedule($item['subscription']['trial_frequency'], $next_payment, $item['subscription']['trial_cycle']);
			$trial_end = $this->calculateSchedule($item['subscription']['trial_frequency'], $trial_end, $item['subscription']['trial_cycle'] * $item['subscription']['trial_duration']);
		} elseif ($item['subscription']['trial_status'] == 1) {
			$next_payment = $this->calculateSchedule($item['subscription']['trial_frequency'], $next_payment, $item['subscription']['trial_cycle']);
			$trial_end = new \DateTime('0000-00-00');
		}

		if ($trial_end > $subscription_end && $item['subscription']['duration'] != 0) {
			$subscription_end = new \DateTime(date_format($trial_end, 'Y-m-d H:i:s'));
			$subscription_end = $this->calculateSchedule($item['subscription']['frequency'], $subscription_end, $item['subscription']['cycle'] * $item['subscription']['duration']);
		} elseif ($trial_end == $subscription_end && $item['subscription']['duration'] != 0) {
			$next_payment = $this->calculateSchedule($item['subscription']['frequency'], $next_payment, $item['subscription']['cycle']);
			$subscription_end = $this->calculateSchedule($item['subscription']['frequency'], $subscription_end, $item['subscription']['cycle'] * $item['subscription']['duration']);
		} elseif ($trial_end > $subscription_end && $item['subscription']['duration'] == 0) {
			$subscription_end = new \DateTime('0000-00-00');
		} elseif ($trial_end == $subscription_end && $item['subscription']['duration'] == 0) {
			$next_payment = $this->calculateSchedule($item['subscription']['frequency'], $next_payment, $item['subscription']['cycle']);
			$subscription_end = new \DateTime('0000-00-00');
		}

		if (isset($response_data->paymentStatus) && $response_data->paymentStatus == 'SUCCESS') {
			$this->addRecurringOrder($order_info, $response_data->orderCode, $token, $price, $subscription_id, date_format($trial_end, 'Y-m-d H:i:s'), date_format($subscription_end, 'Y-m-d H:i:s'));
			$this->updateRecurringOrder($subscription_id, date_format($next_payment, 'Y-m-d H:i:s'));
			$this->addProfileTransaction($subscription_id, $response_data->orderCode, $price, 1);
		} else {
			$this->addProfileTransaction($subscription_id, '', $price, 4);
		}
	}

	/**
	 * cronPayment
	 *
	 * @return array
	 */
	public function cronPayment(): array {
		// Account Order
		$this->load->model('account/order');

		// Checkout Order
		$this->load->model('checkout/order');

		$i = 1;
		$profiles = $this->getProfiles();
		$cron_data = [];

		foreach ($profiles as $profile) {
			$subscription_order = $this->getRecurringOrder($profile['subscription_id']);

			$today = new \DateTime('now');
			$unlimited = new \DateTime('0000-00-00');
			$next_payment = new \DateTime($subscription_order['next_payment']);
			$trial_end = new \DateTime($subscription_order['trial_end']);
			$subscription_end = new \DateTime($subscription_order['subscription_end']);

			$order_info = $this->model_checkout_order->getOrder($profile['order_id']);

			if (($today > $next_payment) && ($trial_end > $today || $trial_end == $unlimited)) {
				$price = $this->currency->format($profile['trial_price'], $order_info['currency_code'], false, false);
				$frequency = $profile['trial_frequency'];
				$cycle = $profile['trial_cycle'];
			} elseif (($today > $next_payment) && ($subscription_end > $today || $subscription_end == $unlimited)) {
				$price = $this->currency->format($profile['price'], $order_info['currency_code'], false, false);
				$frequency = $profile['frequency'];
				$cycle = $profile['cycle'];
			} else {
				continue;
			}

			$order = [
				'token'             => $subscription_order['token'],
				'orderType'         => 'RECURRING',
				'amount'            => (int)($price * 100),
				'currencyCode'      => $order_info['currency_code'],
				'name'              => $order_info['firstname'] . ' ' . $order_info['lastname'],
				'"orderDescription' => $order_info['store_name'] . ' - ' . date('Y-m-d H:i:s'),
				'customerOrderCode' => 'orderRecurring-' . $profile['subscription_id'] . '-repeat-' . $i++
			];

			$this->model_extension_payment_worldpay->logger($order);

			$response_data = $this->model_extension_payment_worldpay->sendCurl('orders', $order);

			$this->model_extension_payment_worldpay->logger($response_data);

			$cron_data[] = $response_data;

			if (isset($response_data->paymentStatus) && $response_data->paymentStatus == 'SUCCESS') {
				$this->addProfileTransaction($profile['subscription_id'], $response_data->orderCode, $price, 1);

				$next_payment = $this->calculateSchedule($frequency, $next_payment, $cycle);
				$next_payment = date_format($next_payment, 'Y-m-d H:i:s');

				$this->updateRecurringOrder($profile['subscription_id'], $next_payment);
			} else {
				$this->addProfileTransaction($profile['subscription_id'], '', $price, 4);
			}
		}

		// Log
		$log = new \Log('worldpay_recurring_orders.log');
		$log->write(print_r($cron_data, 1));

		return $cron_data;
	}

	private function calculateSchedule($frequency, $next_payment, $cycle) {
		if ($frequency == 'semi_month') {
			// https://stackoverflow.com/a/35473574
			$day = date_create_from_format('j M, Y', $next_payment->date);
			$day = date_create($day);
			$day = date_format($day, 'd');
			$value = 15 - $day;
			$isEven = false;

			if ($cycle % 2 == 0) {
				$isEven = true;
			}

			$odd = ($cycle + 1) / 2;
			$plus_even = ($cycle / 2) + 1;
			$minus_even = $cycle / 2;

			if ($day == 1) {
				$odd--;
				$plus_even--;
				$day = 16;
			}

			if ($day <= 15 && $isEven) {
				$next_payment->modify('+' . $value . ' day');
				$next_payment->modify('+' . $minus_even . ' month');
			} elseif ($day <= 15) {
				$next_payment->modify('first day of this month');
				$next_payment->modify('+' . $odd . ' month');
			} elseif ($day > 15 && $isEven) {
				$next_payment->modify('first day of this month');
				$next_payment->modify('+' . $plus_even . ' month');
			} elseif ($day > 15) {
				$next_payment->modify('+' . $value . ' day');
				$next_payment->modify('+' . $odd . ' month');
			}
		} else {
			$next_payment->modify('+' . $cycle . ' ' . $frequency);
		}

		return $next_payment;
	}

	private function addRecurringOrder($order_info, $order_code, $token, $price, $order_recurring_id, $trial_end, $subscription_end): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "worldpay_order_recurring` SET `order_id` = '" . (int)$order_info['order_id'] . "', `order_recurring_id` = '" . (int)$order_recurring_id . "', `order_code` = '" . $this->db->escape($order_code) . "', `token` = '" . $this->db->escape($token) . "', `date_added` = NOW(), `date_modified` = NOW(), `next_payment` = NOW(), `trial_end` = '" . $trial_end . "', `subscription_end` = '" . $subscription_end . "', `currency_code` = '" . $this->db->escape($order_info['currency_code']) . "', `total` = '" . $this->currency->format($price, $order_info['currency_code'], false, false) . "'");
	}

	private function updateRecurringOrder($order_recurring_id, $next_payment): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "worldpay_order_recurring` SET `next_payment` = '" . $next_payment . "', `date_modified` = NOW() WHERE `order_recurring_id` = '" . (int)$order_recurring_id . "'");
	}

	private function getRecurringOrder($order_recurring_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "worldpay_order_recurring` WHERE `order_recurring_id` = '" . (int)$order_recurring_id . "'");

		return $query->row;
	}

	private function addProfileTransaction($order_recurring_id, $order_code, $price, $type): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "order_recurring_transaction` SET `order_recurring_id` = '" . (int)$order_recurring_id . "', `date_added` = NOW(), `amount` = '" . (float)$price . "', `type` = '" . (int)$type . "', `reference` = '" . $this->db->escape($order_code) . "'");
	}

	private function getProfiles() {
		$order_subscription = [];

		$sql = "SELECT `s`.`subscription_id` FROM `" . DB_PREFIX . "subscription` `s` JOIN `" . DB_PREFIX . "order` `o` USING(`order_id`) WHERE `o`.`payment_code` = 'worldpay'";

		$query = $this->db->query($sql);

		foreach ($query->rows as $subscription) {
			$order_subscription[] = $this->getProfile($subscription['subscription_id']);
		}

		return $order_subscription;
	}

	private function getProfile($subscription_id) {
		// Subscriptions
		$this->load->model('account/subscription');

		return $this->model_account_subscription->getSubscription($subscription_id);
	}

	/**
	 * getWorldpayOrder
	 *
	 * @param int $worldpay_order_id
	 *
	 * @return array
	 */
	public function getWorldpayOrder(int $worldpay_order_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "worldpay_order` WHERE `order_code` = '" . (int)$worldpay_order_id . "'");

		return $query->row;
	}

	/**
	 * updateCronJobRunTime
	 *
	 * @return void
	 */
	public function updateCronJobRunTime(): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'payment_worldpay' AND `key` = 'payment_worldpay_last_cron_job_run'");

		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = '0', `code` = 'payment_worldpay', `key` = 'payment_worldpay_last_cron_job_run', `value` = NOW(), `serialized` = '0'");
	}

	/**
	 * sendCurl
	 *
	 * @param string      $url
	 * @param array 	  $order
	 * 
	 * @return array
	 */
	public function sendCurl(string $url, $order = []): array {
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, 'https://api.worldpay.com/v1/' . $url);

		$content_length = 0;

		if ($order) {
			$json = json_encode($order);
			$content_length = strlen($json);

			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
		}

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);

		curl_setopt($curl, CURLOPT_HTTPHEADER, [
			"Authorization: " . $this->config->get('payment_worldpay_service_key'),
			"Content-Type: application/json",
			"Content-Length: " . $content_length
		]);

		$result = json_decode(curl_exec($curl), true);

		curl_close($curl);

		return $result;
	}

	/**
	 * Logger
	 *
	 * @param string $data
	 *
	 * @return void
	 */
	public function logger(string $data): void {
		if ($this->config->get('payment_worldpay_debug')) {
			// Log
			$log = new \Log('worldpay_debug.log');
			$backtrace = debug_backtrace();
			$log->write($backtrace[6]['class'] . '::' . $backtrace[6]['function'] . ' Data:  ' . print_r($data, 1));
		}
	}

	/**
	 * subscriptionPayments
	 *
	 * @return bool
	 */
	public function subscriptionPayments(): bool {
		/*
		 * Used by the checkout to state the module
		 * supports subscriptions.
		 */

		return true;
	}
}
