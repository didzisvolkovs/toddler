<?php
/*
	$Project: Product Variants $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 3.0.2 $ ($Revision: 248 $)
*/

namespace extension\ka_extensions\ka_variants;

class ControllerProduct extends \KaController {

	protected function onLoad() {
		$this->load->model('tool/image');
		
		$this->kamodel('variants');
		
		$this->load->language('extension/ka_extensions/ka_variants/common');
		
		return true;
	}
	
	/* 
		ajax call from the variants tab at the product edit page
		
	*/
	public function variantsTabAjax() {
	
		$json = array();
		
		$this->load->language('catalog/product');
		$this->load->model('catalog/product');

		if (!$this->VariantsTabContent(true)) {
			return false;
		}

		$this->data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		
		$json['html'] = $this->load->view('extension/ka_extensions/ka_variants/variants_tab_content', $this->data);
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		
		return $json;
	}
	
	
	/*
		Requested directly from product.getForm() controller
	*/
	public function variants_tab() {
	
		if (!$this->variantsTabContent()) {
			return false;
		}
		
		return $this->load->view('extension/ka_extensions/ka_variants/variants_tab', $this->data);		
	}
	
	
	/*
		Fills in the data for the variants_tab_content.twig template
	*/
	protected function variantsTabContent($is_ajax = false) {
	
		if (empty($this->request->get['product_id'])) {
			return false;
		}
		
		$product_id = $this->request->get['product_id'];

		$product_info = $this->model_catalog_product->getProduct($product_id);
		if (empty($product_info)) {
			return false;
		}

		$this->data['user_token'] = $this->session->data['user_token'];

		if (class_exists('\KaGlobal') && \KaGlobal::isKaInstalled('ka_cost_price')) {
			$this->data['is_ka_cost_price_installed'] = true;
		}
		
		$this->data['variants_page'] = 1;
		if ($is_ajax) {
			if (!empty($this->request->get['page'])) {
				$this->data['variants_page'] = (int)$this->request->get['page'];
			}
		} else {
			if (!empty($this->request->post['variants_page'])) {
				$this->data['variants_page'] = $this->request->post['variants_page'];
			}
		}
				
		
		$variants_per_page = 50;
		if (defined('KA_VARIANTS_VARIANTS_PER_PAGE')) {
			$variants_per_page = KA_VARIANTS_VARIANTS_PER_PAGE;
		}
		
		$vfilter_data = array(
			'start' => ($this->data['variants_page'] - 1) * $variants_per_page,
			'limit' => $variants_per_page
		);

		$this->data['product_variants'] = array();
		$this->data['def_variant_id']   = 0;
		$this->data['variants_total']   = 0;
		
		if (isset($this->request->post['product_variants'])) {
		
			if (!empty($this->request->post['variants_page'])) {
				$this->data['variants_page'] = (int)$this->request->post['variants_page'];
				if ($this->data['variants_page'] < 1) {
					$this->data['variants_page'] = 1;
				}
				$vfilter_data['start'] = ($this->data['variants_page'] - 1) * $variants_per_page;
			}
		
			$variants = $this->kamodel_variants->getProductVariants($product_id, $vfilter_data);
			
			if (!empty($variants) && !empty($this->request->post['product_variants'])) {
				foreach ($variants as &$variant) {
					foreach($this->request->post['product_variants'] as $post_variant) {
						if ($variant['variant_id'] == $post_variant['variant_id']) {
							$variant = array_merge($variant, $post_variant);
							break;
						}
					}
				}
			}
			$this->data['product_variants'] = $variants;
			
			// init def variant
			if (!empty($this->request->post['def_variant_id'])) {
				$this->data['def_variant_id'] = $this->request->post['def_variant_id'];
			} elseif (!empty($this->request->post['variants_default_id'])) {
				$this->data['def_variant_id'] = $this->request->post['variants_default_id'];
			}
			
			// copy serialized variants data
			if (!empty($this->request->post['variants_serialized'])) {
				$this->data['variants_serialized'] = html_entity_decode($this->request->post['variants_serialized'], ENT_QUOTES, 'UTF-8');
			}
			
		} elseif (!empty($product_info)) {
			$this->data['product_variants'] = $this->kamodel_variants->getProductVariants($product_info['product_id'], $vfilter_data);
			$this->data['def_variant_id'] = $product_info['def_variant_id'];
		}

		// generate image paths for product variants
		//
		if (!empty($this->data['product_variants'])) {
			foreach ($this->data['product_variants'] as &$v) {
			
				if (isset($v['image']) && is_file(DIR_IMAGE . $v['image'])) {
					$v['thumb'] = $this->model_tool_image->resize($v['image'], 100, 100);
					
				} elseif (!empty($product_info) && $v['image'] && is_file(DIR_IMAGE . $v['image'])) {
					$v['thumb'] = $this->model_tool_image->resize($v['image'], 100, 100);
				} else {
					$v['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
				}
			}
		}
		
		$this->data['product_id'] = $product_id;
		$this->data['variants_total'] = $this->kamodel_variants->getTotalProductVariants($product_id);
		
		$variants_pagination = new \KaPagination();
		$variants_pagination->total = $this->data['variants_total'];
		$variants_pagination->page = $this->data['variants_page'];
		$variants_pagination->limit = $variants_per_page;
		$variants_pagination->url = $this->url->link('extension/ka_extensions/ka_variants/product', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

		$this->data['variants_pagination'] = $variants_pagination->render();
		$this->data['variants_results'] = $variants_pagination->getResults($this->language->get('text_pagination'));
		
		return true;
	}
	
}

class_alias(__NAMESPACE__ . '\ControllerProduct', 'ControllerExtensionKaExtensionsKaVariantsProduct');