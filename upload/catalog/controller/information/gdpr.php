<?php
/**
 * Class Gdpr
 *
 * @package Catalog\Controller\Information
 */
class ControllerInformationGdpr extends Controller {
	/**
	 * Index
	 *
	 * @return \Action|object|null
	 */
	public function index(): ?object {
		// Information
		$this->load->model('catalog/information');

		$information_info = $this->model_catalog_information->getInformation($this->config->get('config_gdpr_id'));

		if ($information_info) {
			$this->load->language('information/gdpr');

			$this->document->setTitle($this->language->get('heading_title'));

			$data['breadcrumbs'] = [];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('information/gdpr')
			];

			$data['action'] = $this->url->link('information/gdpr/action');
			$data['title'] = $information_info['title'];
			$data['gdpr'] = $this->url->link('information/information' . '&information_id=' . $information_info['information_id']);
			$data['email'] = $this->customer->getEmail();
			$data['store'] = $this->config->get('config_name');
			$data['limit'] = $this->config->get('config_gdpr_limit');
			$data['cancel'] = $this->url->link('account/account');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('information/gdpr', $data));
		} else {
			return new \Action('error/not_found');
		}

		return null;
	}

	/**
	 * Action
	 *
	 *  Action Statuses
	 *
	 *	EXPORT
	 *
	 *  unverified = 0
	 *	pending    = 1
	 *	complete   = 3
	 *
	 *	REMOVE
	 *
	 *  unverified = 0
	 *	pending    = 1
	 *	processing = 2
	 *	delete     = 3
	 *
	 *	DENY
	 *
	 *  unverified = 0
	 *	pending    = 1
	 *	processing = 2
	 *	denied     = -1
	 *
	 * @return void
	 */
	public function action(): void {
		$this->load->language('information/gdpr');

		$json = [];

		if (isset($this->request->post['email'])) {
			$email = $this->request->post['email'];
		} else {
			$email = '';
		}

		if (isset($this->request->post['action'])) {
			$action = $this->request->post['action'];
		} else {
			$action = '';
		}

		// Validate E-Mail
		if ((oc_strlen($email) > 96) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$json['error']['email'] = $this->language->get('error_email');
		}

		// Validate Action
		$allowed = [
			'export',
			'remove'
		];

		if (!in_array($action, $allowed)) {
			$json['error']['action'] = $this->language->get('error_action');
		}

		if (!$json) {
			// Added additional check so people are not spamming requests
			$status = true;

			// GDPR
			$this->load->model('account/gdpr');

			$results = $this->model_account_gdpr->getGdprsByEmail($email);

			foreach ($results as $result) {
				if ($result['action'] == $action) {
					$status = false;
					break;
				}
			}

			if ($status) {
				$this->model_account_gdpr->addGdpr(oc_token(32), $email, $action);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Success
	 *
	 * @return \Action|object|null
	 */
	public function success(): ?object {
		if (isset($this->request->get['code'])) {
			$code = (string)$this->request->get['code'];
		} else {
			$code = '';
		}

		// GDPR
		$this->load->model('account/gdpr');

		$gdpr_info = $this->model_account_gdpr->getGdprByCode($code);

		if ($gdpr_info) {
			$this->load->language('information/gdpr_success');

			$this->document->setTitle($this->language->get('heading_title'));

			$data['breadcrumbs'] = [];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_account'),
				'href' => $this->url->link('information/gdpr')
			];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('information/gdpr/success')
			];

			if ($gdpr_info['status'] == 0) {
				$this->model_account_gdpr->editStatus($gdpr_info['gdpr_id'], 1);
			}

			if ($gdpr_info['action'] == 'export') {
				$data['text_message'] = $this->language->get('text_export');
			} else {
				$data['text_message'] = sprintf($this->language->get('text_remove'), $this->config->get('config_gdpr_limit'));
			}

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('common/success', $data));
		} else {
			return new \Action('error/not_found');
		}

		return null;
	}
}
