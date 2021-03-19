<?php
class ModelExtensionTotalTotal extends Model {
	public function getTotal($total) {
		$this->load->language('extension/total/total');


		$products = $this->cart->getProducts();

		$this->load->model('localisation/country');

		$shipping = 0;
		$tax = 0;
		$total_tax = 0;
		$product_tax = 0;
		foreach ($products as $product) {
			if($product['dropshipper_option']){
				$data['dropshipper'] = 1;
				foreach($product['dropshipper_option'] as $dropshipper_option) {

					$country = $this->model_localisation_country->getCountry($dropshipper_option['country']);
					$tax_rate = $this->model_localisation_tax_rate->setShippingAddress($country['country_id']);


					if($tax_rate){
						if($dropshipper_option['eutaxuser'] == 1){
								$product_tax = 0;
							}
							else{
								$product_tax = ((float)$product['price'] * $product['quantity']) * ((int)$tax_rate['rate'] / 100);
							}
							$shipping_tax = $country['shipping'] * ((int)$tax_rate['rate'] / 100);
					}
					else{
						$shipping_tax = 0;
					}

					$tax = $product_tax + $shipping_tax;
					$total_tax = $total_tax + $tax;
					$shipping = $shipping + $country['shipping'];

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
			'value'      => max(0, $total['total'] + $total_tax + $shipping),
			'sort_order' => $this->config->get('total_total_sort_order')
		);
	}
}
