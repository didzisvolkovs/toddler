<?php
class ModelExtensionTotalSubTotal extends Model {
	public function getTotal($total) {

		$products = $this->cart->getProducts();

		$this->load->model('localisation/country');

		$shipping = 0;
		foreach ($products as $product) {
			if($product['dropshipper_option']){
				$data['dropshipper'] = 1;
				foreach($product['dropshipper_option'] as $dropshipper_option) {

					$country = $this->model_localisation_country->getCountry($dropshipper_option['country']);

					// $tax = $tax + $product['tax'];
					// $dropshipper_option_data = array(
					// 	'name' => $dropshipper_option['name'],
					// 	'lastname' => $dropshipper_option['lastname'],
					// 	'email' => $dropshipper_option['email'],
					// 	'phone' => $dropshipper_option['phone'],
					// 	'country' => $country['name'],
					// 	'postcode' => $dropshipper_option['postcode'],
					// 	'address' => $dropshipper_option['address'],
					// 	// 'tax' => $product['tax']
					// );
					 $shipping = $shipping + $country['shipping'];

					// if($tax_rate){
					// 	 $product['tax'] = (int)$product['price'] * ((int)$tax_rate / 100);
					// }
					// else{
					// 	$product['tax'] = '0.00';
					// }

					// var_dump($dropshipper_option);
				}
			}
			else{
				$data['dropshipper'] = 0;
				// $tax = 0;
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
