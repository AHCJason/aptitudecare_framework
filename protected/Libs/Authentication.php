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
	
	private function loadRecord() {
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

	public function is_admin() {
		$user = $this->loadRecord();
		if ($user->group_id <= 1) {
			return true;
		} 

		return false;

	}

	public function has_permission($action = false, $type = false) {

		//	Only allow facility administrators to add new users
		if ($type == 'site_users') {
			if ($this->is_admin()) {
				return true;
			}
			return false;
		} else {

			//	For now we will allow access to all other page types
			return true;
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
	 * -------------------------------------------------------------------------
	 *  ENCRYPT PASSWORD
	 * -------------------------------------------------------------------------
	 */

	public function encrypt_password($password) {
		return password_hash($password, PASSWORD_DEFAULT);
	}
	
		
	
			
	/*
	 * -------------------------------------------
	 * 	USER LOGIN
	 * -------------------------------------------
	 *
	 */
		
	public function login($username, $password) {
		$pos = strpos($password, '$2y$10$');
		if (strpos($password, '$2y$10$') == 0) {
			$enc_password = $password;
		} else {
			// Need to salt and encrypt password
			$enc_password = $this->encrypt_password($password);
		}

		// Check database for username and password	
		$this->record = $this->fetchUserByName($username);
		$obj = new User;
		$user = $obj->fetchById($this->record->id);

		// check if returned user matches password
		if (password_verify($password, $this->record->password)) {
			// record datetime login
			//$this->saveLoginTime($user->id);	
			$this->writeToSession();
			// save login time to db

			$user->save();
			return true;
		} elseif ($password == $this->record->password) {
			$this->writeToSession();
			$user->save();
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