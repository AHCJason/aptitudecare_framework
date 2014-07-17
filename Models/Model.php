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


	public function fetchRow($sql, $params = array()) {
		try {
			$record = db()->getRow($sql, $params, get_class($this));
		} catch (PDOException $e) {
			echo $e;
		}
		return $record;
		
	}
	
	public function fetchRows($sql, $params) {
		try {
			$records = db()->getRows($sql, $params, get_class($this));
		} catch (PDOException $e) {
			echo $e;
		}
		return $records;
		
	}
}