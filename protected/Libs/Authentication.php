<?php

class Authentication extends Singleton {
	
	public $table = 'user';
	protected $usernameField = 'email';
	protected $passwordField = 'password';
	protected $record = false;
	
	protected $cookie_name = "authentication_record";
	
		
	/*
	 * -------------------------------------------
	 * 	INITIALIZE THE AUTHENTICATION CLASS
	 * -------------------------------------------
	 *
	 */
	
	public function init() {
		// Check if the users' public_id exists in the session object
		if (!$this->valid()) {
			if (isset (session()->authentication_record)) {
				// If it does then get the user info from the database
				$this->getRecordFromSession();

			}

		} else {
			$this->loadRecord();
		}

		$this->writeToSession();
	}
	
	
	
	
	/*
	 * -------------------------------------------
	 * 	CHECK LOGIN - make sure they really are logged in...
	 * -------------------------------------------
	 *
	 */
	
	public function isLoggedIn() {
		if ($this->valid()) {
			$record = $this->fetchUserByName($this->record->{$this->usernameField});
			
			if ($record == false) {
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
		
	}

	
	
	protected function getRecordFromSession() {
		$sql = "select {$this->table}.*, `module`.`public_id` as `mod_pubid`, `module`.`name` as 'module_name' from {$this->table} inner join `module` on `module`.`id`={$this->table}.`default_module` where {$this->table}.`public_id`=:public_id";
		$params['public_id'] = session()->authentication_record;
		$this->record = db()->fetchRow($sql, $params, $this);
				
	}
			
	
	
	
	/*
	 * -------------------------------------------
	 * 	GET USER - fetch info from the db by username (email address)
	 * -------------------------------------------
	 *
	 */
	
	public function fetchUserByName($username) {
		$sql = "select {$this->table}.*, `module`.`public_id` as `mod_pubid`, `module`.`name` as 'module_name' from {$this->table} inner join `module` on `module`.`id`={$this->table}.`default_module` where {$this->table}.`email`=:username ";
		$params = array(
			":username" => $username,
		);
		
		$result = db()->fetchRow($sql, $params, $this);
		
		if (!empty ($result)) {
			return $result;
		} 
		
		return false;

	}
	
	
	
	
	
	/*
	 * -------------------------------------------
	 * 	FETCH THE USER RECORD FROM THE DB
	 * -------------------------------------------
	 *
	 */
	
	public function loadRecord() {
		if ($this->valid()) {
			$record = $this->fetchUserByName($this->record->email);
			
			if ($record == false) {
				return false;
			} else {
				$this->record = $record;
				return $record;
			}
		} else {
			return false;
		}
		
		
	}
	
	
	public function valid() {
		if ($this->record !== false) {
			return true;
		}
		return false;
	}
	
	
	
	/*
	 * -------------------------------------------
	 * 	WRITE DATA TO THE SESSION
	 * -------------------------------------------
	 *
	 */
		
	public function writeToSession() {
		if ($this->record !== false) {
			$sessionVals = array(
				$this->cookie_name => $this->record->public_id,
				'default_module' => $this->record->module_name
			);	
		} else {
			$sessionVals = array();
		}

		session()->setVals($sessionVals);
		
	}

	
	
	/*
	 * -------------------------------------------
	 * 	GET THE USER RECORD
	 * -------------------------------------------
	 *
	 */	
	
	public function getRecord() {
		return $this->record;
	}

	public function getPublicId() {
		return $this->record->public_id;
	}

	public function getDefaultLocation() {
		return $this->record->default_location;
	}
	
	
	
	
	/*
	 * -------------------------------------------
	 * 	GET THE FULL USERS' NAME
	 * -------------------------------------------
	 *
	 */
	
	public function fullName() {
		return $this->record->first_name . ' ' . $this->record->last_name;
	}
	
		
	
			
	/*
	 * -------------------------------------------
	 * 	USER LOGIN
	 * -------------------------------------------
	 *
	 */
		
	public function login($username, $password) {
		// Need to salt and encrypt password
		$enc_password = password_hash($password, PASSWORD_DEFAULT);
										
		// Check database for username and password	
		$this->record = $this->fetchUserByName($username);

		// check if returned user matches password
		if (password_verify($password, $this->record->password)) {
			// record datetime login
			//$this->saveLoginTime($user->id);	
			$this->writeToSession();
			return true;
		} 
		
		$this->record = false;
		return false;
	}
	
	
	
	
	/*
	 * -------------------------------------------
	 * 	USER LOGOUT
	 * -------------------------------------------
	 *
	 */
	 
	
	public function logout() {
		$this->record = false;
		session_destroy();
	}
	

}