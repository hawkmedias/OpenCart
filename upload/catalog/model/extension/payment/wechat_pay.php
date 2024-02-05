<?php
/**
 * Class Wechat Pay
 *
 * @package 	   Catalog\Model\Extension\Payment
 *
 * @author         Meng Wenbin
 * @copyright      Copyright (c) 2010 - 2022, Chengdu Guangda Network Technology Co. Ltd. (https://www.opencart.cn/)
 * @license        https://opensource.org/licenses/GPL-3.0
 *
 * @see           https://www.opencart.cn
 */
class ModelExtensionPaymentWechatPay extends Model {
	/**
	 * getMethod
	 *
	 * @param array $address
	 *
	 * @return array
	 */
	public function getMethods(array $address): array {
		$this->load->language('extension/payment/wechat_pay');

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_wechat_pay_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

		if (!$this->config->get('payment_wechat_pay_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = [];

		if ($status) {
			$method_data = [
				'code'       => 'wechat_pay',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_wechat_pay_sort_order')
			];
		}

		return $method_data;
	}
}
