<?php
/* 
 $Project: Product Variants $
 $Author: karapuz team <support@ka-station.com> $
 $Version: 3.0.2 $ ($Revision: 236 $) 
*/

namespace extension\ka_extensions\ka_variants;

class ControllerProduct extends \KaController {

	protected $error = array();

	public function index() {

		if (empty($this->request->post)) {
			die('empty parameters');
		}

		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		$this->load->language('product/product');
		$this->load->model('catalog/category');
		$this->load->model('catalog/manufacturer');
		$this->load->model('tool/image');
		$this->load->model('catalog/product');
		$kamodel_ka_variant = $this->kamodel('ka_variant');

		$variant = $kamodel_ka_variant->getVariantByOptions($product_id, $this->request->post['option'], true);		
		if (empty($variant)) {
			die('variant not found');
		}
		
		$variant_id = $variant['variant_id'];
		
		$json = array(
			'error' => '',
			'html'  => ''
		);
		
		$product_info = $this->model_catalog_product->getProduct($product_id, $variant_id);

		$show_discounted_price = (int)$this->config->get('ka_variants_show_discounted_price');
		
		if ($product_info) {

			$quantity = 1;
			if ($show_discounted_price) {
				$quantity = $this->request->post['quantity'];
			}
			$variant = $kamodel_ka_variant->getVariant($variant_id, 0, $quantity);
			
			$kamodel_ka_variant->substituteWithVariant($product_info, $variant);

			// perform cart calculations for one product
			//
			$this->session->data['api_id'] = -100;
			$tmp_cart = new \Cart\Cart($this->registry);
			$tmp_cart->add($product_info['product_id'], $quantity, $this->request->post['option']);
			$products = $tmp_cart->getProducts();
			$tmp_cart->clear();
			$this->session->data['api_id'] = 0;
			
			if (empty($products)) {
				die('A temporary cart cannot be created');
			}
			$cart_product = reset($products);
			
			//calculate total values for the product
			//
			$option_price = $cart_product['option_price'];
			$total_price   = 0;
			$total_special = 0;

			$data['total_price']   = false;
			$data['total_tax']     = false;
			$data['total_special'] = false;
			$data['you_save']      = false;
			
			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$total_price   = (float)$product_info['price'] * (int)$quantity + (float)$option_price * (int)$quantity;
				$total_special = 0;
			
				if (!empty($cart_product['is_special_price'])) {
					$total_special = $cart_product['price'] * (int)$quantity + (float)$option_price * (int)$quantity;
					
					$data['total_special']  = $this->currency->format($this->tax->calculate($total_special, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$data['you_save']       = '-' . number_format(((float)$total_price - (float)$total_special) / (float)$total_price * 100, 0) . '%';
				}
				$data['total_price'] = $this->currency->format($this->tax->calculate($total_price, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				$data['total_tax']   = $this->currency->format((float)$total_special ? $total_special : $total_price, $this->session->data['currency']);
			}
			
			$data['heading_title'] = $product_info['name'];

			$data['text_select'] = $this->language->get('text_select');
			$data['text_manufacturer'] = $this->language->get('text_manufacturer');
			$data['text_model'] = $this->language->get('text_model');
			$data['text_reward'] = $this->language->get('text_reward');
			$data['text_points'] = $this->language->get('text_points');
			$data['text_stock'] = $this->language->get('text_stock');
			$data['text_discount'] = $this->language->get('text_discount');
			$data['text_tax'] = $this->language->get('text_tax');
			$data['text_option'] = $this->language->get('text_option');
			$data['text_minimum'] = sprintf($this->language->get('text_minimum'), $product_info['minimum']);
			$data['text_write'] = $this->language->get('text_write');
			$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true));
			$data['text_note'] = $this->language->get('text_note');
			$data['text_tags'] = $this->language->get('text_tags');
			$data['text_related'] = $this->language->get('text_related');
			$data['text_payment_recurring'] = $this->language->get('text_payment_recurring');
			$data['text_loading'] = $this->language->get('text_loading');

			$data['entry_qty'] = $this->language->get('entry_qty');
			$data['entry_name'] = $this->language->get('entry_name');
			$data['entry_review'] = $this->language->get('entry_review');
			$data['entry_rating'] = $this->language->get('entry_rating');
			$data['entry_good'] = $this->language->get('entry_good');
			$data['entry_bad'] = $this->language->get('entry_bad');

			$data['button_cart'] = $this->language->get('button_cart');
			$data['button_wishlist'] = $this->language->get('button_wishlist');
			$data['button_compare'] = $this->language->get('button_compare');
			$data['button_upload'] = $this->language->get('button_upload');
			$data['button_continue'] = $this->language->get('button_continue');

			$data['manufacturer'] = $product_info['manufacturer'];
			$data['manufacturers'] = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $product_info['manufacturer_id']);
			$data['model'] = $product_info['model'];
			$data['reward'] = $product_info['reward'];
			$data['points'] = $product_info['points'];
			$data['description'] = html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8');
			$data['quantity'] = $product_info['quantity'];
			
			if ($product_info['quantity'] <= 0) {
				$data['stock'] = $product_info['stock_status'];
			} elseif ($this->config->get('config_stock_display')) {
				$data['stock'] = $product_info['quantity'];
			} else {
				$data['stock'] = $this->language->get('text_instock');
			}

			if ($product_info['image']) {
				$data['popup'] = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
			} else {
				$data['popup'] = '';
			}

			if ($product_info['image']) {
				$data['thumb'] = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));
			} else {
				$data['thumb'] = '';
			}

			$data['images'] = array();

			$results = $this->model_catalog_product->getProductImages($this->request->get['product_id']);

			foreach ($results as $result) {
				$data['images'][] = array(
					'popup' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height')),
					'thumb' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height'))
				);
			}
			

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$data['price'] = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$data['price'] = false;
			}

			if ((float)$product_info['special']) {
				$data['special'] = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$data['special'] = false;
			}

			if ($this->config->get('config_tax')) {
				$data['tax'] = $this->currency->format((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
			} else {
				$data['tax'] = false;
			}

			$discounts = $this->model_catalog_product->getProductDiscounts($this->request->get['product_id'], $variant_id);
			
			$data['discounts'] = array();

			foreach ($discounts as $discount) {
				$data['discounts'][] = array(
					'quantity' => $discount['quantity'],
					'price'    => $this->currency->format($this->tax->calculate($discount['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
				);
			}
			
			if ($product_info['minimum']) {
				$data['minimum'] = $product_info['minimum'];
			} else {
				$data['minimum'] = 1;
			}

 			$data['review_status'] = $this->config->get('config_review_status');
 			
 			if ($this->config->get('config_review_guest') || $this->customer->isLogged()) {
 				$data['review_guest'] = true;
 			} else {
 				$data['review_guest'] = false;
 			}
 			
 			$data['reviews'] = sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']);
 			$data['rating'] = (int)$product_info['rating'];
			
			$this->template = 'extension/ka_extensions/ka_variants/product_dynamic';


            if (defined('JOURNAL3_ACTIVE')) {
                $this->load->language('product/compare');

                $data['text_weight'] = $this->language->get('text_weight');
                $data['text_dimension'] = $this->language->get('text_dimension');
                $data['product_quantity'] = $product_info['quantity'];
                $data['product_price_value'] = $product_info['special'] ? $product_info['special'] > 0 : $product_info['price'] > 0;
                $data['product_sku'] = $product_info['sku'];
                $data['product_upc'] = $product_info['upc'];
                $data['product_ean'] = $product_info['ean'];
                $data['product_jan'] = $product_info['jan'];
                $data['product_isbn'] = $product_info['isbn'];
                $data['product_mpn'] = $product_info['mpn'];
                $data['product_location'] = $product_info['location'];
                $data['product_dimension'] = (float)$product_info['length'] || (float)$product_info['width'] || (float)$product_info['height'];
                $data['product_length'] = $this->length->format($product_info['length'], $product_info['length_class_id']);
                $data['product_width'] = $this->length->format($product_info['width'], $product_info['length_class_id']);
                $data['product_height'] = $this->length->format($product_info['height'], $product_info['length_class_id']);
                $data['product_weight'] = (float)$product_info['weight'] ? $this->weight->format($product_info['weight'], $product_info['weight_class_id']) : false;

                $data['product_labels'] = $this->journal3->productLabels($product_info, $product_info['price'], $product_info['special']);
                $data['product_exclude_classes'] = $this->journal3->productExcludeButton($product_info, $product_info['price'], $product_info['special']);
                $data['product_extra_buttons'] = $this->journal3->productExtraButton($product_info, $product_info['price'], $product_info['special']);
                $data['product_blocks'] = array();

                foreach($this->journal3->productBlocks($product_info, $product_info['price'], $product_info['special']) as $module_id => $module_data) {
                    if ($module_data['position'] === 'quickview' && $this->journal3->document->isPopup()) {
                    	if ($block = $this->load->controller('journal3/product_blocks', array('module_id' => $module_id, 'module_type' => 'product_blocks', 'product_info' => $product_info))) {
							$data['product_blocks']['default'][] = $block;
						}
                    } else if ($module_data['position'] === 'quickview_details' && $this->journal3->document->isPopup()) {
                    	if ($block = $this->load->controller('journal3/product_blocks', array('module_id' => $module_id, 'module_type' => 'product_blocks', 'product_info' => $product_info))) {
							$data['product_blocks']['bottom'][] = $block;
						}
                    } else if ($module_data['position'] === 'quickview_image' && $this->journal3->document->isPopup()) {
                    	if ($block = $this->load->controller('journal3/product_blocks', array('module_id' => $module_id, 'module_type' => 'product_blocks', 'product_info' => $product_info))) {
							$data['product_blocks']['image'][] = $block;
						}
                    } else if (!$this->journal3->document->isPopup()) {
                    	if ($block = $this->load->controller('journal3/product_blocks', array('module_id' => $module_id, 'module_type' => 'product_blocks', 'product_info' => $product_info))) {
							$data['product_blocks'][$module_data['position']][] = $block;
						}
                    }
                }

               
                $product_tabs = array();
/* 
                foreach($this->journal3->productTabs($product_info, $product_info['price'], $product_info['special']) as $module_id => $module_data) {
                    if ($module_data['position'] === 'quickview' && $this->journal3->document->isPopup()) {
                   	if ($tab = $this->load->controller('journal3/product_tabs', array('module_id' => $module_id, 'module_type' => 'product_tabs', 'product_info' => $product_info))) {
							$product_tabs['default'][] = $tab;
						}
                    } else if ($module_data['position'] === 'quickview_details' && $this->journal3->document->isPopup()) {
                    	if ($tab = $this->load->controller('journal3/product_tabs', array('module_id' => $module_id, 'module_type' => 'product_tabs', 'product_info' => $product_info))) {
							$product_tabs['bottom'][] = $tab;
						}
                    } else if ($module_data['position'] === 'quickview_image' && $this->journal3->document->isPopup()) {
                    	if ($tab = $this->load->controller('journal3/product_tabs', array('module_id' => $module_id, 'module_type' => 'product_tabs', 'product_info' => $product_info))) {
							$product_tabs['image'][] = $tab;
						}
                    } else if (!$this->journal3->document->isPopup()) {
                    	if ($tab = $this->load->controller('journal3/product_tabs', array('module_id' => $module_id, 'module_type' => 'product_tabs', 'product_info' => $product_info))) {
							$product_tabs[$module_data['position']][] = $tab;
						}
                    }
                }

                foreach ($product_tabs as $position => &$items) {
                    $_items = array();

                    foreach ($items as $item) {
                        $_items[$item['display']][] = $item;
                    }

                    foreach ($_items as $items) {
                        $data['product_blocks'][$position][] = $this->load->controller('journal3/product_tabs/tabs', array('items' => $items, 'position' => $position));
                    }
                }
*/

                $this->load->model('catalog/manufacturer');

                $manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($product_info['manufacturer_id']);

                if ($manufacturer_info && $manufacturer_info['image']) {
                    $data['manufacturer_image'] = $this->model_journal3_image->resize($manufacturer_info['image'], $this->journal3->settings->get('image_dimensions_manufacturer_logo.width'), $this->journal3->settings->get('image_dimensions_manufacturer_logo.height'), $this->journal3->settings->get('image_dimensions_manufacturer_logo.resize'));
                    $data['manufacturer_image2x'] = $this->model_journal3_image->resize($manufacturer_info['image'], $this->journal3->settings->get('image_dimensions_manufacturer_logo.width') * 2, $this->journal3->settings->get('image_dimensions_manufacturer_logo.height') * 2, $this->journal3->settings->get('image_dimensions_manufacturer_logo.resize'));
                } else {
                    $data['manufacturer_image'] = false;
                }

               if ($product_info['special']) {
                   $data['date_end'] = $this->journal3->productCountdown($product_info);
               } else {
                   $data['date_end'] = false;
               }
                if ($this->journal3->document->isPopup()) {
                   $data['view_more_url'] = $this->url->link('product/product', 'product_id=' . (int)$this->request->get['product_id']);
               }
               
                $this->load->model('journal3/product');
                $this->model_journal3_product->addRecentlyViewedProduct($this->request->get['product_id']);

                $data['products_sold'] = $this->model_journal3_product->getProductsSold($this->request->get['product_id']);
                $data['product_views'] = $product_info['viewed'];
                
				$this->data = $data;
				$this->template = 'extension/ka_extensions/ka_variants/product_price';
				$json['product_price'] = $this->render();
				
				$this->template = 'extension/ka_extensions/ka_variants/product_stats';
				$json['product_stats'] = $this->render();
            } else {
				$this->data = $data;
				$json['html'] = $this->render();
			}			
			if (!empty($variant['image'])) {
				$image = $variant['image'];
			} else {
				$image = $product_info['image'];
			}			
			$json['image'] = $this->model_tool_image->resize($image, $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));
			$json['image_popup'] = $this->model_tool_image->resize($image, $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
			
			$json['disabled_options'] = $kamodel_ka_variant->getDisabledOptions($variant);
			
			// pass extra variables to the json response
			//
			$pass_variables = array(
				'quantity',
				'price', 'total_price',
				'special', 'total_special',
				'tax', 'total_tax',
				'you_save'
			);
 			
			foreach ($pass_variables as $v) {
				if (isset($data[$v])) {
					$json[$v] = $data[$v];
				}
			}
			
		} else {
			$json['error'] = 'product not found';
		}

		$this->response->setOutput(json_encode($json));
	}
}

class_alias(__NAMESPACE__ . '\ControllerProduct', 'ControllerExtensionKaExtensionsKaVariantsProduct');