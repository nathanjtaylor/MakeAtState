<?php
# Class to enter into the workflow
# Determines which step in the work and send calls the appropriate class
require_once(__DIR__."/workflow_steps.php");
class ProcessWorkflow extends WorkflowSteps{

    const MORE_INFO_ID = "cancel_more_info_";

    private $project_id = array();
    private $workflow_step;
    private $next_step;
    private $cancel_step;
    private $hold_step;
    private $pTemplate;
    public $dc;
    private $jRow;
    /**
    * Constructor function for workflow steps
    * Templater $sTempl : Templater object for job submit
    */
    public function __construct(Templater &$wTempl){  

        $this->pTemplate = $wTempl;
        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();
        $this->setUser();
        $this->setAccessLevel();
        $this->stepAccess();
        $pTarget = UserData::create('t')->getString();
        $this->job_id = UserData::create('job_id')->getInt(0);

        $this->user_id = UserData::create('uid')->getInt(0);
        $this->job_notes = array();
        # Check if the user is an admin
        if($this->user_id  !== $_SESSION['ident']['user_id'] && ( $this->access_level === "STUDENT STAFF" || $this->access_level === "STAFF" || $this->access_level === "ADMIN")  ) {
            $this->user_id = $_SESSION['ident']['user_id'];
        }

        if(($this->user_id == $_SESSION['ident']['user_id']) && ($this->job_id)) {
            $aData = array("job_id" => $this->job_id);
            $this->jRow = $this->dc->getActiveJobs($aData)[0];
            if (isset($this->jRow)) {
                $this->current_step_order = $this->jRow['ordering'];
                $this->current_step = $this->jRow['curr_work_step_id'];
                $this->job_step_id = $this->jRow['job_step_id'];
            }
        }

        $this->SetAdminEmail();
        if($pTarget == "setprice"){
            LoggerPrime::info("Setting price for job  .job_id:" . $this->job_id );
            $sMatches =  array("job_id" => $this->job_id);
            $jfRow = $this->dc->getJobFiles($sMatches);
            $price_post_data = array();
            // arrange post values by file
            foreach($jfRow as $job_file) {
                foreach($_POST as $key=>$val){
                    if(strpos($key, "_".$job_file['file_id']) !== false){
                        // strip the file id from the key. File is appeneded as "_31" at the end of the key
                        $price_post_key = substr($key, 0, strrpos( $key, '_'));
                        $price_post_data['file_prices'][$job_file['file_id']][$price_post_key] = $val;
                        // if key alreay exixts unset it
                        unset($_POST[$key]);
                        if(array_key_exists($key, $price_post_data)) {
                            unset($price_post_data[$key]);
                        }
                    } else {
                         $price_post_data[$key] = $val;
                    }
                }
            }
            $price_array = array();
            $grand_total = 0;
            foreach($price_post_data['file_prices'] as $file_id=>$price_data) {
                $price_structure = $price_data['price_structure'];
                if($price_structure == "modular"){
                    $const_str = 'price_const_';
                    $quantity_str = 'price_quantity_';
                    $quantity_array = array();
                    $constant_array = array();
                    foreach($price_data as $key=>$val){
                        if(strpos($key, $const_str)!==false){
                            $quantity_array[] = floatval($val);
                        }
                        elseif (strpos($key, $quantity_str)!==false){
                            $constant_array[] = floatval($val);
                            $price_array[$file_id][$key] = floatval($val);
                        }
                    }
                    if(count($quantity_array)!=count($constant_array)){
                        $error_messages[] = "Sorry, we cannot perform this operation.";
                        Alerts::setErrorMessages($error_messages);
                        header("Location: /?t=all_jobs");
                    }
                    $unit_price = 0;
                    foreach($quantity_array as $key=>$val){
                        $unit_price += $val * $constant_array[$key];
                    }
                    $price_array[$file_id]['unit_price'] =sprintf('%01.2f', $unit_price);

                }
                else{
                    $price_array[$file_id]['unit_price'] = sprintf('%01.2f', $price_data['unit_price']);
                }
                $price_array[$file_id]['count'] = $price_data['price_count'];
                $price_array[$file_id]['total_price_before_discount'] = sprintf('%01.2f', $price_data['total_price_before_discount']);
                $price_array[$file_id]['discount'] = $price_data['discount'];
                $price_array[$file_id]['total_pretax'] = sprintf('%01.2f', $price_data['total_price']);
                $price_array[$file_id]['tax'] = sprintf('%01.2f', $price_data['total_price'] * .06);
                $price_array[$file_id]['total'] = sprintf('%01.2f', $price_data['total_price'] * 1.06);
                $grand_total += $price_array[$file_id]['total'];
            }
            
            $price_array['grand_total_price_before_discount'] = $price_post_data['grand_total'];
            $price_array['grand_total'] = $grand_total;
            $price_array['tax'] = $grand_total - $price_post_data['grand_total'];
            $this->next_step = UserData::create('next')->getInt();
            $this->reset_step = UserData::create('reset')->getInt(0);
            $this->submitPriceStep($price_array);
        }
        else if($pTarget == "setdeliverydate"){
            LoggerPrime::info("Setting delivery for job  .job_id:" . $this->job_id );
            $delivery_array['estimated_delivery'] = UserData::create('estimated_delivery')->getString();
            $this->next_step = UserData::create('next')->getInt();
            $this->submitDeliveryDateStep($delivery_array);
        }
        else if($pTarget == "update_print_details"  && ( $this->access_level === "STUDENT STAFF" || $this->access_level === "STAFF" || $this->access_level === "ADMIN")){
            LoggerPrime::info("Updating the print details for job. job_id: ".$this->job_id );            
            $this->updatePrintDetails();
        }
        else if($pTarget == "move_to_step"){
            LoggerPrime::info("Step moved for the job. job_id: ".$this->job_id );
            $move_to_step = UserData::create('move')->getInt(0);
            $this->MoveToStep($move_to_step);
        }
        else{
            $this->workflow_step = UserData::create('step')->getInt();
            $this->project_id = UserData::create('project_id')->getInt();
            $this->next_step = UserData::create('next')->getInt();
            $this->cancel_step = UserData::create('cancel')->getInt();
            $this->hold_step = UserData::create('hold')->getInt();

            // If on step 1, we should handle submission of the survey questions via formdata.

            if(!empty($this->workflow_step) && !empty($this->project_id) && !empty($this->user_id)){
                $this->processInitialStep();
            }
            else if(!empty($this->next_step) && !empty($this->job_id) && !empty($this->user_id)){
                $this->submitStep();
            }
            else if(!empty($this->cancel_step) && !empty($this->job_id) && !empty($this->user_id) && !empty($this->workflow_step)){
                $this->cancelStep();
            }
            else if (!empty($this->hold_step && !empty($this->job_id) && !empty($this->user_id))){
                $this->holdStep();
            }
            else if(!empty($this->job_id) && !empty($this->user_id)){
                $this->determineStepType();
            }
            else{
                LoggerPrime::info("Invalid parameters given for job info page. Job id:" . $this->job_id . " User id: ". $this->user_id);
                $error_messages[] = "Sorry, we cannot perform this operation.";
                Alerts::setErrorMessages($error_messages);
                header("Location: /?t=all_jobs");
            }
        }
    }

    /**
    * Accessing the initial step
    */
    public function processInitialStep(){
        $error_messages = array();
        $this->workflow_step = intval($this->workflow_step);
        switch ($this->workflow_step) {
            case 1:
                # if this is the first setup in the work flow
                $project_data = array();
                $project_name = '';
                if(isset($this->project_id) && !empty($this->project_id) && isset($this->user_id) && $this->user_id == $_SESSION['ident']['user_id']){
                    $aData = array("project_id"=>$this->project_id);
                    $pRow = $this->dc->getRowsById("projects", $aData);
                    // check if the cart is upto date
                    if(empty($pRow) ||  $this->user_id != $pRow[0]['user_id'] || !empty($pRow[0]['removed'])  || !empty($pRow[0]['submitted'])){
                        LoggerPrime::info("Project id not present or user does not have access to submit .User id:" . $this->user_id . "Project id:". $this->project_id );
                        $error_messages[] = "Sorry, we encounted a problem submitting your cart items, please try again";
                        break;
                    }
                    $project_name = $pRow[0]['project_name'];
                    $cData = array("user_id"=>$this->user_id, "removed"=>null, "submitted"=>null, "project_id" =>$pRow[0]['project_id']);
                    $cRow = $this->dc->getRowsById("cart", $cData);
                    foreach($cRow as $cart_data) {
                        // get the file row check if user has permissions for the file and also check if the file is deleted 
                        $fData = array("file_id"=> $cart_data['file_id']);
                        $fRow = $this->dc->getRowsById("files", $fData);
                        if(empty($fRow)  ||  $this->user_id != $fRow[0]['user_id']  || !empty($fRow[0]['deleted']) ){
                            LoggerPrime::info("File id not present or user does not have access to submit .User id:" . $this->user_id . "File id:". $fRow[0]['file_id'] );
                            $error_messages[] = "Sorry, we encounted a problem submitting your cart items, your file is expired.";
                            break;
                        }
                        $project_data[$cart_data['cart_id']]= $cart_data;
                    }
                }
                else{
                    LoggerPrime::info("Failed to send apparopriate cart ids .User id:" . $this->user_id );
                    $error_messages[] = "Sorry, we encounted a problem submitting your cart items, please try again";
                }
                if(empty($error_messages)){
                    $assessment_answers = $this->processAssessmentAnswers();

                    require_once(__DIR__."/initial_step.php");
                    $initial_step = new InitialStep($this->pTemplate, $this->project_id, $this->user_id, $project_data, $project_name, $assessment_answers);
                }else{
                    Alerts::setErrorMessages($error_messages);
                    header('Location: /?t=view_cart');
                }
                break;
            default:
                LoggerPrime::info("Failed to access the initail step in the workflow.User id:" . $this->user_id );
                $error_messages[] = "Sorry, we encounted a problem submitting your cart items, please try again";
                Alerts::setErrorMessages($error_messages);
                header('Location: /?t=view_cart');
                break;
        }
    }

    /**
    * Get the answers to assessment answers
    */
    public function processAssessmentAnswers() {
        $assessment_answers = array();

        if (isset($_POST['q1'])) {
            $q1 = $_POST['q1'];
            $assessment_answers['q1'] = $q1;
        }

        return $assessment_answers;
    }

    /**
    * Determines the next step in the workflow
    */
    public function determineStepType(){
        $error_messages = array();
        if($this->user_id == $_SESSION['ident']['user_id'] ){

            if(isset($this->jRow)  && ( ($this->user_id == $this->jRow['user_id']) || ($this->access_level === "STUDENT STAFF" || $this->access_level === "STAFF" || $this->access_level === "ADMIN") ) ){
                // In jobs table look for job completed step or cancelled step
                // if it is one of tose steps show the final step page 
                if($this->jRow['workflow_step_type_name'] == "Completed step" || $this->jRow['workflow_step_type_name'] == "Cancelled step" || $this->jRow['workflow_step_type_name'] == "Cancelled by user step"){
                    //set the final step
                    if($this->jRow['closed'] == null){
                        $jData = array("job_id"=>$this->jRow['job_id'], "job_updated"=>date('Y-m-d H:i:s') ,"closed"=>date('Y-m-d H:i:s') );
                        $update_job = $this->dc->updateStepsInJob($jData);
                    }
                    $next_row['workflow_step_type_name'] = "Final step";
                    $this->routeSteps($this->jRow, $next_row);
                }
                else{
                    $next_row = $this->getNextStep();
                    if(isset($next_row[0])){
                        $next_row = $next_row[0];
                        $this->routeSteps($this->jRow, $next_row);
                    }else{
                        $error_messages[] = "Sorry we are unable to process the next step";
                    }
                }
            }
            else{
                 $error_messages[] = "Sorry this job does not exist";
            }
        }else {
            LoggerPrime::info("User trying to illegally access job info page. Job id:" . $this->job_id . " User id: ". $this->user_id);
            $error_messages[] = "Sorry, we cannot perform this operation.";
        # --TODO return to jobs page with appropriate error message 
        }
        if(!empty($error_messages)){
             Alerts::setErrorMessages($error_messages);
             header('Location: /?t=all_jobs');
        }
    }

    /**
    * Cancelling a job
    */
    public function cancelStep(){

        //end hold (if exists)
        $open_holds = $this->dc->getJobOpenHold($this->job_id);
        if($open_holds){
            $this->dc->releaseJobHold($this->job_id);
        }

        $this->dc->removeJobCancellation($this->job_id);
        $cancelReason = UserData::create('cancelRadios')->getString();
        if(isset($cancelReason)){
            $moreInfo = UserData::create(self::MORE_INFO_ID . $cancelReason)->getString();
            if(isset($moreInfo)) {
                $this->dc->insertCancellation($this->job_id, $cancelReason, $moreInfo);
            }
            else{
                $this->dc->insertCancellation($this->job_id, $cancelReason, null);
            }
        }



        $error_messages = array();

        $cRow = $this->getStep($this->cancel_step);
        // get the details of the current step to check if user has permissions to cancel on the current step
        $currRow = $this->getStepDetails($this->workflow_step);
        if(isset($cRow[0]) &&  isset($currRow[0]) && ( ($this->jRow['user_id'] == $this->user_id  && $currRow[0]['allow_cancellation'] ==1 )  ||  $this->numeric_access_level >=$cRow[0]['manage_level'] )    ){
            $jData = array("job_id"=>$this->job_id, "curr_work_step_id"=>$this->cancel_step, "job_updated"=>date('Y-m-d H:i:s') ,"closed"=>date('Y-m-d H:i:s') );
            $this->dc->transactionStart();
            $update_job = $this->dc->updateStepsInJob($jData);
            $insert_cancel_step = $this->insertSpecialStep($this->jRow['data'], $this->cancel_step);
            if(!isset($insert_cancel_step) || !isset($update_job)){
                $error_messages[] = "Sorry, we do not recognize this step or this step may already be completed";
                $this->dc->transactionRollback();
            }
            else {    
                //Send message to staff when the job is cancelled
                $subject = "Announcement from 3dPrime regarding cancellation";
                $message_text = $this->helper->setJobCancellationMessage($cRow[0]['user_status'], $this->dc->getCancellationReasonText($cancelReason), $moreInfo);
                $successful_sent = $this->helper->sendMessage($this->jRow, $message_text, $this->admin_email, TRUE);
                #Check if there is an email associated with this step 
                # Step details in $cRow
                if($cRow[0]['email_confirmation'] == 1){
                    $subject = "Message from MakeCentral MakeAtState";
                    $message_text = $this->helper->setJobStepMessage($cRow[0]['user_status']);
                    $successful_sent = $this->helper->sendMessage($this->jRow, $message_text, null);
                    if($successful_sent){
                        $eData = array("user_id"=>$this->jRow["user_id"] , "recipients"=>$this->jRow['email'], "subject"=>$subject, "message"=>$message_text , "created"=>date('Y-m-d H:i:s') , "sent"=>date('Y-m-d H:i:s'),"sent_user_id"=>$this->user_id,"sent_by_admin"=>1);
                        $uData = array("job_id" => $this->jRow["job_id"], "job_step_id" =>$this->jRow['job_step_id'],"created" => date("Y-m-d H:i:s") , "public_view"=>1);
                        $successful_insert = $this->insertMessages($eData , $uData);
                        if( $successful_insert){
                            $this->dc->transactionCommit();
                        }
                        else{
                            LoggerPrime::info("Unable to cancel the job. Job id:" . $this->job_id . " User id: ". $this->user_id);
                            $error_messages[] = "Sorry, we cannot perform this operation 1.";
                            $this->dc->transactionRollback();    
                        }
                    }
                }
                else {
                    $this->dc->transactionCommit();
                }
            }
        }
        else{
            $error_messages[] = "Sorry, we do not not recognize this step or this step may already be completed";
        }
        if(!empty($error_messages)){
             Alerts::setErrorMessages($error_messages);
        }
        header('Location: /?t=workflow&uid='.$this->user_id.'&job_id='.$this->job_id);    
    }

    /**
     * Hold a job
     */
    public function holdStep(){
        $error_messages = array();
        if(isset($this->jRow)){
            $this->printer_name = $this->jRow['printer_name'];
        }
        // get the details of the current step to check if user has permissions to hold on the current step
        $currRow = $this->getStepDetails($this->workflow_step);
        if(isset($currRow[0]) && ( ($this->jRow['user_id'] == $this->user_id  && $currRow[0]['allow_cancellation'] ==1 )  ||  $this->numeric_access_level >=$currRow[0]['manage_level'] )    ){
            //check if job already has open hold
            $open_holds = $this->dc->getJobOpenHold($this->job_id);
            if($open_holds){
                $this->dc->releaseJobHold($this->job_id);
            }
            else {
                //insert hold into job_holds table
                $current_step_type = $this->jRow['curr_work_step_id'];

                $sMatches = array("job_id" => $this->job_id, "work_step_id" => $current_step_type);
                $stepRow = $this->dc->getStepRow($sMatches);
                $current_step = $stepRow[0]['job_step_id'];

                $aData = array($this->job_id, $current_step, date('Y-m-d H:i:s'), $this->user_id);
                $hold_inserted = $this->dc->insertjobHold($aData);

                if (!isset($hold_inserted)) {
                    $error_messages[] = "There was an error when attempting to complete this action";
                    $this->dc->transactionRollback();
                } else {
                    $this->dc->transactionCommit();
                }
            }

        }
        else{
            $error_messages[] = "There was an error when attempting to complete this action";
        }
        if(!empty($error_messages)){
            Alerts::setErrorMessages($error_messages);
        }
        header('Location: /?t=workflow&uid='.$this->user_id.'&job_id='.$this->job_id);
    }
    /**
    * Submitting the appropriate step
    */
    public function submitStep(){

        $open_holds = $this->dc->getJobOpenHold($this->job_id);
        if($open_holds){
            $this->dc->releaseJobHold($this->job_id);
        }
        $this->dc->removeJobCancellation($this->job_id);


        $error_messages = array();
        if($this->user_id == $_SESSION['ident']['user_id']){
            if(isset($this->jRow) ){
                $this->printer_name = $this->jRow['printer_name'];
                $this->current_step_order = $this->jRow['ordering'];
                $this->workflow_id = $this->jRow['workflow_id'];
                $this->current_step =  $this->jRow['curr_work_step_id'];
                # $this->next_step is the current submitted step
                $sRow = $this->getStepDetails($this->next_step);
                #Check if the user has permissions to submit the step
                if(isset($sRow[0]) &&( ($sRow[0]['manage_level']==0 && $this->jRow['user_id'] ==  $_SESSION['ident']['user_id']  &&  empty($_SESSION['ident']['blocked'])) || ( $this->numeric_access_level >=$sRow[0]['manage_level']) )  ){
                    $job_step_id = $this->jRow['job_step_id'];
                    $data = $this->jRow['data'];
                    $sMatches = array("job_id" => $this->job_id, "work_step_id"=>$this->next_step, "completed"=>null, 'reset'=>null );
                    $existing = $this->dc->getStepRow($sMatches);
                    # check if the stpe already exists and is not resetted 
                    if(( !empty($existing[0])  && $existing[0]['completed'] == null) || (!empty($existing[0])  && $existing[0]['reset'] !== null)){
                        $jData = array("job_id"=>$this->job_id, "curr_work_step_id"=>$this->next_step, "job_updated"=>date('Y-m-d H:i:s'));
                        $this->dc->transactionStart();
                        $update_job = $this->dc->updateStepsInJob($jData);
                        $complete_step = $this->completeStep($data, $existing[0]['job_step_id']);
                        if(!isset($complete_step) || !isset($update_job)){
                            $error_messages[] = "Sorry, we cannot perform this operation.";
                            $this->dc->transactionRollback();
                        }
                        else {
                            #Check if there is an email associated with this step 
                            # Step details in $sRow
                            if($sRow[0]['email_confirmation'] == 1){
                                $subject = "Message from MakeCentral MakeAtState";
                                $message_text = $this->helper->setJobStepMessage($sRow[0]['user_status']);
                                $successful_sent = $this->helper->sendMessage($this->jRow, $message_text, null, FALSE, $button_text = "View Job Information");
                                if($successful_sent){
                                    $eData = array("user_id"=>$this->jRow["user_id"] , "recipients"=>$this->jRow['email'], "subject"=>$subject, "message"=>$message_text , "created"=>date('Y-m-d H:i:s') , "sent"=>date('Y-m-d H:i:s'), "sent_user_id"=>$this->user_id,"sent_by_admin"=>1);
                                    $uData = array("job_id" => $this->jRow["job_id"], "job_step_id" =>$this->jRow['job_step_id'],"created" => date("Y-m-d H:i:s") , "public_view"=>1);
                                    $successful_insert = $this->insertMessages($eData , $uData);
                                    if( $successful_insert){
                                        $this->dc->transactionCommit();
                                    }
                                    else{
                                        $error_messages[] = "Sorry, we cannot perform this operation.";
                                        $this->dc->transactionRollback();    
                                    }
                                }
                            }
                            else{
                                $this->dc->transactionCommit();
                            }
                        }
                    }
                    else{
                        $error_messages[] = "Sorry, we do not recognize this step or this step may already be completed";
                    }
                }
                else{
                    $error_messages[] = "Sorry, we cannot perform this operation.";
                }
            }
            else{
                $error_messages[] = "Sorry, we cannot perform this operation.";
            }
        }else {
            $error_messages[] = "Sorry, we cannot perform this operation.";
        # --TODO return to jobs page with appropriate error message 
        }
        if(!empty($error_messages)){
             Alerts::setErrorMessages($error_messages);
        }
        header('Location: /?t=workflow&uid='.$this->user_id.'&job_id='.$this->job_id);
    }
    /**
    * Submitting the price step
    * @param array price_array: price array with price detials
    */
    public function submitPriceStep($price_array){


        $error_messages = array();
        if($this->user_id == $_SESSION['ident']['user_id']){

            if(isset($this->jRow) && ( $this->numeric_access_level >= $this->jRow['manage_level'] )){
                
                $job_step_id = $this->jRow['job_step_id'];
                $data = unserialize($this->jRow['data']);
                $data['price'] = $price_array;
                $group_code = isset($this->jRow['group_tag'][0])?strtoupper($this->jRow['group_tag'][0]):'';
                $data = serialize($data);
                $this->dc->transactionStart();
                // check if the incoming step is marked as reset step
                if(!empty($this->reset_step)) {
                    $rRow = $this->resetSteps();
                }
                $sMatches = array("job_id" => $this->job_id, "work_step_id"=>$this->next_step);
                $existing = $this->dc->getStepRow($sMatches);
                if( (!empty($existing[0])  && $existing[0]['completed'] == null) || (!empty($existing[0])  && $existing[0]['reset'] !== null)){
                    $jData = array("job_id"=>$this->job_id, "curr_work_step_id"=>$this->next_step, "job_updated"=>date('Y-m-d H:i:s'));
                    $update_job = $this->dc->updateStepsInJob($jData);
                    $complete_step = $this->completeStep($data, $existing[0]['job_step_id']);
                    if(!isset($complete_step) || !isset($update_job)){
                        $error_messages[] = "Sorry, we cannot perform this operation .";
                        $this->dc->transactionRollback();
                    }
                    else {
                        //$pRow = $this->getStepDetails($this->next_step);
                        //if($pRow[0]['email_confirmation'] == 1){
                        // send an email to the user when the price is set 
                        $subject = "Message from MakeCentral MakeAtState";
                        $message_text = $this->helper->setPriceMessage($price_array, $this->job_id, $group_code);
                        $successful_sent = $this->helper->sendMessage($this->jRow, $message_text, null, $send_to_admin = FALSE, $button_text = "View Job Information");
                        if($successful_sent){
                            $eData = array("user_id"=>$this->jRow["user_id"] , "recipients"=>$this->jRow['email'], "subject"=>$subject, "message"=>$message_text , "created"=>date('Y-m-d H:i:s') , "sent"=>date('Y-m-d H:i:s'), "sent_user_id"=>$this->user_id,"sent_by_admin"=>1);
                            $uData = array("job_id" => $this->jRow["job_id"], "job_step_id" =>$this->jRow['job_step_id'],"created" => date("Y-m-d H:i:s") , "public_view"=>1);
                            $successful_insert = $this->insertMessages($eData , $uData);
                            if( $successful_insert){
                                $this->dc->transactionCommit();
                            }
                            else{
                                $error_messages[] = "Sorry, we cannot perform this operation.";
                                $this->dc->transactionRollback();    
                            }
                        }else{
                            $this->dc->transactionCommit();
                        }
                    }
                }
                else{
                    $error_messages[] = "Sorry, we do not recognize this step or this step may already be completed";
                }
            }
            else{
                $error_messages[] = "Sorry, we cannot perform this operation.";
            }
        }else {
            $error_messages[] = "Sorry, we cannot perform this operation.";
        # --TODO return to jobs page with appropriate error message 
        }
        if(!empty($error_messages)){
             Alerts::setErrorMessages($error_messages);
        }
        header('Location: /?t=workflow&uid='.$this->user_id.'&job_id='.$this->job_id);
    }
    /**
    * Submitting the price step
    * @param array delivery_array: delivery array with delivery  detials
    */
    public function submitDeliveryDateStep($delivery_array){

        $open_holds = $this->dc->getJobOpenHold($this->job_id);
        if($open_holds){
            $this->dc->releaseJobHold($this->job_id);
        }

        $error_messages = array();
        if($this->user_id == $_SESSION['ident']['user_id']){
            if(isset($this->jRow) && ( $this->numeric_access_level >= $this->jRow['manage_level'] )){
                $job_step_id = $this->jRow['job_step_id'];
                $data = unserialize($this->jRow['data']);
                $data['estimated_delivery'] = $delivery_array['estimated_delivery'];
                $data = serialize($data);
                $sMatches = array("job_id" => $this->job_id, "work_step_id"=>$this->next_step, "completed"=>null, 'reset'=>null);
                $existing = $this->dc->getStepRow($sMatches);
                if( (!empty($existing[0])  && $existing[0]['completed'] == null)  ||  (!empty($existing[0])  && $existing[0]['reset'] !== null) ){
                    $jData = array("job_id"=>$this->job_id, "curr_work_step_id"=>$this->next_step, "job_updated"=>date('Y-m-d H:i:s'));
                    $this->dc->transactionStart();
                    $update_job = $this->dc->updateStepsInJob($jData);
                    $complete_step = $this->completeStep($data, $existing[0]['job_step_id']);
                    if(!isset($complete_step) || !isset($update_job)){
                        $error_messages[] = "Sorry, we cannot perform this operation .";
                        $this->dc->transactionRollback();
                    }
                    else {
                        $dRow = $this->getStepDetails($this->next_step);
                        if($dRow[0]['email_confirmation'] == 1){
                            // send an email to the user when the delivery date is set 
                            $subject = "Message from MakeCentral MakeAtState";
                            $message_text = $this->helper->setDeliveryDateMessage($delivery_array);
                            $successful_sent = $this->helper->sendMessage($this->jRow, $message_text, null);
                            if($successful_sent){
                                $eData = array("user_id"=>$this->jRow["user_id"] , "recipients"=>$this->jRow['email'], "subject"=>$subject, "message"=>$message_text , "created"=>date('Y-m-d H:i:s') , "sent"=>date('Y-m-d H:i:s'),"sent_user_id"=>$this->user_id,"sent_by_admin"=>1);
                                $uData = array("job_id" => $this->jRow["job_id"], "job_step_id" =>$this->jRow['job_step_id'],"created" => date("Y-m-d H:i:s") , "public_view"=>1);
                                $successful_insert = $this->insertMessages($eData , $uData);
                                if( $successful_insert){
                                    $this->dc->transactionCommit();
                                }
                                else{
                                    $error_messages[] = "Sorry, we cannot perform this operation.";
                                    $this->dc->transactionRollback();    
                                }
                            }
                            else{
                                $error_messages[] = "Sorry, we cannot perform this operation.";
                                $this->dc->transactionRollback();    
                            }
                        }else{
                            $this->dc->transactionCommit();
                        }
                    }
                }
                else{
                    $error_messages[] = "Sorry, we do not recognize this step or this step may already be completed";
                }
            }
            else{
                $error_messages[] = "Sorry, we cannot perform this operation.";
            }
        }else {
            $error_messages[] = "Sorry, we cannot perform this operation.";
        # --TODO return to jobs page with appropriate error message 
        }
        if(!empty($error_messages)){
             Alerts::setErrorMessages($error_messages);
        }
        header('Location: /?t=workflow&uid='.$this->user_id.'&job_id='.$this->job_id);
    }
    /**
    * Update print details. Only a privilaged user can update the print details
    */
    function updatePrintDetails(){
        $error_messages = array();
        $success_messages = array();
        if($this->user_id == $_SESSION['ident']['user_id']){
            if(isset($this->jRow)){
                $pValues = array();
                // get values from the post array to update
                foreach($_POST as $k=>$v){
                    $pValues[$k] = is_array($v) ? $v[0] : $v;
                }
                $aData = array("job_id" => $this->jRow["job_id"], "file_id" => $pValues['file_id']);
                $jfRow = $this->dc->getRowsById("job_files", $aData);
                $data = unserialize($jfRow["data"]);
                $data['Type'] = $pValues['workflow'];
                $data['Printer'] = $pValues['printer'];
                $data['Material'] = $pValues['material'];
                $data['Color'] = $pValues['color'];
                $data['Copies'] = (intval($pValues['copies']) > 100) ?100 :intval($pValues['copies']);
                $data['Dimensions'] = $pValues['dimensions'];
                $data['Notes'] = $pValues['notes'];
                $data['Delivery Option'] = $pValues['delivery'];
                $data['Shipping Address'] = $pValues['ship-field-info'];
                $data = serialize($data);
                $sConditionsCol = array("job_id", "file_id");
                $sData = array("job_id" => $this->job_id, "file_id" => $pValues['file_id'], "data" => $data);
                $this->dc->transactionStart();
                $uRow = $this->dc->updateUsingConditions("job_files", $sConditionsCol, $sData);
                if(empty($uRow)){
                    LoggerPrime::info("Unable to update print details. job_id:" .$this->job_id);
                    $error_messages[] = "Sorry, we cannot perform this operation. 0";
                } else {
                    $success_messages[] = "Successfully updated print details"; 
                    $this->dc->transactionCommit();
                }
            } else{
                $error_messages[] = "Sorry, we cannot perform this operation. 1";
                LoggerPrime::info("Illegal update of print details. Job not found . job_id:" .$this->job_id);
            }
        } else{
            $error_messages[] = "Sorry, we cannot perform this operation. 2";
            LoggerPrime::info("Illegal update of print details. user_id:" .$this->user_id);
        }
        if(!empty($error_messages)){
            $this->dc->transactionRollback();
            Alerts::setErrorMessages($error_messages);
        }else {
            Alerts::setSuccessMessages($success_messages);
        }
        header('Location: /?t=workflow&uid='.$this->user_id.'&job_id='.$this->job_id);
    }
    /**
    * Updates the job workflow to the step selected by the admin
    * @param Int $move_to_step : work_step_id of the step to be moved to
    */
    public function MoveToStep($move_to_step){
        $open_holds = $this->dc->getJobOpenHold($this->job_id);
        if($open_holds){
            $this->dc->releaseJobHold($this->job_id);
        }
        $error_messages = array();
        $success_messages = array();
        if($this->user_id == $_SESSION['ident']['user_id']){
            if(isset($this->jRow) ){

                $print_data = unserialize($this->jRow["data"]);

                # $this->next_step is the current submitted step
                $sRow = $this->getMoveStepDetails($move_to_step);

                #Check if the user has permissions to submit the step
                if(isset($sRow[0]) && empty($_SESSION['ident']['blocked'])  &&  $this->numeric_access_level >=$sRow[0]['manage_level'] && $this->numeric_access_level > 0  ){

                    $job_step_id = $this->jRow['job_step_id'];
                    $data = $this->jRow['data'];
                    $sMatches = array("job_id" => $this->job_id, "work_step_id"=>$move_to_step,"reset"=>null);
                    $existing = $this->dc->getStepRow($sMatches);
                    $this->reset_step = $move_to_step;
                    $rRow = array();
                    $this->dc->transactionStart();
                    //check if the step exists and is completed 
                    if( !empty($existing[0]) ){
                        // if yes then reset step
                        $rRow = $this->resetSteps();
                    }
                    else{
                        // reset previous steps
                        $rRow = $this->resetSteps(False);
                    }
                    if(!empty($rRow)){
                        // update jobs table with previous step
                        $jData = array("job_id"=>$this->job_id, "curr_work_step_id"=>$move_to_step, "job_updated"=>date('Y-m-d H:i:s') , "closed"=>null);
                        //insert move to step as current step in jobs table
                        $update_job = $this->dc->updateStepsInJob($jData);
                        //insert move to step details as current step details in job_steps table
                        $insert_step = $this->insertNextStep($data, $move_to_step);
                        // Complete the move_to_step
                        $complete = $this->completeStep($data, $insert_step);
                        // update move to step as the current step in the workflkow
                        if( !empty($update_job) && !empty($insert_step) && !empty($complete)){
                            $this->dc->transactionCommit();
                            $success_messages[] = "Successfully updated workflow step";
                        }
                        else{
                            LoggerPrime::info("Unable to update current step and insert next step . job_id:" .$this->job_id);
                            $error_messages[] = "Sorry, we cannot perform this operation. Unable to update current step and insert next step";
                        }
                    }
                    else{
                        LoggerPrime::info("Unable to reset step . job_id:" .$this->job_id);
                        $error_messages[] = "Sorry, we cannot perform this operation. Unable to reset step";
                    }
                }
                else{
                    LoggerPrime::info("Step may not exist or the user does not have access to make the move. job_id:" .$this->job_id);
                    $error_messages[] = "Sorry, we cannot perform this operation. Step may not exist or the user does not have access to make the move";
                }
            }
            else{
                LoggerPrime::info("Job does not exist  thus unable to move between steps. job_id:" .$this->job_id);
                $error_messages[] = "Sorry, we cannot perform this operation. The job does not exist";
            }
        }else {
            $error_messages[] = "Sorry, we cannot perform this operation.";
        # --TODO return to jobs page with appropriate error message 
        }
        if(!empty($error_messages)){
            Alerts::setErrorMessages($error_messages);
            $this->dc->transactionRollback();    
        }else if(!empty($success_messages)){
            Alerts::setSuccessMessages($error_messages);
        }

        header('Location: /?t=workflow&uid='.$this->user_id.'&job_id='.$this->job_id);
    }
    /**
    * Routes the step to appropriate classes
    * @param array job_details : array of job details
    * @param array step_details: array of next or previous step details
    */
    public function routeSteps($job_details, $step_details){
        $step = $step_details['workflow_step_type_name'];

        if($step == "Price step"){
            require_once(__DIR__.'/price_step.php');
            $general_step = new PriceStep($this->pTemplate, $job_details, $step_details);
        }
        else if($step == "Delivery date step"){
            require_once(__DIR__.'/delivery_date_step.php');
            $delivery_date = new DeliveryDateStep($this->pTemplate, $job_details, $step_details);
        }
        else if($step == "Completed step"){
            require_once(__DIR__.'/job_complete_step.php');
            $job_complete_step = new JobCompleteStep($this->pTemplate, $job_details, $step_details);
        }
        else if($step == "Final step"){
            require_once(__DIR__.'/final_step.php');
            $job_complete_step = new FinalStep($this->pTemplate, $job_details, $step_details);
        }
        else{
            require_once(__DIR__.'/general_step.php');
            $general_step = new GeneralStep($this->pTemplate, $job_details, $step_details);
	}
    }
}

