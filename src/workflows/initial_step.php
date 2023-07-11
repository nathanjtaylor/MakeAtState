<?php
require_once(__DIR__."/workflow_steps.php");
class  InitialStep extends WorkflowSteps {
    protected $project_id = array();
    protected $project_name = '';
    protected $user_id;
    protected $project_data = array();
    private $iTemplate;
    public $dc;
    /**
    * Constructor function for submit 
    * Templater $sTempl : Templater object for job submit
    * @param array project_id : Array of project ids
    * @param int user_id : user id of the user
    * @param array project_data: Project data for all items in the project
    * @param array project_name: Name of the project
    */
    public function __construct(Templater &$sTempl, $project_id, $user_id, $project_data, $project_name){
        $this->iTemplate = $sTempl;
        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();
        $this->project_id = $project_id;
        $this->user_id = $user_id;
        $this->project_data = $project_data;
        $this->project_name = $project_name;
        $this->file_workflow_ids = array();
        //set current step `for the parent class
        $this->current_step = 1;
        if(!empty($this->project_id) && !empty($this->user_id)){
            $this->insertSubmissions();    
        }
    }
    /** 
    * Function to verify cart submission
    * Verifies if the user has access to submit the cart items
    */
    public function insertSubmissions(){
        $error_messages = array();
        # Array to store all the cart data 
        $job_data = array();
        $file_names = array();
        $message_array = array();
        $message_text = "";
        if($this->user_id == $_SESSION['ident']['user_id']){
            # Start transaction to inset jobs
            $this->dc->transactionStart();
            foreach($this->project_data as $id => $data) {
                if($this->user_id != $data['user_id'] || !empty($data['removed']) ){
                    $error_messages[] = "Sorry, we encounted a problem submitting your cart items, please try again";
                    break;
                }
                // mark the cart item as submitted 
                $submitted = $this->dc->markCartItemAsSubmitted($id);        
                // get file row, to check if it exists or to check if it is deleted 
                $fRow = $this->dc->getFile($data['file_id']);
                if(isset($fRow[0]) && empty($fRow[0]['deleted'])) {
                    # check if the card options are still valid 
                    $file_names[] = $fRow[0]["file_name"];
                    $cart_options =  unserialize($data['cart_data']);
                    $verified_cart_data = $this->verifyCartOptions($cart_options, $fRow[0]['file_id']);
                    # Check if the options for the cart items are still valid 
                    if(!$verified_cart_data){
                        $error_messages[] = "The options selected for the items in your cart are outdated. Please edit your cart items before submitting.";
                        break;
                    }

                    $this->setAdminEmail();
                    $job_data['user_id']= $this->user_id;
                    $job_data['project_id'] = $this->project_id;
                    $job_data['file_data'][$fRow[0]['file_id']]['data']= $data['cart_data'];
                    $job_data['file_data'][$fRow[0]['file_id']]['name']= $fRow[0]["file_name"];
                } else{
                    $error_messages[] = "The selected file does not exist. This cart item cannot be submitted";
                }
            } // end of for loop
            $submitted = $this->dc->markProjectAsSubmitted($this->project_id);
            
        }
        else {
            $error_messages[] = "Sorry, we encounted a problem submitting your cart";
        }
        if(empty($error_messages)){
            $inserted_job_id = $this->insertCartJobs($job_data);
            // send a message to the user when submitting a job
            $message_text = $this->helper->setJobSubmittedMessage(implode(",", $file_names));
            //set name of the user and file name into an arry for the helper function
            $message_array = array("fullname"=>$_SESSION['ident']['fullname'], 'email'=>$_SESSION['ident']['email'], 'user_id'=>$this->user_id, 'job_id'=>$inserted_job_id, 'project_name'=>$this->project_name);
            # Check if the options for the cart items are still valid 
            if(empty( $inserted_job_id)){
                $error_messages[] = "The options selected for the items in your cart are outdated. Please edit your cart items before submitting.";
                $this->dc->transactionRollback();
                Alerts::setErrorMessages($error_messages);
                header("Location: /?t=view_cart");
            } else {
                # send message to the user 
                # TODO what happens when the message is not sucessfully sent 
                $successful_sent = $this->helper->sendMessage($message_array, $message_text, null);
                # Insert rows into jobs and job steps
                $this->dc->transactionCommit();
                header("Location: /?t=all_jobs");
            }
        }else{
            $this->dc->transactionRollback();
            Alerts::setErrorMessages($error_messages);
            header("Location: /?t=view_cart");
        }
    }
    /**
    * Verify the options for cart items
    * @param array $cart_data: array containing workflow type, printer, color, material, etc.
    * @param int file_id: file id of the associated file
    *
    */
    public function verifyCartOptions($cart_data, $file_id){
        $verified = FALSE;
        $workflow_type = $cart_data['Type'];
        $workflowRow =  $this->dc->getWorkflowByType($workflow_type);
        if(!empty($workflowRow)){
            $this->workflow_name = $workflowRow[0]['name'];
            $this->workflow_id = $workflowRow[0]['workflow_id'];
            $this->file_workflow_ids[$file_id] = $this->workflow_id;
            $workflow_data = unserialize($workflowRow[0]['data']);
            $printer = $cart_data['Printer'];
            $material = $cart_data['Material'];
            $color = $cart_data['Color'];
            # check if the cart item options are still available for the workflow type
            if(!empty($workflow_data[$printer])){
                foreach ($workflow_data[$printer] as $p){
                    if($p["Material"] == $material && $p['Color'] == $color){
                        $verified = TRUE;
                        break;
                    }
                } 
            }
        }
        return $verified;
    }
    /**
    *  Insert project into jobs
    *  @param Array $job_data : Array of values to be inserted into the jobs and job setps table
    */ 
    public function insertCartJobs($job_data){
        $jRow = 0;
        # get the step id from workflow_steps table
        $wData= array("step_removed"=>null );
        // get the first step in the workflow 
        $wRow = $this->dc->getRowsById('workflow_steps', $wData, $skip=0, $limit=1, $order=array('ordering'), $order_dir = "ASC");
        if(!empty($wRow)){
            $jData = array("user_id"=>$job_data['user_id'], "project_id"=>$this->project_id ,"curr_work_step_id" => $wRow[0]['work_step_id'] , "job_updated" => "NOW()");
            $jRow = $this->dc->insertJobs($jData);
            if(!empty($jRow)){
                $sData = array("job_id"=>$jRow, "work_step_id"=> $wRow[0]['work_step_id'], "completed"=> date('Y-m-d H:i:s'), "completed_user_id"=> $job_data['user_id']);
                $sRow = $this->dc->insertJobSteps($sData);
                # insert into job files
                foreach($job_data['file_data'] as $file_id=>$file ) {
                    $fData = array("job_id"=>$jRow, "user_id"=> $this->user_id, "file_id"=> $file_id, "created"=> "NOW()", "data"=>$file['data'], "file_name"=>$file['name'], "workflow_id"=>$this->file_workflow_ids[$file_id] );
                    $sRow = $this->dc->insertJobFiles($fData);
                    
                }
                # return the job_id if the job is sucessfully inserted, else return empty 
                $jRow = empty($sRow) ? $sRow : $jRow;
            }
        }
        return $jRow;
    }
}
?>
