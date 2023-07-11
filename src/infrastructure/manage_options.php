<?php

# Manage material and colors for 3DPrime printers 

class ManageOptions{

	private $oTemplate;
	private $dc;
	private $helper;

	private $user_id;
	private $access_level;
	private $workflows_id; // flow flow id for this printer
	private $printer_name; // printer name
	private $workflow_name ; // name of the workflow
	private $printer_data ; // array of materials and colors for the printer 
	
	static private $user;
	static private $nav_array;


	/**
	* Constructor function for manage workflow steps
	* @param Templater $iTempl : emplater object for manage workflow steps class
	*/
	public function __construct(Templater &$sTempl){
		$this->oTemplate = $sTempl;
		$this->dc = new DataCalls();
		$this->helper = new PrimeHelper();
		
		$this->setUser();
		$this->setNavigation();
		$this->setAccessLevel();
		
		$pTarget = UserData::create('t')->getString();
		$this->workflow_id = UserData::create('wid')->getInt();
		$this->printer_name = UserData::create('printer')->getString();

		$material = UserData::create('m_name')->getString("");
		$color = UserData::create('c_name')->getString("");

		$success_messages = array();
		$error_messages = array();
		//Check if the user has permissions 		
		if($this->access_level == "ADMIN" || $this->access_level == "STAFF"){
			// when the user is updating the printer name
			if($pTarget == 'edit_printer_options'){
				$new_material = trim(UserData::create('m_new_name')->getString());
				$new_color = trim(UserData::create('c_new_name')->getString());
				$this->updatePrinterOptions($material, $color, $new_material, $new_color);
			}
			// when the user is deleting options
			elseif($pTarget == 'remove_options'){
				$this->deleteOptions($material,$color);
			}
			// when user undo's a delete printer options
			elseif($pTarget == 'undo_remove_options'){
				$this->undoDeleteOptions();
			}
			// when user is adding new printer options for the workflow
			elseif($pTarget == 'add_printer_options'){
				$new_material = trim(UserData::create('m_new_name')->getString());
				$new_color = trim(UserData::create('c_new_name')->getString());
				$this->updatePrinterOptions($material, $color, $new_material, $new_color, $add=TRUE);
            }
            // when users are adding price options like price per gram/min etc.
            elseif($pTarget == 'add_price_options'){
                $price_options_key = trim(UserData::create('price_options_key')->getString());
                $price_options_value = trim(UserData::create('price_options_value')->getFloat());
                $this->addPriceOptions($material, $color,  $price_options_key, $price_options_value);
            }
            // when user is removing a price options
            elseif($pTarget == 'remove_price_options'){
                $price_options_key = trim(UserData::create('price_options_key')->getString());
                $this->removePriceOptions($material, $color,  $price_options_key);
			}else{
				$this->processPrinterOptions();	
			}
		}else{
			// if the user doesnt have permissions send them to home page 
			$error_messages[] = "Sorry this operation is not allowed";
			Alerts::setErrorMessages($error_messages);
			header('Location: /?t=home');

		}

		
	}

	/**
	* Render manage workflows steps template
	*/
	private function renderManageOptionsTemplate(){
		$this->oTemplate->setTemplate('manage_options.html');
		$this->oTemplate->setVariables('page_title', "Manage workflow steps");
		$this->oTemplate->setVariables('error_messages' , Alerts::getErrorMessages());
		$this->oTemplate->setVariables('success_messages' , Alerts::getSuccessMessages());
		$this->oTemplate->setVariables('nav_array', self::$nav_array);	
		//Set varibales for workflow_steps
		$this->oTemplate->setVariables('workflow_id', $this->workflow_id);	
		$this->oTemplate->setVariables('printer_name', $this->printer_name);	
		$this->oTemplate->setVariables('printer_data', $this->printer_data);	
		$this->oTemplate->generate();
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
	* Sets the navigation for the page
	*/

	private function setNavigation(){

		if(self::$nav_array == null){
			self::$nav_array = AuthenticatedUser::getUserNavBar();
		}
	}

	/**
	* process material and color options for display
	*/
	public function processPrinterOptions(){
		$success_messages = array();
		$error_messages = array();
		// check if workflow_id or  printer name are empty
		 if(!empty($this->workflow_id) && !empty($this->printer_name)){  
			$wData = array('workflow_id'=>$this->workflow_id);
			$wRows = $this->dc->getRowsById('workflows' , $wData);
			if(!empty($wRows)){
				$printer_data = unserialize($wRows[0]['data']);
				if(isset($printer_data[$this->printer_name])){
					$this->printer_data = $printer_data[$this->printer_name];
					$this->renderManageOptionsTemplate();
					
				}else{
					$error_messages[] = "Sorry the selected printer doesnot exist";
					Alerts::setErrorMessages($error_messages);
					header('Location: /?t=manage_printers&wid='.$this->workflow_id);
				}
			}else{
				 $error_messages[] = "Sorry, invalid workflow or printer";
				Alerts::setErrorMessages($error_messages);
				header('Location: /?t=manage_infrastructure');

			}


		 }else{
			$error_messages[] = "Sorry, invalid workflow or printer";
			Alerts::setErrorMessages($error_messages);
			header('Location: /?t=manage_infrastructure');
			
		 }
	
	}

	/**
	* edit and update printer materials
	* @param string old_material : material before editing 
	* @param string old_color : color before editing 
	* @param string new_material : user entered material 
	* @param string new_color : user entered color 
	* @param bool add : is set to true if add action is called
	*/
	public function  updatePrinterOptions($old_material, $old_color, $new_material, $new_color, $add=FALSE){
		$success_messages = array();
		$error_messages = array();
		if(!empty($new_material) && !empty($new_color)){
			$wTable = "workflows";
			$wData = array("workflow_id"=>$this->workflow_id);
			$wRows = $this->dc->getRowsById($wTable , $wData);
			$options_updated = FALSE;
			// if workflow  exists
			if(!empty($wRows)){
				$printer_data = unserialize($wRows[0]["data"]);
				// loop through the printer_data, key is the name name of the printer, values are materials and colors
				if(array_key_exists($this->printer_name, $printer_data)){
					// if new printer name already exists 
					foreach($printer_data[$this->printer_name] as $k=>$printer){
						if(strtolower($printer['Material']) == strtolower($new_material) && strtolower($printer['Color']) == strtolower($new_color) ){
							$options_updated = FALSE;
							$error_messages[] = "Sorry the entered Material and Color combination already exists";
							break;
						}
						// Update the material and color values if they are edited 
						if($printer['Material'] == $old_material && $printer['Color'] == $old_color && empty($add)){
							$options_updated = TRUE;
							$printer_data[$this->printer_name][$k]['Material'] = $new_material;
							$printer_data[$this->printer_name][$k]['Color'] = $new_color;
						}
					}
					if(!empty($add) && empty($error_messages)){
						$options_updated = TRUE;
						$printer_data[$this->printer_name][]= ['Material' =>$new_material, 'Color'=>$new_color ];
					}
				}
				else{
					$error_messages[] = "Sorry, this printer does not exist.";
				}
				// serialize the data to store in db
				if(!empty($options_updated)){
					$this->dc->transactionStart();
					$printer_data = serialize($printer_data);
					// Store the new options name in db
					$uData = array('workflow_id'=>$this->workflow_id, 'data'=>$printer_data); 
					$updated_workflow_row = $this->dc-> updateUsingPrimaryKey('workflows', 'workflow_id', $uData);
					
					//Update the workflow_step tables for the printer name
					(!empty($updated_workflow_row)) ? $success_messages[]= "Successfully updated printer options"  : $error_messages[]="Sorry unbale to update the printer options.0000";
				}
			}else{
				LoggerPrime::info("Unable to identify the workflow");
				$error_messages[]="Sorry unbale to update the printer options.";
			}
		}else{
			LoggerPrime::info("Sorry unbale to update the printer options.Please enter both color and material");
			$error_messages[]="Sorry unbale to update the printer options.Please enter both color and material";
		}

		if(!empty($success_messages)){
			$this->dc->transactionCommit();
			Alerts::setSuccessMessages($success_messages);
		}else{
			Alerts::setErrorMessages($error_messages);
			$this->dc->transactionRollback();
		}
		header('Location: /?t=manage_options&wid='.$this->workflow_id.'&printer='.$this->printer_name);
	}
	

    /**
    * Add various price options to the materials and colors 
    * @param string material : material to which the options need to be added
    * @param string color : color to which the options need to be added
    * @param string key : Display key for the options, this will be displayed in the price settings on the job details page, example: Price per gram
    * @param string value : Value associated with the key
    */
    public function addPriceOptions( $material, $color, $key, $value){
		$success_messages = array();
		$error_messages = array();
		if(!empty($key) && !empty($value)){
			$wTable = "workflows";
			$wData = array("workflow_id"=>$this->workflow_id);
			$wRows = $this->dc->getRowsById($wTable , $wData);
			$options_updated = FALSE;
			// if workflow  exists
			if(!empty($wRows)){
				$printer_data = unserialize($wRows[0]["data"]);
				// loop through the printer_data, key is the name name of the printer, values are materials and colors
				if(array_key_exists($this->printer_name, $printer_data)){
                    foreach($printer_data[$this->printer_name] as $k=>$printer_options){
						if($printer_options['Material'] == $material && $printer_options['Color'] == $color){
							$printer_data[$this->printer_name][$k]['price_options'][$key] = $value;
                            $printer_data = serialize($printer_data);
                            // Store the new options name in db
                            $uData = array('workflow_id'=>$this->workflow_id, 'data'=>$printer_data); 
                            $updated_workflow_row = $this->dc-> updateUsingPrimaryKey('workflows', 'workflow_id', $uData);
					        (!empty($updated_workflow_row)) ? $success_messages[]= "Successfully updated printer options"  : $error_messages[]="Sorry unbale to update the printer options";
						}
                    }
                }else{
                    LoggerPrime::info("Unable to identify the printer");
                    $error_messages[]="Sorry unbale to update the price options. Printer unidentified!";
                }
            }else{
				LoggerPrime::info("Unable to identify the workflow");
				$error_messages[]="Sorry unbale to update the price options. Workflow unidentified!";
            }
        
		}else{
			LoggerPrime::info("Sorry unbale to update the price options for material ".$material." and color ".$color);
			$error_messages[]="Sorry unbale to update the price options for material ".$material." and color ".$color;

		}
		if(!empty($success_messages)){
			$this->dc->transactionCommit();
			Alerts::setSuccessMessages($success_messages);
		}else{
			Alerts::setErrorMessages($error_messages);
			$this->dc->transactionRollback();
		}
		header('Location: /?t=manage_options&wid='.$this->workflow_id.'&printer='.$this->printer_name);

    }

    /**
    * Remove price options to the materials and colors 
    * @param string material : material to which the options need to be added
    * @param string color : color to which the options need to be added
    * @param string key : Display key for the options, this will be displayed in the price settings on the job details page, example: Price per gram
    */
    public function removePriceOptions( $material, $color, $key){
		$error_messages = array();
		$success_messages = array();
		if(!empty($key)){
			$wTable = "workflows";
			$wData = array("workflow_id"=>$this->workflow_id);
			$wRows = $this->dc->getRowsById($wTable , $wData);
			$options_updated = FALSE;
			// if workflow  exists
			if(!empty($wRows)){
				$printer_data = unserialize($wRows[0]["data"]);
				// loop through the printer_data, key is the name name of the printer, values are materials and colors
				if(array_key_exists($this->printer_name, $printer_data)){
                    foreach($printer_data[$this->printer_name] as $k=>$printer_options){
						if($printer_options['Material'] == $material && $printer_options['Color'] == $color){
                            // check if the option exists 
                            if (isset($printer_options['price_options'][$key])){
                                unset($printer_data[$this->printer_name][$k]['price_options'][$key]);
                                $printer_data = serialize($printer_data);
                                // Store the updated array  in db
                                $uData = array('workflow_id'=>$this->workflow_id, 'data'=>$printer_data); 
                                $updated_workflow_row = $this->dc-> updateUsingPrimaryKey('workflows', 'workflow_id', $uData);
					            (!empty($updated_workflow_row)) ? $success_messages[]= "Successfully removed ".$key  : $error_messages[]="Sorry unbale to remove the option" .$key;
                            }else{
                                $error_messages[]="Sorry unbale to update the option ".$key.". Option unidentified!";
                            }
						}
                    }
                }else{
                    LoggerPrime::info("Unable to identify the printer");
                    $error_messages[]="Sorry unbale to update the price options. Printer unidentified!";
                }
            }else{
				LoggerPrime::info("Unable to identify the workflow");
				$error_messages[]="Sorry unbale to update the price options. Workflow unidentified!";
            }
        
		}else{
			LoggerPrime::info("Sorry unbale to update the price options for material ".$material." and color ".$color);
			$error_messages[]="Sorry unbale to update the price options for material ".$material." and color ".$color;

		}
		if(!empty($success_messages)){
			$this->dc->transactionCommit();
			Alerts::setSuccessMessages($success_messages);
		}else{
			Alerts::setErrorMessages($error_messages);
			$this->dc->transactionRollback();
		}
		header('Location: /?t=manage_options&wid='.$this->workflow_id.'&printer='.$this->printer_name);
        
    }


	/**
	* Delete material and color from a printer , 
	* @param string material : material to be deleted
	* @param string color : color to be deleted 
	*/
	public function deleteOptions($material, $color){
		$error_messages = array();
		$success_messages = array();
		// if workflow id is empty
		if(!empty($this->workflow_id)){
			//get printer from db
			$wTable = "workflows";
			$wData = array("workflow_id"=>$this->workflow_id);
			$wRows = $this->dc->getRowsById($wTable , $wData);
			// if workflow  exists
			if(!empty($wRows)){ 
				$workflow_name = $wRows[0]['name'];
				$printer_data = unserialize($wRows[0]["data"]);
				
				// loop through the printer_data, key is the name name of the printer, values are materials and colors
				foreach($printer_data as $key=>$values){
					if($key == $this->printer_name){
						foreach($values as $k=>$val){
							if($val['Material'] == $material && $val['Color'] == $color){
								//set it in the session for undo						
								$_SESSION['deleted_options'][$this->printer_name] = array("Material"=>$val['Material'], "Color"=>$val['Color']);
								// unset deleted printer from the array before serializing
								unset($printer_data[$key][$k]);
								break;

							}
						}
						
					}
				}
				// serialize to store in the db
				$printer_data = serialize($printer_data);
				$uData = array('workflow_id'=>$this->workflow_id, 'data'=>$printer_data); 
				// remove from data in workflow table
				$updated_workflow_row = $this->dc-> updateUsingPrimaryKey('workflows', 'workflow_id', $uData);

				//check if deletion is succesful
				if(!empty($updated_workflow_row)  && empty($error_messages)){
					$success_messages[] = 'Sucessfully deleted printer options <a href ="/?t=undo_remove_options&printer='.$this->printer_name.'&wid='.$this->workflow_id.'">Undo</a>' ;
					Alerts::setSuccessMessages($success_messages);
				}else{
					$error_message[] = 'Sorry unable to delete printer options'  ;
					Alerts::setErrorMessages($error_messages);

				}
				header('Location: /?t=manage_options&wid='.$this->workflow_id.'&printer='.$this->printer_name);	

			}else{ // if workflow doesnot exist
				LoggerPrime::info("Unable to identify the workflow");
				$error_messages[]="Sorry the workflow does not exist";
				Alerts::setErrorMessages($error_messages);
				header('Location: /?t=manage_infrastructure');

				
			}
			
		}else{ // if workflow_id is empty
			
			LoggerPrime::info("Empty workflow id is passed");
			$error_messages[]="Sorry the workflow does not exist";
			Alerts::setErrorMessages($error_messages);
			header('Location: /?t=manage_infrastructure');
			
					
		}
	}

	/**
	* Undo delete of a printer from workflow , 
	*/
	
	public function undoDeleteOptions(){
		$error_messages = array();
		$success_messages = array();
		// if workflow id is empty
		if(!empty($this->workflow_id)){
			//get printer from db
			$wTable = "workflows";
			$wData = array("workflow_id"=>$this->workflow_id);
			$wRows = $this->dc->getRowsById($wTable , $wData);
			// if workflow  exists
			if(!empty($wRows)){ 
				if( isset($_SESSION['deleted_options'][$this->printer_name])){
				
					$workflow_name = $wRows[0]['name'];
					$printer_data = unserialize($wRows[0]["data"]);

					//put the deleted printer back in the array
					if(isset($printer_data[$this->printer_name])){
						$printer_data[$this->printer_name][] = $_SESSION['deleted_options'][$this->printer_name];
						// unset it from session
						unset($_SESSION["deleted_options"]);
						// serialize to store in the db
						$printer_data = serialize($printer_data);
					
						$uData = array('workflow_id'=>$this->workflow_id, 'data'=>$printer_data); 
						// update data in workflow table
						$updated_workflow_row = $this->dc-> updateUsingPrimaryKey('workflows', 'workflow_id', $uData);

						//check if undo is succesful
						if(!empty($updated_workflow_row)){
							$success_messages[] = 'Undo action successful '; 
							Alerts::setSuccessMessages($success_messages);
						}else{
							$error_messages[] = 'Sorry unable to perform an undo action ';
						
						}
					}else{
						$error_messages[] = 'Sorry unable to perform an undo action ';
					}
				}else{
					$error_messages[] = 'Sorry unable to perform an undo action ';
				}
				Alerts::setErrorMessages($error_messages);

				header('Location: /?t=manage_options&wid='.$this->workflow_id.'&printer='.$this->printer_name);	

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
