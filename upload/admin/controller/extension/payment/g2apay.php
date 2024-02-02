<?php
/**
 * Class G2apay
 *
 * @package Admin\Controller\Extension\Payment
 */
class ControllerExtensionPaymentG2APay extends Controller {
	/**
	 * @var array<string, string>
	 */
	private array $error = [];

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/payment/g2apay');

		$this->document->setTitle($this->language->get('heading_title'));

		// Settings
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_g2apay', $this->request->post);

			$this->session->data['complete'] = $this->language->get('text_complete');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['username'])) {
			$data['error_username'] = $this->error['username'];
		} else {
			$data['error_username'] = '';
		}

		if (isset($this->error['secret'])) {
			$data['error_secret'] = $this->error['secret'];
		} else {
			$data['error_secret'] = '';
		}

		if (isset($this->error['api_hash'])) {
			$data['error_api_hash'] = $this->error['api_hash'];
		} else {
			$data['error_api_hash'] = '';
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
			'href' => $this->url->link('extension/payment/g2apay', 'user_token=' . $this->session->data['user_token'], true)
		];

		// Order Statuses
		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		// Geo Zones
		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data['action'] = $this->url->link('extension/payment/g2apay', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_g2apay_order_status_id'])) {
			$data['payment_g2apay_order_status_id'] = (int)$this->request->post['payment_g2apay_order_status_id'];
		} else {
			$data['payment_g2apay_order_status_id'] = $this->config->get('payment_g2apay_order_status_id');
		}

		if (isset($this->request->post['payment_g2apay_complete_status_id'])) {
			$data['payment_g2apay_complete_status_id'] = (int)$this->request->post['payment_g2apay_complete_status_id'];
		} else {
			$data['payment_g2apay_complete_status_id'] = $this->config->get('payment_g2apay_complete_status_id');
		}

		if (isset($this->request->post['payment_g2apay_rejected_status_id'])) {
			$data['payment_g2apay_rejected_status_id'] = (int)$this->request->post['payment_g2apay_rejected_status_id'];
		} else {
			$data['payment_g2apay_rejected_status_id'] = $this->config->get('payment_g2apay_rejected_status_id');
		}

		if (isset($this->request->post['payment_g2apay_cancelled_status_id'])) {
			$data['payment_g2apay_cancelled_status_id'] = (int)$this->request->post['payment_g2apay_cancelled_status_id'];
		} else {
			$data['payment_g2apay_cancelled_status_id'] = $this->config->get('payment_g2apay_cancelled_status_id');
		}

		if (isset($this->request->post['payment_g2apay_pending_status_id'])) {
			$data['payment_g2apay_pending_status_id'] = (int)$this->request->post['payment_g2apay_pending_status_id'];
		} else {
			$data['payment_g2apay_pending_status_id'] = $this->config->get('payment_g2apay_pending_status_id');
		}

		if (isset($this->request->post['payment_g2apay_refunded_status_id'])) {
			$data['payment_g2apay_refunded_status_id'] = (int)$this->request->post['payment_g2apay_refunded_status_id'];
		} else {
			$data['payment_g2apay_refunded_status_id'] = $this->config->get('payment_g2apay_refunded_status_id');
		}

		if (isset($this->request->post['payment_g2apay_partially_refunded_status_id'])) {
			$data['payment_g2apay_partially_refunded_status_id'] = (int)$this->request->post['payment_g2apay_partially_refunded_status_id'];
		} else {
			$data['payment_g2apay_partially_refunded_status_id'] = $this->config->get('payment_g2apay_partially_refunded_status_id');
		}

		if (isset($this->request->post['payment_g2apay_username'])) {
			$data['payment_g2apay_username'] = $this->request->post['payment_g2apay_username'];
		} else {
			$data['payment_g2apay_username'] = $this->config->get('payment_g2apay_username');
		}

		if (isset($this->request->post['payment_g2apay_secret'])) {
			$data['payment_g2apay_secret'] = $this->request->post['payment_g2apay_secret'];
		} else {
			$data['payment_g2apay_secret'] = $this->config->get('payment_g2apay_secret');
		}

		if (isset($this->request->post['payment_g2apay_api_hash'])) {
			$data['payment_g2apay_api_hash'] = $this->request->post['payment_g2apay_api_hash'];
		} else {
			$data['payment_g2apay_api_hash'] = $this->config->get('payment_g2apay_api_hash');
		}

		if (isset($this->request->post['payment_g2apay_environment'])) {
			$data['payment_g2apay_environment'] = $this->request->post['payment_g2apay_environment'];
		} else {
			$data['payment_g2apay_environment'] = $this->config->get('payment_g2apay_environment');
		}

		if (isset($this->request->post['payment_g2apay_total'])) {
			$data['payment_g2apay_total'] = $this->request->post['payment_g2apay_total'];
		} else {
			$data['payment_g2apay_total'] = $this->config->get('payment_g2apay_total');
		}

		if (isset($this->request->post['payment_g2apay_secret_token'])) {
			$data['payment_g2apay_secret_token'] = $this->request->post['payment_g2apay_secret_token'];
		} elseif ($this->config->get('payment_g2apay_secret_token')) {
			$data['payment_g2apay_secret_token'] = $this->config->get('payment_g2apay_secret_token');
		} else {
			$data['payment_g2apay_secret_token'] = sha1(uniqid(mt_rand(), 1));
		}

		$data['g2apay_ipn_url'] = HTTPS_CATALOG . 'index.php?route=extension/payment/g2apay/ipn&token=' . $data['payment_g2apay_secret_token'];

		if (isset($this->request->post['payment_g2apay_geo_zone_id'])) {
			$data['payment_g2apay_geo_zone_id'] = (int)$this->request->post['payment_g2apay_geo_zone_id'];
		} else {
			$data['payment_g2apay_geo_zone_id'] = $this->config->get('payment_g2apay_geo_zone_id');
		}

		if (isset($this->request->post['payment_g2apay_status'])) {
			$data['payment_g2apay_status'] = (int)$this->request->post['payment_g2apay_status'];
		} else {
			$data['payment_g2apay_status'] = $this->config->get('payment_g2apay_status');
		}

		if (isset($this->request->post['payment_g2apay_debug'])) {
			$data['payment_g2apay_debug'] = (int)$this->request->post['payment_g2apay_debug'];
		} else {
			$data['payment_g2apay_debug'] = $this->config->get('payment_g2apay_debug');
		}

		if (isset($this->request->post['payment_g2apay_sort_order'])) {
			$data['payment_g2apay_sort_order'] = (int)$this->request->post['payment_g2apay_sort_order'];
		} else {
			$data['payment_g2apay_sort_order'] = $this->config->get('payment_g2apay_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/g2apay', $data));
	}

	/**
	 * Order
	 *
	 * @return string
	 */
	public function order(): string {
		if ($this->config->get('payment_g2apay_status')) {
			// G2apay
			$this->load->model('extension/payment/g2apay');

			$payment_g2apay_order = $this->model_extension_payment_g2apay->getOrder($this->request->get['order_id']);

			if ($payment_g2apay_order) {
				$this->load->language('extension/payment/g2apay');

				$payment_g2apay_order['total_released_formatted'] = $this->currency->format($payment_g2apay_order['total_released'], $payment_g2apay_order['currency_code'], false);
				$payment_g2apay_order['total_formatted'] = $this->currency->format($payment_g2apay_order['total'], $payment_g2apay_order['currency_code'], false);
				$payment_g2apay_order['total_released'] = $this->model_extension_payment_g2apay->getTotalReleased($payment_g2apay_order['g2apay_order_id']);

				$data['g2apay_order'] = $payment_g2apay_order;

				$data['order_id'] = (int)$this->request->get['order_id'];

				$data['user_token'] = $this->session->data['user_token'];

				return $this->load->view('extension/payment/g2apay_order', $data);
			} else {
				return '';
			}
		} else {
			return '';
		}
	}

	/**
	 * Refund
	 *
	 * @return void
	 */
	public function refund(): void {
		$this->load->language('extension/payment/g2apay');

		$json = [];

		if (isset($this->request->post['order_id'])) {
			// G2apay
			$this->load->model('extension/payment/g2apay');

			$payment_g2apay_order = $this->model_extension_payment_g2apay->getOrder($this->request->post['order_id']);

			$refund_response = $this->model_extension_payment_g2apay->refund($payment_g2apay_order, $this->request->post['amount']);

			$this->model_extension_payment_g2apay->logger($refund_response);

			if ($refund_response && $refund_response['status']) {
				$this->model_extension_payment_g2apay->addTransaction($payment_g2apay_order['g2apay_order_id'], 'refund', $this->request->post['amount'] * -1);

				$total_refunded = $this->model_extension_payment_g2apay->getTotalRefunded($payment_g2apay_order['g2apay_order_id']);
				$total_released = $this->model_extension_payment_g2apay->getTotalReleased($payment_g2apay_order['g2apay_order_id']);

				if ($total_released <= 0 && $payment_g2apay_order['release_status'] == 1) {
					$this->model_extension_payment_g2apay->updateRefundStatus($payment_g2apay_order['g2apay_order_id'], 1);

					$refund_status = 1;

					$json['msg'] = $this->language->get('text_refund_ok_order');
				} else {
					$refund_status = 0;

					$json['msg'] = $this->language->get('text_refund_ok');
				}

				$json['date_added'] = date('Y-m-d H:i:s');
				$json['amount'] = $this->currency->format(($this->request->post['amount'] * -1), $payment_g2apay_order['currency_code'], false);
				$json['total_released'] = (float)$total_released;
				$json['total_refunded'] = (float)$total_refunded;
				$json['refund_status'] = $refund_status;

				$json['error'] = false;
			} else {
				$json['error'] = true;

				$json['msg'] = 'Unable to refund: ' . $refund_response['status'];
			}
		} else {
			$json['error'] = true;

			$json['msg'] = $this->language->get('error_data_missing');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		// G2apay
		$this->load->model('extension/payment/g2apay');

		$this->model_extension_payment_g2apay->install();
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		// G2apay
		$this->load->model('extension/payment/g2apay');

		$this->model_extension_payment_g2apay->uninstall();
	}

	/**
	 * Validate
	 *
	 * @return bool
	 */
	protected function validate(): bool {
		if (!$this->user->hasPermission('modify', 'extension/payment/g2apay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_g2apay_username']) {
			$this->error['username'] = $this->language->get('error_username');
		}

		if (!$this->request->post['payment_g2apay_secret']) {
			$this->error['secret'] = $this->language->get('error_secret');
		}

		if (!$this->request->post['payment_g2apay_api_hash']) {
			$this->error['api_hash'] = $this->language->get('error_api_hash');
		}

		return !$this->error;
	}
}
