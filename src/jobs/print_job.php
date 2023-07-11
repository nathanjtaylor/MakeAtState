<?php

class PrintJob
{
    static protected $user;


    private $template;
    private $access_level;
    private $job_id;
    private $user_id;
    private $dc;
    private $job_row;
    private$job_files;
    private $price_details;
    private $short_details;
    private $long_details;
    private $user_details;
    private $file_name;

    private $helper;
    private $created;

    public function __construct(Templater &$templ)
    {
        $this->template=$templ;
        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();
        $this->job_id = UserData::create('job_id')->getInt();
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

        $this->template->setTemplate('print_job.html');
        $this->template->setVariables('page_title', "Print");
        $this->template->setVariables('job_id', $this->job_id);
        $this->template->setVariables('project_name', $this->project_name);
        $this->template->setVariables('short_details', $this->short_details);
        $this->template->setVariables('long_details', $this->long_details);
        $this->template->setVariables('user_details', $this->user_details);
        $this->template->setVariables('job_files', $this->job_files);
        $this->template->setVariables('price_details', $this->price_details);
        $this->template->setVariables('created', $this->created);


        $this->template->generate();
    }

    private function prepareJobInfo(){
        if($this->user_id == $_SESSION['ident']['user_id'] && isset($this->job_row)) {
            $data = unserialize($this->job_row['data']);
            if(isset($data['price']) && !empty($data['price'])) {
                $this->price_details = $data['price'];
            }

            $this->created = $this->helper->convertToPrettyDate($this->job_row['created']);

            //project name
            $this->project_name = $this->job_row['project_name'];
            
            //project files
            $aData = array("job_id"=>$this->job_id, "removed"=>NULL);
            $job_files = $this->dc->getRowsById("job_files", $aData);
            foreach($job_files as $job_file) {
                $job_file['print_details'] = unserialize($job_file['data']);
                $this->job_files[$job_file['file_id']] = $job_file;
                $viewable = $this->helper->fileViewingStatusOnBrowser($job_file['file_name']);
                $this->job_files[$job_file['file_id']]['viewable'] = $viewable;



                //short details
                $short_keys = array("Type", "Printer", "Material", "Color", "Copies", "Delivery Option");
                foreach($short_keys as $key){
                    if(isset($job_file['print_details'][$key]) && $job_file['print_details'][$key]){
                        $this->short_details[$job_file['file_id']][$key] = $job_file['print_details'][$key];
                    }
                }
                //long details
                $long_keys = array("Shipping Address", "Notes");
                foreach($long_keys as $key){
                    if(isset($job_file['print_details'][$key]) && $job_file['print_details'][$key]){
                        $this->long_details[$job_file['file_id']][$key] = $job_file['print_details'][$key];
                    }
                }

            }
            //user details
            $user_keys = array("email", "phone");
            $this->user_details = array();
            $this->user_details['Name'] = $this->job_row['fullname']. ' '. $this->job_row['lastname'];
            $this->user_details['Phone Number'] = $this->job_row['phone_num'];
            $this->user_details['Email Address'] = $this->job_row['email'];
            $this->user_details['Affiliation'] = $print_details['Affiliation'];
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
