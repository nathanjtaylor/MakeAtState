<?php
#Class to add notes to jobs

class AddNotes{

	private $job_id;
	private $notes_text;

	private $nTemplate;
	private $dc;
	private $helper;
	private $access_level;

	static private $user;

	/**
	*Constructor function for SendMessage class
	* @param Templater $ntempl : templater object for send message class
	* @param int job_id : job_id of the job
	* @param string notes_text : text of the note that needs to be added
	*/
	public function __construct(Templater &$nTempl, $job_id, $notes_text){
		$this->nTemplate = $nTempl;
		$this->dc = new DataCalls();
		$this->helper = new PrimeHelper();
		$this->setUser();
		$this->setAccessLevel();
		
		$this->job_id = $job_id;
		$this->notes_text = $notes_text;
	}
	/**
	* Sets the user 
	*/

	private function setUser(){
		//lazy loading  user
		if(self::$user == null){
			self::$user = AuthenticatedUser::getUser();
		}

	}

	/**
	* Get access level for the user 
	*/
	private function setAccessLevel(){
		$this->access_level = AuthenticatedUser::getUserPermissions();
	}

	/**
	* Get the user and file details for the job
	*/
	private function getJobUserDetails(){

		return  $this->dc->getJobUserDetails($this->job_id);
		

	}
	/*Function to insert messages into email and job_updates table
	* @param array $nData : Data to be inserted into notes table
	*/
	private function insertNotes($nData){
		$nRow = $this->dc->insertNotes($nData);
		if(!empty($nRow)){
			return True;
		}
		return False;

	}
	/**
	*Send the message to the user 
	*/
	public function addJobNotes(){
		$successful_add = 0;


		if($this->access_level == "STUDENT STAFF" || $this->access_level == "STAFF" || $this->access_level == "ADMIN" ){
			$jRow = $this->getJobUserDetails();
			if(isset($jRow[0])){
				$nData = array("job_id" => $this->job_id , "added_user_id"=>self::$user['user_id'] , "note_text"=>$this->notes_text );
				$successful_add = $this->insertNotes($nData);
			}
		}
		if($successful_add  == True){
			$job_block_notes = $this->helper->prepareJobNotes($this->dc, $this->job_id);
			$this->nTemplate->setTemplate('job_notes.html');
			$this->nTemplate->setBlock('job_notes_block');
			$this->nTemplate->setBlockVariables('job_notes', $job_block_notes);
			$this->nTemplate->setBlockVariables('success', "True");
			$block = $this->nTemplate->generateBlock();			
			return $block;
		}
		else{
			$this->nTemplate->setTemplate('note_added.html');
			$this->nTemplate->setBlock('note_add_failure');
			$block = $this->nTemplate->generateBlock();			
			return $block;

		}
		
	}

	





}


?>
