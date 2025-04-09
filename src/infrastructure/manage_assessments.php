<?php

# Manage assessment questions

class ManageAssessments{

	private $sTemplate;
	private $dc;
	private $helper;

	private $user_id;
	private $access_level;
	private $work_step_id;
	// private $workflow_steps = array(); // array of all workflow steps for this printer
	private $assessment_questions = array(); // array of all assessment questions
	private $assessment_types = array(); // array of all assessment questions
	private $edit_permissions = array();
	private $edit_types = array();
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
		$this->work_step_id =  UserData::create('work_step_id')->getInt();
	
		$success_messages = array();
		$error_messages = array();
		//Check if the user has permissions 		
		if($this->access_level == "ADMIN" || $this->access_level == "STAFF"){
			// action to update workflow steps
			if($pTarget == "edit_workflow_steps"){
				// getting all the values from the form
				$update_steps = array();
				$update_steps['work_step_id'] = $this->work_step_id;
				$update_steps['name'] = UserData::create('name')->getString();
				$update_steps['ordering'] = UserData::create('ordering')->getInt();
				$update_steps['admin_status'] = UserData::create('admin_status')->getString();
				$update_steps['user_status'] = UserData::create('user_status')->getString();
				$update_steps['manage_level'] = UserData::create('manage_level')->getInt();
				$update_steps['allow_cancellation'] = UserData::create('allow_cancellation')->getInt();
				$update_steps['email_confirmation'] = UserData::create('email_confirmation')->getInt();
				$this->updateWorkflowStep($update_steps);
			}
			// action to delete the workflow_step
			elseif($pTarget == 'remove_workflow_step'){
				$this->deleteWorkflowStep();
			}
			// action to undo delete the workflow_step
			elseif($pTarget == 'undo_remove_workflow_step'){
				$this->undoDeleteWorkflowStep();

			}
			// action to add a new workflow step
			elseif($pTarget == 'add_workflow_steps'){
				$add_steps =  array();
				$add_steps['name'] = UserData::create('name')->getString();
				$add_steps['ordering'] = UserData::create('ordering')->getInt();
				$add_steps['admin_status'] = UserData::create('admin_status')->getString();
				$add_steps['user_status'] = UserData::create('user_status')->getString();
				$add_steps['manage_level'] = UserData::create('manage_level')->getInt();
				$add_steps['allow_cancellation'] = UserData::create('allow_cancellation')->getInt();
				$add_steps['email_confirmation'] = UserData::create('email_confirmation')->getInt();
				$add_steps['step_type_id'] = UserData::create('step_type_id')->getInt();
				
				$this->addWorkflowSteps($add_steps);
			}else{
				$this->processWorkflowSteps();
			}
		}else{
			// if the user doesnt have permissions send them to home page 
			$error_messages[] = "Sorry this operation is not allowed";
			Alerts::setErrorMessages($error_messages);
			header('Location: /?t=home');

		}
		
	}


	/**
	* Render manage workflows steps template
	*/
	private function renderManageWorkflowStepsTemplate(){
		$this->sTemplate->setTemplate('manage_assessments.html');
		$this->sTemplate->setVariables('page_title', "Manage assessments");
		$this->sTemplate->setVariables('error_messages' , Alerts::getErrorMessages());
		$this->sTemplate->setVariables('success_messages' , Alerts::getSuccessMessages());
		$this->sTemplate->setVariables('nav_array', self::$nav_array);	
		//Set varibales for workflow_steps
		// $this->sTemplate->setVariables('workflow_steps', $this->workflow_steps);	
		$this->sTemplate->setVariables('assessment_questions', $this->assessment_questions);	
		$this->sTemplate->setVariables('assessment_types', $this->assessment_types);	
		
		// $this->sTemplate->setVariables('edit_permissions', $this->edit_permissions);	
		$this->sTemplate->setVariables('edit_types', $this->edit_types);	
		$this->sTemplate->setVariables('workflow_step_alerts', $this->workflow_alerts);	

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
	* Process workflows for display
	*/
	public function processWorkflowSteps(){
		$success_messages = array();
		$error_messages = array();

        // $sRows =  $this->dc->getAllWorkflowSteps();
        $q_rows = $this->dc->getAllAssessmentQuestions();
        // $all_permissions = $this->dc->getAllPermissions();
        // $all_step_types = $this->dc->getAllStepTypes();
        $this->assessment_types = $this->dc->getAllAssessmentQuestionTypes();
        // $this->prepareWorkflowStepsForDisplay($sRows, $all_permissions, $all_step_types);
        $this->prepareAssessmentQuestionsForDisplay($q_rows);
        $this->renderManageWorkflowStepsTemplate();
	}

	/**
	* Prepare assessment questions for display
	* @param array q_rows : rows of questions from assessment_questions table
	* @param array all_q_types: all question types from assessment_q_types table 
	*/
	public function prepareAssessmentQuestionsForDisplay($q_rows){
		foreach($q_rows as $key=>$step){
			foreach($this->assessment_types as $k=>$type){
				if($step['qtype_id'] == $type['qtype_id']){
					$step['question_type'] = $type['question_type'];
					if ($type['has_choices'] == '1') {
						$step['choices'] = $this->dc->getQuestionChoices($step['question_id']);
					}
				}
			}
			$this->assessment_questions[$key] = $step;
		}
		foreach($this->assessment_types as $key=>$type){
			$this->edit_types[$type['qtype_id']] = $type['question_type'];
		}

		// Check if general step , price step , job completed step , cancelled step and user cancelled step are available for the workflow 
		//if not do not add it to the dropdown
		// $warnings_array = $this->helper->determineReadinessOfPrinterWorkflow($step_rows, $all_step_types);
		// if(empty($warnings_array)){
		// 	$this->workflow_alerts['success_message'] = "Project workflow is ready and can accept user submissions";
		// }else{
		// 	$this->workflow_alerts['warning_message'] = "Workflow steps incomplete. Project workflow is not ready and cannot accept user submissions";
		// 	$this->workflow_alerts['warnings'] = $warnings_array;
		// }
	}


	/**
	* Update workflow steps in a workflow , workflw_id and printer name must be valid
	* @param array update_steps: array of values from the edit form
	*/

	public function updateWorkflowStep($update_steps){
		$error_messages = array();
		$success_messages = array();
		$step_name_exists = false;
		$adjust_order_work_step_id = false;
		//check if the work_step_id is available
		$sData =  array("work_step_id"=> $update_steps['work_step_id'],  "step_removed"=>null);
		// get db row for this step
		$sRow = $this->dc->getRowsById("workflow_steps", $sData);
		// get db rows for all steps 
        $allStepsRows = $this->dc->getAllWorkflowSteps();
		if(!empty($sRow) && !empty($allStepsRows)){
			// check for exact match for step names 	
			foreach($allStepsRows as $k=>$step){

				if(($step['name'] == $update_steps['name']) && ($update_steps['work_step_id'] !== $step['work_step_id']) ){

					$step_name_exists = true;
					
					$error_messages[] = "A step with same name already exists, please use a different name.";
					break;
				}
				// check the order # from the form  is set for another step
				if(  ($step['ordering'] == $update_steps['ordering']) && ($update_steps['work_step_id'] !== $step['work_step_id'])  ){
					$adjust_order_work_step_id  = $step['ordering'];
				}
			}
			if(empty($step_name_exists) ){
				$this->dc->transactionStart();
				// if the ordering needs adjustment 
				if(!empty($adjust_order_work_step_id )){
					$adjustRows = $this->dc->adjustWorkflowStepOrder($adjust_order_work_step_id);
					if(empty($adjustRows)){
						$error_messages[] = "Sorry , we are unbale to adjust the ordering of the steps.";
					}
				}
				// update the step in db
				$updateStepData = array();
				foreach($update_steps as $dbColName => $value){
					$updateStepData[$dbColName] = empty($value)?'0':$value;
				}
				$updated = $this->dc->updateUsingPrimaryKey('workflow_steps', 'work_step_id', $updateStepData);

				// if success set success message else set error message 
				(!empty($updated)) ?$success_messages[] = "Successfully updated workflow step": $error_messages[]= "Sorry we are unbale to update workflow step";

			}
		}else{
			$error_messages[] = "Sorry, unable to update step, please try again";
		}
		if(!empty($error_messages)){
			$this->dc->transactionRollback();
			Alerts::setErrorMessages($error_messages);
		}
		else if(!empty($success_messages)){
			$this->dc->transactionCommit();
			Alerts::setSuccessMessages($success_messages);
		}
		header('Location: /?t=manage_steps');
	

	}

	/**
	* Function to add a new  workflow step
	* @param array add_step : array of values to be added for a new step, obtained from the form
	*/
	public function addWorkflowSteps($add_step){
		$error_messages = array();
		$success_messages = array();
		$step_name_exists = false;
		$adjust_order_work_step_id = false;

        // get db rows for all steps 
        $allStepsRows = $this->dc->getAllWorkflowSteps();
        // check for exact match for step names 	
        foreach($allStepsRows as $k=>$step){

            if(($step['name'] == $add_step['name']) && ($add_step['work_step_id'] !== $step['work_step_id']) ){

                $step_name_exists = true;
                
                $error_messages[] = "A step with same name already exists, please use a different name.";
                break;
            }
            // check the order # from the form  is set for another step
            if(  ($step['ordering'] == $add_step['ordering']) && ($add_step['work_step_id'] !== $step['work_step_id'])  ){
                $adjust_order_work_step_id  = $step['ordering'];
            }
        }
        if(empty($step_name_exists) ){
            $this->dc->transactionStart();
            // if the ordering needs adjustment 
            if(!empty($adjust_order_work_step_id )){
                $adjustRows = $this->dc->adjustWorkflowStepOrder($adjust_order_work_step_id);
                if(empty($adjustRows)){
                    $error_messages[] = "Sorry , we are unbale to adjust the ordering of the steps.";
                }
            }
            // update the step in db
            $addStepData = array();
            foreach($add_step as $dbColName => $value){
                $addStepData[$dbColName] = empty($value)?'0':$value;
            }
            $updated = $this->dc->insertWorkflowSteps($addStepData);

            // if success set success message else set error message 
            (!empty($updated)) ?$success_messages[] = "Successfully added a new  workflow step": $error_messages[]= "Sorry we are unbale to add a new workflow step";

        }

		if(!empty($error_messages)){
			$this->dc->transactionRollback();
			Alerts::setErrorMessages($error_messages);
		}
		else if(!empty($success_messages)){
			$this->dc->transactionCommit();
			Alerts::setSuccessMessages($success_messages);
		}
		header('Location: /?t=manage_steps');



	}
	/**
	* Function to delete the wprkflow step
	* 
	*/
	public function deleteWorkflowStep(){
		$error_messages = array();
		$success_messages = array();
		// check if and work_step_id is set
		if(isset($this->work_step_id)){
			// update step_removed in wirkflow_steps table for this work_step_id
			$this->dc->transactionStart();
			$sConditionsCol = array('work_step_id');
			$sData = array('work_step_id'=>$this->work_step_id, 'step_removed'=>date('Y-m-d:H:i:s'));
			$dRow = $this->dc->updateUsingConditions('workflow_steps', $sConditionsCol, $sData);	
			(!empty($dRow)) ?$success_messages[] = 'Successfully deleted step from the workflow. <a href = "/?t=undo_remove_workflow_step&work_step_id='.$this->work_step_id.'">Undo</a>':$error_messages[] = "Sorry, we are unable to perform a delete operation";
		}else{
			$error_messages[] = "Sorry, we are unable to perform a delete operation";
		}
		if(!empty($error_messages)){
			$this->dc->transactionRollback();
			Alerts::setErrorMessages($error_messages);
		}
		else if(!empty($success_messages)){
			$this->dc->transactionCommit();
			Alerts::setSuccessMessages($success_messages);
		}
		header('Location: /?t=manage_steps');



	}

	/**
	* Function to undo delete the wprkflow step
	* 
	*/
	public function undoDeleteWorkflowStep(){
		$error_messages = array();
		$success_messages = array();
		// check if work_step_id is set
		if(isset($this->work_step_id)){
			// undo step_removed in workflow_steps table for this work_step_id
			$this->dc->transactionStart();
			$sConditionsCol = array('work_step_id');
			$sData = array('work_step_id'=>$this->work_step_id, 'step_removed'=>null);
			$dRow = $dRow = $dRow = $dRow = $this->dc->updateUsingConditions('workflow_steps', $sConditionsCol, $sData);	
			(!empty($dRow)) ?$success_messages[] = 'Undo operation successful':$error_messages[] = "Sorry, we are unable to perform a undo operation";
		}else{
			$error_messages[] = "Sorry, we are unable to perform a undo operation";
		}
		if(!empty($error_messages)){
			$this->dc->transactionRollback();
			Alerts::setErrorMessages($error_messages);
		}
		else if(!empty($success_messages)){
			$this->dc->transactionCommit();
			Alerts::setSuccessMessages($success_messages);
		}
		header('Location: /?t=manage_steps');



	}
	
}


?>
