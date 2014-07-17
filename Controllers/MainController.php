<?php

/*
 *	All other classes will extend the MainController, so use functions here that need to be 
 *	used in all other controllers.
 *
 */


class MainController {
	public $content;
	public $params = array();
	public $Session;
	
	public $username;
	public $fullname;
	
	public function __construct() {
		// Load the session component
		$this->Session = $this->loadComponent('Session');
		
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

	
	public function loadModel($name) {
		if (file_exists (PROTECTED_DIR . DS . 'Models' . DS . $name . '.php')) {
			require (PROTECTED_DIR . DS . 'Models' . DS . $name . '.php');
			$this->$name = new $name;
			return $this->$name;
		}
		
	}

	public function loadView($folder, $name) {	
		$this->content = lcfirst($folder) . '/' . $name . '.tpl';
		// set the base template
		include(VIEWS . DS . 'main.tpl');
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
	
	public function loadHelper($name) {
		if (file_exists (PROTECTED_DIR . '/libs/helpers/' . $name . 'Helper.php')) {
			require (PROTECTED_DIR . '/libs/helpers/' . $name . 'Helper.php');
		} 
		$helper = new $name;
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
		
	public static function redirect($url = false, $params = array()) {

		if (!empty($url)) {
			header('Location: ' . SITE_URL . '/' . $url);
		} else {
			header('Location: ' . SITE_URL);
		}
		
	}	
	
	public function error($message) {
		
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

}