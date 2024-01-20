<?php
/**
 * Class Globalpay Remote
 *
 * @package Catalog\Model\Extension\Payment
 */
class ModelExtensionPaymentGlobalpayRemote extends Model {
	/**
	 * getMethod
	 *
	 * @param array $address
	 *
	 * @return array
	 */
	public function getMethod(array $address): array {
		$this->load->language('extension/payment/globalpay_remote');

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_globalpay_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

		if (!$this->config->get('payment_globalpay_remote_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = [];

		if ($status) {
			$method_data = [
				'code'       => 'globalpay_remote',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_globalpay_remote_sort_order')
			];
		}

		return $method_data;
	}

	/**
	 * checkEnrollment
	 *
	 * @param mixed $account
	 * @param mixed $amount
	 * @param mixed $currency
	 * @param mixed $order_ref
	 */
	public function checkEnrollment($account, $amount, $currency, $order_ref) {
		$timestamp = date('YmdHis');
		$merchant_id = $this->config->get('payment_globalpay_remote_merchant_id');
		$secret = $this->config->get('payment_globalpay_remote_secret');

		$tmp = $timestamp . '.' . $merchant_id . '.' . $order_ref . '.' . $amount . '.' . $currency . '.' . $this->request->post['cc_number'];
		$hash = sha1($tmp);
		$tmp = $hash . '.' . $secret;
		$hash = sha1($tmp);

		$xml = '';
		$xml .= '<request type="3ds-verifyenrolled" timestamp="' . $timestamp . '">';
		$xml .= '<merchantid>' . $merchant_id . '</merchantid>';
		$xml .= '<account>' . $account . '</account>';
		$xml .= '<orderid>' . $order_ref . '</orderid>';
		$xml .= '<amount currency="' . $currency . '">' . $amount . '</amount>';
		$xml .= '<card>';
		$xml .= '<number>' . $this->request->post['cc_number'] . '</number>';
		$xml .= '<expdate>' . $this->request->post['cc_expire_date_month'] . $this->request->post['cc_expire_date_year'] . '</expdate>';
		$xml .= '<type>' . $this->request->post['cc_type'] . '</type>';
		$xml .= '<chname>' . $this->request->post['cc_name'] . '</chname>';
		$xml .= '</card>';
		$xml .= '<sha1hash>' . $hash . '</sha1hash>';
		$xml .= '</request>';

		$this->logger('checkEnrollment call');
		$this->logger(simplexml_load_string($xml));
		$this->logger($xml);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://remote.globaliris.com/realmpi");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "OpenCart " . VERSION);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);

		curl_close($ch);

		$this->logger('checkEnrollment xml response');
		$this->logger($response);

		return simplexml_load_string($response);
	}

	/**
	 * enrollmentSignature
	 *
	 * @param mixed $account
	 * @param mixed $amount
	 * @param mixed $currency
	 * @param mixed $order_ref
	 * @param mixed $card_number
	 * @param mixed $card_expire
	 * @param mixed $card_type
	 * @param mixed $card_name
	 * @param mixed $pares
	 */
	public function enrollmentSignature($account, $amount, $currency, $order_ref, $card_number, $card_expire, $card_type, $card_name, $pares) {
		// Orders
		$this->load->model('checkout/order');

		$timestamp = date('YmdHis');

		$merchant_id = $this->config->get('payment_globalpay_remote_merchant_id');
		$secret = $this->config->get('payment_globalpay_remote_secret');

		$tmp = $timestamp . '.' . $merchant_id . '.' . $order_ref . '.' . $amount . '.' . $currency . '.' . $card_number;
		$hash = sha1($tmp);
		$tmp = $hash . '.' . $secret;
		$hash = sha1($tmp);

		$xml = '';
		$xml .= '<request type="3ds-verifysig" timestamp="' . $timestamp . '">';
		$xml .= '<merchantid>' . $merchant_id . '</merchantid>';
		$xml .= '<account>' . $account . '</account>';
		$xml .= '<orderid>' . $order_ref . '</orderid>';
		$xml .= '<amount currency="' . $currency . '">' . (int)$amount . '</amount>';
		$xml .= '<card>';
		$xml .= '<number>' . $card_number . '</number>';
		$xml .= '<expdate>' . $card_expire . '</expdate>';
		$xml .= '<type>' . $card_type . '</type>';
		$xml .= '<chname>' . $card_name . '</chname>';
		$xml .= '</card>';
		$xml .= '<pares>' . $pares . '</pares>';
		$xml .= '<sha1hash>' . $hash . '</sha1hash>';
		$xml .= '</request>';

		$this->logger('enrollmentSignature call');
		$this->logger(simplexml_load_string($xml));
		$this->logger($xml);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://remote.globaliris.com/realmpi");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "OpenCart " . VERSION);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);

		curl_close($ch);

		$this->logger('enrollmentSignature xml response');
		$this->logger($response);

		return simplexml_load_string($response);
	}

	/**
	 * capturePayment
	 *
	 * @param mixed $account
	 * @param mixed $amount
	 * @param mixed $currency
	 * @param mixed $order_id
	 * @param mixed $order_ref
	 * @param mixed $card_number
	 * @param mixed $expire
	 * @param mixed $name
	 * @param mixed $type
	 * @param mixed $cvv
	 * @param mixed $issue
	 * @param mixed $eci_ref
	 * @param mixed $eci
	 * @param mixed $cavv
	 * @param mixed $xid
	 */
	public function capturePayment($account, $amount, $currency, $order_id, $order_ref, $card_number, $expire, $name, $type, $cvv, $issue, $eci_ref, $eci = '', $cavv = '', $xid = '') {
		$this->load->language('extension/payment/globalpay_remote');

		// Orders
		$this->load->model('checkout/order');

		$timestamp = date('YmdHis');
		$merchant_id = $this->config->get('payment_globalpay_remote_merchant_id');
		$secret = $this->config->get('payment_globalpay_remote_secret');

		$tmp = $timestamp . '.' . $merchant_id . '.' . $order_ref . '.' . $amount . '.' . $currency . '.' . $card_number;
		$hash = sha1($tmp);
		$tmp = $hash . '.' . $secret;
		$hash = sha1($tmp);

		$order_info = $this->model_checkout_order->getOrder($order_id);

		$xml = '';
		$xml .= '<request type="auth" timestamp="' . $timestamp . '">';
		$xml .= '<merchantid>' . $merchant_id . '</merchantid>';
		$xml .= '<account>' . $account . '</account>';
		$xml .= '<orderid>' . $order_ref . '</orderid>';
		$xml .= '<amount currency="' . $currency . '">' . $amount . '</amount>';
		$xml .= '<comments>';
		$xml .= '<comment id="1">OpenCart</comment>';
		$xml .= '</comments>';
		$xml .= '<card>';
		$xml .= '<number>' . $card_number . '</number>';
		$xml .= '<expdate>' . $expire . '</expdate>';
		$xml .= '<type>' . $type . '</type>';
		$xml .= '<chname>' . $name . '</chname>';
		$xml .= '<cvn>';
		$xml .= '<number>' . (int)$cvv . '</number>';
		$xml .= '<presind>2</presind>';
		$xml .= '</cvn>';

		if (!empty($issue)) {
			$xml .= '<issueno>' . (int)$issue . '</issueno>';
		}

		$xml .= '</card>';

		if ($this->config->get('payment_globalpay_remote_auto_settle') == 0) {
			$xml .= '<autosettle flag="0" />';
		} elseif ($this->config->get('payment_globalpay_remote_auto_settle') == 1) {
			$xml .= '<autosettle flag="1" />';
		} elseif ($this->config->get('payment_globalpay_remote_auto_settle') == 2) {
			$xml .= '<autosettle flag="MULTI" />';
		}

		if ($eci != '' || $cavv != '' || $xid != '') {
			$xml .= '<mpi>';
			if ($eci != '') {
				$xml .= '<eci>' . (string)$eci . '</eci>';
			}

			if ($cavv != '') {
				$xml .= '<cavv>' . (string)$cavv . '</cavv>';
			}

			if ($xid != '') {
				$xml .= '<xid>' . (string)$xid . '</xid>';
			}

			$xml .= '</mpi>';
		}

		$xml .= '<sha1hash>' . $hash . '</sha1hash>';

		if ($this->config->get('payment_globalpay_remote_tss_check') == 1) {
			$xml .= '<tssinfo>';
			$xml .= '<custipaddress>' . $order_info['ip'] . '</custipaddress>';

			if ($this->customer->getId() > 0) {
				$xml .= '<custnum>' . (int)$this->customer->getId() . '</custnum>';
			}

			if ((isset($order_info['payment_iso_code_2']) && $order_info['payment_iso_code_2'] != '') || (isset($order_info['payment_postcode']) && $order_info['payment_postcode'] != '')) {
				$xml .= '<address type="billing">';

				if ((isset($order_info['payment_postcode']) && $order_info['payment_postcode'] != '')) {
					$xml .= '<code>' . filter_var($order_info['payment_postcode'], FILTER_SANITIZE_NUMBER_INT) . '|' . filter_var($order_info['payment_address_1'], FILTER_SANITIZE_NUMBER_INT) . '</code>';
				}
				if ((isset($order_info['payment_iso_code_2']) && $order_info['payment_iso_code_2'] != '')) {
					$xml .= '<country>' . $order_info['payment_iso_code_2'] . '</country>';
				}

				$xml .= '</address>';
			}
			if ((isset($order_info['shipping_iso_code_2']) && $order_info['shipping_iso_code_2'] != '') || (isset($order_info['shipping_postcode']) && $order_info['shipping_postcode'] != '')) {
				$xml .= '<address type="shipping">';

				if ((isset($order_info['shipping_postcode']) && $order_info['shipping_postcode'] != '')) {
					$xml .= '<code>' . filter_var($order_info['shipping_postcode'], FILTER_SANITIZE_NUMBER_INT) . '|' . filter_var($order_info['shipping_address_1'], FILTER_SANITIZE_NUMBER_INT) . '</code>';
				}
				if ((isset($order_info['shipping_iso_code_2']) && $order_info['shipping_iso_code_2'] != '')) {
					$xml .= '<country>' . $order_info['shipping_iso_code_2'] . '</country>';
				}

				$xml .= '</address>';
			}

			$xml .= '</tssinfo>';
		}

		$xml .= '</request>';

		$this->logger('capturePayment call');
		$this->logger(simplexml_load_string($xml));
		$this->logger($xml);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://remote.globaliris.com/realauth");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "OpenCart " . VERSION);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);

		curl_close($ch);

		$this->logger('capturePayment xml response');
		$this->logger($response);

		$response = simplexml_load_string($response);

		$message = '<strong>' . $this->language->get('text_result') . ':</strong> ' . (int)$response->result;
		$message .= '<br/><strong>' . $this->language->get('text_message') . ':</strong> ' . (string)$response->message;
		$message .= '<br/><strong>' . $this->language->get('text_order_ref') . ':</strong> ' . (string)$order_ref;

		if (!empty($response->cvnresult)) {
			$message .= '<br/><strong>' . $this->language->get('text_cvn_result') . ':</strong> ' . (string)$response->cvnresult;
		}

		if (!empty($response->avspostcoderesponse)) {
			$message .= '<br/><strong>' . $this->language->get('text_avs_postcode') . ':</strong> ' . (string)$response->avspostcoderesponse;
		}

		if (!empty($response->avsaddressresponse)) {
			$message .= '<br/><strong>' . $this->language->get('text_avs_address') . ':</strong> ' . (string)$response->avsaddressresponse;
		}

		if (!empty($response->authcode)) {
			$message .= '<br/><strong>' . $this->language->get('text_auth_code') . ':</strong> ' . (string)$response->authcode;
		}

		if (!empty($eci_ref)) {
			$message .= '<br/><strong>' . $this->language->get('text_eci') . ':</strong> (' . (int)$eci . ') ' . $this->language->get('text_3d_s' . (int)$eci_ref);
		}

		if (!empty($response->tss->result)) {
			$message .= '<br/><strong>' . $this->language->get('text_tss') . ':</strong> ' . (int)$response->tss->result;
		}

		$message .= '<br/><strong>' . $this->language->get('text_timestamp') . ':</strong> ' . (string)$timestamp;

		if ($this->config->get('payment_globalpay_remote_card_data_status') == 1) {
			$message .= '<br/><strong>' . $this->language->get('entry_cc_type') . ':</strong> ' . (string)$type;
			$message .= '<br/><strong>' . $this->language->get('text_last_digits') . ':</strong> ' . (string)substr($card_number, -4);
			$message .= '<br/><strong>' . $this->language->get('entry_cc_expire_date') . ':</strong> ' . (string)$expire;
			$message .= '<br/><strong>' . $this->language->get('entry_cc_name') . ':</strong> ' . (string)$name;

			if (!empty($response->cardissuer->bank)) {
				$message .= '<br/><strong>' . $this->language->get('text_card_bank') . ':</strong> ' . (string)$response->cardissuer->bank;
			}

			if (!empty($response->cardissuer->country)) {
				$message .= '<br/><strong>' . $this->language->get('text_card_country') . ':</strong> ' . (string)$response->cardissuer->country;
			}

			if (!empty($response->cardissuer->region)) {
				$message .= '<br/><strong>' . $this->language->get('text_card_region') . ':</strong> ' . (string)$response->cardissuer->region;
			}
		}

		if ($response->result == '00') {
			$this->model_checkout_order->addHistory($order_id, $this->config->get('config_order_status_id'));

			$globalpay_order_id = $this->addOrder($order_info, $response, $account, $order_ref);

			if ($this->config->get('payment_globalpay_remote_auto_settle') == 1) {
				$this->addTransaction($globalpay_order_id, 'payment', $order_info);

				$this->model_checkout_order->addHistory($order_id, $this->config->get('payment_globalpay_remote_order_status_success_settled_id'), $message);
			} else {
				$this->addTransaction($globalpay_order_id, 'auth', 0);

				$this->model_checkout_order->addHistory($order_id, $this->config->get('payment_globalpay_remote_order_status_success_unsettled_id'), $message);
			}
		} elseif ($response->result == '101') {
			// Decline
			$this->addHistory($order_id, $this->config->get('payment_globalpay_remote_order_status_decline_id'), $message);
		} elseif ($response->result == '102') {
			// Referal B
			$this->addHistory($order_id, $this->config->get('payment_globalpay_remote_order_status_decline_pending_id'), $message);
		} elseif ($response->result == '103') {
			// Referal A
			$this->addHistory($order_id, $this->config->get('payment_globalpay_remote_order_status_decline_stolen_id'), $message);
		} elseif ($response->result == '200') {
			// Error Connecting to Bank
			$this->addHistory($order_id, $this->config->get('payment_globalpay_remote_order_status_decline_bank_id'), $message);
		} elseif ($response->result == '204') {
			// Error Connecting to Bank
			$this->addHistory($order_id, $this->config->get('payment_globalpay_remote_order_status_decline_bank_id'), $message);
		} elseif ($response->result == '205') {
			// Comms Error
			$this->addHistory($order_id, $this->config->get('payment_globalpay_remote_order_status_decline_bank_id'), $message);
		} else {
			// Other
			$this->addHistory($order_id, $this->config->get('payment_globalpay_remote_order_status_decline_id'), $message);
		}

		return $response;
	}

	/**
	 * addOrder
	 *
	 * @param array  $order_info
	 * @param object $response
	 * @param string $account
	 * @param string $order_ref
	 *
	 * @return int
	 */
	public function addOrder(array $order_info, object $response, string $account, string $order_ref): int {
		if ($this->config->get('payment_globalpay_remote_auto_settle') == 1) {
			$settle_status = 1;
		} else {
			$settle_status = 0;
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpay_remote_order` SET `order_id` = '" . (int)$order_info['order_id'] . "', `settle_type` = '" . (int)$this->config->get('payment_globalpay_remote_auto_settle') . "', `order_ref` = '" . $this->db->escape($order_ref) . "', `order_ref_previous` = '" . $this->db->escape($order_ref) . "', `date_added` = NOW(), `date_modified` = NOW(), `capture_status` = '" . (int)$settle_status . "', `currency_code` = '" . $this->db->escape($order_info['currency_code']) . "', `pasref` = '" . $this->db->escape($response->pasref) . "', `pasref_previous` = '" . $this->db->escape($response->pasref) . "', `authcode` = '" . $this->db->escape($response->authcode) . "', `account` = '" . $this->db->escape($account) . "', `total` = '" . $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) . "'");

		return $this->db->getLastId();
	}

	/**
	 * addTransaction
	 *
	 * @param int    $globalpay_remote_order_id
	 * @param string $type
	 * @param array  $order_info
	 *
	 * @return void
	 */
	public function addTransaction(int $globalpay_remote_order_id, string $type, array $order_info): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "globalpay_remote_order_transaction` SET `globalpay_remote_order_id` = '" . (int)$globalpay_remote_order_id . "', `date_added` = NOW(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) . "'");
	}

	/**
	 * Logger
	 *
	 * @param string $message
	 *
	 * @return void
	 */
	public function logger(string $message): void {
		if ($this->config->get('payment_globalpay_remote_debug') == 1) {
			// Log
			$log = new \Log('globalpay_remote.log');
			$log->write($message);
		}
	}
}
