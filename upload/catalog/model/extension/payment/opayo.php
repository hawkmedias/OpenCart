<?php
/**
 * Class Opayo
 *
 * @package Catalog\Model\Extension\Payment
 */
class ModelExtensionPaymentOpayo extends Model {
	/**
	 * getMethod
	 *
	 * @param array $address
	 * @param float $total
	 */
	public function getMethod(array $address, float $total): array {
		$this->load->language('extension/payment/opayo');

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_opayo_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

		if ($this->config->get('payment_opayo_total') > 0 && $this->config->get('payment_opayo_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('payment_opayo_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = [];

		if ($status) {
			$method_data = [
				'code'       => 'opayo',
				'title'      => $this->language->get('text_title'),
				'sort_order' => $this->config->get('payment_opayo_sort_order')
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
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "opayo_card` WHERE `customer_id` = '" . (int)$customer_id . "' ORDER BY `card_id`");

		$card_data = [];

		foreach ($query->rows as $row) {
			$card_data[] = [
				'card_id'     => $row['card_id'],
				'customer_id' => $row['customer_id'],
				'token'       => $row['token'],
				'digits'      => '**** ' . $row['digits'],
				'expiry'      => $row['expiry'],
				'type'        => $row['type'],
			];
		}

		return $card_data;
	}

	/**
	 * addCard
	 *
	 * @param array $card_data
	 *
	 * @return int
	 */
	public function addCard(array $card_data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "opayo_card` SET `customer_id` = '" . $this->db->escape($card_data['customer_id']) . "', `digits` = '" . $this->db->escape($card_data['Last4Digits']) . "', `expiry` = '" . $this->db->escape($card_data['ExpiryDate']) . "', `type` = '" . $this->db->escape($card_data['CardType']) . "', `token` = '" . $this->db->escape($card_data['Token']) . "'");

		return $this->db->getLastId();
	}

	/**
	 * updateCard
	 *
	 * @param int    $card_id
	 * @param string $token
	 */
	public function updateCard(int $card_id, string $token): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "opayo_card` SET `token` = '" . $this->db->escape($token) . "' WHERE `card_id` = '" . (int)$card_id . "'");
	}

	/**
	 * getCard
	 *
	 * @param int    $card_id
	 * @param string $token
	 *
	 * @return array|bool
	 */
	public function getCard(int $card_id, string $token): array|bool {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "opayo_card` WHERE (`card_id` = '" . $this->db->escape($card_id) . "' OR `token` = '" . $this->db->escape($token) . "') AND `customer_id` = '" . (int)$this->customer->getId() . "'");

		if ($query->num_rows) {
			return $query->row;
		} else {
			return false;
		}
	}

	/**
	 * deleteCard
	 *
	 * @param int $card_id
	 *
	 * @return void
	 */
	public function deleteCard(int $card_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "opayo_card` WHERE `card_id` = '" . (int)$card_id . "'");
	}

	/**
	 * addOrder
	 *
	 * @param int    $order_id
	 * @param array  $response_data
	 * @param array  $payment_data
	 * @param string $card_id
	 */
	public function addOrder(int $order_id, array $response_data, array $payment_data, string $card_id): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "opayo_order` SET `order_id` = '" . (int)$order_id . "', `VPSTxId` = '" . $this->db->escape($response_data['VPSTxId']) . "', `VendorTxCode` = '" . $this->db->escape($payment_data['VendorTxCode']) . "', `SecurityKey` = '" . $this->db->escape($response_data['SecurityKey']) . "', `TxAuthNo` = '" . $this->db->escape($response_data['TxAuthNo']) . "', `date_added` = now(), `date_modified` = now(), `currency_code` = '" . $this->db->escape($payment_data['Currency']) . "', `total` = '" . $this->currency->format($payment_data['Amount'], $payment_data['Currency'], false, false) . "', `card_id` = '" . $this->db->escape($card_id) . "'");

		return $this->db->getLastId();
	}

	/**
	 * getOrder
	 *
	 * @param int $order_id
	 *
	 * @return array|bool
	 */
	public function getOrder(int $order_id): array|bool {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "opayo_order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

		if ($query->num_rows) {
			$order = $query->row;

			$order['transactions'] = $this->getOrderTransactions($order['opayo_order_id']);

			return $order;
		} else {
			return false;
		}
	}

	/**
	 * updateOrder
	 *
	 * @param array $order_info
	 * @param array $data
	 *
	 * @return int
	 */
	public function updateOrder(array $order_info, array $data): int {
		$this->db->query("UPDATE `" . DB_PREFIX . "opayo_order` SET `SecurityKey` = '" . $this->db->escape($data['SecurityKey']) . "',  `VPSTxId` = '" . $this->db->escape($data['VPSTxId']) . "', `TxAuthNo` = '" . $this->db->escape($data['TxAuthNo']) . "' WHERE `order_id` = '" . (int)$order_info['order_id'] . "'");

		return $this->db->getLastId();
	}

	/**
	 * deleteOrder
	 *
	 * @param int $vendor_tx_code
	 *
	 * @return void
	 */
	public function deleteOrder(int $vendor_tx_code): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "opayo_order` WHERE `order_id` = '" . $vendor_tx_code . "'");
	}

	/**
	 * addOrderTransaction
	 *
	 * @param int    $opayo_order_id
	 * @param string $type
	 * @param array  $order_info
	 *
	 * @return void
	 */
	public function addOrderTransaction(int $opayo_order_id, string $type, array $order_info): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "opayo_order_transaction` SET `opayo_order_id` = '" . (int)$opayo_order_id . "', `date_added` = now(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . $this->currency->format($order_info['total'], $order_info['currency_code'], false, false) . "'");
	}

	/**
	 * getOrderTransactions
	 *
	 * @param int $opayo_order_id
	 *
	 * @return array|bool
	 */
	private function getOrderTransactions(int $opayo_order_id): array|bool {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "opayo_order_transaction` WHERE `opayo_order_id` = '" . (int)$opayo_order_id . "'");

		if ($query->num_rows) {
			return $query->rows;
		} else {
			return false;
		}
	}

	/**
	 * recurringPayment
	 *
	 * @param array  $item
	 * @param string $vendor_tx_code
	 *
	 * @return void
	 */
	public function recurringPayment(array $item, string $vendor_tx_code): void {
		$this->load->model('checkout/recurring');
		$this->load->model('extension/payment/opayo');

		if (VERSION >= '3.0.1.0') {
			if ($item['recurring']['trial'] == 1) {
				$price = $item['recurring']['trial_price'];
				$trial_amt = $this->currency->format($this->tax->calculate($item['recurring']['trial_price'], $item['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'], false, false) * $item['quantity'] . ' ' . $this->session->data['currency'];
				$trial_text = sprintf($this->language->get('text_trial'), $trial_amt, $item['recurring']['trial_cycle'], $item['recurring']['trial_frequency'], $item['recurring']['trial_duration']);
			} else {
				$price = $item['recurring']['price'];
				$trial_text = '';
			}

			$recurring_amt = $this->currency->format($this->tax->calculate($item['recurring']['price'], $item['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'], false, false) * $item['quantity'] . ' ' . $this->session->data['currency'];
			$recurring_description = $trial_text . sprintf($this->language->get('text_recurring'), $recurring_amt, $item['recurring']['cycle'], $item['recurring']['frequency']);

			if ($item['recurring']['duration'] > 0) {
				$recurring_description .= sprintf($this->language->get('text_length'), $item['recurring']['duration']);
			}

			$order_recurring_id = $this->model_checkout_recurring->addRecurring($this->session->data['order_id'], $recurring_description, $item);

			$this->model_checkout_recurring->editReference($order_recurring_id, $vendor_tx_code);

			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

			$opayo_order_info = $this->getOrder($this->session->data['order_id']);

			$next_payment = new DateTime('now');
			$trial_end = new DateTime('now');
			$subscription_end = new DateTime('now');

			if (($item['recurring']['trial'] == 1) && ($item['recurring']['trial_duration'] != 0)) {
				$next_payment = $this->calculateSchedule($item['recurring']['trial_frequency'], $next_payment, $item['recurring']['trial_cycle']);
				$trial_end = $this->calculateSchedule($item['recurring']['trial_frequency'], $trial_end, $item['recurring']['trial_cycle'] * $item['recurring']['trial_duration']);
			} elseif ($item['recurring']['trial'] == 1) {
				$next_payment = $this->calculateSchedule($item['recurring']['trial_frequency'], $next_payment, $item['recurring']['trial_cycle']);
				$trial_end = new DateTime('0000-00-00');
			}

			if (date_format($trial_end, 'Y-m-d H:i:s') > date_format($subscription_end, 'Y-m-d H:i:s') && $item['recurring']['duration'] != 0) {
				$subscription_end = new DateTime(date_format($trial_end, 'Y-m-d H:i:s'));
				$subscription_end = $this->calculateSchedule($item['recurring']['frequency'], $subscription_end, $item['recurring']['cycle'] * $item['recurring']['duration']);
			} elseif (date_format($trial_end, 'Y-m-d H:i:s') == date_format($subscription_end, 'Y-m-d H:i:s') && $item['recurring']['duration'] != 0) {
				$next_payment = $this->calculateSchedule($item['recurring']['frequency'], $next_payment, $item['recurring']['cycle']);
				$subscription_end = $this->calculateSchedule($item['recurring']['frequency'], $subscription_end, $item['recurring']['cycle'] * $item['recurring']['duration']);
			} elseif (date_format($trial_end, 'Y-m-d H:i:s') > date_format($subscription_end, 'Y-m-d H:i:s') && $item['recurring']['duration'] == 0) {
				$subscription_end = new DateTime('0000-00-00');
			} elseif (date_format($trial_end, 'Y-m-d H:i:s') == date_format($subscription_end, 'Y-m-d H:i:s') && $item['recurring']['duration'] == 0) {
				$next_payment = $this->calculateSchedule($item['recurring']['frequency'], $next_payment, $item['recurring']['cycle']);
				$subscription_end = new DateTime('0000-00-00');
			}

			if (date_format($trial_end, 'Y-m-d H:i:s') >= date_format($subscription_end, 'Y-m-d H:i:s')) {
				$recurring_expiry = date_format($trial_end, 'Y-m-d');
			} else {
				$recurring_expiry = date_format($subscription_end, 'Y-m-d');
			}

			$recurring_frequency = date_diff(new DateTime('now'), new DateTime(date_format($next_payment, 'Y-m-d H:i:s')))->days;

			$response_data = $this->setPaymentData($order_info, $opayo_order_info, $price, $order_recurring_id, $item['recurring']['name'], $recurring_expiry, $recurring_frequency);

			$this->addRecurringOrder($this->session->data['order_id'], $response_data, $order_recurring_id, date_format($trial_end, 'Y-m-d H:i:s'), date_format($subscription_end, 'Y-m-d H:i:s'));

			if ($response_data['Status'] == 'OK') {
				$this->updateRecurringOrder($order_recurring_id, date_format($next_payment, 'Y-m-d H:i:s'));

				$this->addRecurringTransaction($order_recurring_id, $response_data, 1);
			} else {
				$this->addRecurringTransaction($order_recurring_id, $response_data, 4);
			}
		} else {
			if ($item['recurring_trial'] == 1) {
				$price = $item['recurring_trial_price'];
				$trial_amt = $this->currency->format($this->tax->calculate($item['recurring_trial_price'], $item['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'], false, false) * $item['quantity'] . ' ' . $this->session->data['currency'];
				$trial_text = sprintf($this->language->get('text_trial'), $trial_amt, $item['recurring_trial_cycle'], $item['recurring_trial_frequency'], $item['recurring_trial_duration']);
			} else {
				$price = $item['recurring_price'];
				$trial_text = '';
			}

			$recurring_amt = $this->currency->format($this->tax->calculate($item['recurring_price'], $item['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'], false, false) * $item['quantity'] . ' ' . $this->session->data['currency'];
			$recurring_description = $trial_text . sprintf($this->language->get('text_recurring'), $recurring_amt, $item['recurring_cycle'], $item['recurring_frequency']);

			if ($item['recurring_duration'] > 0) {
				$recurring_description .= sprintf($this->language->get('text_length'), $item['recurring_duration']);
			}

			$order_recurring_id = $this->model_checkout_recurring->create($item, $this->session->data['order_id'], $recurring_description);

			$this->model_checkout_recurring->addReference($order_recurring_id, $vendor_tx_code);

			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

			$opayo_order_info = $this->getOrder($this->session->data['order_id']);

			$next_payment = new DateTime('now');
			$trial_end = new DateTime('now');
			$subscription_end = new DateTime('now');

			if ($item['recurring_trial'] == 1 && $item['recurring_trial_duration'] != 0) {
				$next_payment = $this->calculateSchedule($item['recurring_trial_frequency'], $next_payment, $item['recurring_trial_cycle']);
				$trial_end = $this->calculateSchedule($item['recurring_trial_frequency'], $trial_end, $item['recurring_trial_cycle'] * $item['recurring_trial_duration']);
			} elseif ($item['recurring_trial'] == 1) {
				$next_payment = $this->calculateSchedule($item['recurring_trial_frequency'], $next_payment, $item['recurring_trial_cycle']);
				$trial_end = new DateTime('0000-00-00');
			}

			if (date_format($trial_end, 'Y-m-d H:i:s') > date_format($subscription_end, 'Y-m-d H:i:s') && $item['recurring_duration'] != 0) {
				$subscription_end = new DateTime(date_format($trial_end, 'Y-m-d H:i:s'));
				$subscription_end = $this->calculateSchedule($item['recurring_frequency'], $subscription_end, $item['recurring_cycle'] * $item['recurring_duration']);
			} elseif (date_format($trial_end, 'Y-m-d H:i:s') == date_format($subscription_end, 'Y-m-d H:i:s') && $item['recurring_duration'] != 0) {
				$next_payment = $this->calculateSchedule($item['recurring_frequency'], $next_payment, $item['recurring_cycle']);
				$subscription_end = $this->calculateSchedule($item['recurring_frequency'], $subscription_end, $item['recurring_cycle'] * $item['recurring_duration']);
			} elseif (date_format($trial_end, 'Y-m-d H:i:s') > date_format($subscription_end, 'Y-m-d H:i:s') && $item['recurring_duration'] == 0) {
				$subscription_end = new DateTime('0000-00-00');
			} elseif (date_format($trial_end, 'Y-m-d H:i:s') == date_format($subscription_end, 'Y-m-d H:i:s') && $item['recurring_duration'] == 0) {
				$next_payment = $this->calculateSchedule($item['recurring_frequency'], $next_payment, $item['recurring_cycle']);
				$subscription_end = new DateTime('0000-00-00');
			}

			if (date_format($trial_end, 'Y-m-d H:i:s') >= date_format($subscription_end, 'Y-m-d H:i:s')) {
				$recurring_expiry = date_format($trial_end, 'Y-m-d');
			} else {
				$recurring_expiry = date_format($subscription_end, 'Y-m-d');
			}

			$recurring_frequency = date_diff(new DateTime('now'), new DateTime(date_format($next_payment, 'Y-m-d H:i:s')))->days;

			$response_data = $this->setPaymentData($order_info, $opayo_order_info, $price, $order_recurring_id, $item['recurring_name'], $recurring_expiry, $recurring_frequency);

			$this->addRecurringOrder($this->session->data['order_id'], $response_data, $order_recurring_id, date_format($trial_end, 'Y-m-d H:i:s'), date_format($subscription_end, 'Y-m-d H:i:s'));

			if ($response_data['Status'] == 'OK') {
				$this->updateRecurringOrder($order_recurring_id, date_format($next_payment, 'Y-m-d H:i:s'));

				$this->addRecurringTransaction($order_recurring_id, $response_data, 1);
			} else {
				$this->addRecurringTransaction($order_recurring_id, $response_data, 4);
			}
		}
	}

	/**
	 * cronPayment
	 *
	 * @return array
	 */
	public function cronPayment(): array {
		$this->load->model('checkout/order');

		$recurrings = $this->getProfiles();
		$cron_data = [];
		$i = 0;

		foreach ($recurrings as $recurring) {
			$recurring_order = $this->getRecurringOrder($recurring['order_recurring_id']);

			$today = new DateTime('now');
			$unlimited = new DateTime('0000-00-00');
			$next_payment = new DateTime($recurring_order['next_payment']);
			$trial_end = new DateTime($recurring_order['trial_end']);
			$subscription_end = new DateTime($recurring_order['subscription_end']);

			$order_info = $this->model_checkout_order->getOrder($recurring['order_id']);

			if ((date_format($today, 'Y-m-d H:i:s') > date_format($next_payment, 'Y-m-d H:i:s')) && (date_format($trial_end, 'Y-m-d H:i:s') > date_format($today, 'Y-m-d H:i:s') || date_format($trial_end, 'Y-m-d H:i:s') == date_format($unlimited, 'Y-m-d H:i:s'))) {
				$price = $this->currency->format($recurring['trial_price'], $order_info['currency_code'], false, false);
				$frequency = $recurring['trial_frequency'];
				$cycle = $recurring['trial_cycle'];
				$next_payment = $this->calculateSchedule($frequency, $next_payment, $cycle);
			} elseif ((date_format($today, 'Y-m-d H:i:s') > date_format($next_payment, 'Y-m-d H:i:s')) && (date_format($subscription_end, 'Y-m-d H:i:s') > date_format($today, 'Y-m-d H:i:s') || date_format($subscription_end, 'Y-m-d H:i:s') == date_format($unlimited, 'Y-m-d H:i:s'))) {
				$price = $this->currency->format($recurring['recurring_price'], $order_info['currency_code'], false, false);
				$frequency = $recurring['recurring_frequency'];
				$cycle = $recurring['recurring_cycle'];
				$next_payment = $this->calculateSchedule($frequency, $next_payment, $cycle);
			} else {
				continue;
			}

			$opayo_order_info = $this->getOrder($recurring['order_id']);

			if (date_format($trial_end, 'Y-m-d H:i:s') >= date_format($subscription_end, 'Y-m-d H:i:s')) {
				$recurring_expiry = date_format($trial_end, 'Y-m-d');
			} else {
				$recurring_expiry = date_format($subscription_end, 'Y-m-d');
			}

			$recurring_frequency = date_diff(new DateTime('now'), new DateTime(date_format($next_payment, 'Y-m-d H:i:s')))->days;

			$response_data = $this->setPaymentData($order_info, $opayo_order_info, $price, $recurring['order_recurring_id'], $recurring['recurring_name'], $recurring_expiry, $recurring_frequency, $i);

			$cron_data[] = $response_data;

			if ($response_data['RepeatResponseData_' . $i++]['Status'] == 'OK') {
				$this->addRecurringTransaction($recurring['order_recurring_id'], $response_data, 1);

				$this->updateRecurringOrder($recurring['order_recurring_id'], date_format($next_payment, 'Y-m-d H:i:s'));
			} else {
				$this->addRecurringTransaction($recurring['order_recurring_id'], $response_data, 4);
			}
		}

		$log = new Log('opayo_recurring_orders.log');

		$log->write(print_r($cron_data, 1));

		return $cron_data;
	}

	/**
	 * setPaymentData
	 *
	 * @param array  $order_info
	 * @param array  $opayo_order_info
	 * @param float  $price
	 * @param int    $order_recurring_id
	 * @param string $recurring_name
	 * @param string $recurring_expiry
	 * @param int    $recurring_frequency
	 * @param ?int   $i
	 */
	private function setPaymentData(array $order_info, array $opayo_order_info, float $price, int $order_recurring_id, string $recurring_name, string $recurring_expiry, int $recurring_frequency, $i = null): array {
		// Setting
		$_config = new Config();
		$_config->load('opayo');

		$config_setting = $_config->get('payze_opayo');

		$setting = array_replace_recursive((array)$config_setting, (array)$this->config->get('payment_opayo_setting'));

		$payment_data = [];

		if ($setting['general']['environment'] == 'live') {
			$url = 'https://live.sagepay.com/gateway/service/repeat.vsp';
			$payment_data['VPSProtocol'] = '4.00';
		} elseif ($setting['general']['environment'] == 'test') {
			$url = 'https://test.sagepay.com/gateway/service/repeat.vsp';
			$payment_data['VPSProtocol'] = '4.00';
		}

		$payment_data['TxType'] = 'REPEAT';
		$payment_data['Vendor'] = $this->config->get('payment_opayo_vendor');
		$payment_data['VendorTxCode'] = $order_recurring_id . 'RSD' . strftime("%Y%m%d%H%M%S") . mt_rand(1, 999);
		$payment_data['Amount'] = $this->currency->format($price, $this->session->data['currency'], false, false);
		$payment_data['Currency'] = $this->session->data['currency'];
		$payment_data['Description'] = substr($recurring_name, 0, 100);
		$payment_data['RelatedVPSTxId'] = trim($opayo_order_info['VPSTxId'], '{}');
		$payment_data['RelatedVendorTxCode'] = $opayo_order_info['VendorTxCode'];
		$payment_data['RelatedSecurityKey'] = $opayo_order_info['SecurityKey'];
		$payment_data['RelatedTxAuthNo'] = $opayo_order_info['TxAuthNo'];
		$payment_data['COFUsage'] = 'SUBSEQUENT';
		$payment_data['InitiatedType'] = 'MIT';
		$payment_data['MITType'] = 'RECURRING';
		$payment_data['RecurringExpiry'] = $recurring_expiry;
		$payment_data['RecurringFrequency'] = $recurring_frequency;

		if (!empty($order_info['shipping_lastname'])) {
			$payment_data['DeliverySurname'] = substr($order_info['shipping_lastname'], 0, 20);
			$payment_data['DeliveryFirstnames'] = substr($order_info['shipping_firstname'], 0, 20);
			$payment_data['DeliveryAddress1'] = substr($order_info['shipping_address_1'], 0, 100);

			if ($order_info['shipping_address_2']) {
				$payment_data['DeliveryAddress2'] = $order_info['shipping_address_2'];
			}

			$payment_data['DeliveryCity'] = substr($order_info['shipping_city'], 0, 40);
			$payment_data['DeliveryPostCode'] = substr($order_info['shipping_postcode'], 0, 10);
			$payment_data['DeliveryCountry'] = $order_info['shipping_iso_code_2'];

			if ($order_info['shipping_iso_code_2'] == 'US') {
				$payment_data['DeliveryState'] = $order_info['shipping_zone_code'];
			}

			$payment_data['CustomerName'] = substr($order_info['firstname'] . ' ' . $order_info['lastname'], 0, 100);
			$payment_data['DeliveryPhone'] = substr($order_info['telephone'], 0, 20);
		} else {
			$payment_data['DeliveryFirstnames'] = $order_info['payment_firstname'];
			$payment_data['DeliverySurname'] = $order_info['payment_lastname'];
			$payment_data['DeliveryAddress1'] = $order_info['payment_address_1'];

			if ($order_info['payment_address_2']) {
				$payment_data['DeliveryAddress2'] = $order_info['payment_address_2'];
			}

			$payment_data['DeliveryCity'] = $order_info['payment_city'];
			$payment_data['DeliveryPostCode'] = $order_info['payment_postcode'];
			$payment_data['DeliveryCountry'] = $order_info['payment_iso_code_2'];

			if ($order_info['payment_iso_code_2'] == 'US') {
				$payment_data['DeliveryState'] = $order_info['payment_zone_code'];
			}

			$payment_data['DeliveryPhone'] = $order_info['telephone'];
		}

		$response_data = $this->sendCurl($url, $payment_data, $i);

		$response_data['VendorTxCode'] = $payment_data['VendorTxCode'];
		$response_data['Amount'] = $payment_data['Amount'];
		$response_data['Currency'] = $payment_data['Currency'];

		return $response_data;
	}

	/**
	 * calculateSchedule
	 *
	 * @param string $frequency
	 * @param string $next_payment
	 * @param string $cycle
	 *
	 * @return
	 */
	private function calculateSchedule(string $frequency, string $next_payment, string $cycle) {
		if ($frequency == 'semi_month') {
			$day = date_format($next_payment, 'd');
			$value = 15 - $day;
			$is_even = false;

			if ($cycle % 2 == 0) {
				$is_even = true;
			}

			$odd = ($cycle + 1) / 2;
			$plus_even = ($cycle / 2) + 1;
			$minus_even = $cycle / 2;

			if ($day == 1) {
				$odd--;
				$plus_even--;
				$day = 16;
			}

			if ($day <= 15 && $is_even) {
				$next_payment->modify('+' . $value . ' day');
				$next_payment->modify('+' . $minus_even . ' month');
			} elseif ($day <= 15) {
				$next_payment->modify('first day of this month');
				$next_payment->modify('+' . $odd . ' month');
			} elseif ($day > 15 && $is_even) {
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

	/**
	 * addRecurringOrder
	 *
	 * @param int    $order_id
	 * @param array  $response_data
	 * @param int    $order_recurring_id
	 * @param string $trial_end
	 * @param string $subscription_end
	 */
	private function addRecurringOrder(int $order_id, array $response_data, int $order_recurring_id, string $trial_end, string $subscription_end): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "opayo_order_recurring` SET `order_id` = '" . (int)$order_id . "', `order_recurring_id` = '" . (int)$order_recurring_id . "', `VPSTxId` = '" . $this->db->escape($response_data['VPSTxId']) . "', `VendorTxCode` = '" . $this->db->escape($response_data['VendorTxCode']) . "', `SecurityKey` = '" . $this->db->escape($response_data['SecurityKey']) . "', `TxAuthNo` = '" . $this->db->escape($response_data['TxAuthNo']) . "', `date_added` = now(), `date_modified` = now(), `next_payment` = now(), `trial_end` = '" . $trial_end . "', `subscription_end` = '" . $subscription_end . "', `currency_code` = '" . $this->db->escape($response_data['Currency']) . "', `total` = '" . $this->currency->format($response_data['Amount'], $response_data['Currency'], false, false) . "'");
	}

	/**
	 * updateRecurringOrder
	 *
	 * @param int    $order_recurring_id
	 * @param string $next_payment
	 *
	 * @return void
	 */
	private function updateRecurringOrder(int $order_recurring_id, string $next_payment): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "opayo_order_recurring` SET `next_payment` = '" . $next_payment . "', `date_modified` = now() WHERE `order_recurring_id` = '" . (int)$order_recurring_id . "'");
	}

	/**
	 * getRecurringOrder
	 *
	 * @param int $order_recurring_id
	 *
	 * @return array
	 */
	private function getRecurringOrder(int $order_recurring_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "opayo_order_recurring` WHERE `order_recurring_id` = '" . (int)$order_recurring_id . "'");

		return $query->row;
	}

	/**
	 * addRecurringTransaction
	 *
	 * @param int   $order_recurring_id
	 * @param array $response_data
	 * @param int   $type
	 *
	 * @return void
	 */
	private function addRecurringTransaction(int $order_recurring_id, array $response_data, int $type): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "order_recurring_transaction` SET `order_recurring_id` = '" . (int)$order_recurring_id . "', `date_added` = NOW(), `amount` = '" . (float)$response_data['Amount'] . "', `type` = '" . (int)$type . "', `reference` = '" . $this->db->escape($response_data['VendorTxCode']) . "'");
	}

	/**
	 * getProfiles
	 *
	 * @return array
	 */
	private function getProfiles(): array {
		$query = $this->db->query("SELECT `or`.order_recurring_id FROM `" . DB_PREFIX . "order_recurring` `or` JOIN `" . DB_PREFIX . "order` `o` USING(`order_id`) WHERE o.payment_code = 'opayo'");

		$order_recurring = [];

		foreach ($query->rows as $recurring) {
			$order_recurring[] = $this->getProfile($recurring['order_recurring_id']);
		}

		return $order_recurring;
	}

	/**
	 * getProfile
	 *
	 * @param int $order_recurring_id
	 *
	 * @return array
	 */
	private function getProfile(int $order_recurring_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_recurring` WHERE `order_recurring_id` = '" . (int)$order_recurring_id . "'");

		return $query->row;
	}

	/**
	 * updateCronRunTime
	 *
	 * @return void
	 */
	public function updateCronRunTime(): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'opayo' AND `key` = 'payment_opayo_last_cron_run'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES (0, 'opayo', 'payment_opayo_last_cron_run', NOW(), 0)");
	}

	/**
	 * sendCurl
	 *
	 * @param string $url
	 * @param array  $payment_data
	 * @param ?null  $i
	 * @param string $curl
	 */
	public function sendCurl(string $url, array $payment_data, $i = null) {
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($payment_data));

		$response = curl_exec($curl);

		curl_close($curl);

		$response_info = explode(chr(10), $response);

		foreach ($response_info as $string) {
			if (strpos($string, '=') && isset($i)) {
				$parts = explode('=', $string, 2);
				$data['RepeatResponseData_' . $i][trim($parts[0])] = trim($parts[1]);
			} elseif (strpos($string, '=')) {
				$parts = explode('=', $string, 2);
				$data[trim($parts[0])] = trim($parts[1]);
			}
		}

		return $data;
	}

	/**
	 * Log
	 *
	 * @param string       $title
	 * @param array|string $data
	 *
	 * @return void
	 */
	public function log(string $title, array|string $data): void {
		$_config = new Config();
		$_config->load('opayo');

		$config_setting = $_config->get('opayo_setting');

		$setting = array_replace_recursive((array)$config_setting, (array)$this->config->get('payment_opayo_setting'));

		if ($setting['general']['debug']) {
			$log = new Log('opayo.log');

			$log->write($title . ': ' . print_r($data, 1));
		}
	}

	/**
	 * recurringPayments
	 *
	 * @return bool
	 */
	public function recurringPayments(): bool {
		/*
		 * Used by the checkout to state the module
		 * supports recurring recurrings.
		 */
		return true;
	}
}
