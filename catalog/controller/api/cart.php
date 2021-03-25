<?php
class ControllerApiCart extends Controller {
	public function add() {
		$this->load->language('api/cart');

		$json = array();



		if (!isset($this->session->data['api_id'])) {
			$json['error']['warning'] = $this->language->get('error_permission');
		} else {
			if (isset($this->request->post['product'])) {
				$this->cart->clear();


				foreach ($this->request->post['product'] as $product) {
					if (isset($product['option'])) {
						$option = $product['option'];
					} else {
						$option = array();
					}

					if (isset($product['dropshipper_option'])) {
						$dropshipper_option = $product['dropshipper_option'];
					} else {
						$dropshipper_option = array();
					}



					$this->cart->add($product['product_id'], $product['quantity'], $option, '', $dropshipper_option);
				}

				$json['success'] = $this->language->get('text_success');

				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);
			} elseif (isset($this->request->post['product_id'])) {
				$this->load->model('catalog/product');

				$product_info = $this->model_catalog_product->getProduct($this->request->post['product_id']);

				if ($product_info) {
					if (isset($this->request->post['quantity'])) {
						$quantity = $this->request->post['quantity'];
					} else {
						$quantity = 1;
					}

					if (isset($this->request->post['option'])) {
						$option = array_filter($this->request->post['option']);
					} else {
						$option = array();
					}

					$product_options = $this->model_catalog_product->getProductOptions($this->request->post['product_id']);

					foreach ($product_options as $product_option) {
						if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
							$json['error']['option'][$product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
						}
					}



					if (!isset($json['error']['option'])) {

						 // var_dump($this->request->post);

						 if(isset($this->request->post['name']) or
						 		isset($this->request->post['surname']) or
								isset($this->request->post['email']) or
								isset($this->request->post['phone']) or
								isset($this->request->post['country']) or
								isset($this->request->post['postcode']) or
								isset($this->request->post['address']) or
								isset($this->request->post['eutaxuser']))
								{
						$dropshipper_option = array(
							'name' => $this->request->post['name'],
							'lastname' => $this->request->post['surname'],
							'email' => $this->request->post['email'],
							'phone' => $this->request->post['phone'],
							'country' => $this->request->post['country'],
							'postcode' => $this->request->post['postcode'],
							'address' => $this->request->post['address'],
							'eutaxuser' => $this->request->post['eutaxuser'],
						);
					}
					else{
						$dropshipper_option = '';
					}

						$this->cart->add($this->request->post['product_id'], $quantity, $option,'', $dropshipper_option);

						$json['success'] = $this->language->get('text_success');

						unset($this->session->data['shipping_method']);
						unset($this->session->data['shipping_methods']);
						unset($this->session->data['payment_method']);
						unset($this->session->data['payment_methods']);
					}
				} else {
					$json['error']['store'] = $this->language->get('error_store');
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function edit() {
		$this->load->language('api/cart');

		$json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->cart->update($this->request->post['key'], $this->request->post['quantity']);

			$json['success'] = $this->language->get('text_success');

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['reward']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function remove() {
		$this->load->language('api/cart');

		$json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			// Remove
			if (isset($this->request->post['key'])) {
				$this->cart->remove($this->request->post['key']);

				unset($this->session->data['vouchers'][$this->request->post['key']]);

				$json['success'] = $this->language->get('text_success');

				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);
				unset($this->session->data['reward']);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function products() {
		$this->load->language('api/cart');

		$json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error']['warning'] = $this->language->get('error_permission');
		} else {
			// Stock
			// if (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))) {
			// 	$json['error']['stock'] = $this->language->get('error_stock');
			// }

			// Products
			$json['products'] = array();

			$products = $this->cart->getProducts();


			foreach ($products as $product) {
				$product_total = 0;

				foreach ($products as $product_2) {
					if ($product_2['product_id'] == $product['product_id']) {
						$product_total += $product_2['quantity'];
					}
				}

				// if ($product['minimum'] > $product_total) {
				// 	$json['error']['minimum'][] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
				// }

				// $this->load->model('localisation/country');
				// $this->load->model('localisation/tax_rate');
				// $this->load->model('sale/order');
				//
				// $dropshipper_option_data = array();
				//
				// $dropshipper_options = $this->model_sale_order->getOrderDropshipperOptions($this->request->get['order_id'], $product['order_product_id']);
				//
				// if($dropshipper_options){
				// 	$data['dropshipper'] = 1;
				// 	foreach($dropshipper_options as $dropshipper_option){
				//
				// 		$country = $this->model_localisation_country->getCountry($dropshipper_option['shipping_country']);
				// 		$tax = $this->model_localisation_tax_rate->setShippingAddress($country['country_id']);
				//
				// 		 // $tax = $this->model_localisation_zone->getZonesByCountryId($dropshipper_option['shipping_country']);
				//
				//
				// 		$dropshipper_option_data = array(
				// 			'name' => $dropshipper_option['shipping_firstname'],
				// 			'lastname' => $dropshipper_option['shipping_lastname'],
				// 			'email' => $dropshipper_option['shipping_email'],
				// 			'phone' => $dropshipper_option['shipping_phone'],
				// 			'address' => $dropshipper_option['shipping_address_1'],
				// 			'postcode' => $dropshipper_option['shipping_postcode'],
				// 			'country' => $country['name'],
				// 			'eutaxuser' => $dropshipper_option['shipping_eutaxuser']
				// 		);
				// 			$product['shipping'] = $country['shipping'];
				//
				// 		if($tax){
				// 			$product_tax = 0;
				// 			if($dropshipper_option_data['eutaxuser'] == 1){
				// 				$product_tax = 0;
				// 			}
				// 			else{
				// 				$product_tax = ((float)$product['price'] * $product['quantity']) * ((int)$tax['rate'] / 100);
				// 			}
				// 			$product['tax'] = round(($country['shipping'] * ((int)$tax['rate'] / 100)) + $product_tax, 2) ;
				// 			$product['tax_rate'] = (int)$tax['rate'].'%' ;
				// 		}
				// 		else{
				// 			$product['tax'] = '0.00';
				// 			$product['tax_rate'] = '';
				// 		}
				//
				// 	}
				// }
				// else{
				// 	$data['dropshipper'] = 0;
				// 	$product['tax'] = '0.00';
				// }


				$option_data = array();

				foreach ($product['option'] as $option) {
					$option_data[] = array(
						'product_option_id'       => $option['product_option_id'],
						'product_option_value_id' => $option['product_option_value_id'],
						'name'                    => $option['name'],
						'value'                   => $option['value'],
						'type'                    => $option['type']
					);
				}

  // var_dump($product['dropshipper_option']);
	// die();
				$dropshipper_option_data = array();
				if(!empty($product['dropshipper_option'])){
					$data['dropshipper'] = 1;
					foreach ($product['dropshipper_option'] as $dr_option) {
						$this->load->model('localisation/country');

						$this->load->model('localisation/tax_rate');
						$country = $this->model_localisation_country->getCountry($dr_option['country']);
						$tax = $this->model_localisation_tax_rate->setShippingAddress($country['country_id']);

						$dropshipper_option_data = array(
							'name'       => $dr_option['name'],
							'lastname'   => $dr_option['lastname'],
							'email'      => $dr_option['email'],
							'phone'      => $dr_option['phone'],
							'country'    => $country['name'],
							'postcode'   => $dr_option['postcode'],
							'address'    => $dr_option['address'],
							'eutaxuser'  => $dr_option['eutaxuser']
						);
					}
								$product['shipping'] = $country['shipping'];

							if($tax){
								$product_tax = 0;
								if($dropshipper_option_data['eutaxuser'] == 1){
									$product_tax = 0;
								}
								else{
									$product_tax = ((float)$product['price'] * $product['quantity']) * ((int)$tax['rate'] / 100);
								}
								$product['tax'] = round(($country['shipping'] * ((int)$tax['rate'] / 100)) + $product_tax, 2) ;
								$product['tax_rate'] = (int)$tax['rate'].'%' ;
			 			}
		 	}
			 else{
				 	$data['dropshipper'] = 0;
				 	$product['tax'] = '0.00';
					$product['tax_rate'] = 0;
			 }



				$json['products'][] = array(
					'cart_id'    => $product['cart_id'],
					'product_id' => $product['product_id'],
					'name'       => $product['name'],
					'model'      => $product['model'],
					'option'     => $option_data,
					'dropshipper_option'     => $dropshipper_option_data,
					'quantity'   => $product['quantity'],
					'stock'      => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
					'shipping'   => $product['shipping'],
					'tax_rate'   => $product['tax_rate'],
					'tax'   => $product['tax'],
					'price'      => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
					'total'      => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'] + $product['tax'] + $product['shipping'], $this->session->data['currency']),
					'reward'     => $product['reward']
				);
			}

			// Voucher
			$json['vouchers'] = array();

			if (!empty($this->session->data['vouchers'])) {
				foreach ($this->session->data['vouchers'] as $key => $voucher) {
					$json['vouchers'][] = array(
						'code'             => $voucher['code'],
						'description'      => $voucher['description'],
						'from_name'        => $voucher['from_name'],
						'from_email'       => $voucher['from_email'],
						'to_name'          => $voucher['to_name'],
						'to_email'         => $voucher['to_email'],
						'voucher_theme_id' => $voucher['voucher_theme_id'],
						'message'          => $voucher['message'],
						'price'            => $this->currency->format($voucher['amount'], $this->session->data['currency']),
						'amount'           => $voucher['amount']
					);
				}
			}

			// Totals
			$this->load->model('setting/extension');

			$totals = array();
			$taxes = $this->cart->getTaxes();
			$total = 0;

			// Because __call can not keep var references so we put them into an array.
			$total_data = array(
				'totals' => &$totals,
				'taxes'  => &$taxes,
				'total'  => &$total
			);

			$sort_order = array();

			$results = $this->model_setting_extension->getExtensions('total');

			foreach ($results as $key => $value) {
				$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
			}

			array_multisort($sort_order, SORT_ASC, $results);

			foreach ($results as $result) {
				if ($this->config->get('total_' . $result['code'] . '_status')) {
					$this->load->model('extension/total/' . $result['code']);

					// We have to put the totals in an array so that they pass by reference.
					$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
				}
			}

			$sort_order = array();

			foreach ($totals as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $totals);

			$json['totals'] = array();

			foreach ($totals as $total) {
				$json['totals'][] = array(
					'title' => $total['title'],
					'text'  => $this->currency->format($total['value'], $this->session->data['currency'])
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
