<?php

class Authentication {
	
	public function __construct() {
		
	}
	
	public function auth($user) {
		// Check the session for the user info
	}
	
	public function hash_password($password) {
		return password_hash($password, PASSWORD_DEFAULT);
	}
	
}