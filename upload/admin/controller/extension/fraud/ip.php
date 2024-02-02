<?php
/**
 * Class Ip
 *
 * @package Admin\Controller\Extension\Fraud
 */
class ControllerExtensionFraudIp extends Controller {
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
		$this->load->language('extension/fraud/ip');

		$this->document->setTitle($this->language->get('heading_title'));

		// Settings
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('fraud_ip', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=fraud', true));
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=fraud', true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/fraud/ip', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['action'] = $this->url->link('extension/fraud/ip', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=fraud', true);

		if (isset($this->request->post['fraud_ip_order_status_id'])) {
			$data['fraud_ip_order_status_id'] = (int)$this->request->post['fraud_ip_order_status_id'];
		} else {
			$data['fraud_ip_order_status_id'] = $this->config->get('fraud_ip_order_status_id');
		}

		// Order Statuses
		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['fraud_ip_status'])) {
			$data['fraud_ip_status'] = (int)$this->request->post['fraud_ip_status'];
		} else {
			$data['fraud_ip_status'] = $this->config->get('fraud_ip_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/fraud/ip', $data));
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		// Ip
		$this->load->model('extension/fraud/ip');

		$this->model_extension_fraud_ip->install();
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		// Ip
		$this->load->model('extension/fraud/ip');

		$this->model_extension_fraud_ip->uninstall();
	}

	/**
	 * Validate
	 *
	 * @return bool
	 */
	protected function validate(): bool {
		if (!$this->user->hasPermission('modify', 'extension/fraud/ip')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	/**
	 * Ip
	 *
	 * @return void
	 */
	public function ip(): void {
		$this->load->language('extension/fraud/ip');

		// Customers
		$this->load->model('customer/customer');

		// Ip
		$this->load->model('extension/fraud/ip');

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['ips'] = [];

		$results = $this->model_extension_fraud_ip->getIps(($page - 1) * 10, 10);

		foreach ($results as $result) {
			$data['ips'][] = [
				'ip'         => $result['ip'],
				'total'      => $this->model_customer_customer->getTotalCustomersByIp($result['ip']),
				'date_added' => date('d/m/y', strtotime($result['date_added'])),
				'filter_ip'  => $this->url->link('customer/customer', 'user_token=' . $this->session->data['user_token'] . '&filter_ip=' . $result['ip'], true)
			];
		}

		$ip_total = $this->model_extension_fraud_ip->getTotalIps();

		$pagination = new \Pagination();
		$pagination->total = $ip_total;
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('extension/fraud/ip/ip', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($ip_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($ip_total - 10)) ? $ip_total : ((($page - 1) * 10) + 10), $ip_total, ceil($ip_total / 10));

		$this->response->setOutput($this->load->view('extension/fraud/ip_ip', $data));
	}

	/**
	 * Add Ip
	 *
	 * @return void
	 */
	public function addIp(): void {
		$this->load->language('extension/fraud/ip');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/fraud/ip')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			// Ip
			$this->load->model('extension/fraud/ip');

			if (!$this->model_extension_fraud_ip->getTotalIpsByIp($this->request->post['ip'])) {
				$this->model_extension_fraud_ip->addIp($this->request->post['ip']);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Remove Ip
	 *
	 * @return void
	 */
	public function removeIp(): void {
		$this->load->language('extension/fraud/ip');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/fraud/ip')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			// Ip
			$this->load->model('extension/fraud/ip');

			$this->model_extension_fraud_ip->removeIp($this->request->post['ip']);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
