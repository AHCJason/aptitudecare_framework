<?php


class Paginator {

	public $items_per_page;
	public $items_total;
	public $current_page;
	public $num_pages;
	public $high;
	public $low;
	public $default_ipp = 25;


	public function __construct() {
		if (isset (input()->page_count)) {
			$this->current_page = input()->page_count;
		} else {
			$this->current_page = 1;
		}
		
		//$this->items_per_page = (!empty (input()->ipp)) ? input()->ipp:$this->default_ipp;
	}

	public function paginate($sql, $params, $class) {
		//	Calculate the total number of pages
		$this->num_pages = $this->items_total/$this->default_ipp;

		//	Need to get the starting and stopping counts based on the current page and number 
		//	of items per page
		if ($this->current_page == 1) {
			$this->low = 0;
			$this->high = $this->default_ipp;
		} else {
			$this->high = $this->current_page * $this->default_ipp;
			$this->low = ($this->high - $this->default_ipp);
		}

		$sql .= " LIMIT {$this->low}, {$this->default_ipp}";

		smarty()->assignByRef('pagination', $this);
		return db()->fetchRows($sql, $params = array(), $class);
	}


	public function fetchResults($class, $orderby = false, $pageNum = false, $loc = false) {
			$table = $class->fetchTable();
			$params = array();
			if ($loc) {
				$addStates = $loc->fetchLocationStates();
			} else {
				$addStates = false;
			}

			//	Need to first count how many items are in the row to see if we need to paginate the results
			$count = db()->fetchCount($table);
			$this->items_total = $count['items'];
			

			$sql = "SELECT `{$table}`.*";
			$i = 1;
			$belongsTo = $class->fetchBelongsTo();
			if (!empty ($belongsTo)) {
				foreach ($belongsTo as $k => $b) {
					if (isset ($b['join_field'])) {
						$sql .= ", `{$b['table']}`.`{$b['join_field']['column']}` AS {$b['join_field']['name']} ";
					}
					
				}

				$sql .= " FROM `{$table}`";

				foreach ($belongsTo as $k => $b) {
					$sql .= " {$b['join_type']} JOIN `{$b['table']}` ON `{$b['table']}`.`{$b['foreign_key']}` = `{$table}`.`{$b['inner_key']}`";
				}

				$hasMany = $class->fetchHasMany();
				if (!empty ($hasMany)) {
					foreach ($hasMany as $k => $v) {
						$sql .= " {$v['join_type']} JOIN `{$v['table']}` ON `{$v['table']}`.`{$v['foreign_key']}` = `{$table}`.`{$v['inner_key']}`";
					}
				}
			} else {
				$sql .= " FROM `{$table}`";
			}

			if ($addStates) {
				$sql .= " WHERE";
				if (!empty ($hasMany)) {
					foreach ($hasMany as $k => $v) {
						if (input()->type == 'users') {
							$sql .= " `{$v['table']}`.`{$v['join_key']}` = :item";
							$params[":item"] = $loc->id;
						} 				
					}
				} else {
					foreach ($addStates as $key => $state) {
						if (input()->type == 'case_managers' || input()->type == 'healthcare_facilities') {
							$sql .= "  `healthcare_facility`.`state` = :loc_state{$key} OR";
						} elseif (input()->type == 'physicians') {
							$sql .= " `physician`.`state` = :loc_state{$key} OR";
						} elseif (input()->type == 'home_health_clinicians') {
							$sql .= "";
						}
						
						$params[":loc_state{$key}"] = $loc->state;
					}
				}		
				
				$sql = trim($sql, " OR");
			}

			if ($orderby) {
				$sql .= " ORDER BY `{$table}`.`{$orderby}` ASC";
				$params[':orderby'] = $orderby;
			}

			//	If there are more than the default items per page in the result then we need to paginate
			if ($this->items_total > $this->default_ipp) {
				return $this->paginate($sql, $params, $class);
			} else {
				return db()->fetchRows($sql, $params, $class);
			}

			

	}


}