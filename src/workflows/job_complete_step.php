<?php
class JobCompleteStep extends WorkflowSteps{
    public $dc;



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
        parent::renderJobInfoTemplate();
    }

}
?>
