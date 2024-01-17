<?php
/**
 * Class Authorize.net Aim
 *
 * @package Catalog\Controller\Extension\Payment
 */
class ControllerExtensionPaymentAuthorizeNetAim extends Controller {
	/**
	 * @return string
	 *
	 * catalog/model/checkout/order/editOrder/after
	 * catalog/model/checkout/order/addHistory/after
	 */
	public function index(): string {
		$this->load->language('extension/payment/authorizenet_aim');

		$data['months'] = [];

		for ($i = 1; $i <= 12; $i++) {
			$data['months'][] = [
				'text'  => sprintf('%02d', $i),
				'value' => sprintf('%02d', $i)
			];
		}

		$today = getdate();

		$data['year_expire'] = [];

		for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
			$data['year_expire'][] = [
				'text'  => sprintf('%02d', $i % 100),
				'value' => sprintf('%04d', $i)
			];
		}

		return $this->load->view('extension/payment/authorizenet_aim', $data);
	}

	/**
	 * Send
	 *
	 * @return void
	 *
	 * catalog/model/checkout/order/editOrder/after
	 * catalog/model/checkout/order/addHistory/after
	 */
	public function send(): void {
		$url = '';

		if ($this->config->get('payment_authorizenet_aim_server') == 'live') {
			$url = 'https://secure.authorize.net/gateway/transact.dll';
		} elseif ($this->config->get('payment_authorizenet_aim_server') == 'test') {
			$url = 'https://test.authorize.net/gateway/transact.dll';
		}

		//$url = 'https://secure.networkmerchants.com/gateway/transact.dll';

		// Orders
		$this->load->model('checkout/order');

		if (!isset($this->session->data['order_id'])) {
			return;
		}

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$post_data = [];

		$post_data['x_login'] = $this->config->get('payment_authorizenet_aim_login');
		$post_data['x_tran_key'] = $this->config->get('payment_authorizenet_aim_key');
		$post_data['x_version'] = '3.1';
		$post_data['x_delim_data'] = 'true';
		$post_data['x_delim_char'] = '|';
		$post_data['x_encap_char'] = '"';
		$post_data['x_relay_response'] = 'false';
		$post_data['x_first_name'] = $order_info['payment_firstname'];
		$post_data['x_last_name'] = $order_info['payment_lastname'];
		$post_data['x_company'] = $order_info['payment_company'];
		$post_data['x_address'] = $order_info['payment_address_1'];
		$post_data['x_city'] = $order_info['payment_city'];
		$post_data['x_state'] = $order_info['payment_zone'];
		$post_data['x_zip'] = $order_info['payment_postcode'];
		$post_data['x_country'] = $order_info['payment_country'];
		$post_data['x_phone'] = $order_info['telephone'];
		$post_data['x_customer_ip'] = $this->request->server['REMOTE_ADDR'];
		$post_data['x_email'] = $order_info['email'];
		$post_data['x_description'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
		$post_data['x_amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], 1.00000, false);
		$post_data['x_currency_code'] = $this->session->data['currency'];
		$post_data['x_method'] = 'CC';
		$post_data['x_type'] = ($this->config->get('payment_authorizenet_aim_method') == 'capture') ? 'AUTH_CAPTURE' : 'AUTH_ONLY';
		$post_data['x_card_num'] = str_replace(' ', '', $this->request->post['cc_number']);
		$post_data['x_exp_date'] = $this->request->post['cc_expire_date_month'] . $this->request->post['cc_expire_date_year'];
		$post_data['x_card_code'] = $this->request->post['cc_cvv2'];
		$post_data['x_invoice_num'] = (int)$this->session->data['order_id'];
		$post_data['x_solution_id'] = 'A1000015';

		// Customer Shipping Address Fields
		if ($order_info['shipping_method']) {
			$post_data['x_ship_to_first_name'] = $order_info['shipping_firstname'];
			$post_data['x_ship_to_last_name'] = $order_info['shipping_lastname'];
			$post_data['x_ship_to_company'] = $order_info['shipping_company'];
			$post_data['x_ship_to_address'] = $order_info['shipping_address_1'] . ' ' . $order_info['shipping_address_2'];
			$post_data['x_ship_to_city'] = $order_info['shipping_city'];
			$post_data['x_ship_to_state'] = $order_info['shipping_zone'];
			$post_data['x_ship_to_zip'] = $order_info['shipping_postcode'];
			$post_data['x_ship_to_country'] = $order_info['shipping_country'];
		} else {
			$post_data['x_ship_to_first_name'] = $order_info['payment_firstname'];
			$post_data['x_ship_to_last_name'] = $order_info['payment_lastname'];
			$post_data['x_ship_to_company'] = $order_info['payment_company'];
			$post_data['x_ship_to_address'] = $order_info['payment_address_1'] . ' ' . $order_info['payment_address_2'];
			$post_data['x_ship_to_city'] = $order_info['payment_city'];
			$post_data['x_ship_to_state'] = $order_info['payment_zone'];
			$post_data['x_ship_to_zip'] = $order_info['payment_postcode'];
			$post_data['x_ship_to_country'] = $order_info['payment_country'];
		}

		if ($this->config->get('payment_authorizenet_aim_mode') == 'test') {
			$post_data['x_test_request'] = 'true';
		}

		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data, '', '&'));

		$response = curl_exec($curl);

		$json = [];

		if (curl_error($curl)) {
			$json['error'] = 'CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl);

			$this->log->write('AUTHNET AIM CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl));
		} elseif ($response) {
			$response_info = [];

			$results = explode('|', $response);

			$i = 1;

			foreach ($results as $result) {
				$response_info[$i] = trim($result, '"');

				$i++;
			}

			if ($response_info[1] == '1') {
				$message = '';

				if (isset($response_info['5'])) {
					$message .= 'Authorization Code: ' . $response_info['5'] . "\n";
				}

				if (isset($response_info['6'])) {
					$message .= 'AVS Response: ' . $response_info['6'] . "\n";
				}

				if (isset($response_info['7'])) {
					$message .= 'Transaction ID: ' . $response_info['7'] . "\n";
				}

				if (isset($response_info['39'])) {
					$message .= 'Card Code Response: ' . $response_info['39'] . "\n";
				}

				if (isset($response_info['40'])) {
					$message .= 'Cardholder Authentication Verification Response: ' . $response_info['40'] . "\n";
				}

				if (!$this->config->get('payment_authorizenet_aim_hash') || (strtoupper($response_info[38]) == strtoupper(md5($this->config->get('payment_authorizenet_aim_hash') . $this->config->get('payment_authorizenet_aim_login') . $response_info[7] . $this->currency->format($order_info['total'], $order_info['currency_code'], 1.00000, false))))) {
					$this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('payment_authorizenet_aim_order_status_id'), $message, false);
				} else {
					$this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('config_order_status_id'));
				}

				$json['redirect'] = $this->url->link('checkout/success', '', true);
			} else {
				$json['error'] = $response_info[4];
			}
		} else {
			$json['error'] = 'Empty Gateway Response';

			$this->log->write('AUTHNET AIM CURL ERROR: Empty Gateway Response');
		}

		curl_close($curl);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
