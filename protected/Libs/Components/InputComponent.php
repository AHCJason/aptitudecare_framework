<?php

class Input {
	
	public $input = array();
	public $_post = array();
	public $_get = array();	
	
	private function stripslashes_deep($value)
	{
		$value = is_array($value) ?
					array_map('stripslashes_deep', $value) :
					stripslashes($value);

		return $value;
	}

	public function __construct() {
		//$_REQUEST = $this->stripslashes_deep($_REQUEST);
		
		foreach ($_REQUEST as $key => $value) {	
			if (is_array($value)) {
				foreach ($value as $k => $v) {
					if (is_array($v)) {
						$this->$key->$k = $v;
					} else {
						#since php 5.4 object creation is not implicit.
						if(!isset($this->{$key}))
						{
							$this->{$key} = new stdClass();
						}
						@$this->{$key}->{$k} = @stripslashes(@$v);
					}
					
				}
			} elseif (isset ($_POST[$key])) {
				$this->$key = stripslashes($value);
			} elseif (isset ($_GET[$key])) {
				$this->$key = stripslashes($value);
			}
		}
		

	}
	
	public function is($data) {
		if ($data == 'post') {
			if ($_POST) {
				return true;
			}
		}
	}
	
	public function post($name = false) {
		if ($name != false) {
			return $this->_post[$name];
		} else {
			return $this->_post;
		}
	}
	
	public function get($name = false) {
		if ($name != false) {
			return $this->_get[$name];
		} else {
			return $this->_get;
		}
	}
	
}