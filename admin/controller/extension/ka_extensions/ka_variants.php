<?php
/*
	$Project: Product Variants $
	$Author: karapuz team <support@ka-station.com> $
	$Version: 3.0.2 $ ($Revision: 249 $)
*/
namespace extension\ka_extensions;

class ControllerKaVariants extends \KaInstaller {

	protected $extension_version = '3.0.2.1';
	protected $min_store_version = '3.0.0.0';
	protected $max_store_version = '3.0.3.9';
	protected $min_ka_extensions_version = '4.1.0.5';
	protected $max_ka_extensions_version = '4.1.1.9';

	protected $tables;

	public function getTitle() {
		$this->load->language('extension/ka_extensions/ka_variants');

		$str = str_replace('{{version}}', $this->extension_version, $this->language->get('heading_title_ver'));
		return $str;
	}

	protected function onLoad() {
		$this->load->model('user/user_group');
		$this->load->model('setting/setting');

		$this->load->language('extension/ka_extensions/ka_variants');

 		$this->tables = array(
 			'ka_product_variants' => array(
 				'fields' => array(
					'variant_id' => array(
						'type' => 'int(11)',
					),
					'product_id' => array(
						'type' => 'int(11)',
					),
					'price' => array(
						'type' => 'decimal(15,4)',
					),
					'ka_cost_price' => array(
						'type' => 'decimal(15,4)',
						'query' => 'ALTER TABLE `' . DB_PREFIX . 'ka_product_variants` ADD `ka_cost_price` DECIMAL(15,4) NOT NULL',
					),
					'quantity' => array(
						'type' => 'int(8)',
					),
					'weight' => array(
						'type' => 'decimal(15,8)',
					),
					'model' => array(
						'type' => 'varchar(64)',
					),
					'image' => array(
						'type' => 'varchar(255)',
					),
					'hash' => array(
						'type' => 'varchar(255)',
					),
				),
				'indexes' => array(
					'PRIMARY' => array(
						'fields' => 'variant_id',
					),
					'product_id' => array(
						'fields' => 'product_id',
					),
					'model' => array(
						'fields' => array('model'),
						'query'  => "ALTER TABLE `" . DB_PREFIX . "ka_product_variants` ADD INDEX ( `model` )"
					),
				)
 			),

 			'ka_variant_options' => array(
 				'fields' => array(
					'variant_id' => array(
						'type' => 'int(11)',
					),
					'option_value_id' => array(
						'type' => 'int(11)',
					),
					'product_id' => array(
						'type' => 'int(11)',
					),
				),
				'indexes' => array(
					'PRIMARY' => array(
						'fields' => array('variant_id', 'option_value_id'),
					),
					'product_id' => array(
						'fields' => 'product_id',
					),
				),
			),

 			'ka_variant_to_discount' => array(
 				'fields' => array(
					'variant_id' => array(
						'type' => 'int(11)',
					),
					'discount_id' => array(
						'type' => 'int(11)',
					),
					'product_id' => array(
						'type' => 'int(11)',
					),
				),
				'indexes' => array(
					'PRIMARY' => array(
						'fields' => array('variant_id', 'discount_id'),
					),
					'product_id' => array(
						'fields' => 'product_id',
					),
				),
			),

 			'ka_variant_to_special' => array(
 				'fields' => array(
					'variant_id' => array(
						'type' => 'int(11)',
					),
					'special_id' => array(
						'type' => 'int(11)',
					),
					'product_id' => array(
						'type' => 'int(11)',
					),
				),
				'indexes' => array(
					'PRIMARY' => array(
						'fields' => array('variant_id', 'special_id'),
					),
					'product_id' => array(
						'fields' => 'product_id',
					),
				),
			),

 			'option' => array(
 				'fields' => array(
					'is_variant' => array(
						'type' => 'tinyint(4)',
						'query' => "ALTER TABLE `" . DB_PREFIX . "option` ADD `is_variant` TINYINT(4) NOT NULL DEFAULT '0'",
					),
					'can_be_disabled' => array(
						'type' => 'tinyint(1)',
						'query' => "ALTER TABLE `" . DB_PREFIX . "option` ADD `can_be_disabled` TINYINT(1) NOT NULL DEFAULT '0'",
					),
				),
			),

 			'order_product' => array(
 				'fields' => array(
					'variant_id' => array(
						'type' => 'int(11)',
						'query' => "ALTER TABLE `" . DB_PREFIX . "order_product` ADD `variant_id` int(11) NOT NULL",
					),
					'variant_hash' => array(
						'type' => 'varchar(255)',
						'query' => "ALTER TABLE `" . DB_PREFIX . "order_product` ADD `variant_hash` VARCHAR( 255 ) NOT NULL",
					),
				),
			),

 			'product' => array(
 				'fields' => array(
					'def_variant_id' => array(
						'type' => 'int(11)',
						'query' => "ALTER TABLE `" . DB_PREFIX . "product` ADD `def_variant_id` INT( 11 ) NOT NULL",
					),
				),
			),
		);


		$this->tables['ka_product_variants']['query'] = "
			CREATE TABLE `" . DB_PREFIX ."ka_product_variants` (
			  `variant_id` int(11) NOT NULL AUTO_INCREMENT,
			  `product_id` int(11) NOT NULL,
			  `price` decimal(15,4) NOT NULL,
			  `ka_cost_price` decimal(15,4) NOT NULL,
			  `quantity` int(8) NOT NULL,
			  `weight` decimal(15,8) NOT NULL,
			  `model` varchar(64) NOT NULL,
			  `image` varchar(255) NOT NULL,
			  `hash` varchar(255) NOT NULL,
			  PRIMARY KEY (`variant_id`),
			  KEY `product_id` (`product_id`),
			  KEY `model` (`model`)
			) DEFAULT CHARSET=utf8;
		";

		$this->tables['ka_variant_options']['query'] = "
			CREATE TABLE `" . DB_PREFIX . "ka_variant_options` (
			  `variant_id` int(11) NOT NULL,
			  `option_value_id` int(11) NOT NULL,
			  `product_id` int(11) NOT NULL,
			  PRIMARY KEY (`variant_id`,`option_value_id`),
			  KEY `product_id` (`product_id`)
			) DEFAULT CHARSET=utf8;
		";

		$this->tables['ka_variant_to_discount']['query'] = "
			CREATE TABLE `" . DB_PREFIX . "ka_variant_to_discount` (
			  `variant_id` int(11) NOT NULL,
			  `discount_id` int(11) NOT NULL,
			  `product_id` int(11) NOT NULL,
			  UNIQUE KEY `variant_id` (`variant_id`,`discount_id`),
			  KEY `product_id` (`product_id`)
			) DEFAULT CHARSET=utf8;
		";


		$this->tables['ka_variant_to_special']['query'] = "
			CREATE TABLE `" . DB_PREFIX . "ka_variant_to_special` (
			  `variant_id` int(11) NOT NULL,
			  `special_id` int(11) NOT NULL,
			  `product_id` int(11) NOT NULL,
			  UNIQUE KEY `variant_id` (`variant_id`,`special_id`),
			  KEY `product_id` (`product_id`)
			) DEFAULT CHARSET=utf8;
		";

		return true;
	}

	public function index() {

		$heading_title = $this->getTitle();
		$this->document->setTitle($heading_title);

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('ka_variants', $this->request->post);
			$this->addTopMessage($this->language->get('Settings have been stored sucessfully.'));

			$this->response->redirect($this->url->link('marketplace/extension', 'type=ka_extensions&user_token=' . $this->session->data['user_token'], true));
		}

		$this->data['heading_title']   = $heading_title;

		$this->data['ka_variants_show_discounted_price'] = $this->config->get('ka_variants_show_discounted_price');

		$this->data['button_save']     = $this->language->get('button_save');
		$this->data['button_cancel']   = $this->language->get('button_cancel');
		$this->data['extension_version']  = $this->extension_version;

		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => false
		);
  		$this->data['breadcrumbs'][] = array(
	 		'text'      => $this->language->get('Ka Extensions'),
			'href'      => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true),
   			'separator' => ' :: '
 		);

 		$this->data['breadcrumbs'][] = array(
	 		'text'      => $heading_title,
			'href'      => $this->url->link('extension/ka_extensions/ka_variants', 'user_token=' . $this->session->data['user_token'], true),
   			'separator' => ' :: '
 		);

		$this->data['action'] = $this->url->link('extension/ka_extensions/ka_variants', 'user_token=' . $this->session->data['user_token'], true);
		$this->data['cancel'] = $this->url->link('marketplace/extension', 'type=ka_extensions&user_token=' . $this->session->data['user_token'], true);

		$this->template = 'extension/ka_extensions/ka_variants/settings';
		$this->children = array(
			'common/header',
			'common/column_left',
			'common/footer'
		);

		$this->response->setOutput($this->render());
	}


	protected function validate() {

		if (!$this->user->hasPermission('modify', 'extension/ka_extensions/ka_variants')) {
			$this->addTopMessage($this->language->get('error_permission'), 'E');

			return false;
		}

		return true;
	}


	public function install() {

		// if (!parent::install()) {
		// 	return false;
		// }

		$settings = array(
			'ka_variants_show_discounted_price' => 1,
		);
		$this->model_setting_setting->editSetting('ka_variants', $settings);

		$this->load->model('setting/setting');

		// grant permissions
		$this->load->model('user/user_group');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/ka_extensions/ka_variants/product');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/ka_extensions/ka_variants/product');

		return true;
	}


	public function uninstall() {
		return true;
	}
}

class_alias(__NAMESPACE__ . '\ControllerKaVariants', 'ControllerExtensionKaExtensionsKaVariants');
