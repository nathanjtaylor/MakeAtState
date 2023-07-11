<?php
class ViewJobs
{
    private $jTemplate;
    private $dc;
    private $view_all_jobs;
    private $access_level = "BASIC";
    private $current_tab = 1; # 1- for all jobs 2 for my jobs
    private $current_type =1; #1 for new jobs 2 held jobs 3 for open jobs 4 for closed jobs
    private $filter_by_printer = ''; #set if the the user selects the printer from the filter options
    private $filter_by_group = '';
    # sort options for the jobs page. Key names in the array are the column names that needs to be passed to the query
    private $sort_options = array("j.created"=>"Job created", "j.job_updated"=>"Job updated", "w.ordering"=>"Badge");
    # sort direction for the
    private $sort_dir_options = array("ASC"=>"Ascending", "DESC"=>"Descending");
    # default sort array to send to the db
    private $sort_array = array("sort_field"=>"j.created", "sort_dir"=>"ASC");
    private $aJobs =array();
    private $printers = array();
    private $groups = array();
    private $helper;
    #Variables for pagination
    private $jPage;
    private $pagination_array = array();
    private static $user;
    private static $nav_array;
    /**
    * Constructor functon for viewing jobs
    * @param Template $jTempl : Templater object for viewing jobs
    */
    public function __construct(Templater &$jTempl)
    {
        $this->jTemplate = $jTempl;
        $this->dc = new DataCalls();
        #Helper class object where all the helper functions reside
        $this->helper = new PrimeHelper();
        $this->setUser();
        $this->jPage = UserData::create('page')->getInt(-1);
        $pTarget = UserData::create('t')->getString('my_jobs');
        $pType = UserData::create('type')->getString('n');
        # set if the the user selects the printer from the filter options
        $this->setFiltering();
        $this->setSort();

        $this->access_level = $this->checkUserPermissions();
        # Logic to go to a job from the search box 
        if ($pTarget  == "go_to_job") {
            $job_id =  UserData::create('go_to_job_id')->getInt(0);
            $this->goToJob($job_id);
        }

        # Always set the target to "my_jobs" if the access level is baseic
        if ($this->access_level == "BASIC") {
            $pTarget = "my_jobs";
        }
        # Set the view all jobs flags so the staff or admin user gets the option to see all active jobs
        else {
            $this->view_all_jobs = 1;
        }
        $this->current_tab = ($pTarget=="all_jobs") ? 1 : 2;
        (!empty($statusType))? $this->status_type =1 : $this->status_type =2;

        if ($pTarget == "all_jobs") {
            if (isset($pType) && $pType == "c") {
                $this->getAllActiveJobs($pTarget, $pType, null, $closed=true);
            } else {
                $this->getAllActiveJobs($pTarget, $pType, null, $closed=false);
            }
            $this->prepareForDisplay(true);
        } else {
            if (isset($pType) && $pType == "c") {
                $this->getAllActiveJobs($pTarget, $pType, self::$user['user_id'], $closed=true);
            } else {
                if($pType != 'o' && $pType != 'h'){
                    $pType = 'n';
                }
                $this->getAllActiveJobs($pTarget, $pType, self::$user['user_id'], $closed=false);
            }

            $this->prepareForDisplay();
        }
        $this->setNavigation();
        $this->renderJobsTemplate();
    }

    /**
    * Render jobjs template
    */
    public function renderJobsTemplate()
    {

        $this->jTemplate->setTemplate('view_jobs.html');
        $this->jTemplate->setVariables('page_title', "Jobs");
        $this->jTemplate->setVariables('error_messages', Alerts::getErrorMessages());
        $this->jTemplate->setVariables('nav_array', self::$nav_array);
        # Set all the jobs for the template
        $this->jTemplate->setVariables('jobs', $this->aJobs);
        $this->jTemplate->setVariables("printers", $this->printers);
        $this->jTemplate->setVariables("groups", $this->groups);
        $this->jTemplate->setVariables("view_all_jobs", $this->view_all_jobs);
        $this->jTemplate->setVariables("current_tab", $this->current_tab);
        $this->jTemplate->setVariables("current_type", $this->current_type);
        $this->jTemplate->setVariables("filter_by_group", $this->filter_by_group);
        $this->jTemplate->setVariables("filter_by_printer", $this->filter_by_printer);
        $this->jTemplate->setVariables("sort_options", $this->sort_options);
        $this->jTemplate->setVariables("sort_dir_options", $this->sort_dir_options);
        $this->jTemplate->setVariables("sort_array", $this->sort_array);
        $this->jTemplate->setVariables("pagination_array", $this->pagination_array);
        $this->jTemplate->generate();
    }
    /**
    * Sets the user
    */
    private function setUser()
    {
        //lazy loading  user
        if (self::$user == null) {
            self::$user = AuthenticatedUser::getUser();
        }
    }
    /**
    * Sets the navigation for the page
    */
    private function setNavigation()
    {
        if (self::$nav_array == null) {
            self::$nav_array = AuthenticatedUser::getUserNavBar();
        }
    }

    /**
    * Checks if the user is a basic user or a student staff, staff , admin
    * Student staff, staff and admins can view all jobs
    **/
    private function checkUserPermissions()
    {
        return AuthenticatedUser::getUserPermissions();
    }
    /**
    * Get all  jobs
    * @param string pTarget :  Traget of the page ,either "all_jobs" or "my_jobs"
    * @param string pType : Type of jobs to be displayed "c" for closed "o" for open "h" for held
    * @param int user_id : User id of the user for to view their jobs
    * @param bool $closed : flag that determines if closed jobs should be displayed
    */
    public function getAllActiveJobs($pTarget, $pType, $user_id = null, $closed = false)
    {

        // logic to check if the user wants open and new jobs
        if ($closed == false) {
            // get all the initial steps in a workflow
            $iData = $this->dc->getInitialSteps();
            $initial_steps = array();
            foreach ($iData as $k=>$row) {
                $initial_steps[] = $row['work_step_id'];
            }
            if (!empty($initial_steps)) {
                // if the new jobs tab is selected only get the jobs that are on the initial workflow steps
                if ($pType == 'n') {
                    $this->current_type =1;
                    $initial_steps = 'IN ('. implode(',', $initial_steps) . ')';
                }
                elseif  ($pType == 'h') {
                    $this->current_type =2;
                    $initial_steps = 'NOT NULL';
                }
                else {
                    $this->current_type =3;
                    $initial_steps = 'NOT IN ('. implode(',', $initial_steps) . ')';
                }
                $aData = (empty($user_id))  ?  array("closed"=> null, "curr_work_step_id" =>  $initial_steps ) :  array("user_id"=>$user_id, "closed"=> null ,"curr_work_step_id" =>  $initial_steps);
            } else {
                $this->current_type = ($pType == 'o')?3:1;
                $aData = (empty($user_id)) ? array("closed"=> null ) :  array("user_id"=>$user_id, "closed"=> null);
            }
        } else {
            $this->current_type =4;
            $aData= (empty($user_id)) ? array("closed"=> "NOT NULL") :   array("user_id"=>$user_id, "closed"=> "NOT NULL")  ;
        }

        $include_holds = 0;
        if ($pType == 'h'){
            $include_holds = 2;
        }
        else {
            $include_holds = (empty($user_id)) ? 1 : 0;
        }

        $this->groups = $this->dc->getGroups();

        $group_exists = false;
        foreach($this->groups as $group){
            if($group['group_id'] == $this->filter_by_group){
                $group_exists = true;
            }
        }
        if(!$group_exists){
            $this->filter_by_group = '';
        }
        # prepares the data for the side bar , adds the printer and count information
        $this->prepareSidebarForDisplay($aData, $include_holds);


        # check if the printer is a valid printer
        $jPagination =   $this->helper->getPaginationValues($this->jPage);
        $jRows = $this->dc->getActiveJobs($aData, $jPagination['skip'], $jPagination['limit'], $this->filter_by_printer, $this->sort_array, $include_holds, $this->filter_by_group);


        $jTotal = $this->dc->getTotalRows();
        if (!empty($jRows)) {
            $this->aJobs = $jRows;
            if (!empty($jTotal)) {
                $jPagination['total'] = $jTotal;
                // check if any of the filter options are selected
                if (!empty($this->filter_by_printer)) {
                    $jPagination['target'] = "/?t=".$pTarget."&type=".$pType."&printer=".$this->filter_by_printer. "&sort=".$this->sort_array['sort_field']."&dir=".$this->sort_array['sort_dir']."&page=";
                } else if(isset($this->sort_array['sort_dir']) && !empty($this->sort_array['sort_dir'])) { 
                    $jPagination['target'] = "/?t=".$pTarget."&type=".$pType."&sort=".$this->sort_array['sort_field']."&dir=".$this->sort_array['sort_dir']."&page=";
                } else {
                    $jPagination['target'] = "/?t=".$pTarget."&type=".$pType."&page=";
                }
                $this->pagination_array = $this->helper->preparePaginationArray($jPagination);
            }
        }
    }
    /**
    * Prepares the job array fordisplay
    * @param bool $view_all_jobs : set to true if the user has access to view all jobs
    */
    public function prepareForDisplay($view_all_jobs = false)
    {
        foreach ($this->aJobs as $key=>$job) {
            if ($view_all_jobs) {
                $this->aJobs[$key]['short_message'] = $job['admin_status'];
            } else {
                $this->aJobs[$key]['short_message'] = $job['user_status'];
            }
            $this->aJobs[$key]['formatted_date'] = $this->helper->convertToPrettyDate($this->aJobs[$key]['job_updated']);
            $this->aJobs[$key]['created_formatted_date'] = $this->helper->convertToPrettyDate($this->aJobs[$key]['created']);
            $data = unserialize($job['data']);
            # Adding this check as come of the old jobs have grand_total set
            if (isset($data['price']['grand_total'])) {
                $this->aJobs[$key]['price'] = $data['price']['grand_total'];
            }

            if (isset($data['estimated_delivery'])) {
                $this->aJobs[$key]['estimated_delivery'] = $this->helper->convertToPrettyDate($data['estimated_delivery']);
            }
        }
    }

    /**
    * Prepares side bar for display
    * Gets all the available printers
    * @param array $aData : Data to send to the db based on param set in the url
    */
    public function prepareSidebarForDisplay($aData, $hold)
    {
        if($this->filter_by_group){
            $all_workflows = $this->dc->getGroupWorkflows($this->filter_by_group);
        }
        else{
            $all_workflows = $this->dc->getWorkflows();
        }
        $all_printers = $this->helper->getPrintersFromWorkflow($all_workflows);
        foreach ($all_printers as $printer) {
            //$count = $this->dc->getCountsForJobsonPrinters($aData, $printer['printer_name'], null, null, $hold);
            $this->printers[$printer] = 1;
        }

    }

    /**
    * Redirects the privilaged user to a job detials page
    * @param int job_id: Job id for the job
    */
	public function goToJob($job_id) {
        # TODO - Error messages not showing 
        $error_messages = array();
        if ($this->access_level == "BASIC") {
            $error_messages[] = "Sorry the selected operation cannot be completed";
            LoggerPrime::info("User trying to illegally access job info page. Job id:" . $job_id . " User id: ". $this->user_id);
        }
        else {
            if (!empty($job_id)) {
                $job_row = $this->dc->getJobRow(array("job_id" => $job_id));
                if(isset($job_row[0]) && isset($job_row[0]['user_id'])){
                    header('Location: /?t=workflow&uid='.$job_row[0]['user_id'].'&job_id='.$job_id, true);
                }
                else{
                    $error_messages[] = "Requested job could not be found";
                }

            }
            else {
                $error_messages[] = "Requested job could not be found";

            }
        }
        if (!empty($error_messages)) {
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=all_jobs', true);
            exit();
        }

	}

    private function setFiltering()
    {

        if(isset($_SESSION['filter_printer'])){
            $this->filter_by_printer = $_SESSION['filter_printer'];
        }
        if(UserData::create('printer')->getString('')){
            $this->filter_by_printer = UserData::create('printer')->getString('');
        }
        if($this->filter_by_printer){
            $_SESSION['filter_printer'] = $this->filter_by_printer;
        }

        if(isset($_SESSION['filter_group'])){
            $this->filter_by_group = $_SESSION['filter_group'];
        }
        if(UserData::create('group')->getString('')){
            $this->filter_by_group = UserData::create('group')->getString('');
        }
        if(isset($this->filter_by_group)){
            $_SESSION['filter_group'] = $this->filter_by_group;
        }
    }

    private function setSort()
    {
        $sort_field = UserData::create('sort')->getString('');
        if(!$sort_field && isset($_SESSION['sort_field'])){
            $sort_field = $_SESSION['sort_field'];
        }
        if($sort_field){
            $_SESSION['sort_field'] = $sort_field;
        }

        $sort_dir = UserData::create('dir')->getString('');
        if(!$sort_dir && isset($_SESSION['sort_dir'])){
            $sort_dir = $_SESSION['sort_dir'];
        }
        if($sort_dir){
            $_SESSION['sort_dir'] = $sort_dir;
        }

        # Logic to assign values in sort_array
        if (!empty($sort_field) && array_key_exists($sort_field, $this->sort_options) && !empty($sort_dir) && array_key_exists($sort_dir, $this->sort_dir_options)) {
            $this->sort_array['sort_field'] = $sort_field;
            $this->sort_array['sort_dir'] = $sort_dir;
        }

    }
}
