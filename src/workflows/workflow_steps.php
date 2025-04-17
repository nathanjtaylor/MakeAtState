<?php
class WorkFlowSteps {
    protected $job_id;
    protected $user_id;
    protected $current_step;
    protected $reset_step;
    protected $current_step_order;
    protected $workflow_name;
    protected $workflow_step_list;
    protected $printer_name;
    protected $project_name;
    protected $workflow_id;
    protected $dc;
    protected $access_level;
    protected $numeric_access_level;
    protected $helper;
    protected $edit_print_details = array();
    protected $group_id;
    protected $admin_email;
    protected $admin_edit_price = array();
    static protected $user;
    static protected $nav_array;

    protected $job_details;
    protected $step_details;
    protected $job_notes;
    protected $job_show_notes;
    protected $wTemplate;
    protected $cancellation_reasons;

    protected $send_updates = array();
    protected $print_details = array();
    protected $status_details = array();
    protected $price_details = array();
    protected $action_details = array();
    protected $job_files = array();


    protected $user_details = array();

    protected $delivery_date_details = array();

    protected $staff_held = false;

    protected $user_held = false;


    /**
     * WorkFlowSteps constructor.
     * @param Templater $templ: Templater object for step
     * @param $job_details: array of job details
     * @param $step_details: array of next or previous step details
     */
    public function __construct(Templater &$template, $job_details, $step_details){
        $this->wTemplate = $template;
        $this->job_details =  $job_details;
        $this->step_details = $step_details;
        $this->current_step_order = $job_details['ordering'];
        $this->job_id = $job_details['job_id'];
        $this->user_id =$job_details['user_id'];
        $this->project_name =$job_details['project_name'];
        $this->job_notes = array();
        $this->job_show_notes = False;
        $this->helper = new PrimeHelper();
        $this->dc = new DataCalls();
        $this->setAccessLevel();
        if($this->access_level != 'BASIC') {
            $this->staff_held = boolval($this->dc->getJobOpenHold($this->job_id));
            $this->cancellation_reasons = $this->dc->getCancellationReasons(1);
        }
        else{
            $this->user_held = boolval($this->dc->getJobOpenHold($this->job_id));
            $this->cancellation_reasons = $this->dc->getCancellationReasons(0);
        }

        $this->setAdminEmail();
        $this->setUser();
        $this->setNavigation();
        $this->setAccessLevel();
        $this->stepAccess();
        $this->prepareForDisplay();
        $this->renderJobInfoTemplate();
    }

    /**
     * Render job info template
     */
    protected function renderJobInfoTemplate(){
        $this->wTemplate->setTemplate("job_info.html");
        $this->wTemplate->setVariables('page_title', "Job Information");
        $this->wTemplate->setVariables('error_messages' , Alerts::getErrorMessages());
        $this->wTemplate->setVariables('success_messages' , Alerts::getSuccessMessages());
        $this->wTemplate->setVariables('nav_array', self::$nav_array);
        $this->wTemplate->setVariables('project_name', $this->project_name);
        $this->wTemplate->setVariables('job_notes', $this->job_notes);
        $this->wTemplate->setVariables('job_show_notes', $this->job_show_notes);
        $this->wTemplate->setVariables('delivery_date_details', $this->delivery_date_details);
        $this->wTemplate->setVariables('print_details', $this->print_details);
        $this->wTemplate->setVariables('edit_print_details', $this->edit_print_details);
        $this->wTemplate->setVariables('send_updates', $this->send_updates);
        $this->wTemplate->setVariables('job_files', $this->job_files);
        $this->wTemplate->setVariables('price_details', $this->price_details);
        $this->wTemplate->setVariables('admin_edit_price', $this->admin_edit_price);
        $this->wTemplate->setVariables('user_details', $this->user_details);
        $this->wTemplate->setVariables("status_details" , $this->status_details);
        $this->wTemplate->setVariables("action_details" , $this->action_details);
        $this->wTemplate->setVariables("workflow_step_list" , $this->workflow_step_list);
        $this->wTemplate->setVariables('staff_held', $this->staff_held);
        $this->wTemplate->setVariables('user_held', $this->user_held);
        $this->wTemplate->setVariables('group_id', $this->group_id);
        $this->wTemplate->setVariables('cancellation_reasons', $this->cancellation_reasons);
        $this->wTemplate->setVariables('questions', $this->getQuestions());

        $this->wTemplate->generate();

    }

    /**
    * gets the next step for the job
    */
    protected function getNextStep(){
        $aData = array("step_removed"=>null);
        $order = array("ordering");
        $wRow = $this->dc->getWorkflowStep($aData,$this->current_step_order, $skip=0, $limit = 1 , $order, $next=True);
        return $wRow;
    }
    /**
    * gets the previous step for the job
    */
    protected function getPreviousStep(){
        $aData = array("step_removed"=>null);
        $order = array("ordering");
        $wRow = $this->dc->getWorkflowStep($aData,$this->current_step_order, $skip=0, $limit = 1 , $order, $next=False);
        return $wRow;
    }
    /**
    * gets the cancel step for admin
    */
    protected function getAdminCancelStep(){
        $aData = array("workflow_step_type_name" => "Cancelled step", "step_removed"=>null);
        $order = array("ordering");
        $wRow = $this->dc->getWorkflowStep($aData,null, $skip=0, $limit = 1 , $order, $next=True);
        return $wRow;
    }
    /**
     * gets the Hold step for admin
     */
    protected function getAdminHoldStep(){
        $aData = array("workflow_step_type_name" => "Hold step", "step_removed"=>null);
        $order = array("ordering");
        $wRow = $this->dc->getWorkflowStep($aData,null, $skip=0, $limit = 1 , $order, $next=True);
        return $wRow;
    }
    /**
    * gets the  cancel step for the user
    */
    protected function getUserCancelStep(){
        $aData = array("workflow_step_type_name" => "Cancelled by user step", "step_removed"=>null);
        $order = array("ordering");
        $wRow = $this->dc->getWorkflowStep($aData, null, $skip=0, $limit = 1 , $order, $next=True);
        return $wRow;
    }


    /**
    * gets the  price step for the workflow
    */
    protected function getPriceStep(){
        $aData = array("workflow_step_type_name" => "Price step", "step_removed"=>null);
        $order = array("ordering");
        $wRow = $this->dc->getWorkflowStep($aData,null, $skip=0, $limit = 1 , $order, $next=True);
        return $wRow;
    }

    /**
    * gets the next step for the job
    * @param int work_step_id : work step id of the  step
    */
    protected function getStep($work_step_id){

        $aData = array("step_removed"=>null, "work_step_id"=>$work_step_id);
        $order = array("ordering");
        $wRow = $this->dc->getWorkflowStep($aData, null, $skip=0, $limit = 1 , $order, $next=True);
        return $wRow;
    }

    /**
    * gets the next step for the job
    * @param int work_step_id : work step id of the  step
    */
    protected function getStepDetails($work_step_id){

        $aData = array("step_removed"=>null);
        $order = array("ordering");
        APP::printVar($this->current_step_order);
        APP::printVar($work_step_id);
        $wRow = $this->dc->getWorkflowStep($aData, $this->current_step_order, $skip=0, $limit = 1 , $order, $next=True);
        return $wRow;
    }

    /**
     * gets the next step for the job
     * @param int work_step_id : work step id of the  step
     */
    protected function getMoveStepDetails($work_step_id){

        $aData = array("step_removed"=>null);
        $order = array("ordering");

        $wRow = $this->dc->getWorkflowStep($aData, null, $skip=0, $limit = 1 , $order, $next=True);
        return $wRow;
    }

    /**
    * Complete current step
    * @param string $data : String of serialized data
    * @param int $job_step_id : job step id of the job_steps table
    */
    protected function completeStep($data, $job_step_id){
        $aData = array('job_step_id'=>$job_step_id, 'data'=>$data, 'completed'=>date('Y-m-d H:i:s'), 'completed_user_id'=>$this->user_id, 'reset'=>null);
        $completed = $this->dc->markStepAsCompleted($aData);
        return $completed;
    }
    /**
    * Complete current step
    * @param string $data : String of serialized data
    * @param int $next_step_id : job step id of the job_steps table
    */
    protected function insertNextStep($data, $next_step_id){
        $inserted = False;
        $sMatches = array("job_id" => $this->job_id, "work_step_id"=>$next_step_id, "reset"=>null, "completed"=>null);
        $existing = $this->dc->getStepRow($sMatches);
        if(empty($existing[0])){
            $aData = array($this->job_id, $next_step_id, $data, $this->user_id);
            $inserted = $this->dc->insertNextStep($aData);
        }
        return $inserted;
    }
    /**
    * Complete current step
    * @param string $data : String of serialized data
    * @param int $cancel_step_id : job step id of the job_steps table
    */
    protected function insertSpecialStep($data, $special_step_id){
        $inserted = False;
        $sMatches = array("job_id" => $this->job_id, "work_step_id"=>$special_step_id , "reset"=>null);
        $existing = $this->dc->getStepRow($sMatches);

        $aData = array($this->job_id, $special_step_id, $data, $this->user_id, null);
        $inserted = $this->dc->insertSpecialStep($aData);


        return $inserted;
    }


    /*Function to insert messages into email and job_updates table
    * @param array $eData : Data to be inserted into email table
    * @param array $uData: Data to be inserted into job_updates table
    */
    protected function insertMessages($eData, $uData){
        $eRow = $this->dc->insertEmails($eData);
        if(!empty($eRow)){
            $uData['email_id'] = $eRow;
            $uRow = $this->dc->insertJobUpdates($uData);
            if(!empty($uRow)){
                return True;
            }
        }
        return False;
    }
    /**
    * Resets all steps following the  reset_step  id of the workflow , all steps following the work step id for the job will be reset
    i..e marked as reset
    *  @param array $next : Bool value to determine the which steps to reset: if next is set to true all steps following the reset step will be reset , if set to false all steps leading to the reset step will be reset
    */
    protected function resetSteps($next=True){
        $reset = FALSE;
        $aData = array("step_removed"=>null);
        $order = array("ordering");
        // getting the row of the reset step from workflow_steps table
        $sMatches =  array("work_step_id"=> $this->reset_step);
        $reset_step_details = $this->dc->getWorkflowStepRow($sMatches);
        $this->current_step_order = $reset_step_details[0]['ordering'];
        if(isset($reset_step_details[0]['ordering'])){
            // get all the steps following the right steps
            $wRow = $this->dc->getWorkflowStep($aData,$this->current_step_order, $skip=0 , $limit = null, $order, $next=$next);
            $mSteps = [$this->reset_step];
            // add all step ids into the array following the reset step
            foreach ($wRow as $steps){
                $mSteps[] = $steps['work_step_id'];
            }
            $reset = $this->dc->resetJobSteps($mSteps, $this->job_id);
        }
        return $reset;
    }
    /**
    *  Resets workflow , if the workflow or printer is changed after the job is submitted
    *  @param Array $job_data : Array of values to be inserted into the jobs and job steps table
    *  @param string $printer_name : Name of the printer that the workflow needs to be set to
    *  @param string $workflow_type : Type of the workflow  that the workflow needs to be set to
    *  @param Int $job_id : job_id of the job
    */ 
    public function resetWorkflow($job_data, $printer_name, $workflow_type, $job_id){
        $inserted = FALSE;
        #get the workflow_id from the workflow table
        $workflowRow =  $this->dc->getWorkflowByType($workflow_type);
        $workflow_id = $workflowRow[0]['workflow_id'];
        # get the step id from workflow_steps table
        $wData= array("printer_name"=>$printer_name, "workflow_id"=>$workflow_id,"step_removed"=>null );
        // get the first step in the workflow 
        $wRow = $this->dc->getRowsById('workflow_steps', $wData, $skip=0, $limit=1, $order=array('ordering'), $order_dir = "ASC");

        // reset all existing steps if the workflow is changed
        $reset = $this->dc->resetJobSteps($mSteps = null, $this->job_id);
        // update step id in job row 
        $jData = array("job_id"=>$job_id, "curr_work_step_id"=>$wRow[0]['work_step_id'], "job_updated"=>date('Y-m-d H:i:s') , "closed"=>null);
        //insert move to step as current step in jobs table
        $update_job = $this->dc->updateStepsInJob($jData);
        if(!empty($wRow) && !empty(reset) && !empty($update_job)){
            $sData = array("job_id"=>$job_id, "work_step_id"=> $wRow[0]['work_step_id'], "completed_user_id"=> $job_data['user_id'], "data"=>$job_data['data']);
            $sRow = $this->dc->insertJobSteps($sData);
            $inserted = empty($sRow) ? FALSE : TRUE;
        }
        return $inserted;
    }
    /**
    * Get all workflow steps for this printer
    */
    protected function getWorkflowList(){
        $wRow =  $this->dc->getAllWorkflowSteps();
        foreach ($wRow as $k=>$steps){
            $this->workflow_step_list[$k]["name"] = $steps["name"];
            $this->workflow_step_list[$k]["work_step_id"] = $steps["work_step_id"];
        }
    }
    /**
    * Add job updates for a jobi       
    */
    protected function setJobUpdates(){
        return true;
    }
    /**
    * Marks the current step as completed
    */
    public function markCurrentStep(){
        return true;
    }
    /**
    * Sets the user 
    */
    protected function setUser(){
        //lazy loading  user
        if(self::$user == null){
            self::$user = AuthenticatedUser::getUser();
        }
    }
    /**
    * Get access level for the user 
    */
    protected function setAccessLevel(){
        $this->access_level = AuthenticatedUser::getUserPermissions();
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
    * Get numeric access level of the user to compare it to the step access level
    */
    protected function stepAccess(){
         $this->numeric_access_level = AuthenticatedUser::getNumericUserPermissions();
    }
    /**
    *Prepare workflows array
    */
    protected function getPrimeWorkflows(){
        $wRows = $this->dc->getWorkflows();
        $workflows = array();
        foreach ($wRows as $key=>$wf){
            #Add workflow only if they are printers
            if(!empty(unserialize($wf["data"]))){
                $workflows[$wf["name"]]['data'] = unserialize($wf["data"]); 
                $workflows[$wf["name"]]['workflow_id'] = $wf["workflow_id"]; 
                $workflows[$wf["name"]]['allowed_ext_data'] = $wf["allowed_ext_data"]; 
            }
        }
        return  $workflows;
    }    

    /**
    * Function to unserialize the data array and prepare it for display
    */
    protected function getWorkflowDetails(){
        $workflows = $this->getPrimeWorkflows();
        foreach($this->job_files as $file_id=>$file) {
            $file_ext_array = explode('.',  $file["file_name"]);
            $ext = end($file_ext_array);
            $file_ext = strtolower($ext);
            $allowed_workflows = array();
            $types =  array();
            foreach ($workflows as $type=>$value) {
                $allowed_exts = unserialize($value['allowed_ext_data']);
                if (in_array($file_ext, $allowed_exts)) {
                    $types[] = $type;
                    $allowed_workflows[$type] = $value;
                }
            }
            $this->edit_print_details[$file_id]['types'] = $types;
            $print_details = unserialize($file['data']);
            $this->getWorkflowPrinters($file_id, $allowed_workflows, $print_details['Type']);
            $this->getMaterials($file_id, $allowed_workflows, $print_details['Type'], $print_details['Printer']);
            $this->getColors($file_id, $allowed_workflows, $print_details['Type'], $print_details['Printer'], $print_details['Material']);
        }
    }

    /** 
    *Function to get the printers based on the type of workflow
    * @param string: $file_id: File id of the file
    * @param array: $workflows : array for workflows 
    * @param string: $type : type of workflow
    */
    protected function getWorkflowPrinters($file_id, $workflows, $type){
        $printers = array();
        foreach($workflows[$type]['data'] as $key => $values){
            # Check if the printer has workflow steps associated to it , if not , do not show the printer in the dropdown
            $workflowStepRow  = $this->dc->getAllWorkflowSteps();
            // Check if general step , price step , job completed step , cancelled step and user cancelled step are available for the workflow 
            //if not do not add it to the dropdown
            //if not do not add it to the dropdown
            $workflowStepTypes = $this->dc->getAllStepTypes();
            $warnings_array = $this->helper->determineReadinessOfPrinterWorkflow($workflowStepRow, $workflowStepTypes);
            if(empty($warnings_array)){
                $printers[] = $key;
            }
        }
        $this->edit_print_details[$file_id]['printers'] = $printers;
    }

    /**
    * Function to get materials based on the type and printer
    * @param string: $file_id: File id of the file
    * @param array: $workflows : array for workflows 
    * @param string $type : type of the workflow selected
    * @param string $printer : printer selected for the type
    */
    protected function getMaterials($file_id, $workflows, $type, $printer){
        #TODO: refactor this function so printer is not required
        #TODO: printer can be deleted or renamed
        $materials = array();
        if(isset($workflows[$type]['data'][$printer])) {
            foreach ($workflows[$type]['data'][$printer] as $key => $values) {
                foreach ($values as $k => $material) {
                    if ($k == "Material") {
                        if (!in_array($material, $materials)) {
                            $materials[] = $material;
                        }
                    }
                }
            }
        }
        $this->edit_print_details[$file_id]['materials'] = $materials;
    }

    /**
    * Function to get colors based on the type and printer
    * @param string: $file_id: File id of the file
    * @param array: $workflows : array for workflows 
    * @param string $type : type of the workflow selected
    * @param string $printer : printer selected for the type
    * @param string $material : material set for the selected printer
    */
    protected function getColors($file_id, $workflows, $type, $printer, $material){
        #TODO: refactor this function so printer is not required
        #TODO: printer can be deleted or renamed

        $colors = array();
        $color_for_selected_material = FALSE;
        if(isset($workflows[$type]['data'][$printer])) {
            foreach ($workflows[$type]['data'][$printer] as $key => $values) {
                foreach ($values as $k => $v) {
                    if ($k == "Material" && $v == $material) {
                        $color_for_selected_material = TRUE;
                    }
                    if (($k == "Color") && (!in_array($v, $colors)) && $color_for_selected_material) {
                        $colors[] = $v;
                        $color_for_selected_material = FALSE;
                    }
                }
            }
        }
        $this->edit_print_details[$file_id]['colors'] = $colors;
    }

    /**
     * function to be overwritten in child classes
     */
    protected function additionalWorkForDisplay(){}

    /**
     * Prepare for display on the job details page
     */
    private function prepareForDisplay(){
        $error_messages =  array();
        // check if the privileged user(student staff, staff , admin) has high enough access level to view
        if(!empty( $this->job_details) && ( $this->numeric_access_level !=0) &&  ( $this->numeric_access_level >= $this->step_details['manage_level'] )   ){
            $this->job_notes = $this->helper->prepareJobNotes($this->dc, $this->job_id);
            $this->job_show_notes = True;
            $this->viewUserDetails();
            $this->viewEditJobDetails();
            $this->setJobStatus();

            $this->additionalWorkForDisplay();

            if(isset($this->job_details['data']) && !empty($this->job_details['data'])){
                $this->adminEditPriceDetails();
                $this->setJobPrice();
            }

            if(isset($this->job_details['data']) && !empty($this->job_details['data'])){
                $this->setDeliveryDate();
            }
            // if the step is a user action step i..e manage_level =0  and the privileged user(student staff, staff , admin) is also the user who submitted the job
            if(($this->step_details['manage_level'] == 0) && (self::$user['user_id'] == $this->job_details['user_id']) ){
                $this->setUserActions();
            }else{
                $this->setActions();
            }
            $this->setWorkflowList();
        }
        // this catches all public users who own the job or other users with elevated privileges
        else if(!empty( $this->job_details) &&( (self::$user['user_id'] === $this->job_details['user_id'])  || $this->numeric_access_level > 0)   ){
            # --TODO
            $this->viewJobDetails();
            $this->setUserJobStatus();
            if(isset($this->job_details['data']) && !empty($this->job_details['data'])){
                $this->setJobPrice();
            }
            if(isset($this->job_details['data']) && !empty($this->job_details['data'])){
                $this->setDeliveryDate();
            }
            $this->setUserActions();

        }else {
            $error_messages[] = "Sorry, we cannot perform this operation.";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=all_jobs');

        }
    }

    /**
     * View User Details: Sets the user details for the administrators
     */
    private function viewUserDetails(){
        $this->user_details['user_id'] = $this->job_details['user_id'];
        $this->user_details['email'] = $this->job_details['email'];
        $this->user_details['fullname'] = $this->job_details['fullname'];
        $this->user_details['lastname'] = $this->job_details['lastname'];
        $this->user_details['phone_num'] = $this->helper->formatPhoneNumber($this->job_details['phone_num']);
    }
    /**
     * View job details
     */
    private function viewJobDetails(){
        $details = $this->job_details;
        # set the print details for the job
        $aData = array("job_id"=>$this->job_id);
        $job_files = $this->dc->getRowsById("job_files", $aData);
        $this->setJobFiles($job_files);  
    }


    /**
     * View and edit job details, privileged users only
     */
    private function viewEditJobDetails(){
        $details = $this->job_details;
        # set the print details for the job
        $aData = array("job_id"=>$this->job_id, "removed"=>NULL);
        $job_files = $this->dc->getRowsById("job_files", $aData);
        $this->setJobFiles($job_files);  
        // gets all the workflow details like printers materials colors for editing print options
        $this->getWorkflowDetails();
    }

    /**
     * Prepare Job files for display
     * @param array $job_files: All files associated with a job
     */
     private function setJobFiles($job_files){
        foreach($job_files as $job_file) {
            $job_file['print_details'] = unserialize($job_file['data']);
            $this->job_files[$job_file['file_id']] = $job_file;
            $viewable = $this->helper->fileViewingStatusOnBrowser($job_file['file_name']);
            $this->job_files[$job_file['file_id']]['viewable'] = $viewable;

        }
     }

    /**
     * Set the status array for the the job
     **/
    private function setJobStatus(){
        if(isset($this->job_details['file_count']) && isset ($this->job_details['admin_status'])){
            # Set the status messages for display for each step
            $this->status_details['file_count'] = $this->job_details['file_count'];
            $this->status_details['short_message'] = $this->job_details['admin_status'];
            $this->status_details['formatted_date'] = $this->helper->convertDateForDisplay($this->job_details['job_updated']);
            $this->status_details['print_label'] = true;
        }
    }

    /**
     * Edit price after it's being set. Privileged users only
     */
    private function adminEditPriceDetails(){
        #gets the details for the price step
        $price_step = $this->getPriceStep();
        #set num of copies into edit price array for display purposes
        $data = unserialize($this->job_details['data']);
        foreach($this->job_files as $file_id=>$file) {
            // set price constants for the printer 	
            $this->admin_edit_price['files'][$file_id]['price_constants'] = $this->getPrinterPriceConstants($file['print_details']);
        }

        // set the  price step id for edit _price section
        $this->admin_edit_price['next'] = $price_step[0]['work_step_id'];
        $this->admin_edit_price['user_id'] = $this->job_details['user_id'];
        $this->admin_edit_price['job_id'] = $this->job_details['job_id'];
        $this->admin_edit_price['button_name'] = $this->step_details['name'];
    }

    /**
    * Get price constants for the printer, material and color.
    *
    *@param array $print_details: Array containing print details for the job
    */
    protected function getPrinterPriceConstants($print_details){
        $price_constants = array();
        $workflows = $this->getPrimeWorkflows();
        $types =  array();
        foreach($workflows as $type=>$workflow_data){
            if($type == $print_details['Type']) {
                foreach ($workflow_data['data'] as $printer=>$printer_data){
                    foreach($printer_data as $printer_options){
                        if ($printer_options['Material'] == $print_details['Material'] && $printer_options['Color'] == $print_details['Color'] && isset($printer_options['price_options'])) {
                            $price_constants = $printer_options['price_options'];
                        }
                    }
                }
            }
        }
        return $price_constants; 
    }


    /**
     * Set the price details  for the the job
     **/
    private function setJobPrice(){
        $data = unserialize($this->job_details['data']);
        if(array_key_exists('price', $data)) {
            $this->price_details = $data['price'];
        }
    }

    /**
     * Set the delivery date   for the the job
     **/
    private function setDeliveryDate(){
        $data = unserialize($this->job_details['data']);
        if(array_key_exists('estimated_delivery', $data)) {
            $this->delivery_date_details = $this->helper->convertToPrettyDate($data['estimated_delivery']);
        }
    }

    /**
     * Set the actions for more actions block
     **/
    private function setActions(){
        $this->action_details['uid'] = $this->job_details['user_id'];
        $this->action_details['job_id'] = $this->job_details['job_id'];
        $this->send_updates['job_id'] =  $this->job_details['job_id'];
        $this->action_details['job_step_id'] = $this->job_details['job_step_id'];
        $this->action_details['group_code'] = isset($this->job_details['group_tag'][0])?strtoupper($this->job_details['group_tag'][0]):'';
        //set the next step only if the user has permission to view the next step
        if(!empty($this->numeric_access_level) && $this->step_details['manage_level'] !=0 && $this->numeric_access_level >= $this->step_details['manage_level']){
            $this->action_details['name'] = $this->step_details['name'];
            $this->action_details['next'] = $this->step_details['work_step_id'];
        }



        $cancel_step = $this->getAdminCancelStep();
        $user_cancel = $this->getUserCancelStep();

        if(isset($this->step_details['work_step_id']) && isset($cancel_step) && !empty($cancel_step[0]) && $cancel_step[0]['work_step_id'] != $this->step_details['work_step_id'] && $user_cancel[0]['work_step_id'] != $this->step_details['work_step_id']){
            $this->action_details['current_step'] = $this->step_details['work_step_id'];
            $this->action_details['cancel'] = $cancel_step[0]['work_step_id'];
            $this->action_details['hold'] = 1;


        }
    }
    /**
     * Set the actions for more actions block
     **/
    private function setUserActions(){
        $this->action_details['uid'] = $this->job_details['user_id'];
        $this->action_details['job_id'] = $this->job_details['job_id'];
        $this->action_details['job_step_id'] = $this->job_details['job_step_id'];
        $this->action_details['group_code'] = isset($this->job_details['group_tag'][0])?strtoupper($this->job_details['group_tag'][0]):'';
        //set the next step only if the user has permission to view the next step
        if(($this->step_details['manage_level'] == 0) && (self::$user['user_id'] == $this->job_details['user_id']) ){
            $this->action_details['name'] = $this->step_details['name'];
            $this->action_details['next'] = $this->step_details['work_step_id'];
        }
        $admin_cancel = $this->getAdminCancelStep();

        $cancel_step = $this->getUserCancelStep();

        if(isset($this->step_details['work_step_id']) && isset($cancel_step) && !empty($cancel_step[0]) && $this->step_details['allow_cancellation'] ==1 && self::$user['user_id'] == $this->job_details['user_id'] && $cancel_step[0]['work_step_id'] != $this->step_details['work_step_id'] && $admin_cancel[0]['work_step_id'] != $this->step_details['work_step_id'] ){
            $this->action_details['current_step'] = $this->step_details['work_step_id'];
            $this->action_details['cancel'] = $cancel_step[0]['work_step_id'];
        }

    }
    /**
     * Sets the list of all the steps in the workflow for the printer
     */
    private function setWorkflowList(){
        $this->getWorkflowList();
    }

    /**
     * Set the status array for the the job
     **/
    private function setUserJobStatus(){
        if(isset($this->job_details['user_status']) && isset ($this->job_details['file_count'])){
            # Set the status messages for display for each step
            $this->status_details['file_count'] = $this->job_details['file_count'];
            $this->status_details['short_message'] = $this->job_details['user_status'];
            $this->status_details['formatted_date'] = $this->helper->convertDateForDisplay($this->job_details['job_updated']);
            $this->status_details['print_label'] = false;
        }

    }

    protected function setAdminEmail()
    {
        if(isset($this->workflow_id)){
            $workflowRow =  $this->dc->getWorkflowById($this->workflow_id);
            $this->group_id = $workflowRow['group_id'];
            $gaRow = $this->dc->getGroupAdminEmail($this->group_id);
            $this->admin_email = $gaRow['admin_email'];
        }
    }

    /**
    *Function to get assessment questions
    */
    private function getQuestions(){
        $questions = $this->dc->getAssessmentQuestions();
        $answers = $this->dc->getAssessmentAnswersByProjectId($this->job_id);

        $answer_text_lookup = [];
        foreach ($answers as $answer) {
            $answer_text_lookup[$answer['question_id']] = $answer['answer_text'];
        }
        $question_answer = [];
        foreach ($questions as $question) {
            if (isset($answer_text_lookup[$question["question_id"]])){
                $question["answer_text"] = $answer_text_lookup[$question["question_id"]];
                $question_answer[] = $question;
            }
        }
        return $question_answer;
    }
}

