<?php
/**
 * Class Logout
 *
 * @package Admin\Controller\Common
 */
class ControllerCommonLogout extends Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->user->logout();

		unset($this->session->data['user_token']);

		$this->response->redirect($this->url->link('common/login', '', true));
	}
}
