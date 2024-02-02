<?php
/**
 * Class Cardconnect
 *
 * @package Admin\Model\Extension\Payment
 */
class ModelExtensionPaymentCardConnect extends Model {
	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cardconnect_card` (
			  `cardconnect_card_id` int(11) NOT NULL AUTO_INCREMENT,
			  `cardconnect_order_id` int(11) NOT NULL DEFAULT '0',
			  `customer_id` int(11) NOT NULL DEFAULT '0',
			  `profileid` varchar(16) NOT NULL DEFAULT '',
			  `token` varchar(19) NOT NULL DEFAULT '',
			  `type` varchar(50) NOT NULL DEFAULT '',
			  `account` varchar(4) NOT NULL DEFAULT '',
			  `expiry` varchar(4) NOT NULL DEFAULT '',
			  `date_added` datetime NOT NULL,
			  PRIMARY KEY (`cardconnect_card_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cardconnect_order` (
			  `cardconnect_order_id` int(11) NOT NULL AUTO_INCREMENT,
			  `order_id` int(11) NOT NULL DEFAULT '0',
			  `customer_id` int(11) NOT NULL DEFAULT '0',
			  `payment_method` varchar(255) NOT NULL DEFAULT '',
			  `retref` varchar(12) NOT NULL DEFAULT '',
			  `authcode` varchar(6) NOT NULL DEFAULT '',
			  `currency_code` varchar(3) NOT NULL DEFAULT '',
			  `total` decimal(15,4) NOT NULL DEFAULT '0.0000',
			  `date_added` datetime NOT NULL,
			  PRIMARY KEY (`cardconnect_order_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cardconnect_order_transaction` (
			  `cardconnect_order_transaction_id` int(11) NOT NULL AUTO_INCREMENT,
			  `cardconnect_order_id` int(11) NOT NULL DEFAULT '0',
			  `type` varchar(50) NOT NULL DEFAULT '',
			  `retref` varchar(12) NOT NULL DEFAULT '',
			  `amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
			  `status` varchar(255) NOT NULL DEFAULT '',
			  `date_modified` datetime NOT NULL,
			  `date_added` datetime NOT NULL,
			  PRIMARY KEY (`cardconnect_order_transaction_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "cardconnect_card`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "cardconnect_order`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "cardconnect_order_transaction`");

		$this->log('Module uninstalled');
	}

	/**
	 * getOrder
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function getOrder(int $order_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cardconnect_order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

		if ($query->num_rows) {
			$order = $query->row;

			$order['transactions'] = $this->getTransactions($order['cardconnect_order_id']);

			return $order;
		} else {
			return [];
		}
	}

	private function getTransactions(int $cardconnect_order_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cardconnect_order_transaction` WHERE `cardconnect_order_id` = '" . (int)$cardconnect_order_id . "'");

		if ($query->num_rows) {
			return $query->rows;
		} else {
			return [];
		}
	}

	/**
	 * getTotalCaptured
	 *
	 * @param int $cardconnect_order_id
	 *
	 * @return float
	 */
	public function getTotalCaptured(int $cardconnect_order_id): float {
		$query = $this->db->query("SELECT SUM(`amount`) AS `total` FROM `" . DB_PREFIX . "cardconnect_order_transaction` WHERE `cardconnect_order_id` = '" . (int)$cardconnect_order_id . "' AND (`type` = 'payment' OR `type` = 'refund')");

		return (float)$query->row['total'];
	}

	/**
	 * Inquire
	 *
	 * @param array  $order_info
	 * @param string $retref
	 *
	 * @return array
	 */
	public function inquire(array $order_info, string $retref): array {
		$this->log('Posting inquire to CardConnect');
		$this->log('Order ID: ' . $order_info['order_id']);

		$url = 'https://' . $this->config->get('payment_cardconnect_site') . '.cardconnect.com:' . (($this->config->get('payment_cardconnect_environment') == 'live') ? 8443 : 6443) . '/cardconnect/rest/inquire/' . $retref . '/' . $this->config->get('payment_cardconnect_merchant_id');

		$header = [];

		$header[] = 'Content-type: application/json';
		$header[] = 'Authorization: Basic ' . base64_encode($this->config->get('payment_cardconnect_api_username') . ':' . $this->config->get('payment_cardconnect_api_password'));

		$this->model_extension_payment_cardconnect->log('Header: ' . print_r($header, true));
		$this->model_extension_payment_cardconnect->log('URL: ' . $url);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response_data = curl_exec($ch);

		if (curl_errno($ch)) {
			$this->model_extension_payment_cardconnect->log('cURL error: ' . curl_errno($ch));
		}

		curl_close($ch);

		$response_data = json_decode($response_data, true);

		$this->log('Response: ' . print_r($response_data, true));

		return $response_data;
	}

	/**
	 * Capture
	 *
	 * @param array $order_info
	 * @param float $amount
	 *
	 * @return array
	 */
	public function capture(array $order_info, float $amount): array {
		// Orders
		$this->load->model('sale/order');

		$this->log('Posting capture to CardConnect');
		$this->log('Order ID: ' . $order_info['order_id']);

		$order = $this->model_sale_order->getOrder($order_info['order_id']);
		$totals = $this->model_sale_order->getTotals($order_info['order_id']);
		$products = $this->model_sale_order->getProducts($order_info['order_id']);
		$shipping_cost = '';

		foreach ($totals as $total) {
			if ($total['code'] == 'shipping') {
				$shipping_cost = $total['value'];
			}
		}

		$items = [];

		$i = 1;

		foreach ($products as $product) {
			$items[] = [
				'lineno'      => $i,
				'material'    => '',
				'description' => $product['name'],
				'upc'         => '',
				'quantity'    => $product['quantity'],
				'uom'         => '',
				'unitcost'    => $product['price'],
				'netamnt'     => $product['total'],
				'taxamnt'     => $product['tax'],
				'discamnt'    => ''
			];

			$i++;
		}

		$data = [
			'merchid'       => $this->config->get('payment_cardconnect_merchant_id'),
			'retref'        => $order_info['retref'],
			'authcode'      => $order_info['authcode'],
			'ponumber'      => $order_info['order_id'],
			'amount'        => round((float)$amount, 2, PHP_ROUND_HALF_DOWN),
			'currency'      => $order_info['currency_code'],
			'frtamnt'       => $shipping_cost,
			'dutyamnt'      => '',
			'orderdate'     => '',
			'shiptozip'     => $order['shipping_postcode'],
			'shipfromzip'   => '',
			'shiptocountry' => $order['shipping_iso_code_2'],
			'Items'         => $items
		];

		$data_json = json_encode($data);

		$url = 'https://' . $this->config->get('payment_cardconnect_site') . '.cardconnect.com:' . (($this->config->get('payment_cardconnect_environment') == 'live') ? 8443 : 6443) . '/cardconnect/rest/capture';

		$header = [];

		$header[] = 'Content-type: application/json';
		$header[] = 'Content-length: ' . strlen($data_json);
		$header[] = 'Authorization: Basic ' . base64_encode($this->config->get('payment_cardconnect_api_username') . ':' . $this->config->get('payment_cardconnect_api_password'));

		$this->model_extension_payment_cardconnect->log('Header: ' . print_r($header, true));
		$this->model_extension_payment_cardconnect->log('Post Data: ' . print_r($data, true));
		$this->model_extension_payment_cardconnect->log('URL: ' . $url);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response_data = curl_exec($ch);

		if (curl_errno($ch)) {
			$this->model_extension_payment_cardconnect->log('cURL error: ' . curl_errno($ch));
		}

		curl_close($ch);

		$response_data = json_decode($response_data, true);

		$this->log('Response: ' . print_r($response_data, true));

		return $response_data;
	}

	/**
	 * Refund
	 *
	 * @param array $order_info
	 * @param float $amount
	 *
	 * @return array
	 */
	public function refund(array $order_info, float $amount): array {
		$this->log('Posting refund to CardConnect');
		$this->log('Order ID: ' . $order_info['order_id']);

		$post_data = [
			'merchid'  => $this->config->get('payment_cardconnect_merchant_id'),
			'amount'   => round((float)$amount, 2, PHP_ROUND_HALF_DOWN),
			'currency' => $order_info['currency_code'],
			'retref'   => $order_info['retref']
		];

		$data_json = json_encode($post_data);

		$url = 'https://' . $this->config->get('payment_cardconnect_site') . '.cardconnect.com:' . (($this->config->get('payment_cardconnect_environment') == 'live') ? 8443 : 6443) . '/cardconnect/rest/refund';

		$header = [];

		$header[] = 'Content-type: application/json';
		$header[] = 'Content-length: ' . strlen($data_json);
		$header[] = 'Authorization: Basic ' . base64_encode($this->config->get('payment_cardconnect_api_username') . ':' . $this->config->get('payment_cardconnect_api_password'));

		$this->model_extension_payment_cardconnect->log('Header: ' . print_r($header, true));
		$this->model_extension_payment_cardconnect->log('Post Data: ' . print_r($post_data, true));
		$this->model_extension_payment_cardconnect->log('URL: ' . $url);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response_data = curl_exec($ch);

		if (curl_errno($ch)) {
			$this->model_extension_payment_cardconnect->log('cURL error: ' . curl_errno($ch));
		}

		curl_close($ch);

		$response_data = json_decode($response_data, true);

		$this->log('Response: ' . print_r($response_data, true));

		return $response_data;
	}

	/**
	 * Void
	 *
	 * @param array  $order_info
	 * @param string $retref
	 *
	 * @return array
	 */
	public function void(array $order_info, string $retref): array {
		$this->log('Posting void to CardConnect');
		$this->log('Order ID: ' . $order_info['order_id']);

		$post_data = [
			'merchid'  => $this->config->get('payment_cardconnect_merchant_id'),
			'amount'   => 0,
			'currency' => $order_info['currency_code'],
			'retref'   => $retref
		];

		$data_json = json_encode($post_data);

		$url = 'https://' . $this->config->get('payment_cardconnect_site') . '.cardconnect.com:' . (($this->config->get('payment_cardconnect_environment') == 'live') ? 8443 : 6443) . '/cardconnect/rest/void';

		$header = [];

		$header[] = 'Content-type: application/json';
		$header[] = 'Content-length: ' . strlen($data_json);
		$header[] = 'Authorization: Basic ' . base64_encode($this->config->get('payment_cardconnect_api_username') . ':' . $this->config->get('payment_cardconnect_api_password'));

		$this->model_extension_payment_cardconnect->log('Header: ' . print_r($header, true));
		$this->model_extension_payment_cardconnect->log('Post Data: ' . print_r($post_data, true));
		$this->model_extension_payment_cardconnect->log('URL: ' . $url);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response_data = curl_exec($ch);

		if (curl_errno($ch)) {
			$this->model_extension_payment_cardconnect->log('cURL error: ' . curl_errno($ch));
		}

		curl_close($ch);

		$response_data = json_decode($response_data, true);

		$this->log('Response: ' . print_r($response_data, true));

		return $response_data;
	}

	/**
	 * updateTransactionStatusByRetref
	 *
	 * @param string $retref
	 * @param string $status
	 *
	 * @return void
	 */
	public function updateTransactionStatusByRetref(string $retref, string $status): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "cardconnect_order_transaction` SET `status` = '" . $this->db->escape($status) . "', `date_modified` = NOW() WHERE `retref` = '" . $this->db->escape($retref) . "'");
	}

	/**
	 * addTransaction
	 *
	 * @param int    $cardconnect_order_id
	 * @param string $type
	 * @param string $retref
	 * @param float  $amount
	 * @param string $status
	 *
	 * @return void
	 */
	public function addTransaction(int $cardconnect_order_id, string $type, string $retref, float $amount, string $status): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "cardconnect_order_transaction` SET `cardconnect_order_id` = '" . (int)$cardconnect_order_id . "', `type` = '" . $this->db->escape($type) . "', `retref` = '" . $this->db->escape($retref) . "', `amount` = '" . (float)$amount . "', `status` = '" . $this->db->escape($status) . "', `date_modified` = NOW(), `date_added` = NOW()");
	}

	/**
	 * Log
	 *
	 * @param string $data
	 *
	 * @return void
	 */
	public function log(string $data): void {
		if ($this->config->get('payment_cardconnect_debug')) {
			$log = new \Log('cardconnect.log');
			$log->write($data);
		}
	}
}
