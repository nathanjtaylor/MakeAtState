<?php

class AddToCart
{
    public $types = array();

    private $cTemplate;
    private $dc;
    private $helper;
    

    private $file_names = array();
    private $file_exts = array();
    private $file_id;
    private $cart_id;
    
    private static $user;
    private static $nav_array;
    private static $workflows;

    

    /**
    * Constructor function
    */
    public function __construct(Templater &$cTempl)
    {
        $this->cTemplate = $cTempl;
        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();
        $this->setUser();
        $this->getPrimeWorkflows();
        $this->setNavigation();
        $pTraget = UserData::create('t')->getString();
        $fTarget = UserData::create('file_id');
        $this->cart_id = UserData::create('cid')->getInt();
        $this->file_id = $fTarget->getArray(Array());
        if (!empty($this->file_id) && ($this->setFileNameForCart())) {
            switch ($pTraget) {
                case 'add_to_cart':
                    $this->prepareWorkflowType();
                    break;

                case 'save_to_cart':
                    $update = empty($this->cart_id) ? false : true ;
                    $this->saveToCart($update);
                    break;
            }
        } elseif ($pTraget !=='ajax') {
            // if invalid cart id is provided
            $error_messages[] = "Sorry, we cannot add this item to your cart";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=home');
        }
    }

    /*
    * function get filename based on file id
    * if file_id does not exist or if the file does not belong to the user return false
    */
    public function setFileNameForCart()
    {
        $valid = false;
        foreach ($this->file_id as $key=>$id) {
            $fRow = $this->dc->getFile($id);
            if (isset($fRow[0]["file_name"])   &&   ($fRow[0]["user_id"] == self::$user['user_id']) && (empty($fRow[0]["deleted"]))) {
                $this->file_names[$id] = $fRow[0]["file_name"];
                $file_ext_array = explode('.',  $fRow[0]["file_name"]);
                $ext = end($file_ext_array);
                $this->file_exts[$id] = strtolower($ext);
                $valid = true;
            }
        }

        return $valid;
    }

    /**
    * Function to render Add to Cart template
    */
    public function renderAddTemplate()
    {
        $this->cTemplate->setTemplate('add_to_cart.html');
        $this->cTemplate->setVariables("nav_array", self::$nav_array);
        $this->cTemplate->setVariables('error_messages', Alerts::getErrorMessages());

        $this->cTemplate->setVariables("page_title", "Add to cart");
        $this->cTemplate->setVariables("file_names", $this->file_names);
        $this->cTemplate->setVariables('file_id', $this->file_id);
        $this->cTemplate->setVariables('cart_id', $this->cart_id);
        $this->cTemplate->SetVariables("types", $this->types);
        $this->cTemplate->generate();
    }

    /**
    *Sets the user
    */
    public function setUser()
    {
        if (self::$user == null) {
            self::$user = AuthenticatedUser::getUser();
        }
    }

    /**
    *Sets the navbar for the user
    */
    public function setNavigation()
    {
        if (self::$nav_array == null) {
            self::$nav_array = AuthenticatedUser::getUserNavBar();
        }
    }
    
    /**
    * get all the workflows available in 3dprime
    */
    public function getPrimeWorkflows()
    {
        if (self::$workflows == null) {
            $wRows = $this->dc->getWorkflows();
            self::$workflows = $this->prepareWorkflows($wRows);
        }
    }

    /**
    *Prepare workflows array
    * @param array $wRows : Workflows array from the db
    */
    public function prepareWorkflows($wRows)
    {
        $workflows = array();
        foreach ($wRows as $key=>$wf) {
            #Add workflow only if they are printers
            if (!empty(unserialize($wf["data"]))) {
                $workflows[$wf["name"]]['data'] = unserialize($wf["data"]);
                $workflows[$wf["name"]]['workflow_id'] = $wf["workflow_id"];
                $workflows[$wf["name"]]['allowed_exts'] = (empty($wf["allowed_ext_data"])) ? array() : unserialize($wf["allowed_ext_data"]);
                $workflows[$wf["name"]]['workflow_tag'] = (empty($wf["workflow_tag"])) ? "" : strtolower($wf["workflow_tag"]);
            }
        }

        return  $workflows;
    }

    /**
    * Function to unserialize the data array and prepare it for display
    * @param array $workflows: Array of workflows from sql db
    */
    public function prepareWorkflowType()
    {
        foreach ($this->file_id as $key=>$id) {
        
            if (self::$workflows == null) {
                $this->getPrimeWorkflows();
            }

            foreach (self::$workflows as $type=>$value) {
                foreach ($value['allowed_exts'] as $val) {
                    if ($this->file_exts[$id] == $val) {
                        #TODO Display an error
                    }
                }
                if (in_array($this->file_exts[$id], $value['allowed_exts'])) {
                    $this->types[$id][] = $type;
                }
            }
        }
        // get all worflow_steps
        $workflowStepRow  = $this->dc->getAllWorkflowSteps();

        // Check if general step , price step , job completed step , cancelled step and user cancelled step are available for the workflow
        //if not do not add it to the dropdown
        $workflowStepTypes = $this->dc->getAllStepTypes();
        $warnings_array = $this->helper->determineReadinessOfPrinterWorkflow($workflowStepRow, $workflowStepTypes);
        if (!empty($warnings_array )) {
            // reset all types. Set it to empty and display the error message. Error message is in the template
            $this->types = array();
            $this->renderAddTemplate();
        }
        else if (count($this->types) == 1) {
            $this->getPrinters($this->types[0]);
        } else {
            $this->renderAddTemplate();
        }
    }

    /**
    *Function to get the printers based on the type of workflow
    * @param string: $type : type of workflow
    * @param string: $id : File id associcated with the file
    * @param bool $ajax: Bool is set to true if called via ajax
    */
    public function getPrinters($type, $id=0, $ajax = false)
    {
        $printers = array();
        if (self::$workflows == null) {
            $this->getPrimeWorkflows();
        }
        foreach (self::$workflows[$type]['data'] as $key => $values) {
            # Check if the printer has workflow steps associated to it , if not , do not show the printer in the dropdown
            if (empty($warnings_array)) {
                $printers[] = $key;
            }
        }
        if ($ajax) {
            $this->cTemplate->setTemplate('select_printers.html');
            $this->cTemplate->setBlock('display_printers');
            $this->cTemplate->setBlockVariables('printers', $printers);
            $this->cTemplate->setBlockVariables('id', $id);
            $block = $this->cTemplate->generateBlock();
            return $block;
        } else {
            $this->cTemplate->setVariables("printers", $printers);
            $this->renderAddTemplate();
        }
        return null;
    }
    
    /**
    * Function to gte materials based on the type and printer
    * @param string $type : type of the workflow selected
    * @param string $printer : printer selected for the type
    * @param string: $id : File id associcated with the file
    * @param bool $ajax :Bool is set to true if called via ajax
    */
    public function getMaterials($type, $printer, $id=0, $ajax = false)
    {
        $materials = array();
        if (self::$workflows == null) {
            $this->getPrimeWorkflows();
        }
        foreach (self::$workflows[$type]['data'][$printer] as $key =>$values) {
            foreach ($values as $k=>$material) {
                if ($k == "Material") {
                    if (!in_array($material, $materials)) {
                        $materials[] = $material;
                    }
                }
            }
        }
        if ($ajax) {
            $this->cTemplate->setTemplate('select_materials.html');
            $this->cTemplate->setBlock('display_materials');
            $this->cTemplate->setBlockVariables("materials", $materials);
            $this->cTemplate->setBlockVariables('id', $id);
            $block = $this->cTemplate->generateBlock();
            return $block;
        } else {
            #--TODO
        }
        return null;
    }

    /**
    * Function to get colors based on the type and printer
    * @param string $type : type of the workflow selected
    * @param string $printer : printer selected for the type
    * @param string $material : material set for the selected printer
    * @param string: $id : File id associcated with the file
    * @param bool $ajax :Bool is set to true if called via ajax
    */
    public function getColors($type, $printer, $material, $id=0, $ajax=false)
    {
        $colors = array();
        $color_for_selected_material = false;
        if (self::$workflows == null) {
            $this->getPrimeWorkflows();
        }
        foreach (self::$workflows[$type]['data'][$printer] as $key => $values) {
            if($values["Material"] == $material && !in_array($values["Color"], $colors)) {
                $colors[] = $values["Color"];
            }
        }
        # get help url based on the workflow tag
        $help_url = "";
        $url_text = "";
        $workflow_tag = self::$workflows[$type]['workflow_tag'];
        if (!empty($workflow_tag)) {

            $availabe_workflow_tags =  APP::getWorkflowTags();
            $workflow_tags_array = $availabe_workflow_tags->get($workflow_tag, '');
            # check if the workflow tag exists in the config file
            if (!empty($workflow_tags_array )) {
                $help_url = !empty($workflow_tags_array->get('help_url')) ? $workflow_tags_array->get('help_url') : "";
                $url_text = !empty($workflow_tags_array->get('url_text')) ? $workflow_tags_array->get('url_text'): "";
            }

        }

        if ($ajax) {
            $this->cTemplate->setTemplate('select_colors.html');
            $this->cTemplate->setBlock('display_colors');
            $this->cTemplate->setBlockVariables("colors", $colors);
            $this->cTemplate->setBlockVariables("help_url", $help_url);
            $this->cTemplate->setBlockVariables("url_text", $url_text);
            $this->cTemplate->setBlockVariables('id', $id);
            $block = $this->cTemplate->generateBlock();
            return $block;
        } else {
            #--TODO
        }
        return null;
    }

    /**
    * Function to get number of copies
    * @param bool $ajax : Bool is set to true if called via ajax
    * @param string $type : Type of the workflow selected
    * @param string $printer : Printer selected by the user
    * @param string $material : Material selected by the user
    * @param string $color : Material selected by the color
    * @param string: $id : File id associcated with the file
    */
    public function getCopies($ajax = false, $type, $printer, $material, $color, $id=0)
    {
        $workflow_tag = self::$workflows[$type]['workflow_tag'];
        $availabe_workflow_tags =  APP::getWorkflowTags();
        $workflow_tags_array = $availabe_workflow_tags->get($workflow_tag, '');
        $price = 0.00;
        if (self::$workflows == null) {
            $this->getPrimeWorkflows();
        }
        foreach (self::$workflows[$type]['data'][$printer] as $key => $values) {
            if($values["Material"] == $material && $values["Color"] == $color) {
                $price = isset($values["Price"]) ? $values["Price"] : 0.00;
            }
        }

        # check if the workflow tag exists in the config file
        if (!empty($workflow_tags_array)) { 
            $dimensions_placeholder_text = !empty($workflow_tags_array->get('dimensions_placeholder')) ? $workflow_tags_array->get('dimensions_placeholder'): "Enter the dimensions of your order";
        } else {
            $dimensions_placeholder_text = "Enter the dimensions of your order";
        }

        $copies = array(1,2,3,4,5,10,20,30,40,40,75,100);

        if ($ajax) {
            $this->cTemplate->setTemplate('select_copies.html');
            $this->cTemplate->setBlock('display_copies');
            $this->cTemplate->setBlockVariables("price", $price);
            $this->cTemplate->setBlockVariables("copies", $copies);
            $this->cTemplate->setBlockVariables("placeholder", $dimensions_placeholder_text);
            $this->cTemplate->setBlockVariables('id', $id);
            $block = $this->cTemplate->generateBlock();
            return $block;
        } else {
            #--TODO
        }
        return null;
    }

    public function verifyAddress($ship_values){
        $cf = APP::config();

        $request_doc = <<<EOT
<AddressValidateRequest USERID="{$cf->get("application.usps_id")}">
<Address>
<Address1></Address1>
<Address2>{$ship_values['ship-address']}</Address2>
<City>{$ship_values['ship-city']}</City>
<State>{$ship_values['ship-state']}</State>
<Zip5>{$ship_values['ship-zip']}</Zip5>
<Zip4></Zip4>
</Address>
</AddressValidateRequest>
EOT;


        $doc_string = preg_replace('/[\t\n\r]/', '', $request_doc);
        $doc_string = urlencode($doc_string);
        $url = "https://secure.shippingapis.com/ShippingAPI.dll?API=Verify&XML=". $doc_string;

        $response = file_get_contents($url);

        $xml=simplexml_load_string($response);
        return $xml;
    }

    /**
    * Function to prepare data to save in the cart as a project
    */
    public function prepareProjectData()
    {
        $project_values = array();
        $post_file_ids = $_POST['file_id'];
        foreach($post_file_ids as $key=>$file_id) {
             $project_values[$key]['file_id'] = $file_id;
             $project_values[$key]['workflow'] = $_POST['workflow'][$key];
             $project_values[$key]['printer'] = $_POST['printer'][$key];
             $project_values[$key]['material'] = $_POST['material'][$key];
             $project_values[$key]['color'] = $_POST['color'][$key];
             $project_values[$key]['dimensions'] = $_POST['dimensions'][$key];
             $project_values[$key]['copies'] = $_POST['copies'][$key];
             $project_values[$key]['notes'] = $_POST['notes'][$key];
        }
        return $project_values;
    }

    /**
    * Function to prepare shipping data for the project
    */
    public function prepareProjectDeliveryData()
    {
        $project_data = $_POST;
        $ship_array = array('ship-address', 'ship-city', 'ship-state', 'ship-zip');
        $campus_array = array('ship-field-info');
        $campus_values = array();
        $ship_values = array();
        $delivery_string = '';

        foreach ($_POST as $k=>$v) {
            if (in_array($k, $ship_array)){
                $ship_values[$k] = $v;
            }
            if (in_array($k, $campus_array)){
                $campus_values[$k] = $v;
            }
        }

        if($project_data['delivery'] == 'shipping'){
            $all_ship_fields = true;
            foreach($ship_array as $field){
                if(empty($ship_values[$field])){
                    $all_ship_fields = false;
                }
            }

            if(!$all_ship_fields){
                $error_messages[] = "Missing shipping information";
            }
            else{
                $delivery_string = ($ship_values['ship-address'].' '.$ship_values['ship-city'].', '.$ship_values['ship-state'].' '.$ship_values['ship-zip']);
            }

            if (!empty($error_messages)) {
                Alerts::setErrorMessages($error_messages);
                header('Location: /?t=add_to_cart&file_id='.$file_id);
            }
        }
        if($project_data['delivery'] == 'campus-mail') {
            $delivery_string = $campus_values['ship-field-info'];
        }

        return $delivery_string;
    }

    /**
    * Function to save items to cart
    * Update options for an existing cart item
    */
    public function saveToCart($update=false)
    {
        $cf = APP::config();
        // to get affiliation from the configs
        $cf_afiliation = $cf->get("app.affiliation");
        $affiliation = $cf_afiliation->get(self::$user['affiliation']);
        if (self::$user == null) {
            $this->setUser();
        }
        $error_messages = array();
        $ship_array = array('ship-address', 'ship-city', 'ship-state', 'ship-zip');
        $campus_array = array('ship-field-info');
        $project_values = $this->prepareProjectData();
        $delivery_string = $this->prepareProjectDeliveryData();
        $user_id = self::$user['user_id'];
        $project_name = $_POST['project_name'];
        $cData = array();
        $cRow = array();

        //check of the selected options are still valid
        if ((!$this->verifySubmissions($project_values))) {
            $error_messages[] = "Sorry we are unable to perform this operation at this time.";
        } else {
            $delivery = $_POST['delivery'];
            $this->dc->transactionStart();
            // create a new project
            if (empty($update) && !$update) {
                $project_id = $this->dc->createProjects($user_id, $project_name);
            }
            foreach ($project_values as $data) {
                $fRow = $this->dc->getFile($data['file_id']); 
                if (empty($fRow) || $fRow[0]['user_id'] != $user_id || !empty($fRow[0]['blocked'])) {
                    $error_messages[] = "Sorry, you don't have permissions to save this item to your cart" ;
                } else {
                    $cData = array('Type'=> $data['workflow'],'Printer'=>$data['printer'], 'Material' => $data['material'], 'Color'=>$data['color'], 'Dimensions'=>$data['dimensions'], 'Copies'=> (intval($data['copies']) > 100) ?100 : $data['copies'], "Notes"=>$data['notes'] , 'Affiliation' =>$affiliation, "Delivery Option"=>$delivery);
                    if(!empty($delivery_string)){
                        $cData['Shipping Address'] = $delivery_string;
                    }
                    $cData = serialize($cData);
                    if (empty($update) && !$update) {
                        $cRow = $this->dc->insertFilesInCart($user_id, $data['file_id'], $project_id, $cData);
                    } else {
                        $aData = array('cart_id'=>$this->cart_id, 'user_id' => $user_id , 'file_id' => $file_id);
                        $cartRow = $this->dc->getRowsById('cart', $aData);
                        #check if the cart_id and file belong to the user
                        if (empty($cartRow)) {
                            $error_messages[] = "Sorry you dont have permissions to update this item to your cart" ;
                        } else {
                            $ucData = array("cart_id"=>$this->cart_id,"cart_data"=>$cData );
                            $cRow = $this->dc->updateUsingPrimaryKey("cart", "cart_id", $ucData);
                        }
                    }
                    if (empty($cRow)) {
                        $error_messages[] = "Sorry we are unable to save this item to your cart.";
                        $this->dc->transactionRollback();
                    }
                }
            }
        }
        if (!empty($error_messages)) {
            Alerts::setErrorMessages($error_messages);
            // update the url with file id
            $url_string = '/?t=add_to_cart';
            foreach($this->file_id as $id) {
                $url_string .= '&file_id[]='.$id;
            }
            header('Location: '.$url_string);
        } else {
            $this->dc->transactionCommit();
            header('Location: /?t=view_cart');
        }
    }

    /**
    * Verify the submissions for cart
    * @param array project_data: Array of post values submitted by the user
    */
    public function verifySubmissions($project_data)
    {
        $verified = false;
        foreach ($project_data as $data) {
            $workflow_type = $data['workflow'];
            $workflowRow =  $this->dc->getWorkflowByType($workflow_type);
            // check if the submitted data is tampered
            if (!empty($workflowRow) && (intval($data['copies']) !== 0)) {
                // Set printer type for the parent class
                $printer_name = $data['printer'];

                $workflow_data = unserialize($workflowRow[0]['data']);
                $printer = $data['printer'];
                $material = $data['material'];
                $color = $data['color'];


                # check if the cart item options are still available for the workflow type
                if (!empty($workflow_data[$printer])) {
                    foreach ($workflow_data[$printer] as $p) {
                        if ($p["Material"] == $material && $p['Color'] == $color) {
                            $verified = true;
                            break;
                        }
                    }
                }
            } 
            // Check at the end of each file. If incorrect or expired workflow is passed then return verified as false
            if(!$verified) {
                return $verified;
            }
        }
        return $verified;
    }
}
