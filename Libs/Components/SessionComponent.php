<?php

class Session {

	public $message = '';
	
	public function __construct() {
		session_start();
	}
	
	
	public static function setMessage($message, $class = false) {
		$_SESSION['message'] = $message;
	}
	
	public function displayMessage() {
		$this->check_message();
		return $this->message;
	}
	
	public function displayData($name) {
		return $this->checkData($name);
	}
	
	private function check_message() {
		if (isset ($_SESSION['message'])) {
			$this->message = $_SESSION['message'];
			unset($_SESSION['message']);
		} else {
			$this->message = '';
		}
	}	
	
	private function checkData($name) {
		if (isset ($_SESSION[$name])) {
			$data = $_SESSION[$name] ;
			unset ($_SESSION[$name]);
		} else {
			$data = '';
		}
		return $data;
	}
	
	public function messageIsSet() {
		if (isset ($_SESSION['message'])) {
			return true;
		} else {
			return false;
		}
	}
	
	public function getSessionInfo() {
		foreach ($_SESSION as $key => $info) {
			smarty()->assign($key, $info);
			unset ($_SESSION[$key]);
		}
	}
	
	public function saveData($name = '', $data = '') {	
		$_SESSION["{$name}"] = $data;
	}
	
	public static function setVals($vals = array()) {
		foreach ($vals as $k => $v) {
			$_SESSION[$k] = $v;
		}
	}
	
}
