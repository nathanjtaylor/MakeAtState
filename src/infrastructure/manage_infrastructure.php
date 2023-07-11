<?php

# Manage infrastructure for 3DPrime

class ManageInfrastructure
{
    private $iTemplate;
    private $dc;
    private $helper;

    private $user_id;
    private $access_level;
    private $workflows;
    private $groups;
    
    private static $user;
    private static $nav_array;
    private static $upload_ext;
    private static $upload_path;



    /**
    * Constructor function for manage infrastructure
    * @param Templater $iTempl : emplater object for manage infrastructure class
    */
    public function __construct(Templater &$iTempl)
    {
        $this->iTemplate = $iTempl;
        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();
        
        $this->setUser();
        $this->setNavigation();
        $this->setAccessLevel();
        if (self::$upload_path == null || self::$upload_ext == null) {
            $upath  = APP::uploadPath();
            self::$upload_path = rtrim($upath->get("path"), '/') .'/';
            self::$upload_ext = $upath->getArray('ext');
        }
        
        $pTarget = UserData::create('t')->getString();
        $wid = UserData::create('wid')->getInt();
        $w_data = array();
    
        $success_messages = array();
        $error_messages = array();
        //Check if the user has permissions
        if ($this->access_level == "ADMIN" || $this->access_level == "STAFF") {
            // when the user is updating the workflow
            if ($pTarget == 'edit_workflow') {
                $w_data['name'] = trim(UserData::create('w_name')->getString());
                $w_data['ext'] = trim(UserData::create('w_ext')->getString());
                $w_data['tag']= trim(UserData::create('w_tag')->getString());
                $w_data['group'] = trim(UserData::create('w_group')->getString());
                $w_data['disabled'] = UserData::create('w_disabled')->getInt(0);

                $this->editWorkflows($wid, $w_data);
            }
            // when the user is deleting the workflow
            elseif ($pTarget == 'remove_workflow') {
                $this->deleteWorkflow($wid);
            }
            // when user undo's a delete
            elseif ($pTarget == 'undo_workflow_delete') {
                $this->undoDeleteWorkflow($wid);
            } elseif ($pTarget == 'add_workflow') {
                $w_data['name'] = trim(UserData::create('w_name')->getString());
                $w_data['ext'] = trim(UserData::create('w_ext')->getString());
                $w_data['tag']= trim(UserData::create('w_tag')->getString());
                $w_data['group'] = trim(UserData::create('w_group')->getString());
                $w_data['disabled'] = UserData::create('w_disabled')->getInt(0);

                $this->addWorkflow($w_data);
            } else {
                $this->prepareWorkflows();
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
    private function renderManageInfrastructureTemplate()
    {
        $this->iTemplate->setTemplate('manage_infrastructure.html');
        $this->iTemplate->setVariables('page_title', "Manage Infrastructure");
        $this->iTemplate->setVariables('error_messages', Alerts::getErrorMessages());
        $this->iTemplate->setVariables('success_messages', Alerts::getSuccessMessages());
        $this->iTemplate->setVariables('nav_array', self::$nav_array);
        //Set variables for workflows
        $this->iTemplate->setVariables('workflows', $this->workflows);
        $this->iTemplate->setVariables('groups', $this->groups);

        $this->iTemplate->addTwigFilter('displayExtentions');
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
    public function prepareWorkflows()
    {
        $success_messages = array();
        $error_messages = array();
    
        $wRows = $this->dc->getWorkflows($hide_disabled=False);
        if (!empty($wRows)) {
            $this->workflows = $wRows;
        }
        $this->groups = $this->dc->getGroups();

        $this->renderManageInfrastructureTemplate();
    }


    /**
    * Edit workflows for 3dprime
    * @param int wid : Workflow id for the workflow to be edited
    * @param arry w_data: workflowdata from the form
    */
    public function editWorkflows($wid, $w_data)
    {
        $success_messages = array();
        $error_messages = array();
        // check if workflow id and new workflow name are set
        if (!empty($wid) && !empty($w_data['name']) && !empty($w_data['ext'])) {
            // check if the ext's entered are vaild
            // seralize_allowed_exts function returns an array with a status and (data or message) keys . The status is either success or error , the data is serialized array and the message is  an error message.
            $allowed_exts = $this->seralize_allowed_exts($w_data['ext']);
            if ($allowed_exts['status']  == 'success') {
                $wTable = "workflows";
                $wPrimaryKey = "workflow_id";
                $aData= array('name'=>$w_data['name'], 'workflow_removed'=>null);
                //check if the entered workflow already exists
                $name_check = $this->dc->getRowsById($wTable, $aData);
                // check if the retuned value is empty or belongs to the current workflow that is being edited
                if (empty($check) || ($check[0]['workflow_id'] == $wid)) {
                    $w_data['tag'] = !empty($w_data['tag'])?$w_data['tag']:null;
                    $wData = array('workflow_id'=>$wid, 'name'=>$w_data['name'], 'allowed_ext_data'=>$allowed_exts['data'],'workflow_tag'=>$w_data['tag'], 'group_id'=>$w_data['group']);
                    $wData['disabled'] = !empty($w_data['disabled']) ? $w_data['disabled']: 0;
                    $wRow = $this->dc->updateUsingPrimaryKey($wTable, $wPrimaryKey, $wData);
                    // check if update is successful
                    if (!empty($wRow)) {
                        $success_messages[] = "Successfully updated workflow.";
                        Alerts::setSuccessMessages($success_messages);
                    } else {
                        $error_messages[] = "Sorry we are unable to update the workflow, the database operation has failed.";
                    }
                } else {
                    $error_messages[] = "A workflow with the same name already exists, please use a different name.";
                }
            } else {
                $error_messages[] = $allowed_exts['message'];
            }
        } else {
            $error_messages[] = "Sorry we are unable to update the workflow, please try again later.";
        }
        Alerts::setErrorMessages($error_messages);
        header('Location: /?t=manage_infrastructure');
    }

    /**
    * Add workflows for 3dprime, only staff and admin have permissions for this action
    * @param arry w_data: workflowdata from the form
    */
    public function addWorkflow($w_data)
    {
        $success_messages = array();
        $error_messages = array();
        // check if workflow id and new workflow name are set
        if (!empty($w_data['name']) && !empty($w_data['ext'])) {
            // check if the ext's entered are vaild
            // seralize_allowed_exts function returns an array with a status and (data or message) keys . The status is either success or error , the data is serialized array and the message is  an error message.
            $allowed_exts = $this->seralize_allowed_exts($w_data['ext']);
            if ($allowed_exts['status']  == 'success') {
                $aData= array('name'=>$w_data['name'], 'workflow_removed'=>null);
                //check if the entered workflow already exists
                $wTable = "workflows";
                $check = $this->dc->getRowsById($wTable, $aData);
                $w_data['tag'] = !empty($w_data['tag'])?$w_data['tag']:null;
                $wData = array('name'=>$w_data['name'],'allowed_ext_data'=>$allowed_exts['data'],  'workflow_removed'=>null, 'workflow_tag'=>$w_data['tag'], 'group_id'=>$w_data['group']);
                $wData['disabled'] = !empty($w_data['disabled']) ? $w_data['disabled']: null;

                // check if workflow addition is successful
                $cRow = $this->dc->getRowsById('workflows', $wData);
                if (!empty($cRow)) {
                    $error_messages[] = "This workflow already exists. Use the edit buttion to make changes to the workflow";
                    Alerts::setErrorMessages($error_messages);
                } else {
                    $wRow = $this->dc->insertWorkflows($wData);
                
                    if (!empty($wRow)) {
                        $success_messages[] = "Successfully added a new workflow.";
                        Alerts::setSuccessMessages($success_messages);
                    } else {
                        $error_messages[] = "Sorry we are unable to add a new  workflow, please try again later.";
                        Alerts::setErrorMessages($error_messages);
                    }
                }
            } else {
                $error_messages[] = $allowed_exts['message'];
            }
        } else {
            $error_messages[] = "Sorry we are unable to add a new workflow, please try again later.";
            Alerts::setErrorMessages($error_messages);
        }
        header('Location: /?t=manage_infrastructure');
    }

    /**
    * Delete a workflow , only staff and admins ahve permissions
    * @param int wid: Workflow id of the workflow that needs to be deleted
    */
    public function deleteWorkflow($wid)
    {
        $success_messages = array();
        $error_messages = array();
        //check if workflow id is empty
        if (!empty($wid)) {
            $wTable = "workflows";
            $wPrimaryKey = "workflow_id";
            $wData = array('workflow_id'=>$wid, 'workflow_removed'=>date('Y:m:d-H:i:s'));
            $wRow = $this->dc->updateUsingPrimaryKey($wTable, $wPrimaryKey, $wData);
            // check if delete is successful
            if (!empty($wRow)) {
                $success_messages[] = 'Successfully deleted workflow. <a href="/?t=undo_workflow_delete&wid='.$wid.'">Undo</a>';
                Alerts::setSuccessMessages($success_messages);
            } else {
                $error_messages[] = "Sorry we are unable to delete the workflow, please try again later.";
                Alerts::setErrorMessages($error_messages);
            }
        } else {
            $error_messages[] = "Sorry we are unable to delete the workflow, please try again later.";
            Alerts::setErrorMessages($error_messages);
        }
        header('Location: /?t=manage_infrastructure');
    }

    /**
    * Undo a deleted   workflow , only staff and admins ahve permissions
    * @param int wid: Workflow id of the workflow that needs to undo delete
    */
    public function undoDeleteWorkflow($wid)
    {
        $success_messages = array();
        $error_messages = array();
        //check if workflow id is empty
        if (!empty($wid)) {
            $wTable = "workflows";
            $wPrimaryKey = "workflow_id";
            $wData = array('workflow_id'=>$wid, 'workflow_removed'=>null);
            $wRow = $this->dc->updateUsingPrimaryKey($wTable, $wPrimaryKey, $wData);
            // check if delete is successful
            if (!empty($wRow)) {
                $success_messages[] = "Undo operation successful";
                Alerts::setSuccessMessages($success_messages);
            } else {
                $error_messages[] = "Sorry we are unable to undo your delete on  the workflow, please try again later.";
                Alerts::setErrorMessages($error_messages);
            }
        } else {
            $error_messages[] = "Sorry we are unable to undo your delete on  the workflow, please try again later.";
            Alerts::setErrorMessages($error_messages);
        }
        header('Location: /?t=manage_infrastructure');
    }

    /**
    * Seralize the comma seperated extensions
    * @param string w_ext: supported exts for the workflow
    */
    private function seralize_allowed_exts($w_ext)
    {
        $input_exts = explode(',', $w_ext);
        $allowed_exts = array();
        $error_exts =  array();
        // check if the exts are vaild
        foreach ($input_exts as $ext) {
            if (in_array(trim(strtolower($ext)), self::$upload_ext)) {
                $allowed_exts[] = trim(strtolower($ext));
            } else {
                $error_exts[] = $ext;
            }
        }
        if (!empty($error_exts)) {
            $message= "Sorry we are unable to perform the workflow operations. Unrecognized file extensions provided. Please fix extensions ";
            foreach ($error_exts as $ext) {
                $message .= $ext;
            }
            return array("status" => "error", "message"=>$message);
        } else {
            return array("status" => "success", "data" =>serialize($allowed_exts));
        }
    }
}
