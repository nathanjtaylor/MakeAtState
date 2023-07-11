<?php

class RemoveFileFromJob
{
    static protected $user;


    private $template;
    private $access_level;
    private $job_id;
    private $user_id;
    private $file_id;
    private $dc;

    public function __construct(Templater &$templ)
    {
        $this->template=$templ;
        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();
        $this->job_id = UserData::create('job_id')->getInt();
        $this->file_id = UserData::create('file_id')->getInt();
        $target = UserData::create('t')->getString();
        $this->setUser();
        $this->setAccessLevel();
        if( $this->access_level === "STUDENT STAFF" || $this->access_level === "STAFF" || $this->access_level === "ADMIN"){
            $this->user_id = $_SESSION['ident']['user_id'];
            if($target == 'undo_remove_file_from_job') {
                $this->undoRemoveFileFromJob();
            } else {
                $this->removeFileFromJob();
            }
        }
        else{
            $error_messages[] = "Sorry, you don't have access to perform this operation";
            LoggerPrime::info("User trying to illegally remove file from job" . $this->job_id . " User id: ". $this->user_id);
            Alerts::setErrorMessages($error_messages);
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

    /**
     * Remove file from jobs. 
     *
     */
    public function removeFileFromJob() {
        if(!empty($this->job_id) && !empty($this->file_id )) {
            $this->dc->transactionStart();    
            $dRow = $this->dc->markJobFilesAsDeleted($this->job_id, $this->file_id, $undo = FALSE);
            if(!empty($dRow )) {
                $success_messages[] ="File successfully removed from job. <a href='/?t=undo_remove_file_from_job&job_id={$this->job_id}&file_id={$this->file_id}'>Undo</a>";
                Alerts::setSuccessMessages($success_messages);
                $this->dc->transactionCommit();

            } else {
                $this->dc->transactionRollback();
                $error_messages[] = "Unable to remove file.";
                LoggerPrime::info('Unable to remove file in with job id'.$this->job_id.' and file id '.$this->file_id);
                Alerts::setErrorMessages($error_messages);

            }
        } else {
            $error_messages[] = "Invalid job id or file id provided";
            LoggerPrime::info('Invalid job id'.$this->job_id.' or file id '.$this->file_id.' provided.');
            Alerts::setErrorMessages($error_messages);

        }
        header('Location: /?t=workflow&job_id='.$this->job_id);
    }

    /**
     * Undo remove file from jobs. 
     *
     */
    public function undoRemoveFileFromJob() {
        if(!empty($this->job_id) && !empty($this->file_id )) {
            $this->dc->transactionStart();    
            $dRow = $this->dc->markJobFilesAsDeleted($this->job_id, $this->file_id, $undo = True);
            if(!empty($dRow )) {
                $success_messages[] ="File added back to job.";
                Alerts::setSuccessMessages($success_messages);
                $this->dc->transactionCommit();

            } else {
                $this->dc->transactionRollback();
                $error_messages[] = "Unable to undo remove file.";
                LoggerPrime::info('Unable to undo remove file in with job id'.$this->job_id.' and file id '.$this->file_id);
                Alerts::setErrorMessages($error_messages);

            }
        } else {
            $error_messages[] = "Invalid job id or file id provided";
            LoggerPrime::info('Invalid job id'.$this->job_id.' or file id '.$this->file_id.' provided.');
            Alerts::setErrorMessages($error_messages);

        }
        header('Location: /?t=workflow&job_id='.$this->job_id);
    }


}

