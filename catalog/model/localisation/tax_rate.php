<?php
class ModelLocalisationTaxRate extends Model {

	public function setShippingAddress($country_id) {
		$tax_query = $this->db->query("SELECT tr1.tax_class_id, tr2.tax_rate_id, tr2.name, tr2.rate, tr2.type, tr1.priority
																		FROM " . DB_PREFIX . "tax_rule tr1
																			LEFT JOIN " . DB_PREFIX . "tax_rate tr2 ON (tr1.tax_rate_id = tr2.tax_rate_id)
																			INNER JOIN " . DB_PREFIX . "tax_rate_to_customer_group tr2cg ON (tr2.tax_rate_id = tr2cg.tax_rate_id)
																			LEFT JOIN " . DB_PREFIX . "zone_to_geo_zone z2gz ON (tr2.geo_zone_id = z2gz.geo_zone_id)
																			LEFT JOIN " . DB_PREFIX . "geo_zone gz ON (tr2.geo_zone_id = gz.geo_zone_id)
																		WHERE tr1.based = 'shipping' AND tr2cg.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND
																					z2gz.country_id = '" . (int)$country_id . "'
																		ORDER BY tr1.priority ASC
                                    limit 1
                                    ");

      if($tax_query->row){
  		return $tax_query->row;
     }
	}

}
