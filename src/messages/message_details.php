<?php

#CLass to view more details fo a message

class MessageDetails{

    private $user_id;
    private $job_id;

    private $dTemaplte;
    private $dc;
    private $helper;
    private $access_level;

    private $print_details = array();
    private $messages = array();
    private $message_actions = array();
    private $is_admin_view = False;
    private $action_details = array();
    static private $user;
    static private $nav_array;

    /**
    * Constructor function for Message Details
    * @param Templater dTempl : Template object
    */
    public function __construct(Templater &$dTempl){
        $this->dTemplate = $dTempl;
        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();
        $this->access_level = $this->getUserPermissions();
        $this->user_id =  UserData::create('user_id')->getInt();
        $this->job_id = UserData::create('job_id')->getInt();
        $this->action_details['job_id'] = $this->job_id;
        $this->setUser();
        $this->setNavigation();
        $this->prepareDisplay();
    }
    /**
    * Render job info template
    */
    public function renderMessageDetailsTemplate(){

        $this->dTemplate->setTemplate('message_details.html');
        $this->dTemplate->setVariables('page_title', "Message Details");
        $this->dTemplate->setVariables('error_messages' , Alerts::getErrorMessages());
        $this->dTemplate->setVariables('success_messages' , Alerts::getSuccessMessages());
        $this->dTemplate->setVariables('nav_array', self::$nav_array);    
        # Set all the messages for the job in the template
        $this->dTemplate->setVariables('print_details', $this->print_details);
        $this->dTemplate->setVariables("messages" , $this->messages);
        $this->dTemplate->setVariables("message_actions" , $this->message_actions);
        $this->dTemplate->setVariables("is_admin_view" , $this->is_admin_view);
        $this->dTemplate->setVariables("action_details" , $this->action_details);
        if(isset($_SESSION['previous_page'])){
            $this->dTemplate->setVariables("previous_page_number" , $_SESSION['previous_page']); 
        }
        $this->dTemplate->generate();
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
    * Sets the navigation for the page
    */

    protected function setNavigation(){

        if(self::$nav_array == null){
            self::$nav_array = AuthenticatedUser::getUserNavBar();
        }
    }
    /**
    * Gets the numeric access level for the user 
    */
    protected function getUserPermissions(){
        return AuthenticatedUser::getNumericUserPermissions();
    }


    /*
    * Prepare for message details display
    */

    public function prepareDisplay(){
        $error_messages = array();

        if(!empty($this->job_id)){
            $jData = array('job_id'=>$this->job_id);
            $jStepData = array('job_id'=>$this->job_id, "completed"=>null, "reset"=>null);
            $jRow = $this->dc->getRowsById('jobs', $jData);
            $order = array("job_step_id");
            $jStepRow = $this->dc->getRowsById('job_steps', $jStepData, $skip = 0, $limit = 1, $order = $order , $order_dir="DESC" );
            if(!empty($jRow[0]) && (($jRow[0]['user_id'] == $this->user_id || $this->access_level > 0)) ){
                $this->setMessagePostions($jRow[0]['user_id']);
                $eRow = $this->dc->getJobMessageDetails($this->job_id);            
                if(!empty($eRow)){
                    foreach ($eRow as $k=>$row) {
                        $this->setJobDetails($row);
                        $this->setMessageActions($row, $jStepRow[0]);
                        // mark message as read
                        if(empty($row['message_read'])){
                            $this->dc->markEmailsAsRead($row['email_id']);
                        }
                        $this->messages[$k]= $this->setMessageDetails($row);

                    }
                }else{
                    // get work step details 
                    $wRow = $this->dc->getJobStepUpdates($this->job_id);
                    if(!empty($wRow[0])){
                        $this->setJobDetails($wRow[0]);
                        $this->setMessageActions($wRow[0]);
                    }
                }
            }else{
                LoggerPrime::info("Insufficient access to access job messages for job with job_id: ". $this->job_id. "  user_id: " . $this->user_id );
                $error_messages[] = "Sorry, we cannot perform this operation.";
            }
        }else{
                LoggerPrime::info("Accessing messages for non existing job with job_id: ". $this->job_id);
                $error_messages[] = "Sorry, we cannot perform this operation.";
        }
        if(!empty($error_messages)){
             Alerts::setErrorMessages($error_messages);
             header('Location: /?t=messages');
        }
        else {
            $this->renderMessageDetailsTemplate();
        }

    }


    /**
    * Set message Actions 
    * @param array row : job messages array
    * @param array steprow :current step details of the job
    */
    public function setMessageActions($row, $steprow=null){
        $this->message_actions['job_id'] = $row['job_id'];
        $this->message_actions['user_id'] = $this->user_id;
        if(isset($steprow)){
            $this->message_actions['job_step_id'] = $steprow['job_step_id'];
        }else{
            $this->message_actions['job_step_id'] = $row['job_step_id'];
        }
        $this->message_actions['is_staff'] = ($this->access_level > 0) ? True : False;


    }
    /**
    * Set message detials 
    * @param array row : job messages array 
    */
    public function setMessageDetails($row){
        $message_details = array();
        $message_details['sent'] = $this->helper->convertToPrettyDateAndTime($row['sent']);
        // if the email is sent by an admin don't show the name 
        if(empty($row['sent_by_admin'])){
            $message_details['fullname'] = $row['fullname'] ;
            $message_details['lastname'] = $row['lastname'];
            $message_details['sent_by_admin'] = False ;
        }else{
            $message_details['fullname'] = '';
            $message_details['sent_by_admin'] = True ;
        }
        $attachments = $this->prepareAttachments($row['email_id']);
        if(!empty($attachments)){
            foreach ($attachments as $k=>$attachment){
                $message_details['attachments'][$k]['file_id'] = $attachment['file_id'];
                $message_details['attachments'][$k]['file_name'] = $attachment['file_name'];
                $message_details['attachments'][$k]['file_size'] = $this->helper->convertFileSizes($attachment['file_size']);
            }
        }
        $message_details['subject'] = $row['subject'];
        $message_details['message'] = $row['message'];

        return $message_details;
    }
    /**
    * Set job detials 
    * @param array row : job messages array 
    */
    public function setJobDetails($row){
        # set the print detils for the job
        $this->print_details = unserialize($row["data"]);
        if(isset($this->print_details['price'])){
            unset($this->print_details['price']);
        }
    }

    /**
    * Sets flag that determines the postion of the messages 
    * @parami Int job_user_id: user id for the job corresponding to the messages
    */
    public function setMessagePostions($job_user_id){
        if(($job_user_id !== $this->user_id && $this->access_level>0)){
            $this->is_admin_view = True;
        }
    }

    /**
    * Asoociate attachments with emails
    * @param int $email_id :  email id for the email to get all attachments
    */
    public function prepareAttachments($email_id){
        $attachments = $this->dc->getEmailAttachments($email_id);
        return $attachments;
    }
}



