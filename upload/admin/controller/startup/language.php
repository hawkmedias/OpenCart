<?php
/**
 * Class Language
 *
 * @package Admin\Controller\Startup
 */
class ControllerStartupLanguage extends Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		// Language
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language` WHERE `code` = '" . $this->db->escape($this->config->get('config_admin_language')) . "'");

		if ($query->num_rows) {
			$this->config->set('config_language_id', $query->row['language_id']);
		}

		// Language
		$language = new \Language($this->config->get('config_admin_language'));
		$language->load($this->config->get('config_admin_language'));
		$this->registry->set('language', $language);
	}
}
