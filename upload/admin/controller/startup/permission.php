<?php
/**
 * Class Logout
 *
 * @package Admin\Controller\Startup
 */
class ControllerStartupPermission extends Controller {
	/**
	 * Index
	 *
	 * @return \Action|object|null
	 */
	public function index(): ?object {
		if (isset($this->request->get['route'])) {
			$route = '';

			$part = explode('/', $this->request->get['route']);

			if (isset($part[0])) {
				$route .= $part[0];
			}

			if (isset($part[1])) {
				$route .= '/' . $part[1];
			}

			// If a 3rd part is found, we need to check if it's under one of the extension folders.
			$extension = [
				'extension/advertise',
				'extension/dashboard',
				'extension/analytics',
				'extension/captcha',
				'extension/currency',
				'extension/extension',
				'extension/feed',
				'extension/fraud',
				'extension/module',
				'extension/other',
				'extension/payment',
				'extension/shipping',
				'extension/theme',
				'extension/total',
				'extension/report'
			];

			if (isset($part[2]) && in_array($route, $extension)) {
				$route .= '/' . $part[2];
			}

			// We want to ingore some pages from having its permission checked.
			$ignore = [
				'common/dashboard',
				'common/login',
				'common/logout',
				'common/forgotten',
				'common/reset',
				'error/not_found',
				'error/permission'
			];

			if (!in_array($route, $ignore) && !$this->user->hasPermission('access', $route)) {
				return new \Action('error/permission');
			}
		}

		return null;
	}
}
