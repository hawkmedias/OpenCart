<?php
/**
 * Class Router
 *
 * @package Catalog\Controller\Startup
 */
class ControllerStartupRouter extends Controller {
	/**
	 * Index
	 *
	 * @return mixed
	 */
	public function index(): mixed {
		// Route
		if (isset($this->request->get['route']) && $this->request->get['route'] != 'startup/router') {
			$route = $this->request->get['route'];
		} else {
			$route = $this->config->get('action_default');
		}

		$args = [];

		// Sanitize the call
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);

		// Trigger the pre events
		$result = $this->event->trigger('controller/' . $route . '/before', [
			&$route,
			&$args
		]);

		if ($result !== null) {
			return $result;
		}

		// We dont want to use the loader class as it would make an controller callable.
		$action = new \Action($route);

		// Any output needs to be another Action object.
		$output = $action->execute($this->registry);

		// Trigger the post events
		$result = $this->event->trigger('controller/' . $route . '/after', [
			&$route,
			&$args,
			&$output
		]);

		if ($result !== null) {
			return $result;
		}

		return $output;
	}
}
