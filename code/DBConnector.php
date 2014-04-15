<?php


class DBConnector extends ViewableData {
	
	protected $defaultDBConnConfig;		//default database
	protected $extraDBConnConfig;		//another database
	protected $connectionKeyName;		//for DB::getConn()
	
	/**
	 * 
	 * $dbConfig = array(
			'type' => 'MySQLDatabase',
			'server' => 'localhost',
			'username' => 'db_username',
			'password' => 'db_password',
			'database' => 'db_name'
		);
	 *	
	 */
	public function __construct($ExtraDBConfig, $db_key_name = null){
		
		global $databaseConfig;
		
		$this->setDefaultDBConfig($databaseConfig);
		
		//start to init new connection to another database.
		$this->setExtraDBConnection($ExtraDBConfig, $db_key_name);
		
	}
	
	/**
	 * Get default SS_Database object
	 */
	private function setDefaultDBConfig($databaseConfig){
		$defaultCofing = DB::getConn();
		$this->defaultDBConnConfig = $defaultCofing;
	}
	
	/**
	 * Generate new SS_Database object
	 */
	private function setExtraDBConnection($ExtraDBConfig, $db_key_name){
		if($db_key_name == 'default') {
			user_error("DBConnector->initDBConnection: \$db_key_name 'default' is token. Please choose another one.", E_USER_ERROR);
		}
		
		if(!isset($ExtraDBConfig['type']) || empty($ExtraDBConfig['type'])) {
			user_error("DBConnector->initDBConnection: Not passed a valid database config", E_USER_ERROR);
		}
		
		if(!isset($ExtraDBConfig['database']) || empty($ExtraDBConfig['database'])) {
			user_error("DBConnector->initDBConnection: database name is required.", E_USER_ERROR);
		}
		
		if($db_key_name === null){
			$db_key_name = $ExtraDBConfig['database'];
		}else{
			$db_key_name = $db_key_name;
		}
		
		//connection exists. return error.
		if(is_object(DB::getConn($db_key_name))) {
			user_error("DBConnector->initDBConnection: \$db_key_name '$db_key_name' is token. Please choose another one.", E_USER_ERROR);
		}
		
		$this->connectionKeyName = $db_key_name;
			
		$dbClass = $ExtraDBConfig['type'];
		$conn = new $dbClass($ExtraDBConfig);
		
		$this->extraDBConnConfig = $conn;
		
		DB::setConn($this->extraDBConnConfig, $db_key_name);
	}
	
	public function __call($method, $arguments) {
		$function_name = $method . '_dbc';
	
		if(method_exists($this, $function_name)){
			//connect to freestyle database
			$this->connectDB();	
			
			$retVal = call_user_func_array(array($this, $function_name), $arguments);
			
			//connect back to silverstripe database before returning result array.
			$this->connectDefaultDB();	
			
			return $retVal;
			
		}else{
			$class = get_class($this);
			throw new Exception("Object->__call(): the method '$method' does not exist on '$class'", 2175);
		}
		
	}
	
	
	/**
	 * connect specific database
	 */
	private function connectDB(){
// 		DB::connect($this->extraDBConnConfig);
		DB::setConn($this->extraDBConnConfig);
	}
	
	/**
	 * connect silverstripe database
	 */
	private function connectDefaultDB(){
// 		DB::connect($this->defaultDBConnConfig);
		DB::setConn($this->defaultDBConnConfig);
	}
	
	
	/**
	 *	Usage : $DBConnector->query('SELECT COUNT(*) FROM "page"');
	 *
	 *	@return Array
	 */
	public function query($query){
	
		$queryOBJ = DB::getConn($this->connectionKeyName)->query($query, E_USER_ERROR);
	
		$NumberOfRows = $queryOBJ->numRecords();
	
		if($NumberOfRows){
			$results = array();
				
			while($record = $queryOBJ->record()){
				$results[] = $record;
			}
		}else{
			$results = false;
		}
	
		return $results;
	}
	
	/**
	 *	Usage : $DBConnector->GetBy('page', 'id', 1);
	 *
	 *	@return Array
	 */
	public function GetBy($from, $condition_field, $value, $select = array()){
		$sqlQuery = new DBC_SQLQuery($this->connectionKeyName);
		$sqlQuery->setFrom($from);
	
		if(!empty($select)){
			foreach ($select as $selected_column){
				$sqlQuery->selectField($selected_column);
			}
		}
	
		$sqlQuery->addWhere("\"{$condition_field}\" = '{$value}'");
	
		$QueryResult = $sqlQuery->execute();
	
		$NumberOfRows = $QueryResult->numRecords();
	
		if($NumberOfRows == 1){
			$results = $QueryResult->record();
		}elseif ($NumberOfRows > 1){	
			$results = array();
	
			while($record = $QueryResult->record()){
				$results[] = $record;
			}
		}else{
			$results = false;
		}
	
		return $results;
	}
	
	
	//***********************************************************************************************************************//
	//		The following functions are suffixed with _dbc.
	//		This is how it setup for making sure that connectDB() will be called before the actual function is called and
	//	    connectDefaultDB() will be called at the end.
	//
	//		example:
	//
	//		When $FSConnector->SQLQuery() is called, then
	//		1. call $this->connectDB()
	// 		2. call $this->SQLQuery_fsc()
	//		3. call $this->connectDefaultDB()
	//***********************************************************************************************************************//
	
	/**
	 *	Usage : $DBConnector->SQLQuery($sqlQuery);
	 *
	 *	@param	SQLQuery
	 *	@return MySQLQuery | boolean false
	 */
	public function SQLQuery_dbc(SQLQuery $sqlQuery){
		$result = $sqlQuery->execute();
		return $result;
	}
	


	
}