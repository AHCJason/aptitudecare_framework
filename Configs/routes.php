<?php


/*
*
* -------------------------------------------------------------
* ROUTE TO THE CORRECT PAGE BASED ON URL
* -------------------------------------------------------------
* 
* NOTE:  This routes file will handle urls like site_url/page/action or site_url?page=page&action=action.
*
*/	
	
	// Get the requested URL
	$request = $_SERVER['REQUEST_URI'];
	// Scan the module directory for use below
	$module_dir = preg_grep('/^([^.])/', scandir(MODULES_DIR));
	// Set module variable to false
	$module = false;
	$camelizedAction = '';
	$underscored_action = '';
	
	/*
	 * Check first for post variables which contain a page and action
	 */
	
	// If the variables are available from a post request, use them...
	if (isset(input()->module)) { 
		// Set module
		$module = input()->module;
		
		// Set the page (or controller)
		if (!isset (input()->page)) {
			$page = $module;
		} else {
			$page = ucfirst(input()->page);
		}
		
		
		// Set the controller action
		if (isset(input()->action)) {
			// this is the controller method
			$action = input()->action;
		} else {
			$camelizedAction = 'index';
		}
				
		
	} elseif (! strstr ($request, '?')) {	
		$queryString = explode('/', $request);
				
		// First check if the first item in the query string is a module
		$queryItem1 = ucfirst(camelizeString($queryString[1]));
		
		// If the query string is in the modules directory array, we know it is a module...
		if (in_array($queryItem1, $module_dir)) {
			$module = $queryItem1;
		}
				
		
		// If the module variable is empty then we are at a global page (login, corporate overview, etc.) and are not utilizing
		// any module controllers or methods.
		if ($module == '') {
			if  (count ($queryString) > 2) {	
				// The first item in the query string will be the controller (page).  We will make it camel cased, remove any hyphens,
				// and capitalize the first letter in the string.
				$page = ucfirst(camelizeString($queryString[1]));
				
			} else { 
	
				$page = 'MainPage';
				
			}
			
			// If there is a second item in the array then this is the controller method (action)
			if (isset ($queryString[2])) {
				$camelizedAction =  camelizeString($queryString[2]);
				$underscored_action = underscoreString($queryString[2]);
			} else {
				$action = 'index';
			}
				
		/*
		 *	If we are working with a module controller we need to look in the module sub-directory for the controller and views.
		 */
		 
		} else { 
			// need to see if there is a 3rd item in the array, if not, then use the default controller...
			if (isset ($queryString[3])) {
				$camelizedAction =  camelizeString($queryString[3]);
				$underscored_action = underscoreString($queryString[3]);
			} else {
				// set controller to be the same as the module
				$page = ucfirst(camelizeString($queryString[1]));
				if (isset($queryString[2])) {
					$camelizedAction = camelizeString($queryString[2]);
					$underscored_action = underscoreString($queryString[2]);
				} else {
					$camelizedAction = 'index';
				}
			}
			
			
		}	
				
		
	/*
	 * 	If there are no variables set and there is a ? in the URL string then...
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
				$camelizedAction = camelizeString($getVars['action']);
			} else {
				$camelizedAction = 'index';
			}	
		}  else {
			$page = 'MainPage';
			$camelizedAction = 'index';
		}
	
	}
		
	
/*
 * -------------------------------------------
 * INSTANTIATE THE CONTROLLER CLASS
 * -------------------------------------------
 *
 */	 
 
 	// If the module variable is not empty we can look directly into that controller directory
 	if ($module != '') {
	 	foreach ($module_dir as $dir) {
			if (file_exists(MODULES_DIR . DS . $dir . DS . 'Controllers' . DS . $page.'Controller.php')) {
				include_once(MODULES_DIR . DS . $dir . DS . 'Controllers' . DS . $page.'Controller.php');
			}
			
		}	
		
		$className = $page.'Controller';
		
		//	Next, look in the protected directory for the controller.	
 	} elseif (file_exists (PROTECTED_DIR . DS . 'Controllers' . DS . $page.'Controller.php')) { 
 		
		include_once (PROTECTED_DIR . DS . 'Controllers' . DS . $page.'Controller.php');
		$className = $page.'Controller';
	} elseif (file_exists (MODULES_DIR . DS . $module . DS . 'Controllers' . DS . $page.'Controller.php')) {  
		// Loop through the modules to look for the controller.
		include_once(MODULES_DIR . DS . $module . DS . 'Controller' . DS . $page.'Controller.php');
	}
					
	
	
	// If the class exists, instantiate it and load the coorespondig view from the Views folder. Otherwise, load the
	// error page.
	if (class_exists($className)) {	
		$controller = new $className;		
				
		// Check the camelized, underscored, and action variables for the method within the class	
		if (method_exists($controller, $camelizedAction)) {
			$controller->$camelizedAction();
			$controller->loadView(lcfirst($page), $camelizedAction, $module);
		} elseif (method_exists($controller, $underscored_action)) { 
			$controller->$underscored_action();
			$controller->loadView(lcfirst($page), $underscored_action, $module);
		} elseif (method_exists($controller, $action)) { 	
			$controller->$action();
			$controller->loadView(lcfirst($page), $action, $module);
		} else {
			// If it does not exist load the default error view
			MainController::loadView('Error', 'index');
		}		
			
	} else {  // If there is not a matching class redirect to the home page.
		MainController::redirect();
	}
