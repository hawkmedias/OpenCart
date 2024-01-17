<?php
/**
 * Class Cardconnect
 *
 * @package Admin\Controller\Extension\Payment
 */
class ControllerExtensionPaymentCardConnect extends Controller {
	/**
	 * @var array<string, string>
	 */
	private array $error = [];

	/**
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/payment/cardconnect');

		$this->document->setTitle($this->language->get('heading_title'));

		// Settings
		$this->load->model('setting/setting');

		// Cardconnect
		$this->load->model('extension/payment/cardconnect');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_cardconnect', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/cardconnect', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['action'] = $this->url->link('extension/payment/cardconnect', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_cardconnect_merchant_id'])) {
			$data['payment_cardconnect_merchant_id'] = $this->request->post['payment_cardconnect_merchant_id'];
		} else {
			$data['payment_cardconnect_merchant_id'] = $this->config->get('payment_cardconnect_merchant_id');
		}

		if (isset($this->request->post['payment_cardconnect_api_username'])) {
			$data['payment_cardconnect_api_username'] = $this->request->post['payment_cardconnect_api_username'];
		} else {
			$data['payment_cardconnect_api_username'] = $this->config->get('payment_cardconnect_api_username');
		}

		if (isset($this->request->post['payment_cardconnect_api_password'])) {
			$data['payment_cardconnect_api_password'] = $this->request->post['payment_cardconnect_api_password'];
		} else {
			$data['payment_cardconnect_api_password'] = $this->config->get('payment_cardconnect_api_password');
		}

		if (isset($this->request->post['payment_cardconnect_token'])) {
			$data['payment_cardconnect_token'] = $this->request->post['payment_cardconnect_token'];
		} elseif ($this->config->has('payment_cardconnect_token')) {
			$data['payment_cardconnect_token'] = $this->config->get('payment_cardconnect_token');
		} else {
			$data['payment_cardconnect_token'] = md5(time());
		}

		if (isset($this->request->post['payment_cardconnect_transaction'])) {
			$data['payment_cardconnect_transaction'] = $this->request->post['payment_cardconnect_transaction'];
		} else {
			$data['payment_cardconnect_transaction'] = $this->config->get('payment_cardconnect_transaction');
		}

		if (isset($this->request->post['payment_cardconnect_site'])) {
			$data['payment_cardconnect_site'] = $this->request->post['payment_cardconnect_site'];
		} elseif ($this->config->has('payment_cardconnect_site')) {
			$data['payment_cardconnect_site'] = $this->config->get('payment_cardconnect_site');
		} else {
			$data['payment_cardconnect_site'] = 'fts';
		}

		if (isset($this->request->post['payment_cardconnect_environment'])) {
			$data['payment_cardconnect_environment'] = $this->request->post['payment_cardconnect_environment'];
		} else {
			$data['payment_cardconnect_environment'] = $this->config->get('payment_cardconnect_environment');
		}

		if (isset($this->request->post['payment_cardconnect_store_cards'])) {
			$data['payment_cardconnect_store_cards'] = $this->request->post['payment_cardconnect_store_cards'];
		} else {
			$data['payment_cardconnect_store_cards'] = $this->config->get('payment_cardconnect_store_cards');
		}

		if (isset($this->request->post['payment_cardconnect_echeck'])) {
			$data['payment_cardconnect_echeck'] = $this->request->post['payment_cardconnect_echeck'];
		} else {
			$data['payment_cardconnect_echeck'] = $this->config->get('payment_cardconnect_echeck');
		}

		if (isset($this->request->post['payment_cardconnect_total'])) {
			$data['payment_cardconnect_total'] = $this->request->post['payment_cardconnect_total'];
		} else {
			$data['payment_cardconnect_total'] = $this->config->get('payment_cardconnect_total');
		}

		if (isset($this->request->post['payment_cardconnect_geo_zone_id'])) {
			$data['payment_cardconnect_geo_zone_id'] = (int)$this->request->post['payment_cardconnect_geo_zone_id'];
		} else {
			$data['payment_cardconnect_geo_zone_id'] = $this->config->get('payment_cardconnect_geo_zone_id');
		}

		if (isset($this->request->post['payment_cardconnect_status'])) {
			$data['payment_cardconnect_status'] = $this->request->post['payment_cardconnect_status'];
		} else {
			$data['payment_cardconnect_status'] = $this->config->get('payment_cardconnect_status');
		}

		if (isset($this->request->post['payment_cardconnect_logging'])) {
			$data['payment_cardconnect_logging'] = $this->request->post['payment_cardconnect_logging'];
		} else {
			$data['payment_cardconnect_logging'] = $this->config->get('payment_cardconnect_logging');
		}

		if (isset($this->request->post['payment_cardconnect_sort_order'])) {
			$data['payment_cardconnect_sort_order'] = $this->request->post['payment_cardconnect_sort_order'];
		} else {
			$data['payment_cardconnect_sort_order'] = $this->config->get('payment_cardconnect_sort_order');
		}

		$data['cardconnect_cron_url'] = HTTPS_CATALOG . 'index.php?route=extension/payment/cardconnect/cron&token=' . $data['cardconnect_token'];

		if ($this->config->get('payment_cardconnect_cron_time')) {
			$data['payment_cardconnect_cron_time'] = date($this->language->get('datetime_format'), strtotime($this->config->get('payment_cardconnect_cron_time')));
		} else {
			$data['payment_cardconnect_cron_time'] = $this->language->get('text_no_cron_time');
		}

		if (isset($this->request->post['payment_cardconnect_order_status_id_pending'])) {
			$data['payment_cardconnect_order_status_id_pending'] = $this->request->post['payment_cardconnect_order_status_id_pending'];
		} elseif ($this->config->has('payment_cardconnect_order_status_id_pending')) {
			$data['payment_cardconnect_order_status_id_pending'] = $this->config->get('payment_cardconnect_order_status_id_pending');
		} else {
			$data['payment_cardconnect_order_status_id_pending'] = '1';
		}

		if (isset($this->request->post['payment_cardconnect_order_status_id_processing'])) {
			$data['payment_cardconnect_order_status_id_processing'] = $this->request->post['payment_cardconnect_order_status_id_processing'];
		} elseif ($this->config->has('payment_cardconnect_order_status_id_processing')) {
			$data['payment_cardconnect_order_status_id_processing'] = $this->config->get('payment_cardconnect_order_status_id_processing');
		} else {
			$data['payment_cardconnect_order_status_id_processing'] = '2';
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->error['payment_cardconnect_merchant_id'])) {
			$data['error_payment_cardconnect_merchant_id'] = $this->error['payment_cardconnect_merchant_id'];
		} else {
			$data['error_payment_cardconnect_merchant_id'] = '';
		}

		if (isset($this->error['cardconnect_api_username'])) {
			$data['error_cardconnect_api_username'] = $this->error['cardconnect_api_username'];
		} else {
			$data['error_cardconnect_api_username'] = '';
		}

		if (isset($this->error['cardconnect_api_password'])) {
			$data['error_cardconnect_api_password'] = $this->error['cardconnect_api_password'];
		} else {
			$data['error_cardconnect_api_password'] = '';
		}

		if (isset($this->error['cardconnect_token'])) {
			$data['error_cardconnect_token'] = $this->error['cardconnect_token'];
		} else {
			$data['error_cardconnect_token'] = '';
		}

		if (isset($this->error['cardconnect_site'])) {
			$data['error_cardconnect_site'] = $this->error['cardconnect_site'];
		} else {
			$data['error_cardconnect_site'] = '';
		}

		// Order Statuses
		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		// Geo Zones
		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/cardconnect', $data));
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		if ($this->user->hasPermission('modify', 'marketplace/extension')) {
			// Cardconnect
			$this->load->model('extension/payment/cardconnect');

			$this->model_extension_payment_cardconnect->install();
		}
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		if ($this->user->hasPermission('modify', 'marketplace/extension')) {
			// Cardconnect
			$this->load->model('extension/payment/cardconnect');

			$this->model_extension_payment_cardconnect->uninstall();
		}
	}

	/**
	 * Order
	 *
	 * @return string
	 */
	public function order(): string {
		if ($this->config->get('payment_cardconnect_status')) {
			// Cardconnect
			$this->load->model('extension/payment/cardconnect');

			if (isset($this->request->get['order_id'])) {
				$order_id = (int)$this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$payment_cardconnect_order = $this->model_extension_payment_cardconnect->getOrder($this->request->get['order_id']);

			if ($payment_cardconnect_order) {
				$this->load->language('extension/payment/cardconnect');

				if ($payment_cardconnect_order['payment_method'] == 'card') {
					$payment_cardconnect_order['payment_method'] = $this->language->get('text_card');
				} else {
					$payment_cardconnect_order['payment_method'] = $this->language->get('text_echeck');
				}

				$payment_cardconnect_order['total_captured_formatted'] = $this->currency->format($payment_cardconnect_order['total_captured'], $payment_cardconnect_order['currency_code'], false, true);
				$payment_cardconnect_order['total_formatted'] = $this->currency->format($payment_cardconnect_order['total'], $payment_cardconnect_order['currency_code'], false, true);
				$payment_cardconnect_order['total_captured'] = $this->model_extension_payment_cardconnect->getTotalCaptured($payment_cardconnect_order['cardconnect_order_id']);

				foreach ($payment_cardconnect_order['transactions'] as &$transaction) {
					switch ($transaction['type']) {
						case 'payment':
							$transaction['type'] = 'Payment';
							break;
						case 'auth':
							$transaction['type'] = 'Authorize';
							break;
						case 'refund':
							$transaction['type'] = 'Refund';
							break;
						case 'void':
							$transaction['type'] = 'Void';
							break;
						default:
							$transaction['type'] = 'Payment';
					}

					$transaction['amount'] = $this->currency->format($transaction['amount'], $payment_cardconnect_order['currency_code'], false, true);

					if ($transaction['status'] == 'Y') {
						$transaction['status'] = 'Accepted';
					} elseif ($transaction['status'] == 'N') {
						$transaction['status'] = 'Rejected';
					}

					$transaction['date_modified'] = date($this->language->get('datetime_format'), strtotime($transaction['date_modified']));
					$transaction['date_added'] = date($this->language->get('datetime_format'), strtotime($transaction['date_added']));
				}

				$data['payment_cardconnect_order'] = $payment_cardconnect_order;
				$data['order_id'] = (int)$this->request->get['order_id'];
				$data['user_token'] = $this->session->data['user_token'];

				return $this->load->view('extension/payment/cardconnect_order', $data);
			} else {
				return '';
			}
		} else {
			return '';
		}
	}

	/**
	 * Inquire
	 *
	 * @return void
	 */
	public function inquire(): void {
		$this->load->language('extension/payment/cardconnect');

		$json = [];

		if ($this->config->get('payment_cardconnect_status')) {
			if (isset($this->request->post['order_id']) && isset($this->request->post['retref'])) {
				// Cardconnect
				$this->load->model('extension/payment/cardconnect');

				$payment_cardconnect_order = $this->model_extension_payment_cardconnect->getOrder($this->request->post['order_id']);

				if ($payment_cardconnect_order) {
					$inquire_response = $this->model_extension_payment_cardconnect->inquire($payment_cardconnect_order, $this->request->post['retref']);

					if (isset($inquire_response['respstat']) && $inquire_response['respstat'] == 'C') {
						$json['error'] = $inquire_response['resptext'];
					} else {
						$this->model_extension_payment_cardconnect->updateTransactionStatusByRetref($this->request->post['retref'], $inquire_response['setlstat']);

						$json['date_modified'] = date($this->language->get('datetime_format'));
						$json['status'] = $inquire_response['setlstat'];
						$json['success'] = $this->language->get('text_inquire_success');
					}
				} else {
					$json['error'] = $this->language->get('error_no_order');
				}
			} else {
				$json['error'] = $this->language->get('error_data_missing');
			}
		} else {
			$json['error'] = $this->language->get('error_not_enabled');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Capture
	 *
	 * @return void
	 */
	public function capture(): void {
		$this->load->language('extension/payment/cardconnect');

		$json = [];

		if ($this->config->get('payment_cardconnect_status')) {
			if (isset($this->request->post['order_id']) && isset($this->request->post['amount'])) {
				if ($this->request->post['amount'] > 0) {
					// Cardconnect
					$this->load->model('extension/payment/cardconnect');

					$payment_cardconnect_order = $this->model_extension_payment_cardconnect->getOrder($this->request->post['order_id']);

					if ($payment_cardconnect_order) {
						$capture_response = $this->model_extension_payment_cardconnect->capture($payment_cardconnect_order, $this->request->post['amount']);

						if (!isset($capture_response['retref'])) {
							$json['error'] = $this->language->get('error_invalid_response');
						} elseif (isset($capture_response['respstat']) && $capture_response['respstat'] == 'C') {
							$json['error'] = $capture_response['resptext'];
						} else {
							$this->model_extension_payment_cardconnect->addTransaction($payment_cardconnect_order['cardconnect_order_id'], 'payment', $capture_response['retref'], $this->request->post['amount'], $capture_response['setlstat']);

							$total_captured = $this->model_extension_payment_cardconnect->getTotalCaptured($payment_cardconnect_order['cardconnect_order_id']);

							$json['retref'] = $capture_response['retref'];
							$json['amount'] = $this->currency->format($this->request->post['amount'], $payment_cardconnect_order['currency_code'], false, true);
							$json['status'] = $capture_response['setlstat'];
							$json['date_modified'] = date($this->language->get('datetime_format'));
							$json['date_added'] = date($this->language->get('datetime_format'));
							$json['total_captured'] = $this->currency->format($total_captured, $payment_cardconnect_order['currency_code'], false, true);

							$json['success'] = $this->language->get('text_capture_success');
						}
					} else {
						$json['error'] = $this->language->get('error_no_order');
					}
				} else {
					$json['error'] = $this->language->get('error_amount_zero');
				}
			} else {
				$json['error'] = $this->language->get('error_data_missing');
			}
		} else {
			$json['error'] = $this->language->get('error_not_enabled');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Refund
	 *
	 * @return void
	 */
	public function refund(): void {
		$this->load->language('extension/payment/cardconnect');

		$json = [];

		if ($this->config->get('payment_cardconnect_status')) {
			if (isset($this->request->post['order_id']) && isset($this->request->post['amount'])) {
				if ($this->request->post['amount'] > 0) {
					// Cardconnect
					$this->load->model('extension/payment/cardconnect');

					$payment_cardconnect_order = $this->model_extension_payment_cardconnect->getOrder($this->request->post['order_id']);

					if ($payment_cardconnect_order) {
						$refund_response = $this->model_extension_payment_cardconnect->refund($payment_cardconnect_order, $this->request->post['amount']);

						if (!isset($refund_response['retref'])) {
							$json['error'] = $this->language->get('error_invalid_response');
						} elseif (isset($refund_response['respstat']) && $refund_response['respstat'] == 'C') {
							$json['error'] = $refund_response['resptext'];
						} else {
							$this->model_extension_payment_cardconnect->addTransaction($payment_cardconnect_order['cardconnect_order_id'], 'refund', $refund_response['retref'], $this->request->post['amount'] * -1, $refund_response['resptext']);

							$total_captured = $this->model_extension_payment_cardconnect->getTotalCaptured($payment_cardconnect_order['cardconnect_order_id']);

							$json['retref'] = $refund_response['retref'];
							$json['amount'] = $this->currency->format($this->request->post['amount'] * -1, $payment_cardconnect_order['currency_code'], false, true);
							$json['status'] = $refund_response['resptext'];
							$json['date_modified'] = date($this->language->get('datetime_format'));
							$json['date_added'] = date($this->language->get('datetime_format'));
							$json['total_captured'] = $this->currency->format($total_captured, $payment_cardconnect_order['currency_code'], false, true);

							$json['success'] = $this->language->get('text_refund_success');
						}
					} else {
						$json['error'] = $this->language->get('error_no_order');
					}
				} else {
					$json['error'] = $this->language->get('error_amount_zero');
				}
			} else {
				$json['error'] = $this->language->get('error_data_missing');
			}
		} else {
			$json['error'] = $this->language->get('error_not_enabled');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Void
	 *
	 * @return void
	 */
	public function void(): void {
		$this->load->language('extension/payment/cardconnect');

		$json = [];

		if ($this->config->get('payment_cardconnect_status')) {
			if (isset($this->request->post['order_id']) && isset($this->request->post['retref'])) {
				// Cardconnect
				$this->load->model('extension/payment/cardconnect');

				$payment_cardconnect_order = $this->model_extension_payment_cardconnect->getOrder($this->request->post['order_id']);

				if ($payment_cardconnect_order) {
					$void_response = $this->model_extension_payment_cardconnect->void($payment_cardconnect_order, $this->request->post['retref']);

					if (!isset($void_response['authcode']) || $void_response['authcode'] != 'REVERS') {
						$json['error'] = $void_response['resptext'];
					} else {
						$json['retref'] = $void_response['retref'];
						$json['amount'] = $this->currency->format(0.00, $payment_cardconnect_order['currency_code'], false, true);
						$json['status'] = $void_response['resptext'];
						$json['date_modified'] = date($this->language->get('datetime_format'));
						$json['date_added'] = date($this->language->get('datetime_format'));

						$json['success'] = $this->language->get('text_void_success');
					}
				} else {
					$json['error'] = $this->language->get('error_no_order');
				}
			} else {
				$json['error'] = $this->language->get('error_data_missing');
			}
		} else {
			$json['error'] = $this->language->get('error_not_enabled');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/cardconnect')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_cardconnect_merchant_id']) {
			$this->error['payment_cardconnect_merchant_id'] = $this->language->get('error_merchant_id');
		}

		if (!$this->request->post['payment_cardconnect_api_username']) {
			$this->error['cardconnect_api_username'] = $this->language->get('error_api_username');
		}

		if (!$this->request->post['payment_cardconnect_api_password']) {
			$this->error['cardconnect_api_password'] = $this->language->get('error_api_password');
		}

		if (!$this->request->post['payment_cardconnect_token']) {
			$this->error['cardconnect_token'] = $this->language->get('error_token');
		}

		if (!$this->request->post['payment_cardconnect_site']) {
			$this->error['cardconnect_site'] = $this->language->get('error_site');
		}

		return !$this->error;
	}
}
