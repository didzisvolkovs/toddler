<?php
class ModelExtensionTotalTotal extends Model {
	public function getTotal($total) {
		$this->load->language('extension/total/total');


		$products = $this->cart->getProducts();

		$this->load->model('localisation/country');

		$shipping = 0;
		$tax = 0;
		foreach ($products as $product) {
			if($product['dropshipper_option']){
				$data['dropshipper'] = 1;
				foreach($product['dropshipper_option'] as $dropshipper_option) {

					$country = $this->model_localisation_country->getCountry($dropshipper_option['country']);
					$tax_rate = $this->model_localisation_tax_rate->setShippingAddress($country['country_id']);


					$tax = $tax + ($product['price'] * ($tax_rate['rate'] / 100));
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
			  $tax = 0;
			}
		}

		$total['totals'][] = array(
			'code'       => 'total',
			'title'      => $this->language->get('text_total'),
			'value'      => max(0, $total['total'] + $tax + $shipping),
			'sort_order' => $this->config->get('total_total_sort_order')
		);
	}
}
