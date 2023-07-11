<?php
#Class to send messages to users 
class SendMessage{
	const UPLOAD_ERR_INI_SIZE		= 0x0001;
	const UPLOAD_ERR_FORM_SIZE		= 0x0002;
	const UPLOAD_ERR_PARTIAL		= 0x0003;
	const UPLOAD_ERR_NO_FILE		= 0x0004;
	const UPLOAD_ERR_NO_TMP_DIR		= 0x0006;
	const UPLOAD_ERR_CANT_WRITE		= 0x0007;
	const UPLOAD_ERR_EXTENSION		= 0x0008;
    

    private $job_id;
    private $job_step_id;
    private $job_user_id;
    private $user_id;
    private $message_text;
    private $target_page; // to determine which part of the application the message is being sent
    private $mTemplate;
    private $dc;
    private $helper;
    private $access_level;
    private $attachment_path;
    private $attachment_exts = array();
    private $error_messages = array();
    private $success_messages = array();
    static private $user = array();
    private $admin_email;
    private $group_id;

    /**
    *Constructor function for SendMessage class
    * @param Templater $mtempl : templater object for send message class
    * @param int job_id : job_id of the job
    * @param int job_step_id: job_step_id of the job
    * @param string message_text : text of the message that needs to be sent
    */
    public function __construct(Templater &$mTempl){
        $this->mTemplate = $mTempl;
        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();
        $this->setUser();
        $this->setAccessLevel();
        $this->job_id = UserData::create('message_job_id')->getInt(0);
        $this->job_step_id =  UserData::create('message_step_id')->getInt(0);
        $this->message_text = UserData::create('message_text')->getString('');
        $this->target_page = UserData::create('target_page')->getString('');
        $this->group_id = UserData::create('group_id')->getString('');

        if($this->group_id){
            $gaRow = $this->dc->getGroupAdminEmail($this->group_id);
            $this->admin_email = $gaRow['admin_email'];
        }

	    $upath  = APP::uploadPath();
	    $this->attachment_path = rtrim($upath->get("attachment_path"), '/') .'/';
		$this->attachment_exts = $upath->getArray("attachment_ext"); 
        $this-> preprocessSendMessageAction();
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
    * Get access level for the user 
    */
    private function setAccessLevel(){
        $this->access_level = AuthenticatedUser::getUserPermissions();
    }

    /**
    * Pre process messages, determine of the message is sent by the user or admin
    */
    private function preprocessSendMessageAction(){
        $jRow = $this->getJobUserDetails();
        // check if the job exists
        if(isset($jRow[0]) && !empty($this->message_text)){
            $this->job_user_id = $jRow[0]['user_id'];
            // check if the user owns the job
            if($this->job_user_id == $this->user_id){
                $this->sendJobMessage($jRow, $sent_by_admin = 0);
            }
            //check if the user is an admin
            else if( $this->access_level == "STUDENT STAFF" || $this->access_level == "STAFF" || $this->access_level == "ADMIN" ){
                $this->sendJobMessage($jRow, $sent_by_admin = 1);
            }
            else{
                // redirect the user throwing an error message
                LoggerPrime::info("Unauthorized user trying to send message .User id:" . $this->user_id );

            }
        }else{
            $this->error_messages[] = "Sorry, we are unable to send your message.";
            Alerts::setErrorMessages($this->error_messages);
            if($this->target_page == "job_info"){
                header('Location: /?t=workflow&uid='.$this->user_id.'&job_id='.$this->job_id);
            }else{
                header('Location: /?t=message_details&job_id='.$this->job_id);
            }


        }


    }
    /**
    * Get the user and file details for the job
    */
    private function getJobUserDetails(){
        return  $this->dc->getJobUserDetails($this->job_id);
    }
    /*Function to insert messages into email and job_updates table
    * @param array $eData : Data to be inserted into email table
    * @param array $uData: Data to be inserted into job_updates table
    */
    private function insertMessages($eData, $uData){
        $email_id = False;
        $eRow = $this->dc->insertEmails($eData);
        if(!empty($eRow)){
            $uData['email_id'] = $eRow;
            $uRow = $this->dc->insertJobUpdates($uData);
            if(!empty($uRow)){
                $email_id = $eRow;
            }
        }
        return $email_id;
    }
    /**
    * Send a message 
    * @param array $jRow : job array from the db
    * @param int $sent_by_admin : is set to 1 if the message is sent by admin if not it's set to 0
    */
    public function sendJobMessage($jRow, $sent_by_admin){
        // check if the email was sent by an admin
        if ($sent_by_admin == 1){
            $eData = array("user_id"=>$jRow[0]["user_id"] , "recipients"=>$jRow[0]['email'], "subject"=>"Message from MakeCentral MakeAtState", "message"=>$this->message_text , "created"=>date('Y-m-d H:i:s') , "sent"=>date('Y-m-d H:i:s'), "sent_user_id"=>$this->user_id,"sent_by_admin"=>intval($sent_by_admin));
        }else{
            $eData = array("user_id"=>$jRow[0]["user_id"] , "recipients"=>$jRow[0]['email'], "subject"=>"Message from MakeCentral MakeAtState", "message"=>$this->message_text , "created"=>date('Y-m-d H:i:s') , "sent"=>date('Y-m-d H:i:s'), "sent_user_id"=>$this->user_id);
        }
        $uData = array("job_id" => $jRow[0]["job_id"], "job_step_id" =>$this->job_step_id,"created" => date("Y-m-d H:i:s") , "public_view"=>1);
        //start a db transaction
        $this->dc->transactionStart();
        $email_id = $this->insertMessages($eData , $uData);
        if($email_id){
            $this->addAttachments($email_id);
        }else{
            $this->error_messages[] = "Sorry, we unable to send your message";
        }
        if(!empty($this->error_messages)){
            $this->dc->transactionRollback();
            Alerts::setErrorMessages($this->error_messages);
        }
        else{
            // send the email to the user
            if($sent_by_admin === 1){
                $successful_send = $this->helper->sendMessage($jRow[0], $this->message_text, null);
            }else{ // send email to admin
                $successful_send = $this->helper->sendMessage($jRow[0], $this->message_text, $this->admin_email, $send_to_admin = TRUE);
            }
            if ($successful_send){
                    if($this->target_page == "job_info"){
                        $this->success_messages[] = 'Message sent successfully. <a href = "/?t=message_details&job_id='.$this->job_id.'">View  all messages </a>';
                    }else{
                        $this->success_messages[] = "Message sent successfully.";
                    }
                Alerts::setSuccessMessages($this->success_messages);
                $this->dc->transactionCommit();
            }else{
                $this->dc->transactionRollback();
            }
        }
        if($this->target_page == "job_info"){
            header('Location: /?t=workflow&uid='.$this->user_id.'&job_id='.$this->job_id);
        }else{
            header('Location: /?t=message_details&job_id='.$this->job_id);
        }

    }
    /**
    * Associates an attachment with a message 
    * @param int email_id : email_id of the associated email
    */
    public function addAttachments($email_id){
        $uploaded_attachments = $_FILES["files"];
        $attachments_array =  array();

        foreach($uploaded_attachments as $key=>$val){
            foreach($val as $index=>$v){
                $attachments_array[$index][$key] = $v;
            }
        }
        $upload_error_occured = FALSE;
        $total_size_of_upload =0 ;
        foreach($attachments_array as $k=>$attachment){
            $error = $attachment['error'];
            if($error == 0 ){
                $attachment_size = $attachment['size'];
                $total_size_of_upload += $attachment_size;
                $attachment_name = $attachment['name'];
                $attachment_type = $attachment['type'];
				$attachment_ext = array_pop(explode("." , $attachment_name));
                if(!(in_array(strtolower($attachment_ext), $this->attachment_exts))){
                    $error = self::UPLOAD_ERR_EXTENSION;
                    $upload_error_occured = TRUE;
                    $this->handleUploadErrors($error);
                    
                }
                // check if sum of upload  is grater than 100MB
                else if($total_size_of_upload > 100000000){
                    $error = self::UPLOAD_ERR_FORM_SIZE;
                    $upload_error_occured = TRUE;
                    $this->handleUploadErrors($error);

                }
                else if(false){
                    // TODO - check for file size from the db
            
                }
            }
            else{
                $upload_error_occured = TRUE;
                $this->handleUploadErrors($error[0]); 
            }
        }
        //atempt to save files if there are no errors
        if(empty($upload_error_occured) && !empty($attachments_array)){
            $this->saveAttachments($attachments_array, $email_id);
        }

    }

	/**
	* Saves files in the the share and adds them to the db
	* @param array $attachments_array: array of attachments associated to the email
    * @param int email_id : email_id of the associated email
	*/

	public function saveAttachments($attachments_array, $email_id){
		if(self::$user != null && empty(self::$user['blocked']) ){
			foreach($attachments_array as $key=> $ufile){	
		        	
				$file_name = $ufile["name"];
				// strip all chars except[A-Za-z0-1._]
				$file_name = preg_replace('{[^ \w._-]}','', $file_name);
				
				$tmp_name = $ufile["tmp_name"];
				$size = $ufile["size"];
                // user id should be the user who owns the job 
                // change this in the future when we come up with a better solution
				$user_id = $this->job_user_id;
				$base_name = basename($file_name);

				$email_dir = $this->attachment_path .$email_id."/";
				$upload_sys_path = $email_dir. $base_name;	

				$upload_row = $this->dc->insertFiles($user_id, $file_name, $upload_sys_path, $size );

				// update the file_name with unique file_id from the db
				$updated_file_name  = $upload_row ."_". $file_name;
				$upload_sys_path = $email_dir . $updated_file_name;  
			
				// update the current row in the db with the updated path 
				$fData = array("file_id"=>$upload_row, "sys_file_path"=> $upload_sys_path);
				$this->dc->updateUsingPrimaryKey("files", "file_id", $fData);

				$aData = array("file_id"=>$upload_row, "email_id"=> $email_id);
                $this->dc->insertAttachments($aData);
			
				if($this->checkEmailDir($email_dir)){
					if(move_uploaded_file($tmp_name , $upload_sys_path)){
						LoggerPrime::info("File uploaded successfully for email with email id" .$email_id);
						$this->success_messages[] = "Image " .$file_name. " successfully sent"; 
					}
					else {
						LoggerPrime::info("File upload failed");
						$this->error_messages[] = "File upload failed";
					}
				}
				else {
				
					LoggerPrime::info("Unabe to create a email  directory");
					$this->error_messages[] =" Unabe to create a user directory";
				}
			}

		}
	}

	/**
	* checks if the user has a dir in the share if not creates one with his user_id
	* @param string email_dir: path to the email attachment directory
	*/
	public function checkEmailDir($email_dir){
		if (is_dir($email_dir)){
			return true;
		}
		else {
			return mkdir($email_dir, 0750);

		}		

	}

	/**
	* handles the file upload errors
	* @param Int error_code  , error code returned bu upload
	*/

	public function handleUploadErrors($error_code){
		switch ($error_code) {
				case self::UPLOAD_ERR_INI_SIZE:
				case self::UPLOAD_ERR_FORM_SIZE:
					$this->error_messages[] = "Upload excceds the maixium size limit. Upload limit 100MB";
					LoggerPrime::info("Upload excceds the maixium size limit");	
					break;
				case self::UPLOAD_ERR_PARTIAL:
				case self::UPLOAD_ERR_NO_FILE:
					$this->error_messages[] = "File upload failed , File doesn't exist or cancelled due to partial upload";
					LoggerPrime::info("File upload failed , File doesn't exist or cancelled due to partial upload");
					break;
				case self::UPLOAD_ERR_NO_TMP_DIR:
				case self::UPLOAD_ERR_CANT_WRITE:
					$this->error_messages = "Upload directory is unavailabe";
					LoggerPrime::info("Upload directory is unavailabe");
					break;
				case self::UPLOAD_ERR_EXTENSION:
					$message = "The file extension is not supported.  Supported extensions are ";
			
					foreach($this->attachment_exts as $ext){
						$message .=".". $ext ." ";
					}
					$this->error_messages[] = $message;
					LoggerPrime::info("The file extension is not supported");
					break;
			}
	}

}
?>
