<?php

class AppModel {


	public function generate($id = null, $class = null) {
		if ($id == null) {
			$class = get_called_class();
			return new $class;
		} else {
			if ($class != null) {
				$called_class = $class;
			} else {
				$called_class = get_called_class();
			}

			$class = new $called_class;
			return $this->fetchById($id, $class);

		}
	}
	
	
	public function fetchOne($sql, $params = array(), $class = null) {
		if ($class != null) {
			$called_class = $class;
		} else {
			$called_class = get_called_class();
		}
		
		$class = new $called_class;
		try {
			return db()->fetchRow($sql, $params, $class);
		} catch (PDOException $e) {
			echo $e;
		}
	}
	
	public function fetchAll($sql = null, $params = array()) {
		$called_class = get_called_class();
		$class = new $called_class;
		$table = $class->fetchTable();

		if ($sql == null) {
			$sql = "SELECT * FROM `{$table}`";

		}

		try {
			return db()->fetchRows($sql, $params, $class);
		} catch (PDOException $e) {
			echo $e;
		}
	}


	public function fetchCustom($sql, $params = array()) {
		$called_class = get_called_class();
		$class = new $called_class;
		$table = $class->fetchTable();
		return db()->fetchRows($sql, $params, $class);
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

	public function update($sql, $params) {
		return db()->update($sql, $params);		
	}

	public function save($data = false, $database = false) {
		if (!$database) {
			$database = db()->dbname;
		} 

		try {
			if ($data != false) {
				if (!isset ($this->id) || $this->id != '') {
					$this->id = db()->saveRow($data, $database);
					return $this;
				} else {
					db()->updateRow($data);
				}
				
			} else {
				if (isset ($this->id) && $this->id != '') {
					db()->updateRow($this);
				} else {
					$this->id =  db()->saveRow($this, $database);
					return $this;
				}
				
			}
		} catch (PDOException $e) {
			echo $e;
		}
		return true;
	}



	/*
	 * -------------------------------------------------------------------------
	 *  DELETE ITEM BY ID
	 * -------------------------------------------------------------------------
	 */
	
	public function delete($data = false) {
		try {
			if ($data) {
				db()->destroy($data);
			} else {
				db()->destroy($this);
			}

		} catch (PDOException $e) {
			echo $e;
		}

		return true;
	}


	public function deleteQuery($sql, $params = null) {
		try {
			db()->destroyQuery($sql, $params);
		} catch (PDOException $e) {
			echo $e;
		}

		return true;
	}
	
	public function fetchById($id, $className = null) {
		$params[':id'] = $id;

		if ($className != null) {
			$model = $className;
		} else {
			$model = get_class($this);
		}

		$class = new $model;
		$table = $class->fetchTable();
		
		
		$sql = "SELECT `{$table}`.*";
		$belongsTo = $class->fetchBelongsTo();

		if (!empty ($belongsTo)) {
			// foreach ($class->belongsTo as $k => $b) {
			// 	if (isset ($b['join_field'])) {
			// 		$sql .= ", `{$b['table']}`.`{$b['join_field']['column']}` AS {$b['join_field']['name']} ";
			// 	}
					
			// }

			$sql .= " FROM `{$table}`";

			// foreach ($belongsTo as $k => $b) {
			// 	$sql .= " {$b['join_type']} JOIN `{$b['table']}` ON `{$b['table']}`.`{$b['foreign_key']}` = `{$table}`.`{$b['inner_key']}`";
			// }
		} else {
			$sql .= " FROM `{$table}`";
		} 

		$sql .= " WHERE `{$table}`.";

		if (is_numeric($id)) {
			if ($model == 'HomeHealthSchedule') {
				$sql .= "patient_id=:id";
			} else {
				$sql .= "id=:id";
			}
			
		} else {
			$sql .= "public_id=:id";
		}

		return $this->fetchOne($sql, $params, $class);
	}


	public function fetchFields() {
		return $this->_manage_fields;
	}

	public function fetchTable() {
		return $this->table;
	}

	public function fetchBelongsTo() {
		if (isset ($this->belongsTo)) {
			return $this->belongsTo;
		}
		return false;
	}

	public function fetchHasMany() {
		if (isset ($this->hasMany)) {
			return $this->hasMany;
		}
		return false;
	}

	public function fetchColumnsToInclude() {
		return $this->_add_fields;
	}


	/*
	 * -------------------------------------------------------------------------
	 *  FETCH ALL DATA FOR MANAGE PAGE
	 * -------------------------------------------------------------------------
	 */

	public function fetchManageData($loc = false, $orderby = false) {
		if (isset (input()->type)) {
			$model = ucfirst(camelizeString(depluralize(input()->type)));
			$class = new $model;			
		} else {
			$className = get_called_class();
			input()->type = $className;
			$class = new $className;
			
		}

		if (isset (input()->page_num)) {
			$_pageNum = input()->page_num;
		} else {
			$_pageNum = false;
		}

		$pagination = new Paginator();
		$results = $pagination->fetchResults($class, $orderby, $_pageNum, $loc);

		if (!empty ($results)) {
			return $results; 
		} 

		return false;
	}


	// public function fetchColumnNames() {
	// 	$called_class = get_called_class();
	// 	$class = new $called_class;
	// 	$table = $class->fetchTable();
	// 	$sql = "SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`=:dbname AND `TABLE_NAME`=:table";
	// 	$params[':table'] = $table;
	// 	$params[':dbname'] = db()->dbname;
	// 	try {
	// 		return db()->fetchColumns($sql, $params, $class);
	// 	} catch (PDOException $e) {
	// 		echo $e;
	// 	}
	// }


	public function fetchColumnNames() {
		$table = $this->fetchTable();
		$columnNames =  db()->fetchColumnNames($table);

		foreach ($columnNames as $n) {
			$this->$n = null;
		}

		return $this;
	}


	public function fetchRowCount($states) {
		$state = null;
		foreach ($states as $k => $s) {
			$state .= "'{$s->state}', ";
		}
		$state = trim($state, ", ");

		$sql = "SELECT count(id) AS items FROM {$this->table} WHERE {$this->table}.state IN ($state)";

		return $this->fetchOne($sql);
	}


	public function fullName() {
		return $this->last_name . ", " . $this->first_name;
	}
	
	
	
	
}