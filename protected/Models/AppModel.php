<?php

class AppModel {

	public function generate($id = null) {
		if ($id == null) {
			$class = get_called_class();
			return new $class;
		} else {
			$called_class = get_called_class();
			$class = new $called_class;
			return self::fetchById($id);

		}
	}
	
	
	public function fetchOne($sql, $params = array()) {
		$called_class = get_called_class();
		$class = new $called_class;
		try {
			return db()->fetchRow($sql, $params, $class);
		} catch (PDOException $e) {
			echo $e;
		}
	}
	
	public function fetchAll($sql, $params = array()) {
		$called_class = get_called_class();
		$class = new $called_class;
		try {
			return db()->fetchRows($sql, $params, $class);
		} catch (PDOException $e) {
			echo $e;
		}
	}

	public function fetchAllData() {
		$table = $this->fetchTable();
		$sql = "SELECT `{$table}`.* FROM `{$table}`";
		$params = array();

		try {
			return db()->fetchRows($sql, $params, $this);
		} catch (PDOException $e) {
			echo $e;
		}

	}

	public function save($data = false) {
		try {
			if ($data) {
				pr ($data);
				if (!isset ($this->id) || $this->id != '') {
					db()->updateRow($this);
				} else {
					return db()->saveRow($this);
				}
				
			} else {
				if (isset ($this->id) && $this->id != '') {
					db()->updateRow($this);
				} else {
					return db()->saveRow($this);
				}
				
			}
		} catch (PDOException $e) {
			echo $e;
		}
		return true;
	}
	
	
	public function fetchById($id) {

		$params[':id'] = $id;
		
		if (!isset($this)) {
			$table = underscoreString(get_called_class());
			$sql = "SELECT * FROM {$table} WHERE {$table}.";
			if (is_numeric($id)) {
				$sql .= "`id`=:id";
			} else {
				$sql .= "`public_id`=:id";
			}

			return self::fetchOne($sql, $params);
		} else {
			$table = $this->table;
			$sql = "SELECT {$table}.* FROM {$table} ";
			if (isset ($this->belongsTo)) {
				foreach ($this->belongsTo as $k => $b) {
					$sql .= " INNER JOIN {$k} ON {$table}.{$b['inner_key']}={$k}.{$b['foreign_key']} WHERE {$k}.";
				}
			} else {
				$sql .= " WHERE {$table}.";
			}
			if (is_numeric($id)) {
				$sql .= "id=:id";
			} else {
				$sql .= "public_id=:id";
			}

			return $this->fetchOne($sql, $params);

		} 

		
		

	}


	public function fetchFields() {
		return $this->_manage_fields;
	}

	public function fetchTable() {
		return $this->table;
	}

	public function fetchColumnsToInclude() {
		return $this->_add_fields;
	}


	/*
	 * -------------------------------------------------------------------------
	 *  FETCH ALL DATA FOR MANAGE PAGE
	 * -------------------------------------------------------------------------
	 */

	public function fetchManageData() {
		if (isset (input()->type)) {
			$model = ucfirst(camelizeString(depluralize(input()->type)));
			$class = new $model;
			$table = $class->fetchTable();
			$sql = "SELECT `{$table}`.*";
			$i = 1;
			if (isset ($class->belongsTo)) {
				foreach ($class->belongsTo as $k => $b) {
					if (isset ($b['join_field'])) {
						$sql .= ", `{$b['table']}`.`{$b['join_field']['column']}` AS {$b['join_field']['name']} ";
					}
					
				}

				$sql .= " FROM `{$table}`";

				foreach ($class->belongsTo as $k => $b) {
					$sql .= " {$b['join_type']} JOIN `{$b['table']}` ON `{$b['table']}`.`{$b['foreign_key']}` = `{$table}`.`{$b['inner_key']}`";
				}
			} else {
				$sql .= " FROM `{$table}`";
			}

			return $this->fetchAll($sql);
		}

		return false;
	}


	public function fetchColumnNames() {
		$called_class = get_called_class();
		$class = new $called_class;
		$table = $class->fetchTable();
		$sql = "SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`=:dbname AND `TABLE_NAME`=:table";
		$params[':table'] = $table;
		$params[':dbname'] = db()->dbname;
		try {
			return db()->fetchColumns($sql, $params, $class);
		} catch (PDOException $e) {
			echo $e;
		}
	}
	
	
	
	
}