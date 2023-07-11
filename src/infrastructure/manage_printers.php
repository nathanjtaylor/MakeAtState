<?php

// Manage printers infrastructure for 3DPrime

class ManagePrinters
{

    private $pTemplate;
    private $dc;
    private $helper;    
    private $type; // name of the workflow 
    private $workflow_id;
    private $user_id;
    private $access_level;
    private $printers;
    static private $user;
    static private $nav_array;


    /**
    * Constructor function for manage printers
     *
    * @param Templater $pTempl : Templater object for manage printer class
    */
    public function __construct(Templater &$pTempl)
    {
        $this->pTemplate = $pTempl;
        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();
        $this->setUser();
        $this->setNavigation();
        $this->setAccessLevel();
        $pTarget = UserData::create('t')->getString();
        $this->workflow_id = UserData::create('wid')->getInt();
        $old_printer_name = UserData::create('printer')->getString();
        $new_printer_name = trim(UserData::create('p_name')->getString());
        $success_messages = array();
        $error_messages = array();
        //Check if the user has permissions         
        if($this->access_level == "ADMIN" || $this->access_level == "STAFF") {
            // when the user is updating the printer name
            if($pTarget == 'update_printer') {
                $this->updatePrinter($old_printer_name, $new_printer_name);
            }
            // when the user is deleting printer
            elseif($pTarget == 'remove_printer') {
                $this->deleteprinter($old_printer_name);
            }
            // when user undo's a delete printer action
            elseif($pTarget == 'undo_remove_printer') {
                $this->undoDeleteWorkflow($old_printer_name);
            }
            // when users are adding new printer for the workflow
            elseif($pTarget == 'add_printer') {
                $this->addPrinter($new_printer_name);
            }else{
                $this->preparePrinters();
            }
        }else{
            // if the user doesnt have permissions send them to home page 
            $error_messages[] = "Sorry this operation is not allowed";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=home');
        }
    }


    /**
    * Render manage printer template
    */
    private function renderManagePrintersTemplate()
    {
        $this->pTemplate->setTemplate('manage_printers.html');
        $this->pTemplate->setVariables('page_title', "Manage Printers");
        $this->pTemplate->setVariables('error_messages', Alerts::getErrorMessages());
        $this->pTemplate->setVariables('success_messages', Alerts::getSuccessMessages());
        $this->pTemplate->setVariables('nav_array', self::$nav_array);    
        //Set varibales for printers
        $this->pTemplate->setVariables('workflow_id', $this->workflow_id);    
        $this->pTemplate->setVariables('type', $this->type);    
        $this->pTemplate->setVariables('printers', $this->printers);    
        $this->pTemplate->generate();
    }


    /**
    * Sets the user 
    */
    private function setUser()
    {
        //lazy loading  user
        if(self::$user == null) {
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

        if(self::$nav_array == null) {
            self::$nav_array = AuthenticatedUser::getUserNavBar();
        }
    }
    
    /**
    * Prepare workflows for display
    */
    public function preparePrinters()
    {
        $success_messages = array();
        $error_messages = array();
        $wTable = "workflows";
        $wData = array("workflow_id"=>$this->workflow_id);
        $wRows = $this->dc->getRowsById($wTable, $wData);
        // if workflow doesnot exist
        if(!empty($wRows)) {

            $workflows = array();
            // Unserialize the data in workflows
            foreach ($wRows as $key=>$wf){
                // Add workflow only if they are printers
                if(!empty(unserialize($wf["data"]))) {
                    $workflows[$wf["name"]] = unserialize($wf["data"]); 
                }
            }
            //key is workflow type , printers is an array of printers 
            foreach($workflows as $type=>$printers){
                $this->type = $type;
                //printer is the name of the printer, values consist of materials and colors
                foreach($printers as $printer=>$values){
                    $this->printers[] = $printer;
                }
            }
            $this->renderManagePrintersTemplate();    
        }
        else{
            $error_messages[]="Sorry the workflow does not exist";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=manage_infrastructure');
            
        }
        

    }

    /**
    * Update the name of the printer , admins and staff have access
     *
    * @param string old_printer_name: Old name of the printer
    * @param string new_printer_name: New name of the printer
    */
    public function updatePrinter($old_printer_name, $new_printer_name)
    {
        $success_messages = array();
        $error_messages = array();
        if(!empty($old_printer_name) && !empty($new_printer_name)) {
            $wTable = "workflows";
            $wData = array("workflow_id"=>$this->workflow_id);
            $wRows = $this->dc->getRowsById($wTable, $wData);
            $new_printer_data = array();
            // if workflow  exists
            if(!empty($wRows)) {
                $printer_data = unserialize($wRows[0]["data"]);
                
                // loop through the printer_data, key is the name name of the printer, values are materials and colors
                foreach($printer_data as $key=>$values){
                    // if new printer name already exists 
                    if(strtolower($key) == strtolower($new_printer_name) && $new_printer_name != $old_printer_name) {
                        //empty new printer array
                        $new_printer_data =array();
                        $error_messages[] = "Sorry the entered printer name already exists";
                    
                        break;
                    }

                    // if old printer name is key update it with the new name

                    if($key == $old_printer_name) {
                        $new_printer_data[$new_printer_name] = $values;
                    }else{
                        $new_printer_data[$key] = $values;
                    }
                }
                // serialize the data to store in db
                if(!empty($new_printer_data)) {
                    $this->dc->transactionStart();
                    $printer_data = serialize($new_printer_data);
                    // Store the new printer name in db
                    $uData = array('workflow_id'=>$this->workflow_id, 'data'=>$printer_data); 
                    $updated_workflow_row = $this->dc-> updateUsingPrimaryKey('workflows', 'workflow_id', $uData);
                    
                    //Update the workflow_step tables for the printer name
                    if(!empty($updated_workflow_row)) {
                        // check if there are workflow steps for the printer 
                        $checkData =  array('workflow_id'=>$this->workflow_id, 'printer_name'=>$old_printer_name);
                        $checkWorkflowSteps  = $this->dc->getRowsById('workflow_steps', $checkData);
                        if(!empty($checkWorkflowSteps)) {
                            $pValues =  array($new_printer_name, $this->workflow_id , $old_printer_name);    
                            $updated_workflow_steps = $this->dc->updatePrinterInWorkflowSteps($pValues);
                            (!empty($updated_workflow_steps)) ? $success_messages[]= "Successfully updated printer name"  : $error_messages[]="Sorry unbale to update the printer name.";
                        }else{
                            $success_messages[]= "Successfully updated printer name"; 

                        }
                    }

                    else{
                        $error_messages[]="Sorry unbale to update the printer name.";
                        
                    }
                }
            }else{
                $error_messages[]="Sorry unbale to update the printer name.";
            
            }
        }else{
            $error_messages[]="Sorry unbale to update the printer name.";

        }

        if(!empty($success_messages)) {
            $this->dc->transactionCommit();
            Alerts::setSuccessMessages($success_messages);
                        


        }else{
            Alerts::setErrorMessages($error_messages);
            $this->dc->transactionRollback();

        }
        header('Location: /?t=manage_printers&wid='.$this->workflow_id);
    }

    /**
    * Add a new a printer from workflow , 
     *
    * @param string new_printer_name : Name of the printer to be add
    */
    public function addPrinter($new_printer_name)
    {
        $error_messages = array();
        $success_messages = array();
        // if workflow id is empty
        if(!empty($this->workflow_id)) {
            //get printer from db
            $wTable = "workflows";
            $wData = array("workflow_id"=>$this->workflow_id);
            $wRows = $this->dc->getRowsById($wTable, $wData);
            $printer_exists = false;
            // if workflow  exists
            if(!empty($wRows)) { 
                if(!empty($new_printer_name)) {
                    $workflow_name = $wRows[0]['name'];
                    $printer_data = unserialize($wRows[0]["data"]);
                    if (!empty($printer_data)) {
                        // loop through the printer_data, key is the name name of the printer, values are materials and colors
                        foreach($printer_data as $key=>$values){
                            // check if the printer already exists
                            if(strtolower($key) == strtolower($new_printer_name)) {
                                $printer_exists = true;
                                break;
                            }
                        }
                    }
                    if($printer_exists == true) {
                        $error_messages[] = "A printer with same name already exists";
                    }else{
                        $printer_data[$new_printer_name] = array();    
                        $printer_data = serialize($printer_data);
                        $uData = array('workflow_id'=>$this->workflow_id, 'data'=>$printer_data); 
                        // remove from data in workflow table
                        $updated_workflow_row = $this->dc-> updateUsingPrimaryKey('workflows', 'workflow_id', $uData);
                        //no need to mark as removed in workflow_steps table, as existing jobs for that deleted printer should work
                        //Once all jobs for the deleted printer are completed there is no way to start a new job for the printer so workflow steps will never be used

                        //check if deletion is succesful
                        if(!empty($updated_workflow_row)) {
                            $success_messages[] = 'Sucessfully added printer '. $new_printer_name . ' from workflow '. $workflow_name ;
                            Alerts::setSuccessMessages($success_messages);
                        }else{
                            $error_message[] = 'Sorry unable to add a new printer'; 
                        }
                        
                    }
                    // serialize to store in the db
                }else{
                    $error_messages[] = 'Sorry unable to add a new printer ';
                }
                Alerts::setErrorMessages($error_messages);
                header('Location: /?t=manage_printers&wid='.$this->workflow_id);    

            }else{ // if workflow doesnot exist
                $error_messages[]="Sorry the workflow does not exist";
                Alerts::setErrorMessages($error_messages);
                header('Location: /?t=manage_infrastructure');

                
            }
            

            


        }else{ // if workflow_id is empty
            $error_messages[]="Sorry the workflow does not exist";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=manage_infrastructure');
            
                    
        }
    }

    /**
    * Delete a printer from workflow , 
     *
    * @param string delete_printer_name : Name of the printer to be deleted
    */
    public function deletePrinter($delete_printer_name)
    {
        $error_messages = array();
        $success_messages = array();
        // if workflow id is empty
        if(!empty($this->workflow_id)) {
            //get printer from db
            $wTable = "workflows";
            $wData = array("workflow_id"=>$this->workflow_id);
            $wRows = $this->dc->getRowsById($wTable, $wData);
            // if workflow  exists
            if(!empty($wRows)) { 
                if(!empty($delete_printer_name)) {
                    $workflow_name = $wRows[0]['name'];
                    $printer_data = unserialize($wRows[0]["data"]);
                    
                    // loop through the printer_data, key is the name name of the printer, values are materials and colors
                    foreach($printer_data as $key=>$values){

                        if($key == $delete_printer_name) {
                            //set it in the session for undo                    
                            $_SESSION['deleted_printer'][$key] = $values;
                            // unset deleted printer from the array before serializing
                            unset($printer_data[$key]);
                        }
                    }
                    // serialize to store in the db
                    $printer_data = serialize($printer_data);
                    $uData = array('workflow_id'=>$this->workflow_id, 'data'=>$printer_data); 
                    // remove from data in workflow table
                    $updated_workflow_row = $this->dc-> updateUsingPrimaryKey('workflows', 'workflow_id', $uData);
                    //no need to mark as removed in workflow_steps table, as existing jobs for that deleted printer should work
                    //Once all jobs for the deleted printer are completed there is no way to start a new job for the printer so workflow steps will never be used

                    //check if deletion is succesful
                    if(!empty($updated_workflow_row)) {
                        $success_messages[] = 'Sucessfully deleted printer '. $delete_printer_name . ' from workflow '. $workflow_name . ' <a href ="/?t=undo_remove_printer&printer='.$delete_printer_name.'&wid='.$this->workflow_id.'">Undo</a>' ;
                        Alerts::setSuccessMessages($success_messages);
                    }else{
                        $error_message[] = 'Sorry unable to delete printer '. $delete_printer_name . ' from workflow '. $workflow_name  ;
                    }
                }else{
                    $error_messages[] = 'Sorry unable to delete printer ';
                }
                Alerts::setErrorMessages($error_messages);
                header('Location: /?t=manage_printers&wid='.$this->workflow_id);    

            }else{ // if workflow doesnot exist
                $error_messages[]="Sorry the workflow does not exist";
                Alerts::setErrorMessages($error_messages);
                header('Location: /?t=manage_infrastructure');

                
            }
            

            


        }else{ // if workflow_id is empty
            $error_messages[]="Sorry the workflow does not exist";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=manage_infrastructure');
            
                    
        }
    }

    /**
    * Undo delete of a printer from workflow , 
     *
    * @param string undo_printer_name : Name of the printer to undo delete
    */
    
    public function undoDeleteWorkflow($undo_printer_name)
    {
        $error_messages = array();
        $success_messages = array();
        // if workflow id is empty
        if(!empty($this->workflow_id)) {
            //get printer from db
            $wTable = "workflows";
            $wData = array("workflow_id"=>$this->workflow_id);
            $wRows = $this->dc->getRowsById($wTable, $wData);
            // if workflow  exists
            if(!empty($wRows)) { 
                if(!empty($undo_printer_name) && isset($_SESSION["deleted_printer"][$undo_printer_name])) {
                
                    $workflow_name = $wRows[0]['name'];
                    $printer_data = unserialize($wRows[0]["data"]);

                    //put the deleted printer back in the array
                    $printer_data[$undo_printer_name] = $_SESSION["deleted_printer"][$undo_printer_name];
                    // unset it from session
                    unset($_SESSION["deleted_printer"]);
                    // serialize to store in the db
                    $printer_data = serialize($printer_data);
                    
                    $uData = array('workflow_id'=>$this->workflow_id, 'data'=>$printer_data); 
                    // update data in workflow table
                    $updated_workflow_row = $this->dc-> updateUsingPrimaryKey('workflows', 'workflow_id', $uData);

                    //check if undo is succesful
                    if(!empty($updated_workflow_row)) {
                        $success_messages[] = 'Undo action successful '. $undo_printer_name . ' printer was put back in workflow '. $workflow_name; 
                        Alerts::setSuccessMessages($success_messages);
                    }else{
                        $error_messages[] = 'Sorry unable to perform an undo action ';
                    
                    }
                }else{
                    $error_messages[] = 'Sorry unable to perform an undo action ';
                }
                Alerts::setErrorMessages($error_messages);

                header('Location: /?t=manage_printers&wid='.$this->workflow_id);    

            }else{ // if workflow doesnot exist
                $error_messages[]="Sorry the workflow does not exist";
                Alerts::setErrorMessages($error_messages);
                header('Location: /?t=manage_infrastructure');

                
            }
            

            


        }else{ // if workflow_id is empty
            $error_messages[]="Sorry the workflow does not exist";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=manage_infrastructure');
            
                    
        }
                
    }

}

?>
