<?php
#Class to view all job updates
class JobUpdates
{
    private $job_id;
    private $user_id;
    private $access_level;
    private $job_messages = array();
    private $job_step_updates = array();
    private $job_notes = array();
    private $isPrivilegedUser = false;
    private $cancellation= array();
    private $dc;
    private $uTemplate;
    private $helper;
    private static $user;
    private static $nav_array;

    /**
    * Constructor function  job updates
    * @param Templater $uTempl: Templater object
    */
    public function __construct(Templater &$uTempl)
    {
        $this->uTemplate = $uTempl;
        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();
        $this->job_id = UserData::create('job_id')->getInt();
        $this->setUser();
        $this->setNavigation();
        $this->access_level= $this->getPermissions();
        $this->prepareJobUpdates();
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
    protected function setNavigation()
    {
        if (self::$nav_array == null) {
            self::$nav_array = AuthenticatedUser::getUserNavBar();
        }
    }
    /**
    * Checks if the user is a basic user or a student staff, staff , admin
    * Student staff, staff and admins can view all jobs
    **/
    private function getPermissions()
    {
        return AuthenticatedUser::getUserPermissions();
    }
    /**
    * Render job updates template
    */
    public function renderJobUpdatesTemplate()
    {
        $this->uTemplate->setTemplate('job_updates.html');
        $this->uTemplate->setVariables('page_title', "Jobs updates");
        $this->uTemplate->setVariables('error_messages', Alerts::getErrorMessages());
        $this->uTemplate->setVariables('nav_array', self::$nav_array);
        # Set aul the jobs for the template
        $this->uTemplate->setVariables("job_id", $this->job_id);
        $this->uTemplate->setVariables("user_id", $this->user_id);
        $this->uTemplate->setVariables("job_messages", $this->job_messages);
        $this->uTemplate->setVariables("job_step_updates", $this->job_step_updates);
        $this->uTemplate->setVariables("job_notes", $this->job_notes);
        $this->uTemplate->setVariables("isPrivilegedUser", $this->isPrivilegedUser);
        $this->uTemplate->setVariables("cancellation", $this->cancellation);
        $this->uTemplate->generate();
    }
    /**
    * prepare for job updates display
    */
    public function preparejobUpdates()
    {
        $error_message = array();
        $sMatches = array('job_id'=>$this->job_id);
        $jRow = $this->dc->getJobMessages($this->job_id);
        $iRow = $this->dc->getJobRow($sMatches);
        $uRow = $this->dc->getJobStepUpdates($this->job_id);
        $hRow = $this->dc->getJobHolds($this->job_id);


        if (isset($jRow[0]) || isset($uRow[0])) {
            $this->user_id = $_SESSION['ident']['user_id'];
            if ($this->access_level == "STUDENT STAFF" || $this->access_level == "STAFF" || $this->access_level == "ADMIN") {
                $this->job_messages = $this->prepareMessages($jRow);
                $this->job_step_updates = $this->prepareStepUpdates($uRow, $hRow, true);
                $this->job_notes = $this->helper->prepareJobNotes($this->dc, $this->job_id);
                $this->isPrivilegedUser = true;

                //$admin_cancel = $this->getAdminCancelStep();
                //$cancel_step = $this->getUserCancelStep();


                //if($cancel_step[0]['work_step_id'] != $this->step_details['work_step_id'])

                $cancellationRow = $this->dc->getJobCancellation($this->job_id);

                if(isset($cancellationRow)){
                    $this->cancellation['reason']=$cancellationRow['reason'];
                    $this->cancellation['other']=$cancellationRow['other_reason'];
                }

                $this->renderJobUpdatesTemplate();

            } elseif ($iRow[0]['user_id'] == $_SESSION['ident']['user_id']) {
                $this->job_messages = $this->prepareMessages($jRow);
                $this->job_step_updates = $this->prepareStepUpdates($uRow, $hRow);
                $this->renderJobUpdatesTemplate();
            } else {
                LoggerPrime::info("User trying to illegally access job updates page. user_id: " .$this->user_id);
                $error_messages[] = "Sorry this operation is not allowed";
                Alerts::setErrorMessages($error_messages);
                header('Location: /?t=all_jobs');
            }
        } else {
            #error_message
            LoggerPrime::info("User trying to illegally access job updates page. user_id: " .$this->user_id);
            $error_messages[] = "Sorry this operation is not allowed";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=all_jobs');
        }
    }

    /**
    * prepaere messages for display
    * @param array messagesRow : array of rows from the db
    * $parm bool admin : set to true if the user has higher privilages
    */
    public function prepareMessages($messagesRow)
    {
        $messages = array();
        foreach ($messagesRow as $k=>$row) {
            $messages[$k]['subject'] = $row['subject'];
            $messages[$k]['message_text'] = $row['message'];
            $messages[$k]['sent'] = $this->helper->convertToPrettyDateAndTime($row['sent']);
            $messages[$k]['read'] = $row['message_read'];
        }
        return $messages;
    }
    /**
    * prepaere messages for display
    * @param array messagesRow : array of rows from the db
    * $parm bool admin : set to true if the user has higher privilages
    */
    public function prepareStepUpdates($updatesRow, $holdsRow, $admin=false)
    {
        $updates = array();
        $k = 0;
        foreach ($updatesRow as $row) {
            if ($admin) {
                $updates[$k]['name'] = $row['fullname'];
            } else {
                $updates[$k]['name'] = ($row['user_id'] == $_SESSION['ident']['user_id'])?$row['fullname']:"MakeCentral staff";
            }
            $updates[$k]['status'] = $row['admin_status'];
            //$updates[$k]['sent'] = $this->helper->convertToPrettyDate($row['completed']);
            $updates[$k]['sent'] = ($row['completed']);
            $k+=1;
        }
        if($admin) {
            foreach ($holdsRow as $row) {
                if (isset($row['hold_placed'])){
                    $updates[$k]['sent'] = $row['hold_placed'];
                    $updates[$k]['name'] = $row['fullname'];
                    $updates[$k]['status'] = 'Hold Placed';
                    $k+=1;
                }
                if(isset($row['hold_released'])){
                    $updates[$k]['sent'] = $row['hold_released'];
                    $updates[$k]['name'] = $row['fullname'];
                    $updates[$k]['status'] = 'Hold Released';
                    $k+=1;
                }
            }


            usort($updates, function ($a, $b) {
                return strcmp($a['sent'], $b['sent']);
            });
        }

        foreach($updates as $r=>$row){
            $updates[$r]['sent'] = $this->helper->convertToPrettyDateAndTime($row['sent']);
        }

        return $updates;
    }
}
