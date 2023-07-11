<?php
class StreamFile
{
    private $file_id ;
    private $fTemplate;
    private static $user;
    private $dc;
    private $disposition;
    public function __construct(Templater &$fTempl)
    {
        $this->fTemplate = $fTempl;
        $this->setCurrentUser();
        $fTarget = UserData::create('fid');
        $this->file_id = $fTarget->getInt();
        $fDisposition = UserData::create('disposition');
        $this->disposition = $fDisposition->getStr();
        $this->dc = new DataCalls();
        $this->getFileForViewing();
    }

    /**
    *Sets the current user
    */
    private function setCurrentUser()
    {
        if (self::$user == null) {
            self::$user = AuthenticatedUser::getUser();
        }
    }
    
    /**
    *Stream the file for viewing and display
    */
    private function getFileForViewing()
    {
        $error_messages = array();
        $user_access = AuthenticatedUser::getUserPermissions();
        $fRow = $this->dc->getFile($this->file_id);

        #-- TODO Give admin and staff viewing permissions
        if ((!empty($fRow)) && (empty($fRow[0]['deleted'])) &&((self::$user['user_id'] == $fRow[0]['user_id']) || ($user_access  === "STUDENT STAFF" || $user_access  === "STAFF" || $user_access  === "ADMIN"))) {
            LoggerPrime::debug("Viewing file".  $this->file_id);
            $sys_file_path = $fRow[0]['sys_file_path'];
            $this->sendFileForViewing($sys_file_path);
        } else {
            # if the file does not exist or if the user does not have access to view the file
            LoggerPrime::debug("Trying to view file illegally. File id:".  $this->file_id. " User id: ".self::$user['user_id']);
            $this->error_messages[] = "Sorry, unable to perform this operation";
            Alerts::setErrorMessages($this->error_messages);
            header("Location: /?t=home");
        }
    }
    
    /**
    *Send file for viewing
    * @param string file_path : path of the file for viewing
    */
    private function sendFileForViewing($file_path)
    {
        LoggerPrime::debug("file path".  $file_path);

        $real_path = realpath($file_path);
        // check if the share is mounted
        if (!empty($real_path) && is_readable($real_path)) {
            ob_end_clean(); //close and clean the buffer
            LoggerPrime::debug("real file path".  $real_path);
            $file_size = filesize($real_path);
            $path_parts = pathinfo($real_path);
            $file_ext = strtolower($path_parts['extension']);
            $sBasename = $path_parts["basename"];
            /* Fix chars for content-disposition header */
            if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
                $sBasename = rawurlencode($sBasename);
            } else {
                $sBasename = str_replace('"', '\"', $sBasename);
            }
            $f_ext = APP::uploadPath()->getArray('ext');
            $a_ext = APP::uploadPath()->getArray('attachment_ext');
            $file_type_array = APP::getFileTypes();
            $ext_array = $file_type_array->get($file_ext);
            if ((in_array($file_ext, $f_ext) || in_array($file_ext, $a_ext))&& !empty($ext_array)) {
                $cdisp= !empty($this->disposition)? $this->disposition : "inline";
                #$ctype= $ext_array->get('mime_type');
                $ctype = mime_content_type($file_path);
                header("Pragma: public");
                header("Content-Type: ".$ctype);
                header("Content-Transfer-Encoding: binary");
                header("Content-Length: ".$file_size);
                header("Content-Disposition: $cdisp; filename=\"".$sBasename."\";");
                readfile($real_path);
                exit(0);
            }
        } else {
            // if the file path does not exist
            $this->sendEmailToOwner();
            LoggerPrime::debug("File path does not exist or is not readable: ".  $real_path);
            $this->error_messages[] = "Sorry, unable to perform this operation. Please try again later.";
            Alerts::setErrorMessages($this->error_messages);
            header("Location: /?t=home");
        }
    }

    /**Generates Email template. Sends an email to the owner when the mount is not availabe
    * @param string token: onetime token for the user
    */
    private function sendEmailToOwner()
    {
        $cf = APP::config();
        $owner_email = $cf->get('application.owner');
        $this->fTemplate->setTemplate('warning_emails_to_site_owner.html');
        $this->fTemplate->setBlockVariables('message', 'mount');
        $this->fTemplate->setBlock('warnings_to_owner');
        $email_block = $this->fTemplate->generateBlock();
        $subject = "MakeAtState files unavailabe";
        // To send HTML mail, the Content-type header must be set
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=iso-8859-1';
        // Additional headers
        $headers[] = 'From: MakeAtState <'. $cf->get('application.email').'>';
        return mail($owner_email, $subject, $email_block, implode("\r\n", $headers));
    }
}
