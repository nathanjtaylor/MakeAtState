<?php
class UserJobs
{
    private $jTemplate;
    private $dc;
    private $view_all_jobs;
    private $numeric_access_level ;
    private $aJobs =array();
    private $helper;
    private $jUserid ; //user if for the jobs to view
    private $filter_by_printer = NULL; #set if the the user selects the printer from the filter options
    # sort options for the jobs page. Key names in the array are the column names that needs to be passed to the query
    private $sort_options = array("j.created"=>"Job created", "j.job_updated"=>"Job updated", "w.ordering"=>"Badge");
    # sort direction for the
    private $sort_dir_options = array("ASC"=>"Ascending", "DESC"=>"Desending");
    # default sort array to send to the db
    private $sort_array = array("sort_field"=>"j.created", "sort_dir"=>"ASC");
    private $printers = array();
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
        $this->jUserid = UserData::create('juid')->getInt();
        # set if the the user selects the printer from the filter options
        $sort_feild = UserData::create('sort')->getString('');
        $sort_dir = UserData::create('dir')->getString('');
        # Logic to assign values in sort_array
        if (!empty($sort_feild) && array_key_exists($sort_feild, $this->sort_options) && !empty($sort_dir) && array_key_exists($sort_dir, $this->sort_dir_options)) {
            $this->sort_array['sort_field'] = $sort_feild;
            $this->sort_array['sort_dir'] = $sort_dir;
        }
        $this->numeric_access_level = $this->checkUserPermissions();
        $this->setNavigation();
        if ($this->numeric_access_level > 0 && !empty($this->jUserid)) {
            $this->getAllActiveJobs($this->jUserid);
            $this->prepareForDisplay();
            $this->renderJobsTemplate();
        } else {
            header('Location: /?t=home');
        }
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
        $this->jTemplate->setVariables('juid', $this->jUserid);
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
        return AuthenticatedUser::getNumericUserPermissions();
    }
    /**
    * Get all  jobs
    * @param int user_id : User id of the user for to view their jobs
    */
    public function getAllActiveJobs($user_id = null)
    {
        $aData = array("user_id"=>$user_id);
        $jPagination =     $this->helper->getPaginationValues($this->jPage);
        # prepares the data for the side bar , adds the printer and count information
        
        $jRows = $this->dc->getActiveJobs($aData, $jPagination['skip'], $jPagination['limit'], $this->filter_by_printer, $this->sort_array);
        $jTotal = $this->dc->getTotalRows();
        if (!empty($jRows)) {
            $this->aJobs = $jRows;
            if (!empty($jTotal)) {
                $jPagination['total'] = $jTotal;
                $jPagination['target'] = "/?t=user_jobs&juid=".$user_id."&page=";
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
            $short_message = "";
            if ($view_all_jobs) {
                $this->aJobs[$key]['short_message'] = $job['admin_status'];
            } else {
                $this->aJobs[$key]['short_message'] = $job['user_status'];
            }
            $this->aJobs[$key]['formatted_date'] = $this->helper->convertDateForDisplay($this->aJobs[$key]['job_updated']);
            $this->aJobs[$key]['created_formatted_date'] = $this->helper->convertToPrettyDate($this->aJobs[$key]['created']);
            $data = unserialize($job['data']);
            if (isset($data['price']['total_price'])) {
                $this->aJobs[$key]['price'] = $data['price']['total_price'];
            }
            if (isset($data['estimated_delivery'])) {
                $this->aJobs[$key]['estimated_delivery'] = $this->helper->convertToPrettyDate($data['estimated_delivery']);
            }
        }
    }
}
