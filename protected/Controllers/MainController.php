<?php

/*
 *	All other classes will extend the MainController, so use functions here that need to be 
 *	used in all other controllers.
 *
 */


class MainController {

	public $module;
	public $page;
	public $action;
	public $template = 'main';
	public $helper = null;
	
	public function __construct() {			
		
							
		// Load any other components defined in the child class
		if (!empty($this->components)) {
			foreach($this->components as $c) {
				$this->loadComponent($c);
			}
		}
	}
	
	
	/*
	 *
	 * -------------------------------------------------------------
	 *  LOAD MODELS, VIEWS, PLUGINS, COMPONENTS, AND HELPERS
	 * -------------------------------------------------------------
	 * 
	 */

	
	public function loadModel($name, $id = false) {
		if (file_exists (FRAMEWORK_PROTECTED_DIR . DS . 'Models' . DS . $name . '.php')) {
			require_once (FRAMEWORK_PROTECTED_DIR . DS . 'Models' . DS . $name . '.php');
			
		} elseif (file_exists (APP_PROTECTED_DIR . DS . 'Models' . DS . $name . '.php')) {
			require_once (APP_PROTECTED_DIR . DS . 'Models' . DS . $name . '.php');
		}	
		
		if (class_exists($name)) {
			$class = new $name;
		} else {
			echo "Could not find class {$name}";
			exit;
		}
		
		if ($id) {
			return $class->fetchById($id);
		} else {
			return $class;
		}
		
	}



	/*
	 * -------------------------------------------------------------------------
	 * PAGE VIEW
	 * -------------------------------------------------------------------------
	 *
	 *	Set the content - this is the tpl file fort method which is called in the
	 *	controller,  then call the default main.tpl file.
	 *
	 */

	public function loadView($folder, $name, $module = '') {		
		smarty()->assign('current_url', SITE_URL . $_SERVER['REQUEST_URI']);
		smarty()->assign('module', $module);


		//	Make sure the session is valid and get the user info
		//	Re-direction is failing here, for some reason we are not passing the 
		//	auth()->isLoggedIn() test

		if (!auth()->isLoggedIn()) {
			if ($folder != 'login') {
				$this->redirect(array('page' => 'login', 'action' => 'index'));
			} 
		} 

		if ($module != '') {
			$this->module = $module;
			if (file_exists(MODULES_DIR . DS . $module . DS . 'Views/' . underscoreString($folder) . DS . $name . '.tpl')) {
				smarty()->assign('content', MODULES_DIR . DS . $module . DS . 'Views/' . underscoreString($folder) . '/' . $name . '.tpl');
			} else {
				smarty()->assign('content', underscoreString($folder) . '/' . $name . '.tpl');
			}
			
		} else {
			$this->module = '';
			smarty()->assign('content', underscoreString($folder) . '/' . $name . '.tpl');
		}
		
		if ($this->helper != null) {
			$helper = $this->loadHelper($this->helper, $this->module);
			smarty()->assignByRef('patientTools', $helper);
		}

		$this->page = ucfirst($folder);
		$this->action = $name;

		
		if ($module != '') {
			// Get all the locations for the user
			$locations = $this->loadModel('Location')->fetchLocations(auth()->getPublicId(), $module);
			// Get the modules to which the user has access
			$modules = $this->loadModel('Module')->fetchUserModules(auth()->getPublicId());
			
		} else {
			$locations = '';
			$modules = '';
		}
		
		
		smarty()->assign('currentUrl', currentUrl());	
		smarty()->assign('locations', $locations);
		smarty()->assign('modules', $modules);


		// Check session for errors to be displayed
		session()->checkFlashMessages();
		
		//	If is_micro is set in the url then display a blank template
		if (isset (input()->isMicro) && input()->isMicro == 1) {
			$this->template = 'blank';
		}

		// set the base template
		smarty()->display("layouts/{$this->template}.tpl");
		
	}


	
	public function loadElement($name) {
		$obj = new PageController();
		$element = $obj->element($name);
		return $element;
	}
	
	public function loadPlugin($name) {
		if (file_exists (PROTECTED_DIR . '/plugins/' . $name . '.php')) {
			require (PROTECTED_DIR . '/plugins/' . $name . '.php');
		} 
	}
	
	public function loadHelper($name, $module = null) {

		if (file_exists (FRAMEWORK_PROTECTED_DIR . '/Views/helpers/' . $name . 'Helper.php')) {
			require (FRAMEWORK_PROTECTED_DIR . '/Views/helpers/' . $name . 'Helper.php');
		} elseif (file_exists (APP_PROTECTED_DIR . DS . 'Views/helpers/' . $name . 'Helper.php')) {
			require (APP_PROTECTED_DIR . '/Views/helpers/' . $name . 'Helper.php');
		} elseif (file_exists (MODULES_DIR . DS . $module . DS . 'Views/helpers/' . $name . 'Helper.php')) {
			require (MODULES_DIR . DS . $module . DS . 'Views/helpers/' . $name . 'Helper.php');
		} 

		$className = $name . 'Helper';

		$helper = new $className;
		return $helper;
	}
	
	public function loadComponent($name) {
/*
		if (file_exists (FRAMEWORK_DIR . '/Libs/Components/' . $name . 'Component.php')) {
			require (FRAMEWORK_DIR . '/Libs/Components/' . $name . 'Component.php');
		} elseif (file_exists(PROTECTED_DIR . '/Libs/Components' . $name . 'Component.php')) {
			require (PROTECTED_DIR . '/Libs/Components' . $name . 'Component.php');
		}
*/
		$component = new $name;
		return $component;
	}
		
	
	public function template($name = false) {
		global $config;
		if ($name) {
			$config['main_template'] = $name.'.tpl';
		}
		
	}
	
	
	public function set($name, $var) {
		$this->$name = $var;
	}
	
	
			
	/*
	 *
	 * -------------------------------------------------------------
	 *  Redirects pages
	 * -------------------------------------------------------------
	 * 
	 * This method does not work yet.  Want to pass a url and have the page redirect,
	 * useful after form submissions or on validation failures.
	 *
	 */
		
	public function redirect($params = false) {	
		if (is_array($params)) {	
			if (isset($params['module'])) {
				if (!isset ($params['page'])) {
					$redirect_url = SITE_URL . "/?module=" . $params['module'];
				} else {
					if (isset ($params['action'])) {
						$redirect_url = SITE_URL . '?module=' . $params['module'] . '&page=' . $params['page'] . '&action=' . $params['action']; 
					} else {
						$redirect_url = SITE_URL . '?module=' . $params['module'] . '&page=' . $params['page'];
					}
				}
			} else {	
				if (isset($params['page'])) {
					$page = strtolower(preg_replace('/([^A-Z-])([A-Z])/', '$1-$2', $params['page']));
				} else {
					$page = 'MainPage';
				}
				if (isset($params['action'])) {
					$action = $params['action'];
					if ($params['action'] == 'index') {
						$redirect_url = SITE_URL . "/{$page}";
					} else {
						$redirect_url = SITE_URL . "/{$page}/{$action}";
					}	
				} else {
					$redirect_url = SITE_URL . "/{$page}";
				}
			}
		} elseif ($params) {
			$redirect_url = $params;
		} else {
			$redirect_url = SITE_URL;
		}
		$this->redirectTo($redirect_url);
		
	}	
	
	private function redirectTo($url) {
		header("Location: " . $url);
		exit;
	}
		
	
	
	/*
	 *
	 * -------------------------------------------------------------
	 *  VALIDATE DATA
	 * -------------------------------------------------------------
	 * 
	 */
	 
	 protected function validateData($dataArray = array(), $flash_message = false, $redirect_to = false) {
	 	$fail = false;
		$returnData = array();
		foreach ($dataArray as $key => $data) {
			foreach ($data as $k => $d) {
				 if ($d == '') {
				 	$fail = true;
					session()->setFlash($flash_message);
					$this->redirect($redirect_to);
				} else {
					session()->saveData($k, strip_tags($d));
					$returnData[$key][$k] = strip_tags($d);
				}
			}
		}
		
		if ($fail) {
			exit;
		}
		
		return $returnData;		

	 }
	
	
	
	/*
	 *
	 * -------------------------------------------------------------
	 *  Looks in a folder and returns the contents
	 * -------------------------------------------------------------
	 * 
	 * This method is especially useful for folders with photos (i.e. - for the slideshow on the home page)
	 *
	 */
	
	protected function directoryToArray($directory, $recursive) {
	    $array_items = array();
	    if ($handle = opendir($directory)) {
	        while (false !== ($file = readdir($handle))) {
	            if ($file != "." && $file != "..") {
	                if (is_dir($directory. "/" . $file)) {
	                    if($recursive) {
	                        $array_items = array_merge($array_items, directoryToArray($directory. "/" . $file, $recursive));
	                    }
	                    $file = $directory . "/" . $file;
	                    $array_items[] = preg_replace("/\/\//si", "/", $file);
	                } else {
	                    $file = $directory . "/" . $file;
	                    $array_items[] = preg_replace("/\/\//si", "/", $file);
	                }
	            }
	        }
	        closedir($handle);
	    } else {
		    echo "<br />Make sure $directory exists and try again.";
		    exit;
	    }
	    
	    foreach ($array_items as $item) {
		    $explodedArray[] = (explode('/', $item));
	    }
	    
	    foreach ($explodedArray as $a) {
		    $filteredArray[] = array_pop($a);

	    }
	    
	    return $filteredArray;
	}
		
	

	/*
	 * -------------------------------------------------------------------------
	 * 	COMMON FUNCTIONS FOR THE PAGES IN THE DATA TAB
	 * -------------------------------------------------------------------------
	 */


	public function manage() {
		$model = depluralize(ucfirst(camelizeString(input()->page)));

		if (isset (input()->type)) {
			$pageTitle = stringify(input()->type);
			$dataModel = ucfirst(camelizeString(depluralize(input()->type)));
		} else {
			$pageTitle = stringify($model);
			$dataModel = ucfirst(camelizeString(depluralize($model)));
		}
		
		smarty()->assign('title', "Manage {$pageTitle}");
		smarty()->assign('headerTitle', $pageTitle);

		$class = $this->loadModel($dataModel)->fetchManageData();
		$classArray[0] = array();
		if (!empty ($class)) {
			foreach ($class as $key => $value) {
				foreach ($value as $k => $v) {
					$classArray[$key][$k] = $v;
					if (!in_array($k, $value->_manage_fields)) {
						unset($classArray[$key][$k]); 		
					}
					
				}
				
			}
		}
		
		smarty()->assign('data', $classArray);
	}

	public function add() {

	}

	public function edit() {

	}
	
	
	
	
	/*
	 *
	 * -------------------------------------------------------------
	 *  This method sends an email using the PHPMailer plugin
	 * -------------------------------------------------------------
	 * 
	 */
	 
	public function sendEmail($data) {
		
		global $config;
		global $params;
		
		$mail = new PHPMailer(true);
		$mail->IsSMTP();
		
		/**
		 * These mail settings are specific to bluehost
		 */
		
		
		try {				
			$mail->SMTPDebug = 2;                    
			$mail->SMTPAuth = true;    
			$mail->SMTPSecure = "ssl";              
			$mail->Host = $config['email_host'];  // email must be sent from server for bluehost 
			$mail->Port = 465;                   
			$mail->Username = $config['email_username'];  
			$mail->Password = $config['email_password'];       
			$mail->SetFrom($data['post']['email'], $data['post']['name']);    
			$mail->AddAddress($config['email_to']);
			$mail->Subject = $params['site_name'] . ' Message: ' . $data['post']['subject'];
			$mail->Body = $data['post']['message_body'];
			if ($mail->Send()) {
				return true;
			} else {
				return false;
			}
		} catch (phpmailerException $e) {
			echo $e->errorMessage(); //Pretty error messages from PHPMailer
		} catch (Exception $e) {
			echo $e->getMessage(); //Boring error messages from anything else!
		}
	}

	public function fullName() {
		return $this->first_name . ' ' . $this->last_name;
	}



}