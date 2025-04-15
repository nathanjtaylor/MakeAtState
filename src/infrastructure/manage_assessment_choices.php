<?php

# Manage assessment question choices

class ManageAssessmentChoices {

	private $sTemplate;
	private $dc;
	private $helper;

	private $user_id;
	private $access_level;
	private $question_id;
	private $option_id;
	private $question = array(); // this assessment question
	private $assessment_choices = array(); // this assessment question's choices
	private $assessment_types = array(); // array of all assessment types
	private $edit_permissions = array();
	private $workflow_alerts = array(); // contains messages to help the user by provind more info about the workflow steps
	
	static private $user;
	static private $nav_array;


	/**
	* Constructor function for manage workflow steps
	* @param Templater $iTempl : emplater object for manage workflow steps class
	*/
	public function __construct(Templater &$sTempl){
		$this->sTemplate = $sTempl;
		$this->dc = new DataCalls();
		$this->helper = new PrimeHelper();
		
		$this->setUser();
		$this->setNavigation();
		$this->setAccessLevel();
		
		$pTarget = UserData::create('t')->getString();
		$this->question_id = UserData::create('question_id')->getInt();
		$this->option_id = UserData::create('option_id')->getInt();

		$success_messages = array();
		$error_messages = array();
		//Check if the user has permissions 		
		if($this->access_level == "ADMIN" || $this->access_level == "STAFF"){

			if ($pTarget == "edit_assessment_choices") {
				$update_values = array();
				$update_values['option_id'] = $this->option_id;
				$update_values['option_text'] = UserData::create('option_text')->getString();
				$update_values['ordering'] = UserData::create('ordering')->getInt();
				APP::printVar($update_values);

				// $this->updateAssessment($update_values, $q_choices);
			}
			elseif ($pTarget == "add_assessment_choices") {
				$add_values = array();
				$add_values['option_text'] = UserData::create('option_text')->getString();
				$add_values['ordering'] = UserData::create('ordering')->getInt();
				APP::printVar($add_values);
				// $this->addAssessment($add_values);
			}
			elseif ($pTarget == "remove_assessment_choices") {
				// $this->deleteAssessment();
			}
			elseif ($pTarget == "undo_remove_assessment_choices") {
				// $this->undoDeleteAssessment();
			}
			else {
				$this->processAssessmentChoiceOptions();
			}
		} else {
			// if the user doesnt have permissions send them to home page 
			$error_messages[] = "Sorry this operation is not allowed";
			Alerts::setErrorMessages($error_messages);
			header('Location: /?t=home');
		}
	}

	/**
	* Render manage workflows steps template
	*/
	private function renderManageAssessmentOptionsTemplate(){
		$this->sTemplate->setTemplate('manage_assessment_choices.html');
		$this->sTemplate->setVariables('page_title', "Manage assessment choices");
		$this->sTemplate->setVariables('error_messages' , Alerts::getErrorMessages());
		$this->sTemplate->setVariables('success_messages' , Alerts::getSuccessMessages());
		$this->sTemplate->setVariables('nav_array', self::$nav_array);	

		$this->sTemplate->setVariables('question', $this->question);
		$this->sTemplate->setVariables('assessment_choices', $this->assessment_choices);

		$this->sTemplate->generate();
	}

	/**
	* Sets the user 
	*/
	private function setUser(){
		//lazy loading  user
		if(self::$user == null){
			self::$user = AuthenticatedUser::getUser();
			$this->user_id = self::$user['user_id'];
		}

	}
	/**
	* Get access level for the user 
	*/
	private function setAccessLevel(){
		$this->access_level = AuthenticatedUser::getUserPermissions();
	}
	/**
	* Sets the navigation for the page
	*/
	private function setNavigation(){

		if(self::$nav_array == null){
			self::$nav_array = AuthenticatedUser::getUserNavBar();
		}
	}
	
	/**
	* Process assessment question choices
	*/
	public function processAssessmentChoiceOptions(){
		$success_messages = array();
		$error_messages = array(); 

        $this->question = $this->dc->getAssessmentQuestionbyID($this->question_id)[0];
        $this->assessment_choices = $this->dc->getAssessmentChoicesbyQuestionID($this->question_id);
		APP::printVar($this->question);
        $this->renderManageAssessmentOptionsTemplate();
	}

	/**
	* Update workflow steps in a workflow , workflw_id and printer name must be valid
	* @param array update_steps: array of values from the edit form
	*/
// 	public function updateAssessment($update_values, $q_choices){
// 		$error_messages = array();
// 		$success_messages = array();
// 		$adjust_order_question_id = false;

// 		//check if the work_step_id is available
// 		$sData = array("question_id"=> $update_values['question_id'], "question_removed"=>null);
// 		// get db row for this step
// 		$sRow = $this->dc->getRowsById("assessment_questions", $sData);
// 		// get db rows for all steps 
//         $allQsRows = $this->dc->getAllAssessmentQuestions();
// 		if(!empty($sRow) && !empty($allQsRows)){
// 			// check if order needs updating	
// 			foreach($allQsRows as $k=>$question){
// 				if(
// 					($question['ordering'] == $update_values['ordering']) && 
// 					($update_values['question_id'] !== $question['question_id'])
// 					) {
// 					$adjust_order_question_id = $question['ordering'];
// 				}
// 			}

// 			$this->dc->transactionStart();
// 			// if the ordering needs adjustment 
// 			if(!empty($adjust_order_question_id)){
// 				$adjustRows = $this->dc->adjustQuestionOrder($adjust_order_question_id);
// 				if(empty($adjustRows)){
// 					$error_messages[] = "Sorry, we are unable to adjust the ordering of the questions.";
// 				}
// 			}
// 			// update the step in db
// 			$updateQuestionData = array();
// 			foreach($update_values as $dbColName => $value){
// 				$updateQuestionData[$dbColName] = empty($value)?'0':$value;
// 			}
// 			$updated = $this->dc->updateUsingPrimaryKey('assessment_questions', 'question_id', $updateQuestionData);

// 			// if success set success message else set error message 
// 			(!empty($updated)) ?$success_messages[] = "Successfully updated assessment question": $error_messages[]= "Sorry, we are unable to update the assessment question";

// 		}else{
// 			$error_messages[] = "Sorry, unable to update question, please try again";
// 		}
// 		if(!empty($error_messages)){
// 			$this->dc->transactionRollback();
// 			Alerts::setErrorMessages($error_messages);
// 		}
// 		else if(!empty($success_messages)){
// 			$this->dc->transactionCommit();
// 			Alerts::setSuccessMessages($success_messages);
// 		}
// 		header('Location: /?t=manage_assessments');
// 	}

// 	/**
// 	* Function to add a new  workflow step
// 	* @param array add_step : array of values to be added for a new step, obtained from the form
// 	*/
// 	public function addAssessment($add_values){
// 		$error_messages = array();
// 		$success_messages = array();
// 		$adjust_order_question_id = false;

//         // get db rows for all steps 
//         $allQsRows = $this->dc->getAllAssessmentQuestions();
		
//         foreach($allQsRows as $k=>$question){
//             if(  ($question['ordering'] == $add_values['ordering']) && ($add_values['question_id'] !== $question['question_id'])  ){
//                 $adjust_order_question_id  = $question['ordering'];
//             }
//         }

// 		$this->dc->transactionStart();
// 		// if the ordering needs adjustment 
// 		if(!empty($adjust_order_question_id)){
// 			$adjustRows = $this->dc->adjustQuestionOrder($adjust_order_question_id);
// 			if(empty($adjustRows)){
// 				$error_messages[] = "Sorry , we are unbale to adjust the ordering of the steps.";
// 			}
// 		}
// 		// update the step in db
// 		$addQuestionData = array();
// 		foreach($add_values as $dbColName => $value){
// 			$addQuestionData[$dbColName] = empty($value)?'0':$value;
// 		}
// 		$updated = $this->dc->insertAssessmentQuestion($addQuestionData);

// 		// if success set success message else set error message 
// 		(!empty($updated)) ?$success_messages[] = "Successfully added a new assessment question.": $error_messages[]= "Sorry, we are unable to add a new assessment question.";

// 		if(!empty($error_messages)){
// 			$this->dc->transactionRollback();
// 			Alerts::setErrorMessages($error_messages);
// 		}
// 		else if(!empty($success_messages)){
// 			$this->dc->transactionCommit();
// 			Alerts::setSuccessMessages($success_messages);
// 		}
// 		header('Location: /?t=manage_assessments');
// 	}

// 	/**
// 	* Function to delete the assessment question.
// 	*/
// 	public function deleteAssessment(){
// 		$error_messages = array();
// 		$success_messages = array();
// 		// check if and question_id is set
// 		if(isset($this->question_id)){
// 			// update question_removed in assessment_questions table for this question_id
// 			$this->dc->transactionStart();
// 			$sConditionsCol = array('question_id');
// 			$sData = array('question_id'=>$this->question_id, 'question_removed'=>date('Y-m-d:H:i:s'));
// 			$dRow = $this->dc->updateUsingConditions('assessment_questions', $sConditionsCol, $sData);	
// 			(!empty($dRow)) ?$success_messages[] = 'Successfully deleted step from the workflow. <a href = "/?t=undo_assessment_delete&question_id='.$this->question_id.'">Undo</a>':$error_messages[] = "Sorry, we are unable to perform a delete operation";
// 		}else{
// 			$error_messages[] = "Sorry, we are unable to perform a delete operation";
// 		}
// 		if(!empty($error_messages)){
// 			$this->dc->transactionRollback();
// 			Alerts::setErrorMessages($error_messages);
// 		}
// 		else if(!empty($success_messages)){
// 			$this->dc->transactionCommit();
// 			Alerts::setSuccessMessages($success_messages);
// 		}
// 		header('Location: /?t=manage_assessments');
// 	}

// 	/**
// 	* Function to undo deletion of an assessment question
// 	*/
// 	public function undoDeleteAssessment(){
// 		$error_messages = array();
// 		$success_messages = array();
// 		// check if question_id is set
// 		if(isset($this->question_id)){
// 			$this->dc->transactionStart();
// 			$sConditionsCol = array('question_id');
// 			$sData = array('question_id'=>$this->question_id, 'question_removed'=>null);
// 			$dRow = $dRow = $dRow = $dRow = $this->dc->updateUsingConditions('assessment_questions', $sConditionsCol, $sData);	
// 			(!empty($dRow)) ?$success_messages[] = 'Undo operation successful':$error_messages[] = "Sorry, we are unable to perform a undo operation";
// 		}else{
// 			$error_messages[] = "Sorry, we are unable to perform a undo operation";
// 		}
// 		if(!empty($error_messages)){
// 			$this->dc->transactionRollback();
// 			Alerts::setErrorMessages($error_messages);
// 		}
// 		else if(!empty($success_messages)){
// 			$this->dc->transactionCommit();
// 			Alerts::setSuccessMessages($success_messages);
// 		}
// 		header('Location: /?t=manage_assessments');
// 	}

}


?>
