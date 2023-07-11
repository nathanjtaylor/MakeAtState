
<?php

class Alerts{

	static private $error_messages = array();
	static private $success_messages = array();

	/**
	*Set the error messages 
	* @param array $error_messages : array of messages
	*/
	static public function setErrorMessages($error_messages){
		self::$error_messages = $error_messages;
		$_SESSION['error_messages'] = self::$error_messages;
	}

	/**
	* get the error messages for display
	*/
	static public function getErrorMessages(){
		$error_messages = array();
		if(isset($_SESSION['error_messages'])){
			$error_messages = $_SESSION['error_messages'];
			$_SESSION['error_messages'] = array();	
			self::$error_messages =  array();
		}
		return $error_messages;
	}

	/**
	*Set the success messages 
	* @param array $success_messages : array of messages
	*/
	static public function setSuccessMessages($success_messages){
		self::$success_messages = $success_messages;
		$_SESSION['success_messages'] = self::$success_messages;
		 

	}

	/**
	* get the error messages for display
	*/
	static public function getSuccessMessages(){
		$success_messages = array();
		if(isset($_SESSION['success_messages'])){
			$success_messages = $_SESSION['success_messages'];
			$_SESSION['success_messages'] = array();	
			self::$success_messages =  array();
		}
		return $success_messages;
	}
	

}
?>

