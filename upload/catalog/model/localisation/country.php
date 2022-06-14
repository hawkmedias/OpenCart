<?php
class ModelLocalisationCountry extends Model {
	public function getCountry($country_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE `country_id` = '" . (int)$country_id . "' AND `status` = '1'");

		return $query->row;
	}
	
	public function getCountryByIsoCode2($iso_code_2) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE `iso_code_2` = '" . $this->db->escape($iso_code_2) . "' AND `status` = '1'");

		return $query->row;
	}

	public function getCountryByIsoCode3($iso_code_3) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE `iso_code_3` = '" . $this->db->escape($iso_code_3) . "' AND `status` = '1'");

		return $query->row;
	}

	public function getCountries() {
		$country_data = $this->cache->get('country.catalog');

		if (!$country_data) {
			$query = $this->db->query("SELECT *, c.`name` FROM `" . DB_PREFIX . "country` c WHERE c.`status` = '1' ORDER BY c.`name` ASC");

			$country_data = $query->rows;
			
			$this->cache->set('country.catalog', $country_data);
		}

		return $country_data;
	}
}