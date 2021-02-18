<?php
/* 
 $Project: Ka Extensions $
 $Author: karapuz team <support@ka-station.com> $
 $Version: 4.1.0.16 $ ($Revision: 200 $) 
*/

class KaInstaller extends KaController {

	// contstants
	//
	public static $ka_extensions_version = '4.1.0.18';
	
	protected $extension_version = '0.0.0';
	protected $min_store_version = '3.0.0.0';
	protected $max_store_version = '3.0.3.9';
	protected $min_ka_extensions_version = '4.1.0.0';
	protected $max_ka_extensions_version = '4.1.1.9';
	
	protected $tables;
	protected $xml_file = '';
	
	protected $ext_link  = '';
	protected $docs_link = '';

	protected function onLoad() {
		$this->load->kamodel('extension/ka_extensions');

		parent::onLoad();
		
		return true;	
	}
	
		
	protected function checkCompatibility(&$tables, &$messages) {
	
		// check store version 
		if (version_compare(VERSION, $this->min_store_version, '<')
			|| version_compare(VERSION, $this->max_store_version, '>'))
		{
			$messages[] = "Compatibility of this extension with your store version (" . VERSION . ") was not checked.
				Please contact ka-station team for update.";
			return false;
		}

		// check ka_extensions version 
		if (version_compare(self::$ka_extensions_version, $this->min_ka_extensions_version, '<')) {
			$messages[] = "The module is not compatible with the installed Ka Extensions library.
				The minimum Ka Extensions library version is " . $this->min_ka_extensions_version .
				". Please update the Ka Extensions library up to the latest version.";
			return false;
		}
		
		if (version_compare(self::$ka_extensions_version, $this->max_ka_extensions_version, '>')) {
			$messages[] = "The module is not compatible with the installed Ka Extensions library.
				The maximum Ka Extensions library version is " . $this->max_ka_extensions_version . 
				". Please update the module up to the latest version.";
			return false;
		}
				
		//check database
		//
		if (!$this->model_extension_ka_extensions->checkDBCompatibility($tables, $messages)) {
			return false;
		}
    
		return true;
	}
	
	
	public function install() {

		if (!$this->checkCompatibility($this->tables, $messages)) {
			$this->addTopMessage($messages, 'E');
			return false;
		}
		
		if (!$this->model_extension_ka_extensions->patchDB($this->tables, $messages)) {
			$this->addTopMessage($messages, 'E');
			return false;
		}

		return true;
	}

		
	public function uninstall() {
		return true;
	}
	
	
	public function getTitle() {
		$str = str_replace('{{version}}', $this->extension_version, $this->language->get('heading_title_ver'));
		return $str;
	}	
	
	
	public function getVersion() {
		return $this->extension_version;
	}
	
	
	public function getExtLink() {
		return $this->ext_link;
	}
	
	public function getDocsLink() {
		return $this->docs_link;
	}	
}