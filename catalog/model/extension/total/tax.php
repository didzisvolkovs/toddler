<?php
class ModelExtensionTotalTax extends Model {
	public function getTotal($total) {
		$this->load->language('extension/total/tax');

		$products = $this->cart->getProducts();

		$this->load->model('localisation/country');
		$this->load->model('localisation/tax_rate');
		$dropshipper = 0;
		foreach ($products as $product) {
			if($product['dropshipper_option']){
				$dropshipper = 1;
				foreach($product['dropshipper_option'] as $dropshipper_option) {

					$country = $this->model_localisation_country->getCountry($dropshipper_option['country']);
					$tax = $this->model_localisation_tax_rate->setShippingAddress($country['country_id']);
					$tax_product[] = array(
						'tax_class_id' => $tax['tax_class_id'],
						'price' => $product['price'] * ($tax['rate'] / 100),
						'name' => $tax['name'],
						'rate' => $tax['rate'],
						'tax_rate_id' => $tax['tax_rate_id']
					);

				}
			}
			else{
				$dropshipper = 0;
				// $tax = 0;
			}
		}

		$raw = array();

			if(isset($tax_product)){
				$mainigais = 0;
			 foreach($tax_product as $key => $val)
			 {
				 if(isset($raw['tax_total'][$val['tax_rate_id']])){
					 $raw['tax_total'][$val['tax_rate_id']]['price'] += $val['price'];
				 }
				 else{
					 $raw['tax_total'][$val['tax_rate_id']]['price'] = $val['price'];
				}
				$raw['tax_total'][$val['tax_rate_id']] = array(
					'rate'	 => $val['rate'],
					'name' => $val['name'],
					'price' =>  $raw['tax_total'][$val['tax_rate_id']]['price']
					);
			 }

		 }


		if($dropshipper == 0){
		foreach ($total['taxes'] as $key => $value) {
			if ($value > 0) {
					$total['totals'][] = array(
						'code'       => 'tax',
						'title'      => $this->tax->getRateName($key),
						'value'      => $value,
						'sort_order' => $this->config->get('total_tax_sort_order')
					);

					$total['total'] += $value;
					}
				}
			}
			else{
				foreach($raw['tax_total'] as $tax_total){
					if($tax_total['price'] > 0){
					$total['totals'][] = array(
						'code'       => 'tax',
						'title'      => $this->language->get('text_dropshipptax').(int)$tax_total['rate'].'%',
						'value'      => $tax_total['price'],
					  'sort_order' => 2
					);
				}
				}
			}


	}
}
