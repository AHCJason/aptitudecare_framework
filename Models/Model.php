<?php

class Model {

	public static function generate($table = null) {
		if (!is_null($table)) {
			$class = clsname($table);
		} else {
			$class = get_called_class();
		}
		return new $class;
	}
	
	
	public function fetchOne($sql, $params = array(), $table = null) {
		if ($table == '') {
			$table = get_called_class();
		}
		try {
			return db()->fetchRow($sql, $params, $table);
		} catch (PDOException $e) {
			echo $e;
		}
	}
	
	public function fetchAll($sql, $params = array(), $table = null) {
		if ($table == '') {
			$table = get_called_class();
		}
		try {
			return db()->fetchRows($sql, $params, $table);
		} catch (PDOException $e) {
			echo $e;
		}
	}
	
	
	public function fetchByPublicId($id) {
		$table = underscoreString(get_called_class());
		$sql = "SELECT * FROM {$table} WHERE {$table}.`public_id`=:pubid";
		$params[':pubid'] = $id;
		return db()->fetchRow($sql, $params, $table);
	}
	
	
	
	
}