<?php

class PriceStep extends WorkflowSteps{


	public $dc;

	private $edit_price = array();


	/**
	*Constructor function for general step
	* @param Templater template : Templater objcect for general step
	* @param array  job_details : array of job details
	* @param array step_details: array of next or previous step details
	*/
	public function __construct(Templater &$template, $job_details, $step_details){
		parent::__construct($template, $job_details, $step_details);
		$inserted =$this->insertNextStep($job_details['data'], $step_details['work_step_id']);
		#$this->insertNextStep();
	}

	/**
	* Render job info template
	*/
	public function renderJobInfoTemplate(){
		$this->wTemplate->SetVariables('edit_price', $this->edit_price);
		parent::renderJobInfoTemplate();
	}

	protected function additionalWorkForDisplay()
	{
		$this->setEditPriceDetails();
	}

	/**
	* Set the edit price array
	*/
	private function setEditPriceDetails(){
		#set num of copies into eidt price array for display purposes
        foreach($this->job_files as $file_id=>$file) {
            $this->edit_price['files'][$file_id]['Copies'] = $file['print_details']['Copies'];
            // set price constants for the printer 	
            $this->edit_price['files'][$file_id]['price_constants'] = $this->getPrinterPriceConstants($file['print_details']);
        }
        // set the  price step id for edit _price setcion	
        $this->edit_price['next'] = $this->step_details['work_step_id'];
        $this->edit_price['user_id'] = $this->job_details['user_id'];
        $this->edit_price['job_id'] = $this->job_details['job_id'];
        $this->edit_price['button_name'] = $this->step_details['name'];
	}




}


?>

