<?php
/**
 * Class Other
 *
 * @package Admin\Controller\Extension\Extension
 */
class ControllerExtensionExtensionOther extends Controller {
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
		$this->load->language('extension/extension/other');

		// Extensions
		$this->load->model('setting/extension');

		$this->getList();
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		$this->load->language('extension/extension/other');

		// Extensions
		$this->load->model('setting/extension');

		if ($this->validate()) {
			$callable = [$this->{'model_setting_extension'}, 'install'];

			if (is_callable($callable)) {
				$this->model_setting_extension->install('other', $this->request->get['extension']);
			}

			// User Groups
			$this->load->model('user/user_group');

			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/other/' . $this->request->get['extension']);
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/other/' . $this->request->get['extension']);

			// Call install method if it exists
			$this->load->controller('extension/other/' . $this->request->get['extension'] . '/install');

			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->getList();
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->load->language('extension/extension/other');

		// Extensions
		$this->load->model('setting/extension');

		if ($this->validate()) {
			$callable = [$this->{'model_setting_extension'}, 'uninstall'];
			
			if (is_callable($callable)) {
				$this->model_setting_extension->uninstall('other', $this->request->get['extension']);
			}

			// Call uninstall method if it exists
			$this->load->controller('extension/other/' . $this->request->get['extension'] . '/uninstall');

			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->getList();
	}

	/**
	 * Get List
	 *
	 * @return void
	 */
	protected function getList(): void {
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

		$extensions = $this->model_setting_extension->getExtensionsByType('other');

		foreach ($extensions as $key => $value) {
			if (!is_file(DIR_APPLICATION . 'controller/extension/other/' . $value . '.php') && !is_file(DIR_APPLICATION . 'controller/other/' . $value . '.php')) {
				$callable = [$this->{'model_setting_extension'}, 'uninstall'];

				if (is_callable($callable)) {
					$this->model_setting_extension->uninstall('other', $value);
				}

				unset($extensions[$key]);
			}
		}

		$data['extensions'] = [];

		// Compatibility code for old extension folders
		$files = glob(DIR_APPLICATION . 'controller/extension/other/*.php');

		if ($files) {
			foreach ($files as $file) {
				$extension = basename($file, '.php');

				$this->load->language('extension/other/' . $extension, 'extension');

				$data['extensions'][] = [
					'name'      => $this->language->get('extension')->get('heading_title'),
					'status'    => $this->config->get('other_' . $extension . '_status') ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
					'install'   => $this->url->link('extension/extension/other/install', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
					'uninstall' => $this->url->link('extension/extension/other/uninstall', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
					'installed' => in_array($extension, $extensions),
					'edit'      => $this->url->link('extension/other/' . $extension, 'user_token=' . $this->session->data['user_token'], true)
				];
			}
		}

		$data['promotion'] = $this->load->controller('extension/extension/promotion');

		$this->response->setOutput($this->load->view('extension/extension/other', $data));
	}

	/**
	 * Validate
	 *
	 * @return bool
	 */
	protected function validate(): bool {
		if (!$this->user->hasPermission('modify', 'extension/extension/other')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
