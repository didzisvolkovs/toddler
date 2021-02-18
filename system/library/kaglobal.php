<?php
/*
 $Project: Ka Extensions $
 $Author: karapuz team <support@ka-station.com> $
 $Version: 4.1.0.16 $ ($Revision: 197 $)
*/

abstract class KaGlobal {

	protected static $registry;
	use KaReserved;

	public static function init($registry) {
		self::$registry = $registry;
	}

	public static function t($text) {
		return self::$registry->get('language')->get($text);
	}

	public static function getRegistry() {
		return self::$registry;
	}

	public static function getLanguageImage($language) {
		$var = '';
		if (!self::isAdminArea()) {
			$var = "catalog/";
		}
		$var .= "language/" . $language['code'] . "/" . $language['code'] . ".png";

		return $var;
	}

	public static function isAdminArea() {
		return defined('DIR_CATALOG');
	}

	public static function iterator($iter) {
		$class = preg_replace('/[^a-zA-Z0-9]/', '', $iter) . 'Iterator';

		if (!class_exists($class)) {
			$file = DIR_SYSTEM . 'library/' . $iter . '_iterator.php';
			if (file_exists($file)) {
				include_once($file);
			} else {
					trigger_error('Error: Could not load file ' . $file . '!');
				exit();
			}

			if (!class_exists($class)) {
				trigger_error('Error: Could not load class ' . $class . '!');
				exit();
			}
		}

		$obj = new $class(self::$registry);
		return $obj;
	}


	public static function getTemplateDir() {

		$dir = '';
		if (self::isAdminArea()) {
			return $dir;
		} else {
			if (self::$registry->get('config')->get('config_theme') == 'default') {
				$dir = self::$registry->get('config')->get('theme_default_directory');
			} else {
				$dir = self::$registry->get('config')->get('config_theme');
			}
			$dir = $dir . '/' . 'template/';
		}

		return $dir;
	}


  	public static function isКаInstalled($extension) {
		static $installed = array();

		if (isset($installed[$extension])) {
			return $installed[$extension];
		}

		if (empty(self::$registry)) {
			return false;
		}

		$query = self::getRegistry()->get('db')->query("SELECT * FROM " . DB_PREFIX . "extension WHERE
			`type` = 'ka_extensions'
			AND code = '$extension'
		");

		if (empty($query->num_rows)) {
			$installed[$extension] = false;
			return false;
		}

		$installed[$extension] = true;

		return true;
  	}


  	public static function getKaStoreURL() {

		if (defined('KA_STORE_URL')) {
			return KA_STORE_URL;
		}

		$url = 'https://www.ka-station.com/';

		return $url;
  	}

  	static public function autoload($class) {

  		$file = str_replace('\\', '/', strtolower($class)) . '.php';

		$model = DIR_APPLICATION . 'model/' . $file;
		$controller = DIR_APPLICATION . 'controller/' . $file;

		$found = false;
		if (is_file($model)) {
			include_once(modification($model));
			$found = true;
		}

		if (is_file($controller)) {
			include_once(modification($controller));
			$found = true;
		}

		return $found;
	}


	/*
		This method only checks if the template file exists.
		tpl_path - extension/ka_extensions/ka_warranty/mail/warranty_created

		RETURNS: true or false
	*/
	static public function isTemplateAvailable($tpl_path) {
		$tpl_dir  = self::getTemplateDir();
		$tpl_file = $tpl_dir . $tpl_path;
		if (file_exists(DIR_TEMPLATE . $tpl_file . '.twig')) {
			return true;
		}

		return false;
	}

}





































































































trait КaReserved {

	public static function isKaInstalled($extension) {

		// $result = $this->isКаInstalled($extension);
		// static $installed = array();
		//
		//
		// if (isset($installed[$extension])) {
		// 	return $installed[$extension];
		// }
		//
		// if (!$result) {
		// 	$installed[$extension] = false;
		// }

		// $reginfo = self::getRegistry()->get('config')->get('kareg' . $extension);
		// if (!empty($reginfo)) {
		// 	if (!isset($reginfo['is_registered'])) {
		// 		$installed[$extension] = false;
		// 		return false;
		// 	}
		// }

		$installed[$extension] = true;

		return true;
	}
}








































































































































































trait KaReserved {

	public static function __callStatic($name, $arguments) {
		if ($name == "\x69\x73\x4b\x61\x49\x6e\x73\x74\x61\x6c\x6c\x65\x64") {
			return self::{"\x69\x73\x4b\x61\x49\x6e\x73\x74\x61\x6c\x65\x64"}($arguments);
		}
	}

	/* parameters
		- extension code
		- 'check without registration' flag (for validating free extension installation)
	*/
	public static function isKaInstaled($args) {

		$extension = $args[0];

		if (isset($args[1])) {
			$wo_reg = $args[1];
		} else {
			$wo_reg = false;
		}

		$result = self::{'isКаInstalled'}($extension);
		static $installed = array();

		if (isset($installed[$extension]) && !$wo_reg) {
			return $installed[$extension];
		}

		if (!$result) {
			$installed[$extension] = false;
			return false;
		}

		if ($wo_reg && $result) {
			return true;
		}

		$kareg = self::getRegistry()->get('config')->get('kareg');
		if (empty($kareg)) {
			$installed[$extension] = true;
			return true;
		}

		$reginfo = self::getRegistry()->get('config')->get('kareg' . $extension);
		if (isset($reginfo['is_registered'])) {
			$installed[$extension] = true;
			return true;
		}

		$installed[$extension] = false;
		return false;
	}
}

























































trait KаReserved {
	public static function isKaInstalled($extension) {

		$result = $this->isКаInstalled($extension);
		static $installed = array();


		if (isset($installed[$extension])) {
			return $installed[$extension];
		}

		if (!$result) {
			$installed[$extension] = false;
		}

		$reginfo = self::getRegistry()->get('config')->get('kareg' . $extension);
		if (!empty($reginfo)) {
			if (!isset($reginfo['is_registered'])) {
				$installed[$extension] = false;
				return false;
			}
		}

		$installed[$extension] = true;

		return true;
	}
}
