<?php 
class DBC_SQLQuery extends SQLQuery {
	
	protected $DBconnKeyName = false;
	
	
	public function __construct($AlternativeDB, $select = "*", $from = array(), $where = array(), $orderby = array(),
		$groupby = array(), $having = array(), $limit = array()) {
		
		$this->SetAlternativeDB($AlternativeDB);
	
		parent::__construct($select = "*", $from = array(), $where = array(), $orderby = array(), $groupby = array(), $having = array(), $limit = array());
	}
	
	public function SetAlternativeDB($KeyName){
		$this->DBconnKeyName = $KeyName;
		return $this;
	}
	
	
	public function getFilter() {
		Deprecation::notice('3.0', 'Please use itemized filters in getWhere() instead of getFilter()');
		
		if($this->DBconnKeyName){
			return DB::getConn($this->DBconnKeyName)->sqlWhereToString($this->getWhere(), $this->getConnective());
		}else{
			return DB::getConn()->sqlWhereToString($this->getWhere(), $this->getConnective());
		}
	}
	
	
	/**
	 * Generate the SQL statement for this query.
	 *
	 * @return string
	 */
	public function sql() {
		// TODO: Don't require this internal-state manipulate-and-preserve - let sqlQueryToString() handle the new
		// syntax
		$origFrom = $this->from;
		// Sort the joins
		$this->from = $this->getOrderedJoins($this->from);
		// Build from clauses
		foreach($this->from as $alias => $join) {
			// $join can be something like this array structure
			// array('type' => 'inner', 'table' => 'SiteTree', 'filter' => array("SiteTree.ID = 1",
			// "Status = 'approved'", 'order' => 20))
			if(is_array($join)) {
				if(is_string($join['filter'])) $filter = $join['filter'];
				else if(sizeof($join['filter']) == 1) $filter = $join['filter'][0];
				else $filter = "(" . implode(") AND (", $join['filter']) . ")";
	
				$aliasClause = ($alias != $join['table']) ? " AS \"" . Convert::raw2sql($alias) . "\"" : "";
				$this->from[$alias] = strtoupper($join['type']) . " JOIN \""
						. $join['table'] . "\"$aliasClause ON $filter";
			}
		}
	
		if($this->DBconnKeyName){
			$sql = DB::getConn($this->DBconnKeyName)->sqlQueryToString($this);
		}else{
			$sql = DB::getConn()->sqlQueryToString($this);
		}
		
		if($this->replacementsOld) {
			$sql = str_replace($this->replacementsOld, $this->replacementsNew, $sql);
		}
	
		$this->from = $origFrom;
	
		// The query was most likely just created and then exectued.
		if(trim($sql) === 'SELECT * FROM') {
			return '';
		}
		return $sql;
	}
	
	
	/**
	 * Execute this query.
	 * @return SS_Query
	 */
	public function execute() {
		if($this->DBconnKeyName){
			return DB::getConn($this->DBconnKeyName)->query($this->sql(), E_USER_ERROR);
		}else{
			return DB::query($this->sql(), E_USER_ERROR);
		}
	}
	
	
}