<?php
/**
 * Class Realex Remote
 *
 * @package Admin\Controller\Extension\Payment
 */
class ControllerExtensionPaymentRealexRemote extends Controller {
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
		$this->load->language('extension/payment/realex_remote');

		$this->document->setTitle($this->language->get('heading_title'));

		// Settings
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_realex_remote', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['error_merchant_id'])) {
			$data['error_merchant_id'] = $this->error['error_merchant_id'];
		} else {
			$data['error_merchant_id'] = '';
		}

		if (isset($this->error['error_secret'])) {
			$data['error_secret'] = $this->error['error_secret'];
		} else {
			$data['error_secret'] = '';
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
			'href' => $this->url->link('extension/payment/realex_remote', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['action'] = $this->url->link('extension/payment/realex_remote', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_realex_remote_merchant_id'])) {
			$data['payment_realex_remote_merchant_id'] = $this->request->post['payment_realex_remote_merchant_id'];
		} else {
			$data['payment_realex_remote_merchant_id'] = $this->config->get('payment_realex_remote_merchant_id');
		}

		if (isset($this->request->post['payment_realex_remote_secret'])) {
			$data['payment_realex_remote_secret'] = $this->request->post['payment_realex_remote_secret'];
		} else {
			$data['payment_realex_remote_secret'] = $this->config->get('payment_realex_remote_secret');
		}

		if (isset($this->request->post['payment_realex_remote_rebate_password'])) {
			$data['payment_realex_remote_rebate_password'] = $this->request->post['payment_realex_remote_rebate_password'];
		} else {
			$data['payment_realex_remote_rebate_password'] = $this->config->get('payment_realex_remote_rebate_password');
		}

		if (isset($this->request->post['payment_realex_remote_geo_zone_id'])) {
			$data['payment_realex_remote_geo_zone_id'] = (int)$this->request->post['payment_realex_remote_geo_zone_id'];
		} else {
			$data['payment_realex_remote_geo_zone_id'] = $this->config->get('payment_realex_remote_geo_zone_id');
		}

		// Geo Zones
		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_realex_remote_total'])) {
			$data['payment_realex_remote_total'] = $this->request->post['payment_realex_remote_total'];
		} else {
			$data['payment_realex_remote_total'] = $this->config->get('payment_realex_remote_total');
		}

		if (isset($this->request->post['payment_realex_remote_sort_order'])) {
			$data['payment_realex_remote_sort_order'] = (int)$this->request->post['payment_realex_remote_sort_order'];
		} else {
			$data['payment_realex_remote_sort_order'] = $this->config->get('payment_realex_remote_sort_order');
		}

		if (isset($this->request->post['payment_realex_remote_status'])) {
			$data['payment_realex_remote_status'] = (int)$this->request->post['payment_realex_remote_status'];
		} else {
			$data['payment_realex_remote_status'] = $this->config->get('payment_realex_remote_status');
		}

		if (isset($this->request->post['payment_realex_remote_card_data_status'])) {
			$data['payment_realex_remote_card_data_status'] = (int)$this->request->post['payment_realex_remote_card_data_status'];
		} else {
			$data['payment_realex_remote_card_data_status'] = $this->config->get('payment_realex_remote_card_data_status');
		}

		if (isset($this->request->post['payment_realex_remote_debug'])) {
			$data['payment_realex_remote_debug'] = (int)$this->request->post['payment_realex_remote_debug'];
		} else {
			$data['payment_realex_remote_debug'] = $this->config->get('payment_realex_remote_debug');
		}

		if (isset($this->request->post['payment_realex_remote_account'])) {
			$data['payment_realex_remote_account'] = $this->request->post['payment_realex_remote_account'];
		} else {
			$data['payment_realex_remote_account'] = $this->config->get('payment_realex_remote_account');
		}

		if (isset($this->request->post['payment_realex_remote_auto_settle'])) {
			$data['payment_realex_remote_auto_settle'] = $this->request->post['payment_realex_remote_auto_settle'];
		} else {
			$data['payment_realex_remote_auto_settle'] = $this->config->get('payment_realex_remote_auto_settle');
		}

		if (isset($this->request->post['payment_realex_remote_tss_check'])) {
			$data['payment_realex_remote_tss_check'] = $this->request->post['payment_realex_remote_tss_check'];
		} else {
			$data['payment_realex_remote_tss_check'] = $this->config->get('payment_realex_remote_tss_check');
		}

		if (isset($this->request->post['payment_realex_remote_3d'])) {
			$data['payment_realex_remote_3d'] = $this->request->post['payment_realex_remote_3d'];
		} else {
			$data['payment_realex_remote_3d'] = $this->config->get('payment_realex_remote_3d');
		}

		if (isset($this->request->post['payment_realex_remote_liability'])) {
			$data['payment_realex_remote_liability'] = $this->request->post['payment_realex_remote_liability'];
		} else {
			$data['payment_realex_remote_liability'] = $this->config->get('payment_realex_remote_liability');
		}

		if (isset($this->request->post['payment_realex_remote_order_status_success_settled_id'])) {
			$data['payment_realex_remote_order_status_success_settled_id'] = (int)$this->request->post['payment_realex_remote_order_status_success_settled_id'];
		} else {
			$data['payment_realex_remote_order_status_success_settled_id'] = $this->config->get('payment_realex_remote_order_status_success_settled_id');
		}

		if (isset($this->request->post['payment_realex_remote_order_status_success_unsettled_id'])) {
			$data['payment_realex_remote_order_status_success_unsettled_id'] = (int)$this->request->post['payment_realex_remote_order_status_success_unsettled_id'];
		} else {
			$data['payment_realex_remote_order_status_success_unsettled_id'] = $this->config->get('payment_realex_remote_order_status_success_unsettled_id');
		}

		if (isset($this->request->post['payment_realex_remote_order_status_decline_id'])) {
			$data['payment_realex_remote_order_status_decline_id'] = (int)$this->request->post['payment_realex_remote_order_status_decline_id'];
		} else {
			$data['payment_realex_remote_order_status_decline_id'] = $this->config->get('payment_realex_remote_order_status_decline_id');
		}

		if (isset($this->request->post['payment_realex_remote_order_status_decline_pending_id'])) {
			$data['payment_realex_remote_order_status_decline_pending_id'] = (int)$this->request->post['payment_realex_remote_order_status_decline_pending_id'];
		} else {
			$data['payment_realex_remote_order_status_decline_pending_id'] = $this->config->get('payment_realex_remote_order_status_decline_pending_id');
		}

		if (isset($this->request->post['payment_realex_remote_order_status_decline_stolen_id'])) {
			$data['payment_realex_remote_order_status_decline_stolen_id'] = (int)$this->request->post['payment_realex_remote_order_status_decline_stolen_id'];
		} else {
			$data['payment_realex_remote_order_status_decline_stolen_id'] = $this->config->get('payment_realex_remote_order_status_decline_stolen_id');
		}

		if (isset($this->request->post['payment_realex_remote_order_status_decline_bank_id'])) {
			$data['payment_realex_remote_order_status_decline_bank_id'] = (int)$this->request->post['payment_realex_remote_order_status_decline_bank_id'];
		} else {
			$data['payment_realex_remote_order_status_decline_bank_id'] = $this->config->get('payment_realex_remote_order_status_decline_bank_id');
		}

		// Order Statuses
		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/realex_remote', $data));
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		// Realex Remote
		$this->load->model('extension/payment/realex_remote');

		$this->model_extension_payment_realex_remote->install();
	}

	/**
	 * Order
	 *
	 * @return string
	 */
	public function order(): string {
		if ($this->config->get('payment_realex_remote_status')) {
			// Realex Remote
			$this->load->model('extension/payment/realex_remote');

			$realex_order = $this->model_extension_payment_realex_remote->getOrder($this->request->get['order_id']);

			if ($realex_order) {
				$this->load->language('extension/payment/realex_remote');

				$realex_order['total_captured'] = $this->model_extension_payment_realex_remote->getTotalCaptured($realex_order['realex_remote_order_id']);
				$realex_order['total_formatted'] = $this->currency->format($realex_order['total'], $realex_order['currency_code'], 1, true);
				$realex_order['total_captured_formatted'] = $this->currency->format($realex_order['total_captured'], $realex_order['currency_code'], 1, true);

				$data['realex_order'] = $realex_order;
				$data['auto_settle'] = $realex_order['settle_type'];
				$data['order_id'] = (int)$this->request->get['order_id'];
				$data['user_token'] = $this->session->data['user_token'];

				return $this->load->view('extension/payment/realex_remote_order', $data);
			} else {
				return '';
			}
		} else {
			return '';
		}
	}

	/**
	 * Void
	 *
	 * @return void
	 */
	public function void(): void {
		$this->load->language('extension/payment/realex_remote');

		$json = [];

		if (isset($this->request->post['order_id']) && $this->request->post['order_id'] != '') {
			// Realex Remote
			$this->load->model('extension/payment/realex_remote');

			$realex_order = $this->model_extension_payment_realex_remote->getOrder($this->request->post['order_id']);

			$void_response = $this->model_extension_payment_realex_remote->void($this->request->post['order_id']);

			$this->model_extension_payment_realex_remote->logger('Void result:\r\n' . print_r($void_response, 1));

			if (isset($void_response['result']) && $void_response['result'] == '00') {
				$this->model_extension_payment_realex_remote->addTransaction($realex_order['realex_remote_order_id'], 'void', 0.00);

				$this->model_extension_payment_realex_remote->updateVoidStatus($realex_order['realex_remote_order_id'], 1);

				$json['msg'] = $this->language->get('text_void_ok');
				$json['date_added'] = date('Y-m-d H:i:s');

				$json['error'] = false;
			} else {
				$json['error'] = true;

				$json['msg'] = !empty($void_response['message']) ? sprintf($this->language->get('error_status'), (string)$void_response['message']) : $this->language->get('error_void');
			}
		} else {
			$json['error'] = true;

			$json['msg'] = $this->language->get('error_data_missing');
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
		$this->load->language('extension/payment/realex');

		$json = [];

		if (isset($this->request->post['order_id']) && $this->request->post['order_id'] != '' && isset($this->request->post['amount']) && $this->request->post['amount'] > 0) {
			// Realex Remote
			$this->load->model('extension/payment/realex_remote');

			$realex_order = $this->model_extension_payment_realex_remote->getOrder($this->request->post['order_id']);

			$capture_response = $this->model_extension_payment_realex_remote->capture($this->request->post['order_id'], $this->request->post['amount']);

			$this->model_extension_payment_realex_remote->logger('Settle result:\r\n' . print_r($capture_response, 1));

			if (isset($capture_response['result']) && $capture_response['result'] == '00') {
				$this->model_extension_payment_realex_remote->addTransaction($realex_order['realex_remote_order_id'], 'payment', $this->request->post['amount']);

				$total_captured = $this->model_extension_payment_realex_remote->getTotalCaptured($realex_order['realex_remote_order_id']);

				if ($total_captured >= $realex_order['total'] || $realex_order['settle_type'] == 0) {
					$this->model_extension_payment_realex_remote->updateCaptureStatus($realex_order['realex_remote_order_id'], 1);

					$capture_status = 1;

					$json['msg'] = $this->language->get('text_capture_ok_order');
				} else {
					$capture_status = 0;

					$json['msg'] = $this->language->get('text_capture_ok');
				}

				$this->model_extension_payment_realex_remote->updateForRebate($realex_order['realex_remote_order_id'], (string)$capture_response['pasref'], (string)$capture_response['orderid']);

				$json['date_added'] = date('Y-m-d H:i:s');
				$json['amount'] = (float)$this->request->post['amount'];
				$json['capture_status'] = $capture_status;
				$json['total'] = (float)$total_captured;
				$json['total_formatted'] = $this->currency->format($total_captured, $realex_order['currency_code'], 1, true);

				$json['error'] = false;
			} else {
				$json['error'] = true;

				$json['msg'] = isset($capture_response['message']) ? sprintf($this->language->get('error_status'), (string)$capture_response['message']) : $this->language->get('error_capture');
			}
		} else {
			$json['error'] = true;

			$json['msg'] = $this->language->get('error_data_missing');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Rebate
	 *
	 * @return void
	 */
	public function rebate(): void {
		$this->load->language('extension/payment/realex_remote');

		$json = [];

		if (isset($this->request->post['order_id']) && $this->request->post['order_id'] != '') {
			// Realex Remote
			$this->load->model('extension/payment/realex_remote');

			$realex_order = $this->model_extension_payment_realex_remote->getOrder($this->request->post['order_id']);

			$rebate_response = $this->model_extension_payment_realex_remote->rebate($this->request->post['order_id'], $this->request->post['amount']);

			$this->model_extension_payment_realex_remote->logger('Rebate result:\r\n' . print_r($rebate_response, 1));

			if (isset($rebate_response['result']) && $rebate_response['result'] == '00') {
				$this->model_extension_payment_realex_remote->addTransaction($realex_order['realex_remote_order_id'], 'rebate', $this->request->post['amount'] * -1);

				$total_rebated = $this->model_extension_payment_realex_remote->getTotalRebated($realex_order['realex_remote_order_id']);
				$total_captured = $this->model_extension_payment_realex_remote->getTotalCaptured($realex_order['realex_remote_order_id']);

				if ($total_captured <= 0 && $realex_order['capture_status'] == 1) {
					$this->model_extension_payment_realex_remote->updateRebateStatus($realex_order['realex_remote_order_id'], 1);

					$rebate_status = 1;

					$json['msg'] = $this->language->get('text_rebate_ok_order');
				} else {
					$rebate_status = 0;

					$json['msg'] = $this->language->get('text_rebate_ok');
				}

				$json['date_added'] = date('Y-m-d H:i:s');
				$json['amount'] = $this->request->post['amount'] * -1;
				$json['total_captured'] = (float)$total_captured;
				$json['total_rebated'] = (float)$total_rebated;
				$json['rebate_status'] = $rebate_status;

				$json['error'] = false;
			} else {
				$json['error'] = true;

				$json['msg'] = !empty($rebate_response['message']) ? sprintf($this->language->get('error_status'), (string)$rebate_response['message']) : $this->language->get('error_rebate');
			}
		} else {
			$json['error'] = true;

			$json['msg'] = $this->language->get('error_data_missing');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Validate
	 *
	 * @return bool
	 */
	protected function validate(): bool {
		if (!$this->user->hasPermission('modify', 'extension/payment/realex_remote')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_realex_remote_merchant_id']) {
			$this->error['error_merchant_id'] = $this->language->get('error_merchant_id');
		}

		if (!$this->request->post['payment_realex_remote_secret']) {
			$this->error['error_secret'] = $this->language->get('error_secret');
		}

		return !$this->error;
	}
}
