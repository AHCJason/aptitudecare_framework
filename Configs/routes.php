<?php


/*
*
* -------------------------------------------------------------
* ROUTE TO THE CORRECT PAGE BASED ON URL
* -------------------------------------------------------------
* 
* This file will handle urls like site_url/page/action or 
* site_url?page=page&action=action.
*
*/
	// Get requested URL

	$request = $_SERVER['REQUEST_URI'];

	// If the URL does not contain a ?
if (! strstr ($request, '?')) {	
	$queryString = explode('/', $request);
	foreach ($queryString as $q) {
		if (strstr ($q, '-')) {
			$rep = true;
			$replacedString = str_replace('-', '_', $q);
		} else {
			$rep = false;
		}
	}
	
	// if there is more than one item in the url then the first is the controller
	if  (count ($queryString) > 2) {
		
		//$capitalize = preg_replace_callback('/(?<=( |-))./', function ($m) { return strtoupper($m[0]); }, $queryString[1]);
		
		$page = ucfirst (str_replace('-', '', implode('-', array_map('ucfirst', explode('-', $queryString[1])))));
		
		if (isset ($queryString[2])) {
			if ($queryString[2] == '') {
				$action = 'index';
			} elseif ($rep) {
				$action = $replacedString;
			} else {
				$action = $queryString[2];
			}
		} else {
			$action = 'index';
		}	
	} else {
		
		$page = 'MainPage';
		if ($queryString[1] == '') {
			$action = 'index';
		} elseif ($rep) {
			$action = $replacedString;
		} else {
			$action = $queryString[1];
		}
	}


/*
 *	For URL's which contain a ?	
 */

} else {
	$request = $_SERVER['QUERY_STRING'];
	if ($request != '') {
		// parse the page requests and other GET variables
		$parseController = explode ('?', $request);
		
		$parsed = explode('&', $parseController[0]);	
	
		// the page is the first element in the array
		$parsePage = explode('=', $parsed[0]);
		
		// the name of the page (controller) method will be the second
		// element in the array
		$page = $parsePage[1];
					
		// the rest of the array are get statements, parse them out
		$getVars = array();
		foreach ($parsed as $arg) {
			// split GET vars along = symbol to separate variable, values
			list ($variable, $value) = explode ('=', $arg);
			$getVars[$variable] = $value;
		}
		
		if (isset ($getVars['action'])) {
			$action = $getVars['action'];
		} else {
			$action = 'index';
		}	
	}  else {
		$page = 'MainPage';
		$action = 'index';
	}

}


// Set the controller target path
$target = PROTECTED_DIR . DS . 'Controllers' . DS  . $page.'Controller.php';


// Check if target path controller file exists
if (file_exists ($target)) {
	include_once ($target);

	$className = $page.'Controller';
					
	// instantiate class
	if (class_exists ($className)) {
		$controller = new $className;
		
		if (method_exists($controller, $action)) {
			$controller->$action();
			$controller->loadView(lcfirst($page), $action);
		} else {
			MainController::loadView('Error', 'index');
		}
		
		
	} else {
		MainController::redirect();
	}
} else {
	MainController::redirect();
}
