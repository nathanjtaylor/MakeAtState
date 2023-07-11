<?php
class DeliveryDateStep extends WorkflowSteps{

    public $dc;

    private $edit_delivery_date = array();

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
        $this->wTemplate->setVariables('edit_delivery_date', $this->edit_delivery_date);
        parent::renderJobInfoTemplate();
    }

    protected function additionalWorkForDisplay()
    {
        $this->setEditDeliveryDateDetails();
    }

    /**
    * Set the edit delivery date array
    */
    public function setEditDeliveryDateDetails(){
        // set the  price step id for edit _price setcion
        $this->edit_delivery_date['next'] = $this->step_details['work_step_id'];
        $this->edit_delivery_date['user_id'] = $this->job_details['user_id'];
        $this->edit_delivery_date['job_id'] = $this->job_details['job_id'];

    }


}
?>
