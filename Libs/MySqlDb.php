<?php

class MySqlDb {

	protected $db;
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
			die();
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


	public function getRows($sql, $params = array(), $table) {
		$db = $this->getConnection();
		$stm = $db->prepare($sql);
		$stm->execute($params);
		
		return $stm->fetchAll();

	}
	
	public function getRow($sql, $params = array(), $table) {
		$db = $this->getConnection();
		$stm = $db->prepare($sql);
		$stm->execute($params);
		return $stm->fetch(PDO::FETCH_OBJ);
	}

}