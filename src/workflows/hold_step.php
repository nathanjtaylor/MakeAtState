<?php


class HoldStep extends WorkflowSteps
{
    public $dc;

    /**
     *Constructor function for general step
     * @param Templater template : Templater object for general step
     * @param array  job_details : array of job details
     * @param array step_details: array of next or previous step details
     */
    public function __construct(Templater &$template, $job_details, $step_details){
        parent::__construct($template, $job_details, $step_details);
        //$inserted = $this->insertNextStep($job_details['data'], $step_details['work_step_id']);
die();

    }

    private function applyHold()
    {
        $aData = array($this->job_id, $this->step_details['work_step_id'], date('Y-m-d H:i:s'));
        $this->dc->insertHoldStep($aData);
    }

    public function renderJobInfoTemplate(){
        parent::renderJobInfoTemplate();
    }



}
