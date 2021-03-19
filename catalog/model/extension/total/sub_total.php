<?php
class ModelExtensionTotalSubTotal extends Model {
	public function getTotal($total) {

		$products = $this->cart->getProducts();

		$this->load->model('localisation/country');

		$shipping = 0;
		foreach ($products as $product) {
			if($product['dropshipper_option']){
				foreach($product['dropshipper_option'] as $dropshipper_option) {

					$country = $this->model_localisation_country->getCountry($dropshipper_option['country']);

					 $shipping = $shipping + $country['shipping'];
				}
			}
		}
		// var_dump($this->cart->getProducts());

		$this->load->language('extension/total/sub_total');

		$sub_total = $this->cart->getSubTotal();

		if (!empty($this->session->data['vouchers'])) {
			foreach ($this->session->data['vouchers'] as $voucher) {
				$sub_total += $voucher['amount'];
			}
		}

		$total['totals'][] = array(
			'code'       => 'sub_total',
			'title'      => $this->language->get('text_sub_total'),
			'value'      => $sub_total+ $shipping,
			'sort_order' => $this->config->get('sub_total_sort_order')
		);

		$total['total'] += $sub_total;
	}
}
