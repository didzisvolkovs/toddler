<?php
/*
 $Project: Ka Extensions $
 $Author: karapuz team <support@ka-station.com> $
 $Version: 4.1.0.16 $ ($Revision: 197 $)
*/

abstract class KaController extends Controller {

	protected $data = array();
	protected $kadb = null;
	protected $children = array();

	function __construct($registry) {
		parent::__construct($registry);

		$this->kadb = new \KaDB($this->db);

		// if (\KaGlobal::isAdminArea()) {
			$this->document->addStyle('view/stylesheet/ka_extensions.css');
		// }
		$this->onLoad();
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


	protected function addTopMessage($msg, $type = 'I') {

		if (!is_array($msg)) {
			$msg = array($msg);
		}

		foreach ($msg as $text) {
			$this->session->data['ka_top_messages'][] = array(
				'type'    => $type,
				'content' => $text
			);
		}
	}


	protected function getTopMessages($clear = true) {

		if (isset($this->session->data['ka_top_messages'])) {
			$top = $this->session->data['ka_top_messages'];
		} else {
			$top = null;
		}

		if ($clear) {
			$this->session->data['ka_top_messages'] = null;
		}
		return $top;
	}


	protected function render() {

		$this->data['top_messages'] = $this->getTopMessages();

		$file = 'extension/ka_extensions/common/ka_top';

		$this->data['ka_top'] = $this->load->view($file, $this->data);

		$file = 'extension/ka_extensions/common/ka_breadcrumbs';
		$this->data['ka_breadcrumbs'] = $this->load->view($file, $this->data);

		if (!empty($this->children)) {
			foreach ($this->children as $child) {
				$this->data[basename($child)] = $this->load->controller($child);
			}
		}

		return $this->load->view($this->template, $this->data);
	}


	protected function setOutput($param = null) {
		if (!is_null($param)) {
			$this->response->setOutput($param);
		} else {
			$this->response->setOutput($this->render());
		}
	}


	protected function onLoad() {
		return true;
	}


	protected function getNamespace() {
		$class = get_class($this);
		$pos   = strripos($class, '\\');
		$ns    = '';
		if ($pos) {
			$ns = str_replace('\\', '/', substr($class, 0, $pos)) . '/';
		}
		return $ns;
	}

	protected function kamodel($model) {
		$ns = $this->getNamespace();
		return $this->load->kamodel($ns . $model);
	}


	public function __get($key) {

		if ($key == 'params') {
			return null;
		}

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

}
