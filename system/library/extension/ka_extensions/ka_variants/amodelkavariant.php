<?php
/* 
 $Project: Product Variants $
 $Author: karapuz team <support@ka-station.com> $
 $Version: 3.0.2 $ ($Revision: 247 $) 
*/

namespace extension\ka_extensions\ka_variants;

class AModelKaVariant extends \KaModel {

	public function getVariant($variant_id, $customer_group_id = 0, $quantity = 1) {
		
		$variant_id = (int) $variant_id;
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_product_variants 
			WHERE variant_id = '$variant_id'
		");
		if (empty($query->row)) {
			return false;
		}

		if (empty($customer_group_id)) {
			$customer_group_id = $this->customer->getGroupId();
			if (empty($customer_group_id)) {
				 $customer_group_id = $this->config->get('config_customer_group_id');
			}
		}
		
		// get discount
		//				
		$qry = $this->getVariantDiscount($variant_id, $customer_group_id, $quantity);
		if (!empty($qry->row)) {
			$query->row['price'] = $qry->row['price'];
		}

		// get special price
		//
		$qry = $this->getVariantSpecial($variant_id, $customer_group_id, $query->row['price']);
		if (!empty($qry->row)) {
			$query->row['special'] = $qry->row['price'];
		} else {
			$query->row['special'] = null;
		}
		
		$qry_options = $this->db->query("SELECT vo.*, pov.product_option_id, pov.product_option_value_id FROM " . DB_PREFIX . "ka_variant_options vo
			INNER JOIN " . DB_PREFIX . "product_option_value pov ON pov.option_value_id = vo.option_value_id AND pov.product_id = vo.product_id
			WHERE
				variant_id = '$variant_id'
		");
		
		$options = array();		
		foreach ($qry_options->rows as $o) {
			$options[$o['product_option_id']] = $o['product_option_value_id'];
		}
		$query->row['options'] = $options;
		
		return $query->row;
	}

	public function substituteWithVariant(&$product, $variant) {
	
		if (empty($variant)) {
			return false;
		}
	
		$product['price']    = $variant['price'];
		if (isset($variant['discount'])) {
			$product['discount'] = $variant['discount'];
		} else {
			$product['discount'] = null;
		}
		if (isset($variant['special'])) {
			$product['special']  = $variant['special'];
		} else {
			$product['special'] = null;
		}

		if (!empty($variant['image'])) {
			$product['image']    = $variant['image'];
		}
		$product['quantity'] = $variant['quantity'];
		$product['weight']   = $variant['weight'];
		if (!empty($variant['model'])) {
			$product['model']    = $variant['model'];
		}
		$product['variant_id']   = $variant['variant_id'];
		$product['variant_hash'] = $variant['hash'];
		
		return true;
	}

	/*
		The function returns a variant record basing on the options data. If the options data is not sufficient for
		determining a single variant then the false value will be returned
		
		the function can accept options in two ways:
		
			1) $option => $value pair
			
			2)	or as an array of option values like this:
				option_id => [
					'name' => 'option 1',
					'option_value_id' => 10
					...
				}
	*/
	public function getVariantByOptions($product_id, $options) {

		if (empty($options)) {
			return false;
		}
			
		$v_options = array();
		foreach ($options as $value) {
			
			if (!is_array($value)) {
				$value_id = (int) $value;
			} else {
				if (!isset($value['product_option_value_id'])) {
					continue;
				}
				$value_id = $value['product_option_value_id'];
			}
			$qry = $this->db->query("SELECT pov.* FROM " . DB_PREFIX . "product_option_value pov
				INNER JOIN `" . DB_PREFIX . "option` o ON pov.option_id = o.option_id
				WHERE 
					pov.product_option_value_id = '$value_id'					
					AND o.is_variant = 1
			");
			
			if (!empty($qry->rows)) {
				$v_options[] = $qry->row['option_value_id'];
			}		
		}
		
		sort($v_options);
		$v_hash = implode(".", $v_options);
		
		$qry = $this->db->query("SELECT kpv.*, p.model AS product_model FROM " . DB_PREFIX . "ka_product_variants kpv 
			INNER JOIN " . DB_PREFIX . "product p ON kpv.product_id = p.product_id
			WHERE
			p.product_id = '" . (int) $product_id . "'
			AND hash = '" . $this->db->escape($v_hash) . "' LIMIT 1"
		);
		
		if (empty($qry->row)) {
			return false;
		}
		
		// replace an empty variant model with the product model code to avoid issues with an empty model code
		//
		if (empty($qry->row['model'])) {
			$qry->row['model'] = $qry->row['product_model'];
		}

		return $qry->row;
	}

	
	public function getVariantDiscount($variant_id, $customer_group_id, $quantity = 1) {

		$qry = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_discount pd 
			INNER JOIN " . DB_PREFIX . "ka_variant_to_discount kvd ON pd.product_discount_id = kvd.discount_id
			WHERE 
				kvd.variant_id = '" . (int) $variant_id . "'
				AND pd.customer_group_id = '" . (int)$customer_group_id . "' 
				AND quantity <= '" . (int) $quantity . "' 
				AND ((pd.date_start = '0000-00-00' OR pd.date_start < NOW()) 
					AND (pd.date_end = '0000-00-00' OR pd.date_end > NOW())
				) 
			ORDER BY pd.priority ASC, pd.price ASC LIMIT 1
		");
		
		return $qry;
	}
	
	public function getVariantSpecial($variant_id, $customer_group_id, $price = null) {
	
		$extra = '';
		if ($price !== null) {
			$extra .= " AND price < '" . (float) $price . "'";
		}
			
		$qry = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_special ps 
			INNER JOIN " . DB_PREFIX . "ka_variant_to_special kvs ON ps.product_special_id = kvs.special_id
			WHERE 
				kvs.variant_id = '" . (int) $variant_id . "'
				AND ps.customer_group_id = '" . (int)$customer_group_id . "' 
				AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) 
					AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())
				)				
				$extra
			ORDER BY ps.priority ASC, ps.price ASC LIMIT 1
		");
		
		return $qry;
	}
	
}