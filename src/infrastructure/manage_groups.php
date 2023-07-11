<?php


class ManageGroups
{
    private $gTemplate;
    private $dc;
    private $helper;

    private $user_id;
    private $access_level;
    private $groups;

    private static $user;
    private static $nav_array;
    private static $upload_ext;
    private static $upload_path;



    /**
     * Constructor function for manage infrastructure
     * @param Templater $iTempl : emplater object for manage infrastructure class
     */
    public function __construct(Templater &$gTempl)
    {
        $this->iTemplate = $gTempl;
        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();

        $this->setUser();
        $this->setNavigation();
        $this->setAccessLevel();
        if (self::$upload_path == null ) {
            $upath  = APP::uploadPath();
            self::$upload_path = rtrim($upath->get("path"), '/') .'/';
        }

        $pTarget = UserData::create('t')->getString();
        $gid = UserData::create('gid')->getInt();


        $success_messages = array();
        $error_messages = array();
        //Check if the user has permissions

        if ($this->access_level == "ADMIN" || $this->access_level == "STAFF") {
            // when the user is updating the group
            if ($pTarget == 'edit_group') {
                $g_name = trim(UserData::create('g_name')->getString());
                $g_email = trim(UserData::create('g_email')->getString());
                $g_tag = trim(UserData::create('g_tag')->getString());
                $this->editGroup($gid, $g_name, $g_email, $g_tag);
            }
            // when the user is deleting the group
            elseif ($pTarget == 'remove_group') {
                $this->deleteGroup($gid);
            }
            // when user undo's a delete
            elseif ($pTarget == 'undo_group_delete') {
                $this->undoDeleteGroup($gid);
            } elseif ($pTarget == 'add_group') {
                $g_email = trim(UserData::create('g_email')->getString());
                $g_name = trim(UserData::create('g_name')->getString());
                $g_tag = trim(UserData::create('g_tag')->getString());
                $this->addGroup($g_name, $g_email, $g_tag);
            } else {
                $this->prepareGroups();
            }
        } else {
            // if the user doesnt have permissions send them to home page
            $error_messages[] = "Sorry this operation is not allowed";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=home');
        }
    }


    /**
     * Render manage workflows template
     */
    private function renderManageGroupsTemplate()
    {
        $this->iTemplate->setTemplate('manage_groups.html');
        $this->iTemplate->setVariables('page_title', "Manage Groups");
        $this->iTemplate->setVariables('error_messages', Alerts::getErrorMessages());
        $this->iTemplate->setVariables('success_messages', Alerts::getSuccessMessages());
        $this->iTemplate->setVariables('nav_array', self::$nav_array);
        $this->iTemplate->setVariables('groups', $this->groups);
        $this->iTemplate->generate();
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
     * Prepare workflows for display
     */
    public function prepareGroups()
    {
        $success_messages = array();
        $error_messages = array();

        $gRows = $this->dc->getGroups();
        if (!empty($gRows)) {
            $this->groups = $gRows;
        }
        $this->renderManageGroupsTemplate();
    }

    /**
     * Edit workflows for 3dprime
     * @param int wid : Group id for the group to be edited
     * @param string w_name: user entered group name
     * @param string w_ext: supported exts for the workflow
     * @param string w_tag: tag for the workflow
     */
    public function editGroup($gid, $g_name, $g_email, $g_tag)
    {
        $success_messages = array();
        $error_messages = array();
        if (!empty($gid) && !empty($g_name) && !empty($g_email) && !empty($g_tag)) {
            if (true) {
                $wTable = "groups";
                $wPrimaryKey = "group_id";
                $aData= array('group_name'=>$g_name, 'removed'=>null);
                //check if the entered workflow already exists
                $name_check = $this->dc->getRowsById($wTable, $aData);
                $aData= array('group_tag'=>$g_tag, 'removed'=>null);
                $tag_check = $this->dc->getRowsById($wTable,$aData);
                // check if the retuned value is empty or belongs to the current workflow that is being edited
                if ((empty($name_check) || ($name_check[0]['group_id'] == $gid)) && (empty($tag_check) || ($tag_check[0]['group_id'] == $gid))) {
                    $wData = array('group_id'=>$gid, 'group_name'=>$g_name,'group_tag'=>$g_tag, 'admin_email'=>$g_email);
                    $wRow = $this->dc->updateUsingPrimaryKey($wTable, $wPrimaryKey, $wData);
                    // check if update is successful
                    if (!empty($wRow)) {
                        $success_messages[] = "Successfully updated group.";
                        Alerts::setSuccessMessages($success_messages);
                    } else {
                        $error_messages[] = "Sorry, we are unable to update the group. please try again later.";
                    }
                } else {
                    $error_messages[] = "A group with the same name or tag already exists, please use a different name.";
                }
            } else {
            }
        } else {
            $error_messages[] = "Sorry, we are unable to update the group. please try again later.";
        }
        Alerts::setErrorMessages($error_messages);
        header('Location: /?t=manage_groups');
    }

    /**
     * Add workflows for 3dprime, only staff and admin have permissions for this action
     * @param string w_name: user entered workflow name
     * @param string w_ext: supported exts for the workflow
     * @param string w_ext: supported exts for the workflow
     */
    public function addGroup($g_name, $g_email, $g_tag)
    {
        $success_messages = array();
        $error_messages = array();
        // check if workflow id and new workflow name are set
        if (!empty($g_name) && !empty($g_email)) {
            // check if the ext's entered are vaild
            // seralize_allowed_exts function returns an array with a status and (data or message) keys . The status is either success or error , the data is serialized array and the message is  an error message.
            if (true) {
                $aData= array( '$group_tag'=>$g_tag, 'removed'=>null);
                //check if the entered workflow already exists
                $wTable = "groups";
                $check = $this->dc->getRowsById($wTable, $aData);
;
                if (!empty($check)) {
                    $error_messages[] = "This group already exists.";
                    Alerts::setErrorMessages($error_messages);
                } else {
                    $wData= array('group_name'=>$g_name, 'admin_email'=>$g_email, 'group_tag'=>$g_tag, 'removed'=>null);

                    $wRow = $this->dc->insertGroups($wData);

                    if (!empty($wRow)) {
                        $success_messages[] = "Successfully added a new group.";
                        Alerts::setSuccessMessages($success_messages);
                    } else {
                        $error_messages[] = "Sorry we are unable to add a new group, please try again later.";
                        Alerts::setErrorMessages($error_messages);
                    }
                }
            }
        } else {
            $error_messages[] = "Sorry we are unable to add a new group, please try again later.";
            Alerts::setErrorMessages($error_messages);
        }
        header('Location: /?t=manage_groups');
    }

    /**
     * Delete a workflow , only staff and admins ahve permissions
     * @param int wid: Workflow id of the workflow that needs to be deleted
     */
    public function deleteGroup($gid)
    {
        $success_messages = array();
        $error_messages = array();
        //check if workflow id is empty
        if (!empty($gid)) {
            $wTable = "groups";
            $wPrimaryKey = "group_id";
            $wData = array('group_id'=>$gid, 'removed'=>date('Y:m:d-H:i:s'));
            $wRow = $this->dc->updateUsingPrimaryKey($wTable, $wPrimaryKey, $wData);
            // check if delete is successful
            if (!empty($wRow)) {
                $success_messages[] = 'Successfully deleted group. <a href="/?t=undo_group_delete&gid='.$gid.'">Undo</a>';
                Alerts::setSuccessMessages($success_messages);
            } else {
                $error_messages[] = "Sorry we are unable to delete the group, please try again later.";
                Alerts::setErrorMessages($error_messages);
            }
        } else {
            $error_messages[] = "Sorry we are unable to delete the group, please try again later.";
            Alerts::setErrorMessages($error_messages);
        }
        header('Location: /?t=manage_groups');
    }

    /**
     * Undo a deleted   workflow , only staff and admins ahve permissions
     * @param int wid: Workflow id of the workflow that needs to undo delete
     */
    public function undoDeleteGroup($gid)
    {
        $success_messages = array();
        $error_messages = array();
        //check if workflow id is empty
        if (!empty($gid)) {
            $wTable = "groups";
            $wPrimaryKey = "group_id";
            $wData = array('group_id'=>$gid, 'removed'=>null);
            $wRow = $this->dc->updateUsingPrimaryKey($wTable, $wPrimaryKey, $wData);
            // check if delete is successful
            if (!empty($wRow)) {
                $success_messages[] = "Undo operation successful";
                Alerts::setSuccessMessages($success_messages);
            } else {
                $error_messages[] = "Sorry we are unable to undo, please try again later.";
                Alerts::setErrorMessages($error_messages);
            }
        } else {
            $error_messages[] = "Sorry we are unable to undo, please try again later.";
            Alerts::setErrorMessages($error_messages);
        }
        header('Location: /?t=manage_groups');
    }

}