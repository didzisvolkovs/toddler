<?php
/* 
 $Project: Ka Extensions $
 $Author: karapuz team <support@ka-station.com> $
 $Version: 4.1.0.16 $ ($Revision: 198 $) 
*/

class KaMail {
	public $mail;
	public $sender;
	public $data;
	public $images;
	public $registry;

	function __construct($registry, $store_id = 0) {
	
		$this->language = $registry->get('language');
		$this->global_config = $registry->get('config');
		$this->db       = $registry->get('db');
		$this->request  = $registry->get('request');
		$this->session  = $registry->get('session');
		$this->log      = $registry->get('log');
		$this->load     = $registry->get('load');
		$this->registry = $registry;
	
		$mail_engine = $this->global_config->get('config_mail_engine');
		if (empty($mail_engine)) {
			$this->mail = new Mail();
		} else {
			$this->mail = new Mail($mail_engine);
		}
		$this->mail->parameter = $this->global_config->get('config_mail_parameter');
		$this->mail->smtp_hostname = $this->global_config->get('config_mail_smtp_hostname');
		$this->mail->smtp_username = $this->global_config->get('config_mail_smtp_username');
		$this->mail->smtp_password = html_entity_decode($this->global_config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$this->mail->smtp_port     = $this->global_config->get('config_mail_smtp_port');
		$this->mail->smtp_timeout  = $this->global_config->get('config_mail_smtp_timeout');
	
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "store WHERE store_id = '" . (int)$store_id . "'");
		
		if (!empty($query->row)) {
			$this->data['store_name'] = $query->row['name'];
			$this->data['sender']     = $query->row['name'];
			$this->data['store_url']  = $query->row['url'] . 'index.php?route=account/login';

			$query =  $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "setting WHERE 
				store_id = '" . (int)$store_id . "' AND `code` = 'config'"
			);
			$this->config = new Config();
			
			foreach ($query->rows as $setting) {
				if (!$setting['serialized']) {
					$this->config->set($setting['key'], $setting['value']);
				} else {
					$this->config->set($setting['key'], json_decode($result['value'], true));
				}
			}
			
		} else {
			$this->config = &$this->global_config;
			$this->data['store_name'] = $this->config->get('config_name');
			$url = (defined('HTTP_CATALOG')) ? HTTP_CATALOG : HTTP_SERVER;
			$this->data['store_url']  = $url . 'index.php?route=account/login';
		}
		
		$this->data['config'] = &$this->config;
	}


/*
	$tpl has to contain the template name without the extension, Example:
		extension/ka_extensions/product_warranty/mail/product_warranty_created
*/		
	public function send($from, $to, $subject, $tpl, $extra = array()) {

		if (empty($from)) {
			$from = $this->config->get('config_email');
		}

		if (empty($this->data['sender'])) {
			$sender = html_entity_decode($this->config->get('config_name'));
		} else {
			$sender = html_entity_decode($this->data['sender']);
		}

		// HTML Mail
		$this->data['subject'] = $subject;
		$logo = $this->config->get('config_image');
		if (!is_file(DIR_IMAGE . $logo)) {
			$logo = 'no_image.png';
		}
		$this->images['logo'] = $logo;

		$html = $text = '';

		// load a text file
		//
		$template = $tpl . '_txt';
		try {
			// the view substitutes the current template directory itself
			$text = $this->load->view($template, $this->data);
		} catch (\Exception $e) {
		}
		
		if (!empty($this->images)) {
			foreach ($this->images as $ik => $iv) {
		      	if (!empty($iv) && file_exists(DIR_IMAGE . $iv)) {
		      		$filename = DIR_IMAGE . $iv;
		      		$this->data[$ik] = $this->data['_images'][$ik] = 'cid:' . urlencode(basename($filename));
					$this->mail->addAttachment($filename);
			  	}
		  	}
		}

		// load an html file
		try {
			$html = $this->load->view($tpl, $this->data);
		} catch (\Exception $e) {
		
		}
		
		if (empty($html) && empty($text)) {
			$this->log->write("WARNING: template is not found: $tpl");
			return false;
		}
		
		$this->mail->setTo($to);
		$this->mail->setFrom($from);
		$this->mail->setSender($sender);
		$this->mail->setSubject($subject);
		$this->mail->setText($text);
		$this->mail->setHtml($html);

		$this->mail->send();

		return true;
	}
	
	public function addAttachment($filename) {
		return $this->mail->addAttachment($filename);
	}
}