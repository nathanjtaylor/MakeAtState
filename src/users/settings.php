<?php

# Class to update user info like name email ph# and do a password reset

class Settings
{
    private $user_id;
    private $access_level;
    private $sTemplate;
    private $user_details;
    private $user_options;
    private $dc;
    private $helper;
    private static $user;
    private static $nav_array;

    /**
    * Constructor function for user settings
    * @param Templater sTempl : Templater object for manage users  settings class
    */
    public function __construct(Templater &$sTempl)
    {
        $this->setUser();
        $this->setNavigation();
        $this->setAccessLevel();
        LoggerPrime::info("Viewing settings of user with user_id: ".$this->user_id);
        $this->sTemplate = $sTempl;
        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();
        $pTarget = UserData::create('t')->getString();
        // User is atempting to update details
        if ($pTarget == "update_user_details_settings") {
            LoggerPrime::info("User is attempting to update details from settings page. user_id:" .$this->user_id);
            $update_details = array();
            $update_details['name'] =  UserData::create('name')->getString('');
            $update_details['lastname'] =  UserData::create('lastname')->getString('');
            $update_details['email']  =  UserData::create('email')->getString('');
            $update_details['phone']  =  UserData::create('phone')->getString('');
            $update_details['affiliation']  =  UserData::create('affiliation')->getInt(1);
            $update_details['department']  =  UserData::create('department')->getString('');
            $this->updateUserDetails($update_details);
        } elseif ($pTarget == "update_user_pass_settings") {
            LoggerPrime::info("User is attempting to reset password from settings page. user_id:" .$this->user_id);
            $pass_details = array();
            $pass_details['pn'] =  UserData::create('pn')->getString('');
            $pass_details['oldPassword']  =  UserData::create('oldPassword')->getString('');
            $pass_details['newPassword']  =  UserData::create('newPassword')->getString('');
            $pass_details['confirmPassword']  =  UserData::create('cpassword')->getString('');
            $this->passwordReset($pass_details);
        } else {
            $this->prepareDisplay();
        }
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
    * Render manage users template
    */
    private function renderUserSettingsTemplate()
    {
        $this->sTemplate->setTemplate('user_settings.html');
        $this->sTemplate->setVariables('page_title', "User settings");
        $this->sTemplate->setVariables('error_messages', Alerts::getErrorMessages());
        $this->sTemplate->setVariables('success_messages', Alerts::getSuccessMessages());
        $this->sTemplate->setVariables('nav_array', self::$nav_array);
        # Set all the user details for the template
        $this->sTemplate->setVariables('user_details', $this->user_details);
        $this->sTemplate->setVariables('user_options', $this->user_options);
        $this->sTemplate->setVariables('pn', AccessHandler::generatePostNonce());
        $this->sTemplate->generate();
    }

    /**
    * Prepare for display
    * Get user information from db
    */
    public function prepareDisplay()
    {
        $error_messages = array();
        if ($_SESSION['ident']['user_id'] == $this->user_id) {
            $uRow = $this->dc->getUserById($this->user_id);
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
                $this->renderUserSettingsTemplate();
            }
        } else {
            LoggerPrime::info("Access restriced. Trying to access settings page. user_id: ".$this->user_id);
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
        $cf_affiliation = $cf->get("app.affiliation");
        $this->user_details['fullname'] = $uRow['fullname'];
        $this->user_details['lastname'] = $uRow['lastname'];
        $this->user_details['email'] = $uRow['email'];
        $this->user_details['phone_num'] =$this->helper->formatPhoneNumber($uRow['phone_num']);
        $this->user_details['affiliation'] =  $cf_affiliation->get($uRow['affiliation']);
        $this->user_details['affiliation_val'] =  $uRow['affiliation'];
        $this->user_details['department'] =  $uRow['department'];
        $this->user_details['okta_token'] =  $uRow['okta_token'];
        $this->user_details['status'] = $pRow['group_name'];
        // Set storage affiliations for dropdown
        $cf_affi = $cf->enumerateScope("app.affiliation");
        foreach ($cf_affi as $k=>$val) {
            $this->user_details['all_affiliations'][$k]['aff_key'] = $val;
            $this->user_details['all_affiliations'][$k]['aff_val'] = $cf_affiliation->get($val);
        }

        $this->user_options['verified'] = $uRow['verified'];
        // if override permissions exist use the values from over ride permissions row
        if (!empty($override_permissions)) {
            $this->user_options['expire_after'] = $override_permissions['files_expire_after'];
            $this->user_options['total_allocated_size'] = $this->helper->convertFileSizes($override_permissions['total_allocated_size']);
        }
        // else use values from permissions array
        else {
            $this->user_options['expire_after'] = $pRow['files_expire_after'];
            $this->user_options['total_allocated_size'] = $this->helper->convertFileSizes($pRow['total_allocated_size']);
        }
    }
    /**
    * Update details of the user
    * @param array $update_details : Array containg updated values from the form like name , email, phone, affiliation, department
    */
    public function updateUserDetails($update_details)
    {
        $error_messages =array();
        $success_messages = array();
        $cf = APP::config();
        // to get affiliation from the configs
        $cf_affiliation = $cf->get("app.affiliation");
        if ($_SESSION['ident']['user_id'] == $this->user_id) {
            $uRow = $this->dc->getUserByEmail("users", $update_details['email']);
            // check if the email address is taken by another user
            if (!empty($uRow) && $this->user_id !== $uRow[0]['user_id']) {
                LoggerPrime::info("Unable to update email. Email address already exists ".$this->user_id);
                $error_messages[] = "Unable to update user details";
            } else {
                $ph = $update_details['phone'] ? str_replace('-', '', $update_details['phone']) : null;
                $affiliation  = (isset($update_details['affiliation']) && !empty(intval($update_details['affiliation'])) && $cf_affiliation->get(intval($update_details['affiliation'])))?intval($update_details['affiliation']) :1 ;// if un expected value is entered default them to community users
                $update_array = array('email' => $update_details['email'],  'fullname'=>$update_details['name'], 'lastname'=>$update_details['lastname'],  'phone_num' =>$ph,  "affiliation"=> $affiliation, "department"=>$update_details['department'], 'user_id'=>$this->user_id);
                $this->dc->transactionStart();
                $updated = $this->dc->updateUsingPrimaryKey("users", "user_id", $update_array);
                if (!empty($updated)) {
                    //Successful update of user details
                    LoggerPrime::info("User details updated successfully for user_id".$this->user_id . " and email: ". $update_details['email']);
                    //update the name in the session
                    $_SESSION['ident']['fullname'] =  $update_details['name'];
                    $success_messages[] = "Successfully updated user information";
                    $this->dc->transactionCommit();
                } else {
                    LoggerPrime::info("Unable to update user details  for user_id".$this->user_id . " and email: " .$update_details['email']);
                    $error_messages[] = "Sorry, we are unable to update your details, please try again later";
                    $this->dc->transactionRollback();
                }
            }
        } else {
            LoggerPrime::info("Access restriced. Trying to update user details. user_id: ".$this->user_id);
            $error_messages[] = "Sorry this operation is not allowed";
        }
        if (!empty($error_messages)) {
            Alerts::setErrorMessages($error_messages);
        } elseif (!empty($success_messages)) {
            Alerts::setSuccessMessages($success_messages);
        }
        header('Location: /?t=settings');
    }

    /**
    * function to reset passwords, checks the current password of thee user
    * @param array passwordReset : array containing nonce , old password, newPassword and confirm password fields
    */
    public function passwordReset($passwordReset)
    {
        $error_messages = array();
        $success_messages = array();
        if (isset($_SESSION['ident'])) {
            LoggerPrime::info("Trying to reset password. user_id: " .$this->user_id);
            $email = $_SESSION['ident']['email'];
            $uRow = $this->dc->getUserByEmail("users", $email);
            $pnonce = $passwordReset['pn'];
            if (!empty($uRow)) {
                $isAuthenticated = false;
                if ($_SESSION['POST']['pn'] === $pnonce) {
                    $pHash = $uRow[0]['pw_hash'];
                    $isAuthenticated = password_verify($passwordReset['oldPassword'], $pHash);
                } else {
                    LoggerPrime::info("Trying to reset password. Incorrect password nonce. user_id: " .$this->user_id);
                    $error_messages[] = "Sorry unable to reset password, please try again";
                }
                if ($isAuthenticated) {
                    if ($passwordReset['newPassword'] === $passwordReset['confirmPassword']) {
                        $pw_hash = AccessHandler::createPasswordHash($passwordReset['newPassword']);
                        $sData = array("user_id"=>$this->user_id, 'pw_hash'=>$pw_hash);
                        $uRow = $this->dc-> updateUsingPrimaryKey("users", "user_id", $sData);
                        if (!empty($uRow)) {
                            $success_messages[] = "Password reset successful";
                        }
                    } else {
                        $error_messages[] = "Unable to reset password, please ensure that new password anc confirm password fields match.";
                    }
                } else {
                    //incorrect password
                    LoggerPrime::info("Trying to reset password. Incorrect password entered. user_id: " .$this->user_id);
                    $error_messages[] = "Incorrect password, please try again";
                }
            }
        }
        if (!empty($error_messages)) {
            Alerts::setErrorMessages($error_messages);
        } elseif (!empty($success_messages)) {
            Alerts::setSuccessMessages($success_messages);
        }
        header('Location: /?t=settings');
    }
}
