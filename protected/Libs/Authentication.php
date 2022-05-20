<?php

class Authentication extends Singleton {
	
	public $prefix = 'ac';
	public $table = 'user';
	protected $usernameField = 'email';
	protected $passwordField = 'password';
	protected $record = false;
	
	protected $cookie_name = "authentication_record";

	protected $vc = null;
	
		
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
	 *  For some reason take the record, load user name, and go refresh record in memory.
	 *  Shoe horn the SSO check in this as every page calls it to see if logged in.
	 */
	
	public function isLoggedIn(MainController &$superObject = null) {
		#echo ("isloggedin");
		if ($this->valid()) {
			$record = $this->fetchUserByName($this->record->{$this->usernameField});
			
			if ($record == false) {
				return false;
			} else {
				return true;
			}
		} else {
			//return false;

			//if we aren't logged in, let's check the SSO state.
			if(isset($_COOKIE['VouchCookie']))
			{
				//echo "Vouch Cookie Mode\n";
				$vc = $this->VouchCookie();
				if($vc->status == 200){
					$this->record = $this->fetchUserByName($vc->login);

					//we have failed to find the email address. so now we get to create the user....
					if($this->record == false && $superObject != null){
						//$trace = debug_backtrace();
      					//$trace[1]->object->doSomething();

						//var_dump(debug_backtrace());
						$user = $superObject->getMeBlankUser();
						$user->public_id = getRandomString();
						$user->first_name = $vc->first_name;
						$user->last_name = $vc->last_name;
						$user->email = $vc->login;
						$user->password = NULL;
						$user->temp_password = 0;
						$user->phone = NULL;
						$user->default_location = NULL;
						$user->default_module = NULL;

						//var_dump($user);
						//if save succeeded, lets inject admissions too.
						if ($user->save()) {
							$this->record = $this->fetchUserByName($vc->login);
							//echo($user->public_id);
						}
					}

					$isAdmitUser = false;
					if($superObject != null) {
						$modules = $superObject->getModules();

						//manual and hackish set modules to session, we will use this in admissions to get module switching to make sense.
						if(!isset($_SESSION[APP_NAME]['modules'])) {
							$_SESSION[APP_NAME]['modules'] = array();
							foreach($modules as $k => $v)
							{
								$_SESSION[APP_NAME]['modules'][] = $v->name;
							}
							//die(print_r($modules, true));
						}
						foreach($modules as $m)
						{
							if($m->name == "Admission") {
								$isAdmitUser = true;
							}
						}
					}

					if($isAdmitUser && $this->record != false) {
						$obj = new AdmissionDashboardUser;
						$user = &$this->record;
						$siteUser = $obj->checkForExisting($this->record->public_id);
						$siteUser->pubid = $user->public_id;
						$siteUser->email = $user->email;
						$siteUser->first = $user->first_name;
						$siteUser->last = $user->last_name;
						$siteUser->is_coordinator = 0;
						$siteUser->module_access = 0;
						$siteUser->timeout = 0;
						$siteUser->phone = NULL;

						$siteUser->save($siteUser, db()->dbname2);
					}
					
					$obj = new User;
					$user = $obj->fetchById($this->record->id);
					//echo "Saved User\n";
			
					$this->writeToSession();
					
					//die();
					if ($this->record == false) {
						return false;
					} else {
						return true;
					}
				}
			}
		}
		
	}

	public function getRawVouchCookie() {
		if(!is_null($this->vc) && isset($this->vc->raw_cookie)) {
			return $this->vc->raw_cookie;
		} else {
			return null;
		}
	}

	/*
	 * -------------------------------------------
	 * 	VouchCookie, it's "heavy" 8-10 ms, so let's only do it once per page load and store in auth object as protected $vc
	 * -------------------------------------------
	 *
	 */
	public function VouchCookie()
	{
		if(is_null($this->vc) && isset($_COOKIE['VouchCookie'])){
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, "http://localhost:9090/vp_in_a_path/validate");
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADERFUNCTION,
				function($curl, $header) use (&$headers)
				{
					$len = strlen($header);
					$header = explode(':', $header, 2);
					if (count($header) < 2) // ignore invalid headers
					return $len;

					$headers[(trim($header[0]))] = trim($header[1]);

					return $len;
				}
			);
			curl_setopt( $ch, CURLOPT_COOKIE, "VouchCookie=" . $_COOKIE['VouchCookie']);
			$content = curl_exec( $ch );
			$response = curl_getinfo( $ch );
			curl_close ( $ch );
			
			$this->vc = (object)[];
			$Vouch = &$this->vc;
			$Vouch->status = $response['http_code'];

			if($Vouch->status === 200)
			{	
				$Vouch->success = $headers['X-Vouch-Success'];

				//AHC OKTA vs DEV Okta
				if(isset($headers['X-Vouch-Idp-Claims-Preferred-Username']))
				{
					$Vouch->login = $headers['X-Vouch-Idp-Claims-Preferred-Username'];
				} else {				
					$Vouch->login = $headers['X-Vouch-Idp-Claims-Login'];
				}

				//decode useraccess claim for groups
				$Vouch->Useraccess = explode(",", $headers['X-Vouch-Idp-Claims-Useraccess']);
				#var_dump($access);
				#$access = explode
				array_walk($Vouch->Useraccess, function(&$value){
					$value = trim($value, "\" ");
				});

				//decode available locations
				$Vouch->Available_Loc = explode(",", $headers['X-Vouch-Idp-Claims-Available-Locations']);
				#var_dump($access);
				#$access = explode
				array_walk($Vouch->Available_Loc, function(&$value){
					$value = trim($value, "\" ");
				});

				$Vouch->default_module = $headers['X-Vouch-Idp-Claims-Defaultmodule'];
				if(isset($headers['X-Vouch-Idp-Claims-Default-Location'])) {
					$Vouch->default_location = $headers['X-Vouch-Idp-Claims-Default-Location'];
				} else {
					$Vouch->default_location = $Vouch->Available_Loc[0];
				}
				
				$Vouch->name = $headers['X-Vouch-Idp-Claims-Name'];
				$Vouch->first_name = $headers['X-Vouch-Idp-Claims-Given-Name'];
				$Vouch->last_name = $headers['X-Vouch-Idp-Claims-Family-Name'];
				$Vouch->email = $headers['X-Vouch-Idp-Claims-Email'];
				$Vouch->okta_userid = $headers['X-Vouch-Idp-Claims-Sub'];

				$Vouch->raw_cookie = $_COOKIE['VouchCookie'];
			}
		} 
		return $this->vc;
	}
	
	//take session user public_id and go get user record from DB
	protected function getRecordFromSession() {
		$sql = "select u.*, m.`public_id` as `mod_pubid`, m.`name` as 'module_name' from {$this->tableName()} u inner join `ac_module` AS m on m.`id`=u.`default_module` where u.`public_id`=:public_id";
		$params['public_id'] = session()->authentication_record;
		$this->record = db()->fetchRow($sql, $params, $this);
	}

	//take session public_id and go get permissions for groups from VouchCookie
	//take session public_id and go get permissions for groups from DB if local auth
	protected function getGroupsFromSession() {
		if(isset($_COOKIE['VouchCookie']))
		{
			$vc = $this->VouchCookie();
			if($vc->status == 200){
				//get id from pubID in ac_user, lookup group_ids in ac_user_group, use group_id to get list of permission_id from ac_group_permission, user permission_id to get names of permissions from ac_permission.

				$sql = "SELECT * FROM ac_permission WHERE id IN (SELECT permission_id FROM ac_group_permission WHERE group_id IN (SELECT id FROM ac_group WHERE name IN (";
				foreach($vc->Useraccess as $key => $value) {
					$sql .= ":access$key, ";
					$params[":access$key"] = $value;
				}
				//fix hanging fencewire.
				$sql = trim($sql, ", ");
				$sql .= ")))";
				return db()->FetchRows($sql, $params, $this);
			} else {
				die("failed validation");
			}
			#var_dump($content);
			#var_dump($response['http_code']);
			#var_dump($headers);
		} else {
			//get id from pubID in ac_user, lookup group_ids in ac_user_group, use group_id to get list of permission_id from ac_group_permission, user permission_id to get names of permissions from ac_permission.
			$sql = "SELECT * FROM ac_permission WHERE id IN (SELECT permission_id FROM ac_group_permission WHERE group_id IN (SELECT group_id FROM ac_user_group WHERE user_id = (SELECT id FROM ac_user WHERE public_id = :public_id)))";
			$params[":public_id"] = session()->authentication_record;
			return db()->FetchRows($sql, $params, $this);
		}

	}
			
	
	
	
	/*
	 * -------------------------------------------
	 * 	GET USER - fetch info from the db by username (email address)
	 * -------------------------------------------
	 *
	 */
	
	public function fetchUserByName($username) {
		//old query that got module name...
		//$sql = "select {$this->tableName()}.*, `ac_module`.`public_id` as `mod_pubid`, `ac_module`.`name` as 'module_name' from {$this->tableName()} inner join `ac_module` on `ac_module`.`id`={$this->tableName()}.`default_module` where {$this->tableName()}.`email`=:username ";
		$sql = "select {$this->tableName()}.* from {$this->tableName()} where {$this->tableName()}.`email`=:username ";
		$params = array(
			":username" => $username,
		);
		
		$result = db()->fetchRow($sql, $params, $this);
		
		if (!empty ($result)) {
			//overwrite DB with default module from vouch
			//todo add check if in vouch mode then do.
			$result->module_name = $this->VouchCookie()->default_module;
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
		//bypass this function, we use Vouch now.
		return false;
		$user = $this->loadRecord();
		if ($user->group_id == 1 || $user->group_id == 7 || $user->group_id == 8) {
			return true;
		} 

		return false;

	}


	public function is_dietary_admin() {
		return false;
		$user = $this->loadRecord();
		$userGroups = $this->fetchGroups($user->id);
		if ($user->group_id == 1 || $user->group_id == 10 || in_array(1, $userGroups) || in_array(10, $userGroups)) {
			return true;
		}
		return false;
	}

/* //pretty sure only is_dietary_admin() uses this.
	private function fetchGroups($user) {
		$sql = "SELECT * FROM ac_user_group WHERE user_id = :user_id";
		$params[":user_id"] = $user;
		return db()->fetchRow($sql, $params, $this);
	}
*/

	// This functionality was replaced by the new hasPermission() function below
	// public function has_permission($action = false, $type = false) {

	// 	// Use the new GBAC to see if the user's group has permission to complete the task

	// 	//	Only allow facility administrators to add new users
	// 	if ($type == 'site_users') {
	// 		if ($this->is_admin()) {
	// 			return true;
	// 		}
	// 		return false;
	// 	} else {

	// 		//	For now we will allow access to all other page types
	// 		return true;
	// 	}
	// }
	

	public function hasPermission($perm) {
		$groups = $this->getGroupsFromSession();
		foreach ($groups as $g) {
			if ($g->description == $perm) {
				return true;
			}
 		}

 		return false;
	}
	
	public function valid() {
		if ($this->record !== false) {
			return true;
		}
		return false;
	}


	public function tableName() {
		return $this->prefix . "_" . $this->table;
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
				#'default_module' => $this->record->module_name
				'default_module' => $this->VouchCookie()->default_module
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
		//todo add check if in vouch mode then do.
		if(isset($this->record->module_name))
			$this->record->module_name = $this->VouchCookie()->default_module;
		return $this->record;
	}

	public function getPublicId() {
		return $this->record->public_id;
	}

	public function getDefaultLocation() {
		//todo add check if in vouch mode then do.
		if(isset($this->VouchCookie()->default_location)){
			return $this->VouchCookie()->default_location;	
		} else {
			return $this->record->default_location;
		}
		//return $this->record->default_location;
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
		
		//destroy vouch tokens too.
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, "http://localhost:9090/vp_in_a_path/logout");
		curl_setopt( $ch, CURLOPT_COOKIE, "VouchCookie=" . $_COOKIE['VouchCookie']);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
		curl_close ( $ch );
		$this->record = false;
		setcookie("VouchCookie", "", time()-3600, "/", ".dev.local.aptitudecare.com");
		session_destroy();
	}
	

}