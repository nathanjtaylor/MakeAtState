<?php
/** 
* Logger function for 3dPrime 
* Extends PHP Logger class
*/

class LoggerPrime {


	const LOG_NONE 		= 0x000;
	const LOG_CRITICAL	= 0x002;
	const LOG_ERROR		= 0x004;
	const LOG_WARNING	= 0x008;
	const LOG_INFO		= 0x00A;
	const LOG_DEBUG		= 0x00F;

	static protected $stiLogFile ;

	protected $iLogFile;

	static public $iLogLevel = self::LOG_ERROR;

	static public $iLogFileWritable = FALSE;
	
	public function setLogFile(){
		// lazy loading of log file
		if(LoggerPrime::$stiLogFile == null){
			LoggerPrime::$stiLogFile = APP::logfile();
		}
		
	}


	function logEntry($iMessage, $iType){
		$iTimeStamp = date("Y-m-d H-i-s");
		if(LoggerPrime::$iLogFileWritable){
			$sEntry = "[{$iTimeStamp}][{$iType}][{$iMessage}]" . PHP_EOL;
			if(!file_put_contents(LoggerPrime::$stiLogFile, $sEntry, FILE_APPEND | LOCK_EX)){
				trigger_error("LoggerPrime filure: Unable to write to file", E_USER_ERROR);
				exit(1);
			}	
		}else{
            #TODO - catch the error when log file is not set
		}
	}

	/**
	* Initialize  for  Logger Prime 

	*/
	 static public function initialize($iLogFile, $iLevel){
		LoggerPrime::$stiLogFile = $iLogFile;
        LoggerPrime::$iLogLevel = $iLevel;
        LoggerPrime::$iLogFileWritable = TRUE;
	}
	
	static public function debug($message){
		if(self::LOG_DEBUG <= LoggerPrime::$iLogLevel){
			LoggerPrime::logEntry($message, "DEBUG");
		}
	}

	static public function info($message = null){
		if(self::LOG_INFO <= LoggerPrime::$iLogLevel){
			LoggerPrime::logEntry($message, "INFO");
		}
	}

	static public function error($message = null){
		if(self::LOG_INFO <= LoggerPrime::$iLogLevel){
			LoggerPrime::logEntry($message, "ERROR");
		}
	}
		
}

?>
