<?php

class ViewFile
{
    private $file_id;
    private $vTemplate;
    private $file_name;
    private $dc;
    private $error_messages = array();
    
    private static $user;

    
    /**
    *Constructor function
    * @param Int file_id : file_id of the file
    * @param Templater vTempl : templater class object
    */
    public function __construct(Templater &$vTempl, $file_id)
    {
        $this->setCurrentUser();
        $this->file_id = $file_id;
        $this->vTemplate = $vTempl;
        $this->dc = new DataCalls();
        if (!empty($this->file_id)) {
            $fRow = $this->dc->getFile($this->file_id);
            $this->file_name = $fRow[0]['file_name'];
            # --TODO check if the user owns the file or has permissions to view it
            $user_access = AuthenticatedUser::getUserPermissions();
            if ($fRow[0]['user_id'] == self::$user['user_id']  && empty($fRow[0]['deleted'])) {
                LoggerPrime::debug("Viewing file".  $this->file_name);
                $this->routeFileType();
            } elseif (($user_access  === "STUDENT STAFF" || $user_access  === "STAFF" || $user_access  === "ADMIN") && (empty($fRow[0]['deleted']))) {
                LoggerPrime::debug("Viewing file".  $this->file_name);


                $this->routeFileType();
            } else {
                // if the user does not have permissions to view the ile
                $this->error_messages[] = "Unable to perform this operation";
                Alerts::setErrorMessages($this->error_messages);
                header("Location: /?t=home");
            }
        }
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
    *Render template for viewing stl files
    */
    private function renderStlViewingTemplate()
    {
        $this->vTemplate->setTemplate('view_file_in_stl_viewer.html');
        $this->vTemplate->setVariables('file_name', $this->file_name);
        $this->vTemplate->setVariables('file_id', $this->file_id);
        $this->vTemplate->generate();
    }

    /**
     * Render template for viewing files that can be viewed in browser
     */
    private function viewFileInBrowser()
    {
        header('Location: /?t=stream_file&fid='.$this->file_id);
    }

    /**
     * figure out what view type to use with this file
     */
    private function routeFileType()
    {
        $file_types_array = APP::getFileTypes();

        $extension = explode('.', $this->file_name);
        $extension = array_pop($extension);
        $extension = strtolower($extension);
        $ext_array = $file_types_array->get($extension);
        if (isset($ext_array)) {
            # views:
            #   0 - no view
            #   1 - three.js
            #   2 - browser
            $view = $ext_array->get('view');

            switch ($view) {
                case 1:
                    $this->renderStlViewingTemplate();
                    break;
                case 2:
                    $this->viewFileInBrowser();
                    break;

                default:
                    $this->error_messages[] = "Unable to perform this operation";
                    Alerts::setErrorMessages($this->error_messages);
                    header("Location: /?t=home");
            }
        }
    }
}
