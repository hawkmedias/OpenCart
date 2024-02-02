<?php
/**
 * Class Sagepay Direct
 *
 * @package Admin\Controller\Extension\Payment
 */
class ControllerExtensionPaymentSagepayDirect extends Controller {
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
		$this->load->language('extension/payment/sagepay_direct');

		$this->document->setTitle($this->language->get('heading_title'));

		// Settings
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_sagepay_direct', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['vendor'])) {
			$data['error_vendor'] = $this->error['vendor'];
		} else {
			$data['error_vendor'] = '';
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
			'href' => $this->url->link('extension/payment/sagepay_direct', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['action'] = $this->url->link('extension/payment/sagepay_direct', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_sagepay_direct_vendor'])) {
			$data['payment_sagepay_direct_vendor'] = $this->request->post['payment_sagepay_direct_vendor'];
		} else {
			$data['payment_sagepay_direct_vendor'] = $this->config->get('payment_sagepay_direct_vendor');
		}

		if (isset($this->request->post['payment_sagepay_direct_test'])) {
			$data['payment_sagepay_direct_test'] = $this->request->post['payment_sagepay_direct_test'];
		} else {
			$data['payment_sagepay_direct_test'] = $this->config->get('payment_sagepay_direct_test');
		}

		if (isset($this->request->post['payment_sagepay_direct_transaction'])) {
			$data['payment_sagepay_direct_transaction'] = $this->request->post['payment_sagepay_direct_transaction'];
		} else {
			$data['payment_sagepay_direct_transaction'] = $this->config->get('payment_sagepay_direct_transaction');
		}

		if (isset($this->request->post['payment_sagepay_direct_total'])) {
			$data['payment_sagepay_direct_total'] = $this->request->post['payment_sagepay_direct_total'];
		} else {
			$data['payment_sagepay_direct_total'] = $this->config->get('payment_sagepay_direct_total');
		}

		if (isset($this->request->post['payment_sagepay_direct_card'])) {
			$data['payment_sagepay_direct_card'] = $this->request->post['payment_sagepay_direct_card'];
		} else {
			$data['payment_sagepay_direct_card'] = $this->config->get('payment_sagepay_direct_card');
		}

		if (isset($this->request->post['payment_sagepay_direct_order_status_id'])) {
			$data['payment_sagepay_direct_order_status_id'] = (int)$this->request->post['payment_sagepay_direct_order_status_id'];
		} else {
			$data['payment_sagepay_direct_order_status_id'] = $this->config->get('payment_sagepay_direct_order_status_id');
		}

		// Order Statuses
		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_sagepay_direct_geo_zone_id'])) {
			$data['payment_sagepay_direct_geo_zone_id'] = (int)$this->request->post['payment_sagepay_direct_geo_zone_id'];
		} else {
			$data['payment_sagepay_direct_geo_zone_id'] = $this->config->get('payment_sagepay_direct_geo_zone_id');
		}

		// Geo Zones
		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_sagepay_direct_status'])) {
			$data['payment_sagepay_direct_status'] = $this->request->post['payment_sagepay_direct_status'];
		} else {
			$data['payment_sagepay_direct_status'] = $this->config->get('payment_sagepay_direct_status');
		}

		if (isset($this->request->post['payment_sagepay_direct_debug'])) {
			$data['payment_sagepay_direct_debug'] = $this->request->post['payment_sagepay_direct_debug'];
		} else {
			$data['payment_sagepay_direct_debug'] = $this->config->get('payment_sagepay_direct_debug');
		}

		if (isset($this->request->post['payment_sagepay_direct_sort_order'])) {
			$data['payment_sagepay_direct_sort_order'] = $this->request->post['payment_sagepay_direct_sort_order'];
		} else {
			$data['payment_sagepay_direct_sort_order'] = $this->config->get('payment_sagepay_direct_sort_order');
		}

		if (isset($this->request->post['payment_sagepay_direct_cron_job_token'])) {
			$data['payment_sagepay_direct_cron_job_token'] = $this->request->post['payment_sagepay_direct_cron_job_token'];
		} elseif ($this->config->get('payment_sagepay_direct_cron_job_token')) {
			$data['payment_sagepay_direct_cron_job_token'] = $this->config->get('payment_sagepay_direct_cron_job_token');
		} else {
			$data['payment_sagepay_direct_cron_job_token'] = sha1(uniqid(mt_rand(), 1));
		}

		$data['sagepay_direct_cron_job_url'] = HTTPS_CATALOG . 'index.php?route=extension/payment/sagepay_direct/cron&token=' . $data['payment_sagepay_direct_cron_job_token'];

		if ($this->config->get('payment_sagepay_direct_last_cron_job_run')) {
			$data['payment_sagepay_direct_last_cron_job_run'] = $this->config->get('payment_sagepay_direct_last_cron_job_run');
		} else {
			$data['payment_sagepay_direct_last_cron_job_run'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/sagepay_direct', $data));
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		// Sagepay Direct
		$this->load->model('extension/payment/sagepay_direct');

		$this->model_extension_payment_sagepay_direct->install();
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		// Sagepay Direct
		$this->load->model('extension/payment/sagepay_direct');

		$this->model_extension_payment_sagepay_direct->uninstall();
	}

	/**
	 * Order
	 *
	 * @return string
	 */
	public function order(): string {
		if ($this->config->get('payment_sagepay_direct_status')) {
			// Sagepay Direct
			$this->load->model('extension/payment/sagepay_direct');

			$payment_sagepay_direct_order = $this->model_extension_payment_sagepay_direct->getOrder($this->request->get['order_id']);

			if ($payment_sagepay_direct_order) {
				$this->load->language('extension/payment/sagepay_direct');

				$payment_sagepay_direct_order['total_released'] = $this->model_extension_payment_sagepay_direct->getTotalReleased($payment_sagepay_direct_order['sagepay_direct_order_id']);

				$payment_sagepay_direct_order['total_formatted'] = $this->currency->format($payment_sagepay_direct_order['total'], $payment_sagepay_direct_order['currency_code'], false, false);
				$payment_sagepay_direct_order['total_released_formatted'] = $this->currency->format($payment_sagepay_direct_order['total_released'], $payment_sagepay_direct_order['currency_code'], false, false);

				$data['payment_sagepay_direct_order'] = $payment_sagepay_direct_order;

				$data['auto_settle'] = $payment_sagepay_direct_order['settle_type'];

				$data['order_id'] = (int)$this->request->get['order_id'];

				$data['user_token'] = $this->session->data['user_token'];

				return $this->load->view('extension/payment/sagepay_direct_order', $data);
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
		$this->load->language('extension/payment/sagepay_direct');

		$json = [];

		if (isset($this->request->post['order_id']) && $this->request->post['order_id'] != '') {
			// Sagepay Direct
			$this->load->model('extension/payment/sagepay_direct');

			$payment_sagepay_direct_order = $this->model_extension_payment_sagepay_direct->getOrder($this->request->post['order_id']);

			$void_response = $this->model_extension_payment_sagepay_direct->void($this->request->post['order_id']);

			$this->model_extension_payment_sagepay_direct->logger('Void result', $void_response);

			if ($void_response['Status'] == 'OK') {
				$this->model_extension_payment_sagepay_direct->addTransaction($payment_sagepay_direct_order['sagepay_direct_order_id'], 'void', 0.00);
				$this->model_extension_payment_sagepay_direct->updateVoidStatus($payment_sagepay_direct_order['sagepay_direct_order_id'], 1);

				$json['msg'] = $this->language->get('text_void_ok');
				$json['date_added'] = date('Y-m-d H:i:s');

				$json['error'] = false;
			} else {
				$json['error'] = true;

				$json['msg'] = isset($void_response['StatusDetail']) && $void_response['StatusDetail'] != '' ? sprintf($this->language->get('error_status'), (string)$void_response['StatusDetail']) : $this->language->get('error_void');
			}
		} else {
			$json['error'] = true;

			$json['msg'] = $this->language->get('error_data_missing');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Release
	 *
	 * @return void
	 */
	public function release(): void {
		$this->load->language('extension/payment/sagepay_direct');

		$json = [];

		if (isset($this->request->post['order_id']) && $this->request->post['order_id'] != '' && isset($this->request->post['amount']) && $this->request->post['amount'] > 0) {
			// Sagepay Direct
			$this->load->model('extension/payment/sagepay_direct');

			$payment_sagepay_direct_order = $this->model_extension_payment_sagepay_direct->getOrder($this->request->post['order_id']);

			$release_response = $this->model_extension_payment_sagepay_direct->release($this->request->post['order_id'], $this->request->post['amount']);

			$this->model_extension_payment_sagepay_direct->logger('Release result', $release_response);

			if ($release_response['Status'] == 'OK') {
				$this->model_extension_payment_sagepay_direct->addTransaction($payment_sagepay_direct_order['sagepay_direct_order_id'], 'payment', $this->request->post['amount']);

				$total_released = $this->model_extension_payment_sagepay_direct->getTotalReleased($payment_sagepay_direct_order['sagepay_direct_order_id']);

				if ($total_released >= $payment_sagepay_direct_order['total'] || $payment_sagepay_direct_order['settle_type'] == 0) {
					$this->model_extension_payment_sagepay_direct->updateReleaseStatus($payment_sagepay_direct_order['sagepay_direct_order_id'], 1);

					$release_status = 1;

					$json['msg'] = $this->language->get('text_release_ok_order');
				} else {
					$release_status = 0;

					$json['msg'] = $this->language->get('text_release_ok');
				}

				$json['date_added'] = date('Y-m-d H:i:s');
				$json['amount'] = $this->request->post['amount'];
				$json['release_status'] = $release_status;
				$json['total'] = (float)$total_released;

				$json['error'] = false;
			} else {
				$json['error'] = true;

				$json['msg'] = isset($release_response['StatusDetail']) && $release_response['StatusDetail'] != '' ? sprintf($this->language->get('error_status'), (string)$release_response['StatusDetail']) : $this->language->get('error_release');
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
		$this->load->language('extension/payment/sagepay_direct');

		$json = [];

		if (isset($this->request->post['order_id'])) {
			// Sagepay Direct
			$this->load->model('extension/payment/sagepay_direct');

			$payment_sagepay_direct_order = $this->model_extension_payment_sagepay_direct->getOrder($this->request->post['order_id']);

			$rebate_response = $this->model_extension_payment_sagepay_direct->rebate($this->request->post['order_id'], $this->request->post['amount']);

			$this->model_extension_payment_sagepay_direct->logger('Rebate result', $rebate_response);

			if ($rebate_response['Status'] == 'OK') {
				$this->model_extension_payment_sagepay_direct->addTransaction($payment_sagepay_direct_order['sagepay_direct_order_id'], 'rebate', $this->request->post['amount'] * -1);

				$total_rebated = $this->model_extension_payment_sagepay_direct->getTotalRebated($payment_sagepay_direct_order['sagepay_direct_order_id']);
				$total_released = $this->model_extension_payment_sagepay_direct->getTotalReleased($payment_sagepay_direct_order['sagepay_direct_order_id']);

				if ($total_released <= 0 && $payment_sagepay_direct_order['release_status'] == 1) {
					$this->model_extension_payment_sagepay_direct->updateRebateStatus($payment_sagepay_direct_order['sagepay_direct_order_id'], 1);

					$rebate_status = 1;

					$json['msg'] = $this->language->get('text_rebate_ok_order');
				} else {
					$rebate_status = 0;

					$json['msg'] = $this->language->get('text_rebate_ok');
				}

				$json['date_added'] = date('Y-m-d H:i:s');
				$json['amount'] = $this->request->post['amount'] * -1;
				$json['total_released'] = (float)$total_released;
				$json['total_rebated'] = (float)$total_rebated;
				$json['rebate_status'] = $rebate_status;

				$json['error'] = false;
			} else {
				$json['error'] = true;

				$json['msg'] = isset($rebate_response['StatusDetail']) && $rebate_response['StatusDetail'] != '' ? sprintf($this->language->get('error_status'), (string)$rebate_response['StatusDetail']) : $this->language->get('error_rebate');
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
		if (!$this->user->hasPermission('modify', 'extension/payment/sagepay_direct')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_sagepay_direct_vendor']) {
			$this->error['vendor'] = $this->language->get('error_vendor');
		}

		return !$this->error;
	}
}
