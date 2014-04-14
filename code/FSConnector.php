<?php


class FSConnector {
	
	protected $databaseConfig_fs;	//freestyle database
	protected $databaseConfig;		//silverstripe database
	
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
	public function __construct($dbConfig = null){
		global $databaseConfig_fs;
		global $databaseConfig;
		
		$this->databaseConfig 		= $databaseConfig;
		
		if($dbConfig !== null && is_array($dbConfig)){
			$this->databaseConfig_fs 	= $dbConfig;
		}else{
			$this->databaseConfig_fs 	= $databaseConfig_fs;
		}
		
	}
	
	
	public function __call($method, $arguments) {
		$function_name = $method . '_fsc';
	
		if(method_exists($this, $function_name)){
			//connect to freestyle database
			$this->connectFS();	
			
			$retVal = call_user_func_array(array($this, $function_name), $arguments);
			
			//connect back to silverstripe database before returning result array.
			$this->connectSS();	
			
			return $retVal;
			
		}else{
			$class = get_class($this);
			throw new Exception("Object->__call(): the method '$method' does not exist on '$class'", 2175);
		}
		
	}
	
	
	/**
	 * connect freestyle database
	 */
	private function connectFS(){
		DB::connect($this->databaseConfig_fs);
	}
	
	/**
	 * connect silverstripe database
	 */
	private function connectSS(){
		DB::connect($this->databaseConfig);
	}
	
	
	
	
	
	//***********************************************************************************************************************//
	//		The following functions are suffixed with _fsc.
	//		This is how it setup for making sure that connectFS() will be called before the actual function is called and
	//	    connectSS() will be called at the end.
	//
	//		example:
	//
	//		When $FSConnector->query() is called, then
	//		1. call $this->connectFS()
	// 		2. call $this->query_fsc()
	//		3. call $this->connectSS()
	//***********************************************************************************************************************//
	
	
	
	public function query_fsc($query){
		
		$queryOBJ = DB::query($query);

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
	
	
	
}