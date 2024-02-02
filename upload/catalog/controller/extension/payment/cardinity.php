<?php
/**
 * Class Cardinity
 *
 * @package Catalog\Controller\Extension\Payment
 */
class ControllerExtensionPaymentCardinity extends Controller {
	/**
	 * @return string
	 */
	public function index(): string {
		$this->load->language('extension/payment/cardinity');

		$data['months'] = [];

		for ($i = 1; $i <= 12; $i++) {
			$data['months'][] = [
				'text'  => sprintf('%02d', $i),
				'value' => sprintf('%02d', $i)
			];
		}

		$today = getdate();
		$data['years'] = [];

		for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
			$data['years'][] = [
				'text'  => sprintf('%02d', $i % 100),
				'value' => sprintf('%04d', $i)
			];
		}

		return $this->load->view('extension/payment/cardinity', $data);
	}

	/**
	 * Send
	 *
	 * @return void
	 */
	public function send(): void {
		$this->load->language('extension/payment/cardinity');

		$json = [];

		// Orders
		$this->load->model('checkout/order');

		// Cardinity
		$this->load->model('extension/payment/cardinity');

		$json['error'] = $json['success'] = $json['3ds'] = '';

		$payment = false;

		$error = $this->validate();

		if (!$error) {
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

			if (strlen($order_info['order_id']) < 2) {
				$order_id = '0' . $order_info['order_id'];
			} else {
				$order_id = $order_info['order_id'];
			}

			if (!empty($order_info['payment_iso_code_2'])) {
				$order_country = $order_info['payment_iso_code_2'];
			} else {
				$order_country = $order_info['shipping_iso_code_2'];
			}

			$payment_data = [
				'amount'             => (float)$this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false),
				'currency'           => $order_info['currency_code'],
				'order_id'           => $order_id,
				'country'            => $order_country,
				'payment_method'     => 'card',
				'payment_instrument' => [
					'pan'       => preg_replace('!\s+!', '', $this->request->post['pan']),
					'exp_year'  => (int)$this->request->post['exp_year'],
					'exp_month' => (int)$this->request->post['exp_month'],
					'cvc'       => $this->request->post['cvc'],
					'holder'    => $this->request->post['holder']
				],
			];

			try {
				$payment = $this->model_extension_payment_cardinity->createPayment($this->config->get('payment_cardinity_key'), $this->config->get('payment_cardinity_secret'), $payment_data);
			} catch (\Cardinity\Exception\Declined $exception) {
				$this->failedOrder($this->language->get('error_payment_declined'), $this->language->get('error_payment_declined'));

				$json['redirect'] = $this->url->link('checkout/checkout', '', true);
			} catch (\Exception $exception) {
				$this->failedOrder();

				$json['redirect'] = $this->url->link('checkout/checkout', '', true);
			}

			$successful_order_statuses = [
				'approved',
				'pending'
			];

			if ($payment) {
				if (!in_array($payment->getStatus(), $successful_order_statuses)) {
					$this->failedOrder($payment->getStatus());

					$json['redirect'] = $this->url->link('checkout/checkout', '', true);
				} else {
					$this->model_extension_payment_cardinity->addOrder([
						'order_id'   => $this->session->data['order_id'],
						'payment_id' => $payment->getId()
					]);

					if ($payment->getStatus() == 'pending') {
						// 3ds
						$authorization_information = $payment->getAuthorizationInformation();

						$encryption_data = [
							'order_id' => $this->session->data['order_id'],
							'secret'   => $this->config->get('payment_cardinity_secret')
						];

						$encryption = new \Encryption();

						$hash = $encryption->encrypt((int)$this->config->get('config_encryption'), json_encode($encryption_data));

						$json['3ds'] = [
							'url'     => $authorization_information->getUrl(),
							'PaReq'   => $authorization_information->getData(),
							'TermUrl' => $this->url->link('extension/payment/cardinity/threeDSecureCallback', '', true),
							'hash'    => $hash
						];
					} elseif ($payment->getStatus() == 'approved') {
						$this->finalizeOrder($payment);

						$json['redirect'] = $this->url->link('checkout/success', '', true);
					}
				}
			}
		} else {
			$json['error'] = $error;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Three D Secure Form
	 *
	 * @return void
	 */
	public function threeDSecureForm(): void {
		$this->load->language('extension/payment/cardinity');

		// Cardinity
		$this->load->model('extension/payment/cardinity');

		$success = false;
		$redirect = false;

		$encryption_data = [
			'order_id' => $this->session->data['order_id'],
			'secret'   => $this->config->get('payment_cardinity_secret')
		];

		$encryption = new \Encryption();

		$hash = $encryption->encrypt((int)$this->config->get('config_encryption'), json_encode($encryption_data));

		if (hash_equals($hash, $this->request->post['hash'])) {
			$success = true;

			$data['url'] = $this->request->post['url'];
			$data['PaReq'] = $this->request->post['PaReq'];
			$data['TermUrl'] = $this->request->post['TermUrl'];
			$data['MD'] = $hash;
		} else {
			$this->failedOrder($this->language->get('error_invalid_hash'));

			$redirect = $this->url->link('checkout/checkout', '', true);
		}

		$data['success'] = $success;
		$data['redirect'] = $redirect;

		$this->response->setOutput($this->load->view('extension/payment/cardinity_3ds', $data));
	}

	/**
	 * Three D Secure Callback
	 *
	 * @return void
	 */
	public function threeDSecureCallback(): void {
		$this->load->language('extension/payment/cardinity');

		// Cardinity
		$this->load->model('extension/payment/cardinity');

		$success = false;

		$error = '';

		$encryption_data = [
			'order_id' => $this->session->data['order_id'],
			'secret'   => $this->config->get('payment_cardinity_secret')
		];

		$encryption = new \Encryption();

		$hash = $encryption->encrypt((int)$this->config->get('config_encryption'), json_encode($encryption_data));

		if (hash_equals($hash, $this->request->post['MD'])) {
			$order = $this->model_extension_payment_cardinity->getOrder($encryption_data['order_id']);

			if ($order && $order['payment_id']) {
				$payment = $this->model_extension_payment_cardinity->finalizePayment($this->config->get('payment_cardinity_key'), $this->config->get('payment_cardinity_secret'), $order['payment_id'], $this->request->post['PaRes']);

				if ($payment && $payment->getStatus() == 'approved') {
					$success = true;
				} else {
					$error = $this->language->get('error_finalizing_payment');
				}
			} else {
				$error = $this->language->get('error_unknown_order_id');
			}
		} else {
			$error = $this->language->get('error_invalid_hash');
		}

		if ($success && isset($payment)) {
			$this->finalizeOrder($payment);

			$this->response->redirect($this->url->link('checkout/success', '', true));
		} else {
			$this->failedOrder($error);

			$this->response->redirect($this->url->link('checkout/checkout', '', true));
		}
	}

	private function finalizeOrder(string $payment): void {
		$this->load->language('extension/payment/cardinity');

		// Orders
		$this->load->model('checkout/order');

		$this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('payment_cardinity_order_status_id'));

		$this->model_extension_payment_cardinity->log($this->language->get('text_payment_success'));
		$this->model_extension_payment_cardinity->log($payment);
	}

	private function failedOrder(?string $log = null, ?string $alert = null): void {
		$this->load->language('extension/payment/cardinity');

		$this->model_extension_payment_cardinity->log($this->language->get('text_payment_failed'));

		if ($log) {
			$this->model_extension_payment_cardinity->log($log);
		}

		if ($alert) {
			$this->session->data['error'] = $alert;
		} else {
			$this->session->data['error'] = $this->language->get('error_process_order');
		}
	}

	private function validate() {
		// Orders
		$this->load->model('checkout/order');

		// Cardinity
		$this->load->model('extension/payment/cardinity');

		$error = [];

		if (!$this->session->data['order_id']) {
			$error['warning'] = $this->language->get('error_process_order');
		}

		if (!$error) {
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

			if (!$order_info) {
				$error['warning'] = $this->language->get('error_process_order');
			}
		}

		if (isset($order_info) && !in_array($order_info['currency_code'], $this->model_extension_payment_cardinity->getSupportedCurrencies())) {
			$error['warning'] = $this->language->get('error_invalid_currency');
		}

		if (!isset($this->request->post['holder']) || oc_strlen($this->request->post['holder']) < 1 || oc_strlen($this->request->post['holder']) > 32) {
			$error['holder'] = true;
		}

		if (!isset($this->request->post['pan']) || oc_strlen($this->request->post['pan']) < 1 || oc_strlen($this->request->post['pan']) > 19) {
			$error['pan'] = true;
		}

		if (!isset($this->request->post['pan']) || !is_numeric(preg_replace('!\s+!', '', $this->request->post['pan']))) {
			$error['pan'] = true;
		}

		if (!isset($this->request->post['exp_month']) || !isset($this->request->post['exp_year'])) {
			$error['expiry_date'] = true;
		} else {
			$expiry = new \DateTime();
			$expiry->setDate($this->request->post['exp_year'], $this->request->post['exp_month'], '1');
			$expiry->modify('+1 month');
			$expiry->modify('-1 day');

			$now = new \DateTime();

			if ($expiry < $now) {
				$error['expiry_date'] = true;
			}
		}

		if (!isset($this->request->post['cvc']) || oc_strlen($this->request->post['cvc']) < 1 || oc_strlen($this->request->post['cvc']) > 4) {
			$error['cvc'] = true;
		}

		return $error;
	}
}
