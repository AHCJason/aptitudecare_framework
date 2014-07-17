<?php 

/*
 *	Set site directories
 */
 	
	define('CSS', SITE_URL . DS . 'css/');
	define('IMAGES', SITE_URL . DS . 'img/');
	define('JS', SITE_URL . DS . 'js/');
	define('FILES', SITE_URL . DS . 'files/');
	define('FONTS', SITE_URL . DS . 'fonts/');
	define('VIEWS', PROTECTED_DIR . DS . 'views');


/*
 *	The config file contains basic, site-wide configurations. 
 *
 */
	
	if (file_exists(PROTECTED_DIR . DS . 'Configs' . DS . 'config.php')) {
		require(PROTECTED_DIR . DS . 'Configs' . DS . 'config.php');
	}
	

	
/*
 * Error Reporting
 *
 */
 
 	set_error_handler('_exeption_handler');

 	if (file_exists(SITE_DIR . DS . '.development')) {
	 	ini_set('html_errors', 'on');
	 	ini_set('display_errors', 'on');
 	} else {
	 	ini_set('html_errors', 'off');
	 	ini_set('display_errors', 'off');
 	}
	
	
/*
 * -------------------------------------------
 * INCLUDE ALL REQUIRED FILES
 * -------------------------------------------
 *
 */	
 
 	require (FRAMEWORK_DIR . DS . 'Vendors/Smarty-3.1.19/libs/Smarty.class.php');
 	require (FRAMEWORK_DIR . DS . 'Controllers' . DS . 'MainController.php');
 	require (FRAMEWORK_DIR . DS . 'Libs/Common.php');
 	
 	spl_autoload_register('__autoload');
 	
 	function __autoload($className) {
	 	// list of directories to scan
		$dirs = array(
			FRAMEWORK_DIR . DS . 'Controllers',
			FRAMEWORK_DIR . DS . 'Libs/',
			FRAMEWORK_DIR . DS . 'Libs/Components/',
			FRAMEWORK_DIR . DS . 'Models/',
			PROTECTED_DIR . DS . 'Controllers/',
			PROTECTED_DIR . DS . 'Libs/',
			PROTECTED_DIR . DS . 'Libs/Components/',
			PROTECTED_DIR . DS . 'Helpers/',
			PROTECTED_DIR . DS . 'Models/'	
		);
		
		foreach ($dirs as $d) {
			if (file_exists("{$d}/{$className}.php")) {
				require ("{$d}/{$className}.php");
			} elseif (file_exists("{$d}/{$className}Controller.php")) {
				require ("{$d}/{$className}Controller.php");
			} elseif (file_exists("{$d}/{$className}Component.php")) {
				require ("{$d}/{$className}Component.php");
			} elseif (file_exists("{$d}/{$className}Helper.php")) {
				require ("{$d}/{$className}Helper.php");
			}
		}
 	}

	
	
	
	
	
	
/*
	$df = array();

	foreach ($dirs as $dir) {

		if (is_dir($dir)) {
			$df = preg_grep('/^([^.])/', scandir($dir));
		}
		
		foreach ($df as $f) {
			if (strpos($f, '.php')) {
				require($dir . $f);
			}
			
		}
	}

	// include the MainController and Model
	require_once(FRAMEWORK_DIR . DS . 'Controllers' . DS . 'MainController.php');
	//require_once(FRAMEWORK_DIR . DS . 'Models' . DS . 'Model.php');
*/


/*
 *	Instantiate the database class for use site wide
 */
	


	if (! function_exists('db')) {
		function db() {
			global $db;
			return $db;
		}
	}

/*
 *	Include file to establish a database connection.
 *
 */

	require(PROTECTED_DIR . DS . 'Configs' . DS . 'database.php');



/*
 * -------------------------------------------
 * Instantiate Smarty
 * -------------------------------------------
 *
 */
 
	$smarty = new Smarty();
	$smarty->setTemplateDir(PROTECTED_DIR . DS . 'Views');
	$smarty->setCompileDir(PROTECTED_DIR . DS . 'Compile');
	$smarty->setCacheDir(PROTECTED_DIR . DS . 'Cache');
	$smarty->setConfigDir(PROTECTED_DIR . DS . 'Configs');
	
	$smarty->assign(array(
		'ROOT' => ROOT,
		'SITE_URL' => SITE_URL
	));
	
	$smarty->escape_html = true;
 


/*
 * Include any additional variables to be available globally
 * 
 */


/*
if (! function_exists ('session')) {
	function session() {
		global $session;
		return $session;
	}
} 
*/

	$input = new Input();
	if (! function_exists('input')) {
		function input() {
			global $input;
			return $input;
		}
	}
	
	if (! function_exists('auth')) {
		function auth() {
			global $auth;
			return $auth;
		}
	}


	if (! function_exists('smarty')) {
		function smarty() {
			global $smarty;
			return $smarty;
		}
	}


 /*
 * INCLUDE ROUTES.PHP 
 * 
 */

 
  
	if (file_exists (FRAMEWORK_DIR . '/Configs/routes.php')) {
		require (FRAMEWORK_DIR . '/Configs/routes.php');
	} else {
		echo "Make sure that /protected/Configs/routes.php exists";
		exit;
	}


