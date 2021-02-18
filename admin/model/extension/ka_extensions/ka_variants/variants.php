<?php
/*
 $Project: Product Variants $
 $Author: karapuz team <support@ka-station.com> $
 $Version: 3.0.2 $ ($Revision: 244 $)
*/

namespace extension\ka_extensions\ka_variants;

class ModelVariants extends \KaModel {

	public function onLoad() {
		$this->load->model('catalog/product');
		$this->load->model('catalog/option');
	}

	public function getAssignedProductsTotal($option_id) {

		$result = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product_option
			WHERE option_id = '" . (int)$option_id . "'
		")->row;

		return $result['total'];
	}

	public function isVariantProduct($product_id) {
		$qry = $this->db->query("SELECT pv.*  FROM " . DB_PREFIX . "ka_product_variants pv
			INNER JOIN " . DB_PREFIX . "product p ON pv.product_id = p.product_id
			WHERE p.product_id = '" . (int) $product_id . "' LIMIT 1
		");

		if (empty($qry->row['variant_id'])) {
			return false;
		}

		return true;
	}

	public function getVariant($variant_id, $customer_group_id = 0) {

		$variant_id = (int) $variant_id;

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_product_variants
			WHERE variant_id = '$variant_id'
		");
		if (empty($query->row)) {
			return false;
		}

		return $query->row;
	}

	public function substituteWithVariant(&$product, $variant) {
		$product['price']    = $variant['price'];
		if (isset($variant['discount'])) {
			$product['discount'] = $variant['discount'];
		} else {
			$product['discount'] = null;
		}
		if (isset($variant['special'])) {
			$product['special']  = $variant['special'];
		} else {
			$product['special']  = null;
		}

		if (!empty($variant['image'])) {
			$product['image']    = $variant['image'];
		}
		$product['quantity'] = $variant['quantity'];
		$product['weight']   = $variant['weight'];
		$product['model']    = $variant['model'];
		$product['variant_id']   = $variant['variant_id'];
		$product['variant_hash'] = $variant['hash'];

		return true;
	}

	/*
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

		$qry = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_product_variants WHERE
			product_id = '" . (int) $product_id . "'
			AND hash = '" . $this->db->escape($v_hash) . "' LIMIT 1"
		);

		if (empty($qry->row)) {
			return false;
		}

		return $qry->row;
	}


	public function getVariantByModel($model) {

		$qry = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_variants
			WHERE model = '" . $this->db->escape($model) . "'
			LIMIT 1
		");

		if (empty($qry->row)) {
			return false;
		}

		return $qry->row['variant_id'];
	}


	public function getProductVariants($product_id, $data = array()) {

		$product_id = (int) $product_id;

		$where = array();
		$limit = '';

		if (isset($data['start']) && isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$limit .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_product_variants
			WHERE product_id = '$product_id'
			$limit
		");

		if (empty($query->rows)) {
			return false;
		}

		$variants = $query->rows;
		$variant_versions = array();

		foreach ($variants as $k => &$v) {

			$qry = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_variant_options kvo
				LEFT JOIN " . DB_PREFIX . "option_value ov ON ov.option_value_id = kvo.option_value_id
				LEFT JOIN `" . DB_PREFIX . "option` o ON o.option_id = ov.option_id
				WHERE kvo.variant_id = '$v[variant_id]'
				ORDER BY o.sort_order, o.option_id
			");
			$v['options'] = array();
			if (empty($qry->rows)) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "ka_product_variants WHERE
					variant_id = '$v[variant_id]'
				");
				$this->log->write("getProductVariants: no variant options. Product_id:$product_id, variant_id: $v[variant_id]");
				continue;
			}

			$v['options'] = $qry->rows;
			$option_value_ids = array();
			$version = '';
			$is_valid_variant = true;
			foreach ($v['options'] as &$vo) {

				$qry_ov = $this->db->query("SELECT * FROM " . DB_PREFIX . "option_value ov
					LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id)
					WHERE ov.option_value_id = '" . (int)$vo['option_value_id'] . "'
						AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'
					ORDER BY ov.sort_order, ov.option_value_id
					"
				);
				$value = $qry_ov->row;
				if (empty($value)) {
					$is_valid_variant = false;
					break;
				}

				$option = $this->model_catalog_option->getOption($value['option_id']);

				$vo['name']         = $option['name'];
				$vo['value']        = $value['name'];
				$vo['option']       = $option;
				$vo['option_value'] = $value;
				$option_value_ids[] = $value['option_value_id'];
				$version .= $option['sort_order'] .
					'.' . $option['option_id'] .
					'.' . $value['sort_order'] .
					'.' . $value['option_value_id'];
			}
			if (!$is_valid_variant) {
				continue;
			}

			$variant_versions[$k] = $version;
		}

		$_variant_versions = $variant_versions;
		usort($_variant_versions, 'version_compare');

		$_variants = array();
		foreach ($_variant_versions as $vv) {
			$_variants[] = &$variants[array_search($vv, $variant_versions)];
		}


		return $_variants;
	}


	public function rebuildVariants($product_id, $update_default_vid = true) {
		$product_id = (int) $product_id;

		// read old variants into array
		//
		$old_variants = $this->getProductVariants($product_id);

		$product = $this->model_catalog_product->getProduct($product_id);

		// get variant options assigned to product
		//
		$qry = $this->db->query("SELECT o.* FROM " . DB_PREFIX . "option_value ov INNER JOIN
			" . DB_PREFIX . "product_option_value pov ON ov.option_value_id = pov.option_value_id INNER JOIN
			`" . DB_PREFIX . "option` o ON o.option_id = pov.option_id
			WHERE
				o.is_variant = 1
				AND pov.product_id = '$product_id'
			GROUP BY o.option_id
		");
		$v_options = $qry->rows;

		// get variant option values assigned to product
		//
		$options_total = 1;
		foreach ($v_options as $k => $v_option) {

			$qry = $this->db->query("SELECT ov.* FROM " . DB_PREFIX . "option_value ov INNER JOIN
				" . DB_PREFIX . "product_option_value pov ON ov.option_value_id = pov.option_value_id INNER JOIN
				`" . DB_PREFIX . "option` o ON o.option_id = pov.option_id
				WHERE
					o.option_id = '$v_option[option_id]'
					AND pov.product_id = '$product_id'
			");

			$v_options[$k]['values'] = $qry->rows;
			$counter = count($qry->rows);
			$v_options[$k]['last_total'] = $options_total;
			$options_total *= $counter;
		}

		// build variants in array
		//
		$variants = array();
		foreach ($v_options as $v_option) {

			$last_total = $v_option['last_total'];
			$total = count($v_option['values']);
			for ($i = 0; $i < $options_total; $i++) {
				if (!isset($variants[$i])) {
					$variants[$i] = array(
						'values'   => array(),
						'model'    => '',
						'weight'   => 0,
						'quantity' => 0,
						'price'    => 0,
						'image'    => '',
						'hash'     => ''
					);
				}
				$variants[$i]['values'][] = $value = $v_option['values'][intval($i / $last_total) % $total];
				$variants[$i]['option_value_ids'][] = $value['option_value_id'];
			}
		}


		$all_vids = array();

		// insert variants to database
		//
		foreach ($variants as $variant) {

			sort($variant['option_value_ids']);
			$hash = implode('.', $variant['option_value_ids']);

			if (!empty($old_variants)) {
				foreach ($old_variants as $ov) {
					if (!empty($ov['hash']) && substr_compare($ov['hash'], $hash, 0, strlen($ov['hash'])) == 0) {
						$variant['model']    = $ov['model'];
						$variant['weight']   = $ov['weight'];
						$variant['price']    = $ov['price'];
						$variant['quantity'] = $ov['quantity'];
						$variant['image']    = $ov['image'];

						if (strcmp($ov['hash'], $hash) === 0) {
							$variant['variant_id'] = $ov['variant_id'];
						}

						break;
					}
				}
			}

			if (empty($variant['variant_id'])) {
				$qry = $this->db->query("INSERT INTO " . DB_PREFIX . "ka_product_variants
					SET product_id = '$product_id',
						model      = '" . $this->db->escape($variant['model']) . "',
						weight     = '" . floatval($variant['weight']) . "',
						price      = '" . floatval($variant['price']) . "',
						image      = '" . $variant['image'] . "',
						hash       = '" . $hash . "',
						quantity   = '" . intval($variant['quantity']) . "'
				");
				$variant_id = $this->db->getLastId();
				if (empty($variant_id)) {
					trigger_error("rebuildVariants: variant_id was not created.");
					return false;
				}

				if (empty($variant['model'])) {
					$this->db->query("UPDATE " . DB_PREFIX . "ka_product_variants SET
						model= '" . $this->db->escape($product['model'] . '_' . $variant_id) . "'
						WHERE variant_id = '$variant_id'
					");
				}
			} else {
				$variant_id = $variant['variant_id'];
			}
			$all_vids[] = $variant_id;

			foreach ($variant['values'] as $value) {

				$this->db->query("REPLACE INTO " . DB_PREFIX . "ka_variant_options SET
					product_id = '$product_id',
					variant_id = '$variant_id',
					option_value_id = '$value[option_value_id]'
				");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "ka_variant_to_discount WHERE
			product_id = '$product_id'
			AND variant_id NOT IN ('" . implode("','", $all_vids) . "')
		");

		$this->db->query("DELETE FROM " . DB_PREFIX . "ka_product_variants WHERE
			product_id = '$product_id'
			AND variant_id NOT IN ('" . implode("','", $all_vids) . "')
		");

		$this->db->query("DELETE FROM " . DB_PREFIX . "ka_variant_options WHERE
			product_id = '$product_id'
			AND variant_id NOT IN ('" . implode("','", $all_vids) . "')
		");

		$this->db->query("DELETE FROM " . DB_PREFIX . "ka_variant_to_special WHERE
			product_id = '$product_id'
			AND variant_id NOT IN ('" . implode("','", $all_vids) . "')
		");

		$def_variant = array();

		if (!$update_default_vid && !empty($product['def_variant_id'])) {
			$qry = $this->db->query("SELECT *,
				(SELECT SUM(quantity) FROM " . DB_PREFIX . "ka_product_variants
					WHERE product_id = '" . $product['product_id'] . "'
				) AS quantity_total
				FROM " . DB_PREFIX . "ka_product_variants WHERE
				product_id = '" . $product['product_id'] . "'
				AND variant_id = '" . $product['def_variant_id'] . "'
			");
			if (!empty($qry->row['variant_id'])) {
				$def_variant = $qry->row;
			}
		}

		if (empty($all_vids)) {

			if (!empty($old_variants)) {
				$this->db->query("UPDATE " . DB_PREFIX . "product SET
					def_variant_id = 0
					WHERE
						product_id = '" . $product['product_id'] . "'
				");
			}

		} elseif ($update_default_vid || empty($def_variant)) {

			$qry = $this->db->query("SELECT *, SUM(quantity) AS quantity_total FROM " . DB_PREFIX . "ka_product_variants WHERE
				product_id = '" . $product['product_id'] . "'
				AND quantity > 0
				GROUP BY variant_id
				ORDER BY price ASC, quantity desc LIMIT 1
			");

			if (empty($qry->row)) {

				// find a variant with the lowest price
				//
				$qry = $this->db->query("SELECT *, 0 AS quantity_total FROM " . DB_PREFIX . "ka_product_variants WHERE
					product_id = '" . $product['product_id'] . "'
					AND price > 0
					GROUP BY variant_id
					ORDER BY price ASC LIMIT 1
				");
			}

			if (empty($qry->row)) {

				// find the first available variant
				//
				$qry = $this->db->query("SELECT *, 0 AS quantity_total FROM " . DB_PREFIX . "ka_product_variants WHERE
					product_id = '" . $product['product_id'] . "'
					GROUP BY variant_id LIMIT 1
				");
			}

			if (empty($qry->row['product_id'])) {
				trigger_error(__METHOD__ . ": variants records are not found for a product with variant options");
			}

			$this->db->query("UPDATE " . DB_PREFIX . "product SET
				price = '" . $qry->row['price'] . "',
				quantity = '" . $qry->row['quantity_total'] . "',
				def_variant_id = '" . $qry->row['variant_id'] . "'
				WHERE
					product_id = '" . $product['product_id'] . "'
			");
		} else {

			$this->db->query("UPDATE " . DB_PREFIX . "product SET
				price = " . $def_variant['price'] . ",
				quantity = '" . $def_variant['quantity_total'] . "'
				WHERE
					product_id = '" . $product['product_id'] . "'
			");
		}

		return true;
	}

	public function getTotalProductVariants($product_id, $data = array()) {

		$res = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "ka_product_variants
			WHERE product_id = '$product_id'
		")->row;


		return $res['total'];
	}


	public function addProductVariants($product_id, $data) {

		$variant_pairs = array();

		if (!empty($data['product_variants'])) {
			foreach ($data['product_variants'] as $v) {
				$v['org_variant_id'] = $v['variant_id'];
				if (!isset($v['ka_cost_price'])) {
					$v['ka_cost_price'] = 0;
				}
				$this->db->query("INSERT " . DB_PREFIX . "ka_product_variants
					SET
						product_id = '$product_id',
						model    = '" . $this->db->escape($v['model']) . "',
						price    = '" . floatval($v['price']) . "',
						ka_cost_price = '" . floatval($v['ka_cost_price']) . "',
						quantity = '" . intval($v['quantity']) . "',
						image    = '" . $this->db->escape(html_entity_decode($v['image'], ENT_QUOTES, 'UTF-8')) . "',
						weight   = '" . floatval($v['weight']) . "',
						hash     = '" . $v['hash'] . "'
				");

				$v['variant_id'] = $this->db->getLastId();
				$variant_pairs[$v['org_variant_id']] = $v['variant_id'];

				// update default variant_id
				//
				if ($v['org_variant_id'] == $data['def_variant_id']) {
					$this->db->query("UPDATE " . DB_PREFIX . "product SET
						price = '" . (float)$v['price'] . "',
						def_variant_id = '" . $v['variant_id'] . "'
						WHERE
						product_id = '" . (int) $product_id . "'
					");
				}

				// copy option identifiers to a variant data
				//
				$qry_org_options = $this->db->query("SELECT * FROM " . DB_PREFIX . "ka_variant_options
					WHERE variant_id = '$v[org_variant_id]'
				");
				foreach ($qry_org_options->rows as $org_opt) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "ka_variant_options SET
						product_id = '$product_id',
						variant_id = '$v[variant_id]',
						option_value_id = '$org_opt[option_value_id]'
					");
				}
			}

			$this->rebuildVariants($product_id);
		}

		return $variant_pairs;
	}

	/*
		requested by editProduct(...)
	*/
	public function editProductVariants($product_id, $data) {

		$this->_editProductVariants($product_id, $data);

		if (empty($data['variants_serialized'])) {
			$this->rebuildVariants($product_id, false);
			return;
		}

		if (!empty($data['variants_default_id'])) {
			$data['def_variant_id'] = $data['variants_default_id'];
		}

		$fields = json_decode(html_entity_decode($data['variants_serialized'], ENT_QUOTES, 'UTF-8'), true);

		$data['product_variants'] = array();
		foreach ($fields as $k => $v) {
			$str = $k . '=' . $v;
			parse_str($str, $res);
			$data['product_variants'] = array_replace_recursive($data['product_variants'], $res['product_variants']);
		}

		$this->_editProductVariants($product_id, $data);

		$this->rebuildVariants($product_id, false);
	}


	protected function _editProductVariants($product_id, $data) {

		if (!empty($data['product_variants'])) {
			foreach ($data['product_variants'] as $v) {

				if (!isset($v['ka_cost_price'])) {
					$v['ka_cost_price'] = 0;
				}

				$this->db->query("UPDATE " . DB_PREFIX . "ka_product_variants
					SET
						model    = '" . $this->db->escape($v['model']) . "',
						ean    = '" . $this->db->escape($v['ean']) . "',
						price    = '" . floatval($v['price']) . "',
						ka_cost_price = '" . floatval($v['ka_cost_price']) . "',
						quantity = '" . intval($v['quantity']) . "',
						image    = '" . $this->db->escape(html_entity_decode($v['image'], ENT_QUOTES, 'UTF-8')) . "',
						weight   = '" . floatval($v['weight']) . "'
					WHERE
						variant_id = '" . (int)$v['variant_id'] . "'"
				);

				if (!empty($data['def_variant_id']) && $v['variant_id'] == $data['def_variant_id']) {
					$this->db->query("UPDATE " . DB_PREFIX . "product SET
						price = '" . (float)$v['price'] . "',
						def_variant_id = '" . $v['variant_id'] . "'
						WHERE
						product_id = '" . (int) $product_id . "'
					");
				}

			}
		}
	}

}

class_alias(__NAMESPACE__ . '\ModelVariants', 'ModelExtensionKaExtensionsKaVariantsVariants');
