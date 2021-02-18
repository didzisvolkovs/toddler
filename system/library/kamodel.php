<?php
/* 
 $Project: Ka Extensions $
 $Author: karapuz team <support@ka-station.com> $
 $Version: 4.1.0.16 $ ($Revision: 197 $) 
*/

abstract class KaModel extends Model {

	protected $lastError;

	function __construct($registry) {
		parent::__construct($registry);

		$this->kadb = new KaDb($this->db);
				
		$this->onLoad();
	}

	
	public function getLastError() {
		return $this->lastError;
	}
	
	protected function onLoad() {
		return true;
	}
	
	protected function setSession($key, $value) {

		$class = get_class($this);
	
		if (!isset($this->session->data["ka_session_$class"])) {
			$this->session->data["ka_session_$class"] = array();
		}
		
		$this->session->data["ka_session_$class"][$key] = $value;
	}
	
	
	protected function &getSession($key) {
		$class = get_class($this);
		
		if (!isset($this->session->data["ka_session_$class"])) {
			$this->session->data["ka_session_$class"] = array();
		}
		
		if (!isset($this->session->data["ka_session_$class"][$key])) {
			$this->session->data["ka_session_$class"][$key] = null;
			return $this->session->data["ka_session_$class"][$key];
		}
		
		return $this->session->data["ka_session_$class"][$key];
	}
	
	
	public function __get($key) {
	
		if (strncasecmp('kamodel_', $key, 8) === 0) {
			$key = substr($key, 8);
			$ns = $this->getNamespace();
			if (!empty($ns)) {
				$key = str_replace('/', '_', $ns) . $key;
			}
			$key = 'model_' . $key;
		}
		
		return parent::__get($key);
	}	
	
	
	protected function getNamespace() {
		$class = get_class($this);
		$pos   = strripos($class, '\\');
		$ns    = '';
		if ($pos == 0) {
			$ns = $model;
		} else {
			$ns = str_replace('\\', '/', substr($class, 0, $pos)) . '/';
		}
		return $ns;	
	}
		
	protected function kamodel($model) {
		$ns = $this->getNamespace();
		$this->load->kamodel($ns . $model);
	}
}