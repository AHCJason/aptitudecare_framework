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

	public function save($data = array()) {
		try {
			if (empty ($data)) {
				if ($this->id != '') {
					db()->updateRow($this);
				} else {
					db()->saveRow($this);
				}
				
			} else {
				$data->table = $this->table;
				if ($data->id != '') {
					db()->updateRow($data);
				} else {
					db()->saveRow($data);
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


	/*
	 * -------------------------------------------------------------------------
	 *  FETCH ALL DATA FOR MANAGE PAGE
	 * -------------------------------------------------------------------------
	 */

	public function fetchManageData() {
		if (isset (input()->type)) {
			$model = ucfirst(camelizeString(depluralize(input()->type)));
			$class = new $model;
			$sql = "SELECT `{$class->table}`.*";
			$i = 1;
			if (isset ($class->belongsTo)) {
				$count = count($class->belongsTo);
				foreach ($class->belongsTo as $k => $b) {
					$linkClass = new $k;
					if (isset ($class->belongsTo[$k]['join_field'])) {
						foreach ($class->belongsTo['join_field'] as $f) {
							pr ($f);
							$sql .= ", `{$b['table']}`.`{$f['join_field']['column']}` AS {$f['join_field']['name']}";
							
						}
						
					} 
					$sql .= " {$b['join_type']} JOIN `{$b['table']}` ON `{$b['table']}`.`{$b['foreign_key']}` = `{$class->table}`.`{$b['inner_key']}`";
				}
			} else {
				$sql .= " FROM `{$class->table}`";
			}
			echo $sql; die();
			return $this->fetchAll($sql);
		}

		return false;
	}
	
	
	
	
}