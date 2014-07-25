<?php

class MySqlDb {

	private $db;
	public $username;
	public $password;
	public $dbname;
	public $host;

	
	public function __construct() {
		
	}
	
	public function conn() {
		try {
			$conn = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->username, $this->password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			echo "ERROR: " . $e->getMessage();
		}
		
		$this->db = $conn;
		
	}
	
	public function getConnection() {
		return $this->db;
	}


	/*
	 * Write database query functions to be used site-wide
	 *
	 */
	 
	public function fetchRows($sql, $params, $table) {
		$conn = $this->getConnection();
		$stmt = $conn->prepare($sql);
		$stmt->execute($params);
				
		$result = $stmt->fetchAll(PDO::FETCH_OBJ);		
		$this->checkPublicId($result, $table);
		return $result;
	}
	
	public function fetchRow($sql, $params, $table) {
		$conn = $this->getConnection();
		$stmt = $conn->prepare($sql);
		$stmt->execute($params);
		
		$result = $stmt->fetch(PDO::FETCH_OBJ);
		$this->checkPublicId($result, $table);
		return $result;
	}
	
	public function update($sql, $params) {
		$conn = $this->getConnection();
		$stmt = $conn->prepare($sql);
		$stmt->execute($params);
	}
	 
	
	
	private function checkPublicId($array, $table) {
		if (is_object($array)) {
			if (isset ($array->public_id)) {
				if ($array->public_id == '') {
					$array->public_id = getRandomString();
					$this->updatePublicId($array, $table);	
				} else {
					return true;
				}
				
			}
		} else {
			foreach ($array as $r) {
				if ($r->public_id == '') {
					$r->public_id = getRandomString();
					$this->updatePublicId($r, $table);
				}
			}
		}
	}
	
	public function updatePublicId($array, $table) {
		$table = underscoreString($table);
		$sql = "UPDATE {$table} SET {$table}.public_id=:public_id WHERE {$table}.id=:id";
		$params = array(
			':public_id' => $array->public_id,
			':id' => $array->id
		);
		$this->update($sql, $params);
	}
	
	public function findByPubId($public_id, $table) {
		$sql = "SELECT * FROM :table WHERE public_id = :public_id";
		$params[':table'] = lcfirst($table);
		$params[':public_id'] = $public_id;
				
		return $this->getResults($sql, $params);
	}
	
	
}