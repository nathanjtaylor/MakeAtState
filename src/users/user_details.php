<?php
# Class to view and edit user details

class UserDetails
{
    private $user_id; // user_id of this user
    private $e_user_id; // user_id of the user to be viewed and edited
    private $uTemplate;
    private $access_level;
    private $user_details;
    private $edit_user_options;
    private $user_actions;
    private $dc;
    private $helper;
    private static $user;
    private static $nav_array;

    /**
    * Constructor function for view and edit users
    * @param Templater uTempl: Templater object
    */
    public function __construct(Templater &$uTempl)
    {
        $this->setUser();
        $this->setAccessLevel();
        $this->setNavigation();
        LoggerPrime::info("Viewing user detailspage.  user_id: ".$this->user_id);
        $this->uTemplate = $uTempl;
        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();
        $this->e_user_id = UserData::create('euid')->getInt();
        LoggerPrime::info("Viewing user details  of user : ".$this->e_user_id);
        $pTarget = UserData::create('t')->getString();
        // if the admin wants to change the user role i.e changing "adminstrator" or "staff" or "Student staff" etc
        if ($pTarget == "edit_user_details") {
            $perm_id =  UserData::create('perm_id')->getInt();
            $this->editUserDetails($perm_id);
        }
        // if the admin wants to change user's storage space or file expiration time
        elseif ($pTarget == "edit_user_status") {
            $size =  UserData::create('size')->getInt();
            $days =  UserData::create('days')->getInt();
            $this->editUserStatus($size, $days);
        } elseif ($pTarget == "verify_user") {
            LoggerPrime::info("Verifying  user : ".$this->e_user_id . "Verified by user: ".$this->user_id);
            $this->verifyActions($this->e_user_id);
        } elseif ($pTarget == "block_user" || $pTarget == "unblock_user") {
            LoggerPrime::info("Blocking or unblocking user : ".$this->e_user_id . "Blocked or unblocked by by user: ".$this->user_id);
            $this->blockActions($pTarget, $this->e_user_id);
        }
        // to view the details
        else {
            $this->processUserData();
        }
    }

    /**
    * Render search  users template
    */
    private function renderUserDetailsTemplate()
    {
        $this->uTemplate->setTemplate('user_details.html');
        $this->uTemplate->setVariables('page_title', "User details");
        $this->uTemplate->setVariables('success_messages', Alerts::getSuccessMessages());
        $this->uTemplate->setVariables('error_messages', Alerts::getErrorMessages());
        $this->uTemplate->setVariables('nav_array', self::$nav_array);
        # Set all the jobs for the template
        $this->uTemplate->setVariables('user_details', $this->user_details);
        $this->uTemplate->setVariables('user_actions', $this->user_actions);
        $this->uTemplate->setVariables('edit_user_options', $this->edit_user_options);
        $this->uTemplate->generate();
    }

    /**
    * Sets the user
    */
    private function setUser()
    {
        //lazy loading  user
        if (self::$user == null) {
            self::$user = AuthenticatedUser::getUser();
            $this->user_id = self::$user['user_id'];
        }
    }

    /**
    * Get access level for the user
    */
    private function setAccessLevel()
    {
        $this->access_level = AuthenticatedUser::getUserPermissions();
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
    * Process user data
    */
    private function processUserData()
    {
        $error_messages = array();
        if ($this->access_level == "ADMIN" && isset($this->e_user_id)) {
            $uRow = $this->dc->getUserById($this->e_user_id);
            if (isset($uRow[0])) {
                $perm_id = $uRow[0]['permission_id'];
                $override_id = $uRow[0]['override_perm_id'];
                $override_permissions = array();
                if (!empty($perm_id)) {
                    $pRow = $this->dc->getPermissions($perm_id);
                    // get all unique permissions for dropdown
                    $all_permissions = $this->dc->getAllPermissions();
                }
                if (!empty($override_id)) {
                    $override_permissions = $this->dc->getPermissions($override_id);
                    $override_permissions = (isset($override_permissions[0])) ? $override_permissions[0]: array();
                }
                $this->prepareUserDisplay($uRow[0], $pRow[0], $all_permissions, $override_permissions);
                $this->renderUserDetailsTemplate();
            }
            // if the user does not exist
            else {
                // showing error message on the search results page
                if (isset($_SESSION['query']) &&  isset($_SESSION['previous_page'])) {
                    $error_messages[] = "Sorry this user does not exist.";
                    Alerts::setErrorMessages($error_messages);
                    header('Location: /?t=search_users&q='.$_SESSION['query'].'&page='.$_SESSION['previous_page']);
                }
                // show manage users page
                else {
                    $error_messages[] = "Sorry this user does not exist.";
                    Alerts::setErrorMessages($error_messages);
                    header('Location: /?t=manage_users');
                }
            }
        } else {
            LoggerPrime::info("Access restriced. Trying to view  user details page. user_id: ".$this->user_id);
            $error_messages[] = "Sorry this operation is not allowed";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=home');
        }
    }

    /**
    * Prepare user deatils for display
    * @param array uRow : User details from the db
    * @param array pRow : Permissions details from the db
    * @param array oRow : Override permissions row from the db
    * @param array all_permissions : All available permissions;
    * @param array override_permissions: override permissions row
    */
    public function prepareUserDisplay($uRow, $pRow, $all_permissions, $override_permissions)
    {
        $cf = APP::config();
        // to get affiliation from the configs
        $cf_afiliation = $cf->get("app.affiliation");
        $this->user_details['fullname'] = $uRow['fullname'];
        $this->user_details['lastname'] = $uRow['lastname'];
        $this->user_details['email'] = $uRow['email'];
        $this->user_details['phone_num'] =$this->helper->formatPhoneNumber($uRow['phone_num']);
        $this->user_details['blocked'] = $uRow['blocked'];
        $this->user_details['verified'] = $uRow['verified'];
        $this->user_details['affiliation'] =  $cf_afiliation->get($uRow['affiliation']);
        $this->user_details['department'] =  $uRow['department'];
        $this->edit_user_options['euid'] = $this->e_user_id;
        $this->edit_user_options['status'] = $pRow['group_name'];
        // if override permissions exist use the values from over ride permissions row
        if (!empty($override_permissions)) {
            $this->edit_user_options['expire_after'] = $override_permissions['files_expire_after'];
            $this->edit_user_options['total_allocated_size'] = $this->helper->convertFileSizes($override_permissions['total_allocated_size']);
        }
        // else use values from permissions array
        else {
            $this->edit_user_options['expire_after'] = $pRow['files_expire_after'];
            $this->edit_user_options['total_allocated_size'] = $this->helper->convertFileSizes($pRow['total_allocated_size']);
        }
        foreach ($all_permissions as $k=>$permissions) {
            $this->edit_user_options['all_permissions'][$k]['perm_id'] = $permissions["permission_id"];
            $this->edit_user_options['all_permissions'][$k]['perm_group'] = $permissions["group_name"];
        }
        // Set storage sizes for dropdown
        $cf_storage = $cf->enumerateScope("app.storage");
        foreach ($cf_storage as $k=>$size) {
            $this->edit_user_options['all_sizes'][$k]['size_key'] = $size;
            $this->edit_user_options['all_sizes'][$k]['size_val'] = $this->helper->convertFileSizes($size);
        }
        // Set number of days for dropdown
        $this->edit_user_options['exp_days'][0] = 30;
        $this->edit_user_options['exp_days'][1] = 60;
        $this->edit_user_options['exp_days'][2] = 90;
        $this->edit_user_options['exp_days'][3] = 180;
        $this->edit_user_options['exp_days'][4] = 360;
        // Set user actions
        $this->user_actions['juid'] =$this->e_user_id;
        // Back actions
        if (isset($_SESSION['previous_page'])) {
            $this->user_actions['previous_page'] = $_SESSION['previous_page'];
        }
        // Block and unblock user actions
        if (!empty($uRow['blocked'])) {
            $this->user_actions['block_target']= 'unblock_user';
            $this->user_actions['block_value']= 'Unblock';
        } else {
            $this->user_actions['block_target']= 'block_user';
            $this->user_actions['block_value']= 'Block';
        }
        $this->user_actions['buid']= $this->e_user_id;
    }

    /**
    * Function to edit user details
    * Change the user from to "Adminstrator" or "Staff" or "Student staff" or "Public user"
    * @param int $perm_id : the new permissions id to be set
    */
    public function editUserDetails($perm_id)
    {
        $error_messages = array();
        $success_messages = array();
        $permission_exits = false;
        // check if the suer has the permissions
        if ($this->access_level == "ADMIN" && isset($this->e_user_id) && isset($perm_id)) {
            $uData = array('user_id'=>$this->e_user_id , 'permission_id'=>$perm_id);
            // get all permissions
            $all_permissions = $this->dc->getAllPermissions();
            //check if the permission exists
            foreach ($all_permissions as $k=>$permissions) {
                if ($permissions['permission_id'] == $perm_id) {
                    $permission_exits = true;
                }
            }
            if ($permission_exits) {
                $uRow = $this->dc->updateUsingPrimaryKey('users', 'user_id', $uData);
                if (!empty($uRow)) {
                    $success_messages[] = "Successfully updated user role";
                    Alerts::setSuccessMessages($success_messages);
                }
                // if the user does not exist
                else {
                    // showing error message on the search results page
                    $error_messages[] = "Sorry , update was not successful, please try again.";
                    Alerts::setErrorMessages($error_messages);
                }
            } else {
                $error_messages[] = "This permission is not vaild";
            }
            header("Location: /?t=user_details&euid=".$this->e_user_id);
        }
        // if the user does not exist
        else {
            $error_messages[] = "Sorry this operation is not allowed";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=home');
        }
    }

    /**
    * Edit user options like storage size and file expiration days
    * @param int size : selected size for update
    * @param int days : selected expiration date for update
    */
    public function editUserStatus($size, $days)
    {
        $success_messages = array();
        $error_messages = array();
        if ($this->access_level == "ADMIN" && isset($this->e_user_id) && isset($size) && isset($days)) {
            $uRow = $this->dc->getUserById($this->e_user_id);
            if (!empty($uRow[0]['override_perm_id'])) {
                //update permissions of override_perm_id in permissions table
                $pData = array("permission_id"=> $uRow[0]['override_perm_id'] , "total_allocated_size" =>$size , "files_expire_after" => $days);
                $pRow = $this->dc->updateUsingPrimaryKey('permissions', 'permission_id', $pData);
                if (!empty($pRow)) {
                    $success_messages[] = "Successfully updated user options";
                    Alerts::setSuccessMessages($success_messages);
                } else {
                    $error_messages[] = "Sorry,  update was not successful, please try again.";
                    Alerts::setErrorMessages($error_messages);
                }
            } else {
                // insert row into permissions tabe with values
                $pData = array("total_allocated_size" =>$size , "files_expire_after" => $days);
                $override_perm_id = $this->dc->insertPermissions($pData);
                if (isset($override_perm_id)) {
                    // insert override_perm_id into the user table
                    $uData = array('user_id'=>$this->e_user_id , 'override_perm_id'=>$override_perm_id);
                    $uRow = $this->dc->updateUsingPrimaryKey('users', 'user_id', $uData);
                    if (!empty($uRow)) {
                        $success_messages[] = "Successfully updated user options";
                        Alerts::setSuccessMessages($success_messages);
                    } else {
                        $error_messages[] = "Sorry,  update was not successful, please try again.";
                        Alerts::setErrorMessages($error_messages);
                    }
                } else {
                    $error_messages[] = "Sorry,  update was not successful, please try again.";
                    Alerts::setErrorMessages($error_messages);
                }
            }
            header("Location: /?t=user_details&euid=".$this->e_user_id);
        }
        // if the user does not exist
        else {
            $error_messages[] = "Sorry this operation is not allowed";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=home');
        }
    }

    /**
    * Block a user
    * @param string pTarget : String to determiner to block or unblock a user
    * @param int block_user_id : User if of the user to be blocked
    */
    public function blockActions($pTarget, $block_user_id)
    {
        $error_messages = array();
        $success_messages = array();
        $message = "";
        if ($this->access_level == "ADMIN" && isset($block_user_id)) {
            // if action is to block
            if ($pTarget == "block_user") {
                $bData = array("blocked"=>date("Y-m-d:H-i-s"), "blocked_user_id"=>$this->user_id, "user_id"=>$block_user_id);
                $message = "block";
            } else {
                $bData = array("blocked"=>"NULL", "blocked_user_id"=>"NULL", "user_id"=>$block_user_id);
                $message = "block";
            }
            $uRow = $this->dc->updateUsingPrimaryKey("users", "user_id", $bData);
            if (!empty($uRow)) {
                $success_messages[]= "User is successfully ".$message."ed";
                Alerts::setSuccessMessages($success_messages);
            } else {
                $error_messages[] = "Sorry unable to ".$message." user, please try again";
                Alerts::setErrorMessages($error_messages);
            }
            header("Location: /?t=user_details&euid=".$this->e_user_id);
        }
        // if the user does not exist
        else {
            $error_messages[] = "Sorry this operation is not allowed";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=home');
        }
    }

    /**
    * Verify a user
    * @param int verify_user_id : User if of the user to be verified
    */
    public function verifyActions($verify_user_id)
    {
        $error_messages = array();
        $success_messages = array();
        $message = "";
        if ($this->access_level == "ADMIN" && isset($verify_user_id)) {
            // if action is to block
            $bData = array("verified"=>date("Y-m-d:H-i-s"),  "user_id"=>$verify_user_id);
            $uRow = $this->dc->updateUsingPrimaryKey("users", "user_id", $bData);
            if (!empty($uRow)) {
                $success_messages[]= "User is successfully verified";
                Alerts::setSuccessMessages($success_messages);
            } else {
                $error_messages[] = "Sorry unable to verify user, please try again";
                Alerts::setErrorMessages($error_messages);
            }
            header("Location: /?t=user_details&euid=".$this->e_user_id);
        }
        // if the user does not exist
        else {
            $error_messages[] = "Sorry this operation is not allowed";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=home');
        }
    }
}
