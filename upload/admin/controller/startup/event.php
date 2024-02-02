<?php
/**
 * Class Event
 *
 * @package Admin\Controller\Startup
 */
class ControllerStartupEvent extends Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		// Add events from the DB

		// Events
		$this->load->model('setting/event');

		$results = $this->model_setting_event->getEvents();

		foreach ($results as $result) {
			if ($result['status']) {
				$part = explode('/', $result['trigger']);

				if ($part[0] == 'admin') {
					array_shift($part);

					$this->event->register(implode('/', $part), new \Action($result['action']), $result['sort_order']);
				}
			}
		}
	}
}
