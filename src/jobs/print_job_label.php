<?php

class PrintJobLabel
{
    static protected $user;


    private $template;
    private $access_level;
    private $job_id;
    private $user_id;
    private $dc;
    private $job_row;
    private $label;
    private $first_name;
    private $last_name;
    private$job_files;
    private $file_name;

    private $helper;
    private $created;

    public function __construct(Templater &$templ)
    {
        $this->template=$templ;
        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();
        $this->job_id = UserData::create('job_id')->getInt();
        $this->label = UserData::create('label')->getString();
        $this->label = ($this->label == 'user') ? 'user' : 'files';
        $aData = array("job_id" => $this->job_id);
        $this->job_row = $this->dc->getActiveJobs($aData)[0];

        $this->setUser();
        $this->setAccessLevel();
        //$this->stepAccess();
        if( $this->access_level === "STUDENT STAFF" || $this->access_level === "STAFF" || $this->access_level === "ADMIN"){
            $this->user_id = $_SESSION['ident']['user_id'];
        }
        else if($this->job_row['user_id'] == $_SESSION['ident']['user_id']){
            $this->user_id = $this->job_row['user_id'];
        } else {
            $error_messages[] = "Sorry, you cannot perform this operation";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=all_jobs');
        }



        $this->prepareJobInfo();
        $this->renderPrintTemplate();

    }

    private function renderPrintTemplate(){

        $this->template->setTemplate('print_job_label.html');
        $this->template->setVariables('page_title', "Print label");
        $this->template->setVariables('job_id', $this->job_id);
        $this->template->setVariables('project_name', $this->project_name);
        $this->template->setVariables('label', $this->label);
        $this->template->setVariables('first_name', $this->first_name);
        $this->template->setVariables('last_name', $this->last_name);
        $this->template->setVariables('job_files', $this->job_files);
        $this->template->setVariables('created', $this->created);

        $this->template->generate();
    }

    private function prepareJobInfo(){
        if($this->user_id == $_SESSION['ident']['user_id'] && isset($this->job_row)) {

            $this->created = $this->helper->convertToPrettyDate($this->job_row['created']);
            //project name
            $this->project_name = $this->job_row['project_name'];
            // user details
            $this->first_name = $this->job_row['fullname'];
            $this->last_name = $this->job_row['lastname'];
            //project files
            $aData = array("job_id"=>$this->job_id, "removed"=>NULL);
            $job_files = $this->dc->getRowsById("job_files", $aData);
            foreach($job_files as $job_file) {
                $this->job_files[$job_file['file_id']] = $job_file;
            }
        }
        else{
            header('Location: /?t=all_jobs');
        }
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
}
