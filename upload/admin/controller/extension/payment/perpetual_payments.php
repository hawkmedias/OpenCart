<?php
/**
 * Class Perpetual Payments
 *
 * @package Admin\Controller\Extension\Payment
 */
class ControllerExtensionPaymentPerpetualPayments extends Controller {
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
		$this->load->language('extension/payment/perpetual_payments');

		$this->document->setTitle($this->language->get('heading_title'));

		// Settings
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_perpetual_payments', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['auth_id'])) {
			$data['error_auth_id'] = $this->error['auth_id'];
		} else {
			$data['error_auth_id'] = '';
		}

		if (isset($this->error['auth_pass'])) {
			$data['error_auth_pass'] = $this->error['auth_pass'];
		} else {
			$data['error_auth_pass'] = '';
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
			'href' => $this->url->link('extension/payment/perpetual_payments', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['action'] = $this->url->link('extension/payment/perpetual_payments', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_perpetual_payments_auth_id'])) {
			$data['payment_perpetual_payments_auth_id'] = $this->request->post['payment_perpetual_payments_auth_id'];
		} else {
			$data['payment_perpetual_payments_auth_id'] = $this->config->get('payment_perpetual_payments_auth_id');
		}

		if (isset($this->request->post['payment_perpetual_payments_auth_pass'])) {
			$data['payment_perpetual_payments_auth_pass'] = $this->request->post['payment_perpetual_payments_auth_pass'];
		} else {
			$data['payment_perpetual_payments_auth_pass'] = $this->config->get('payment_perpetual_payments_auth_pass');
		}

		if (isset($this->request->post['payment_perpetual_payments_test'])) {
			$data['payment_perpetual_payments_test'] = $this->request->post['payment_perpetual_payments_test'];
		} else {
			$data['payment_perpetual_payments_test'] = $this->config->get('payment_perpetual_payments_test');
		}

		if (isset($this->request->post['payment_perpetual_payments_total'])) {
			$data['payment_perpetual_payments_total'] = $this->request->post['payment_perpetual_payments_total'];
		} else {
			$data['payment_perpetual_payments_total'] = $this->config->get('payment_perpetual_payments_total');
		}

		if (isset($this->request->post['payment_perpetual_payments_order_status_id'])) {
			$data['payment_perpetual_payments_order_status_id'] = (int)$this->request->post['payment_perpetual_payments_order_status_id'];
		} else {
			$data['payment_perpetual_payments_order_status_id'] = $this->config->get('payment_perpetual_payments_order_status_id');
		}

		// Order Statuses
		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_perpetual_payments_geo_zone_id'])) {
			$data['payment_perpetual_payments_geo_zone_id'] = (int)$this->request->post['payment_perpetual_payments_geo_zone_id'];
		} else {
			$data['payment_perpetual_payments_geo_zone_id'] = $this->config->get('payment_perpetual_payments_geo_zone_id');
		}

		// Geo Zones
		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_perpetual_payments_status'])) {
			$data['payment_perpetual_payments_status'] = (int)$this->request->post['payment_perpetual_payments_status'];
		} else {
			$data['payment_perpetual_payments_status'] = $this->config->get('payment_perpetual_payments_status');
		}

		if (isset($this->request->post['payment_perpetual_payments_sort_order'])) {
			$data['payment_perpetual_payments_sort_order'] = (int)$this->request->post['payment_perpetual_payments_sort_order'];
		} else {
			$data['payment_perpetual_payments_sort_order'] = $this->config->get('payment_perpetual_payments_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/perpetual_payments', $data));
	}

	/**
	 * Validate
	 *
	 * @return bool
	 */
	protected function validate(): bool {
		if (!$this->user->hasPermission('modify', 'extension/payment/perpetual_payments')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_perpetual_payments_auth_id']) {
			$this->error['auth_id'] = $this->language->get('error_auth_id');
		}

		if (!$this->request->post['payment_perpetual_payments_auth_pass']) {
			$this->error['auth_pass'] = $this->language->get('error_auth_pass');
		}

		return !$this->error;
	}
}
