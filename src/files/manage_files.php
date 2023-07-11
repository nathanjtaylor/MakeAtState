<?php

class ManageFiles
{
    const UPLOAD_ERR_INI_SIZE        = 0x0001;
    const UPLOAD_ERR_FORM_SIZE        = 0x0002;
    const UPLOAD_ERR_PARTIAL        = 0x0003;
    const UPLOAD_ERR_NO_FILE        = 0x0004;
    const UPLOAD_ERR_NO_TMP_DIR        = 0x0006;
    const UPLOAD_ERR_CANT_WRITE        = 0x0007;
    const UPLOAD_ERR_EXTENSION        = 0x0008;

    private $oTemplate;
    private $dc;
    private $combined_file_size = 0;
    private $helper;

    private static $user;
    private static $nav_array;

    private static $file_type_array;
    private static $upload_path;
    private static $upload_ext;
    private static $upload_mimetypes;
    

    public function __construct(Templater &$oTempl)
    {
        $this->oTemplate = $oTempl;
        $this->setUser();
        $this->setNavigation();
        $this->dc = new DataCalls();
        $this->helper =  new PrimeHelper();
        if (self::$upload_path == null || self::$upload_ext == null) {
            $upath  = APP::uploadPath();
            self::$upload_path = rtrim($upath->get("path"), '/') .'/';
            self::$file_type_array = APP::getFileTypes();
            self::$upload_ext = $upath->getArray('ext');
        }
    }

    /**
    * Sets the user
    */
    public function setUser()
    {
        //lazy loading  user
        if (self::$user == null) {
            self::$user = AuthenticatedUser::getUser();
        }
    }

    /**
    * Sets the navigation for the page
    */
    public function setNavigation()
    {
        if (self::$nav_array == null) {
            self::$nav_array = AuthenticatedUser::getUserNavBar();
        }
    }



    public function renderUploadForm($error_messages = array(), $success_messages = array())
    {
        $this->oTemplate->setTemplate('upload_files.html');
        $fRows = $this->retriveFiles();
        $this->oTemplate->setVariables('page_title', "3D Prime Home");
        $this->oTemplate->setVariables("file_rows", $fRows);
        $this->oTemplate->setVariables("error_messages", Alerts::getErrorMessages());
        $this->oTemplate->setVariables("success_messages", Alerts::getSuccessMessages());
        
        $this->oTemplate->setVariables("memory_array", $this->generateMemoryBarValues());

        # Generate post nonce and send it to the form
        $pNonce = AccessHandler::generatePostNonce();
        $this->oTemplate->setVariables("pNonce", $pNonce);
        # Set the navigation options
        $this->oTemplate->setVariables("nav_array", self::$nav_array);
        $this->oTemplate->generate();
    }

    
    /**
    * function to retive all the files uploaded by user
    */
    public function retriveFiles()
    {
        $ufiles = array();
        $display_files = array();
        if (self::$user != null) {
            $user_id = self::$user["user_id"];
            $ufiles = $this->dc->getUserFiles($user_id);
            $file_retention_duration = AuthenticatedUser::getFileRetentionDuration();
        }
        foreach ($ufiles as $k=>$v) {
            // check the file extensions, show only 3d files
            $file_name = $v['file_name'];
            $file_ext = explode(".", $file_name);
            $file_ext = array_pop($file_ext);
            $file_ext = strtolower($file_ext);
            if (in_array(strtolower($file_ext), self::$upload_ext)) {
                // to get add the filesizes
                $display_files[$k] = $v;
                $this->combined_file_size += intval($v['file_size']);
                $display_files[$k]['expires_in'] = $this->helper->getExpiryDurationForFile($v['created'], $file_retention_duration);
                $display_files[$k]['formatted_size'] = $this->helper->convertFileSizes($v['file_size']);
                // check if viewing is allowed
                $type_array = self::$file_type_array->get($file_ext);
                $display_files[$k]['allow_viewing'] = $type_array->get('view'); // returns 0 if viewing is not allowed
            }
        }
        return $display_files;
    }
    

    /**
    * Generates the array for the memory bar
    */
    public function generateMemoryBarValues()
    {
        $memory_array = array();
        $total_allocated_size = AuthenticatedUser::getTotalAllocatedSize();
        
        $fill_percent = round($this->combined_file_size/$total_allocated_size, 4) * 100;
        $memory_array = array('total_allocated_size' => $this->helper->convertFileSizes($total_allocated_size) , 'combined_file_size'=>$this->helper->convertFileSizes($this->combined_file_size), 'fill_percent'=>$fill_percent);

        return $memory_array;
    }
    

    /**
    * function to handle uploading of files
    */
    public function addFile()
    {
        $error_messages = array();

        $uploaded_files = $_FILES["files"];
        $files_array = array();
        foreach ($uploaded_files as $key => $val) {
            foreach ($val as $index=>$v) {
                $files_array[$index][$key] = $v;
            }
        }
        $upload_error_occured = false;
        $total_size_of_upload = 0 ; // combined size of all the files uploaded
        
        foreach ($files_array as $key=>$ufile) {
            $error = $ufile['error'];
            if ($error[0] ==0) {
                $file_size = $ufile["size"];
                $total_size_of_upload += $file_size;
                $file_name = $ufile["name"];
                $file_type = $ufile["type"];
                $file_ext = explode(".", $file_name);
                $file_ext = strtolower(array_pop($file_ext));


                LoggerPrime::debug($file_ext);
                LoggerPrime::debug($file_type);
                $type_array = self::$file_type_array->get($file_ext);
                if (isset($type_array) && !empty($type_array)) {
                    $allowed_ext = $type_array->get('ext');
                    $allowed_mimetypes =    $type_array->getArray('mime_type') ;


                    if (!(in_array(strtolower($file_type), $allowed_mimetypes)) || !($file_ext == $allowed_ext)) {
                        $error = self::UPLOAD_ERR_EXTENSION;
                        $upload_error_occured = true;
                        $this->handleUploadErrors($error);
                    }
                    // check if sum of upload  is grater than 200MB
                    elseif ($total_size_of_upload > 200000000) {
                        $error = self::UPLOAD_ERR_FORM_SIZE;
                        $upload_error_occured = true;
                        $this->handleUploadErrors($error);
                    } elseif (false) {
                        // TODO - check for file size from the db
                    }
                } else {
                    $error = self::UPLOAD_ERR_EXTENSION;
                    $upload_error_occured = true;
                    $this->handleUploadErrors($error);
                }
            } else {
                $upload_error_occured = true;
                $this->handleUploadErrors($error[0]);
            }
        }
        //atempt to save files if there are no errors
        if (empty($upload_error_occured) && !empty($files_array)) {
            $this->saveFiles($files_array);
        }
    }

    /**
    * Saves files in the the share and adds them to the db
    * @param array $files_array: array of file/files uploaded by the user
    */
    public function saveFiles($files_array)
    {
        if (self::$user != null && empty(self::$user['blocked'])) {
            $error_messages =  array();
        
            $success_messages = array();
            $this->dc->transactionStart();
            
            foreach ($files_array as $key=> $ufile) {
                $file_name = $ufile["name"];
                // strip all chars except[A-Za-z0-1._]
                $file_name = preg_replace('{[^ \w._-]}', '', $file_name);
                
                $tmp_name = $ufile["tmp_name"];
                $size = $ufile["size"];
                $user_id = self::$user["user_id"];
                $base_name = basename($file_name);

                $user_dir = self::$upload_path .$user_id."/";
                $upload_sys_path = $user_dir. $base_name;

                $upload_row = $this->dc->insertFiles($user_id, $file_name, $upload_sys_path, $size);

                // update the file_name with unique file_id from the db
                $updated_file_name  = $upload_row ."_". $file_name;
                $upload_sys_path = $user_dir . $updated_file_name;
            
                // update the current row in the db with the updated path
                $aData = array("file_id"=>$upload_row, "sys_file_path"=> $upload_sys_path);
                $this->dc->updateUsingPrimaryKey("files", "file_id", $aData);

                if ($this->checkUserDir($user_dir)) {
                    if (move_uploaded_file($tmp_name, $upload_sys_path)) {
                        LoggerPrime::info("File uploaded successfully");
                        $success_messages[] = "File " .$file_name. " successfully updated";
                    } else {
                        LoggerPrime::info("File upload failed");
                        $error_messages[] = "File upload failed";
                    }
                } else {
                    LoggerPrime::info("Unabe to create a user directory");
                    $error_messages[] =" Unabe to create a user directory";
                }
            }
            if (empty($error_messages)) {
                $this->dc->transactionCommit();
                Alerts::setSuccessMessages($success_messages);
            } else {
                $this->dc->transactionRollback();
                Alerts::setErrorMessages($error_messages);
            }
            header('Location: /?t=home ');
        }
    }

    /**
    * checks if the user has a dir in the share if not creates one with his user_id
    * @param string user_dir: path to the user directory
    */
    public function checkUserDir($user_dir)
    {
        if (is_dir($user_dir)) {
            return true;
        } else {
            return mkdir($user_dir, 0750);
        }
    }
    
    /**
    * handles the file upload errors
    * @param Int error_code  , error code returned bu upload
    */
    public function handleUploadErrors($error_code)
    {
        $error_messages = array();
        switch ($error_code) {
                case self::UPLOAD_ERR_INI_SIZE:
                case self::UPLOAD_ERR_FORM_SIZE:
                    $error_messages[] = "Upload excceds the maixium size limit. Upload limit 100MB";
                    LoggerPrime::info("Upload excceds the maixium size limit");
                    break;
                case self::UPLOAD_ERR_PARTIAL:
                case self::UPLOAD_ERR_NO_FILE:
                    $error_messages[] = "File upload failed , File doesn't exist or cancelled due to partial upload";
                    LoggerPrime::info("File upload failed , File doesn't exist or cancelled due to partial upload");
                    break;
                case self::UPLOAD_ERR_NO_TMP_DIR:
                case self::UPLOAD_ERR_CANT_WRITE:
                    $error_messages = "Upload directory is unavailabe";
                    LoggerPrime::info("Upload directory is unavailabe");
                    break;
                case self::UPLOAD_ERR_EXTENSION:
                    $message = "The file extension is not supported.  Supported extensions are ";
            
                    foreach (self::$upload_ext as $ext) {
                        $message .=".". $ext ." ";
                    }
                    $error_messages[] = $message;
                    LoggerPrime::info("The file extension is not supported");
                    break;
            }
        Alerts::setErrorMessages($error_messages);
        header('Location: /?t=home ');
    }

    /**
    * Deletes files form the db for the user
    * @param int $file_id: file id of the file that needs to be deleted
    */
    public function deleteFile($file_id)
    {
        $error_messages = array();
        $success_messages = array();
        
        $fData = array("file_id" => $file_id);
    
        $fRecord = $this->dc->getRowsById("files", $fData);
        #get jobs associated with this file
        $jRecord = $this->dc->getRowsById("job_files", $fData);
        if (empty($fRecord)) {
            LoggerPrime::info("The record or file does not exist :file_id- " .$file_id);
            $error_messages[] = "The record of file does not exist";
        } elseif ($fRecord[0]['user_id'] !== self::$user['user_id']) {
            LoggerPrime::info("User tried to illegally delete a file. File id: " .$file_id. " User id: " . self::$user['user_id']);
            $error_messages[] = "The file does not exist, and hence it cannot be deleted";
        } elseif (!empty($fRecord["deleted"])) {
            LoggerPrime::info("This file has already been deleted :file_id- ". $file_id);
            $error_messages[] = "This file has already been deleted";
        }
        # Check if an open job is assiated with this file
        elseif (!empty($jRecord)) {
            foreach ($jRecord as $k=>$job_file) {
                if (!empty($job_file['job_id'])) {
                    $jData = array("job_id" => $job_file['job_id']);
                    $jRow = $this->dc->getRowsById("jobs", $jData);
                    if(empty($jRow[0]['closed'])) {
                        LoggerPrime::info("File cannot be deleted, an active job exists for this file :file_id- ". $file_id);
                        $error_messages[] = "File cannot be deleted, an active job exists for this file";
                        break;
                    }
                }
            }
        }
        if (empty($error_messages)) {
            $this->dc->transactionStart();
            $fDeleted = $this->dc->setFileAsDeleted($file_id);
            if (empty($fDeleted)) {
                LoggerPrime::info("Unable to delete file : file_id- ". $file_id);
                $error_messages[] = "Unable to delete file";
                $this->dc->transactionRollback();
            } else {

                #remove file from the cart
                $cDeleted = $this->dc->markCartFileAsDeleted($file_id);
                if (empty($cDeleted)) {
                    LoggerPrime::info("File successfully maked as deleted : file_id- " . $file_id);
                    $success_messages[] = "File successfully deleted <a href ='/?t=undo_delete&uFid={$file_id}'>Undo</a> ";
                } else {
                    LoggerPrime::info("File successfully maked as deleted : file_id- " . $file_id);
                    $success_messages[] = "File successfully deleted <a href ='/?t=undo_delete&uFid={$file_id}'>Undo</a> . File was also successfully removed from your cart ";
                }
                $this->dc->transactionCommit();
            }
        }
        Alerts::setErrorMessages($error_messages);
        Alerts::setSuccessMessages($success_messages);
        header("Location: /?t=home");
    }

    /**
    *Undo delete for a file with file_id
    */
    public function undoDelete()
    {
        if (self::$user == null) {
            $this->setUser();
        }
        $user_id = self::$user['user_id'];
        
        
        $error_messages = array();
        $success_messages = array();
        $file_id = UserData::create('uFid')->getString();
        if (!empty($file_id)) {
            $fRow = $this->dc->getFile($file_id);
            if ($user_id == $fRow[0]['user_id']) {
                $sData = array('file_id'=>$file_id, "deleted"=>null);
                $uRow = $this->dc->updateUsingPrimaryKey("files", "file_id", $sData);
                $success_messages[] = "Undo operation successful";
            } else {
                $error_messages[] = "You are not authorized to perform this action";
            }
        } else {
            $error_messages[] = "We are sorry. Unable to perform undo at this time";
        }
        Alerts::setErrorMessages($error_messages);
        Alerts::setSuccessMessages($success_messages);
        header('Location: /?t=home');
    }
}
