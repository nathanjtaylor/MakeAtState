<?php
class Ajax{
    public $options = array();
    public $oTemplate;
    /**
    * Set ajax options 
    */
    public function __construct($options, Templater &$oTempl){
        $this->options = $options;
        $this->oTemplate = $oTempl;
    }
    /**
    *Make ajax calls based on the options
    */
    public function makeAjaxCall(){
    $func = $this->options['func'];
        switch ($func){
            case "getPrinters":
                require_once __DIR__."../../cart/add_to_cart.php";
                $type = $this->options['type'];
                $id = $this->options['id'];
                $add_to_cart = new AddToCart($this->oTemplate);
                $block = $add_to_cart->getPrinters($type, $id, TRUE);    
                $selector = "prime_printers_".$id;
                $aData = array("selector"=>$selector, "action"=>"replace_id", "value"=>$block);
                echo  json_encode($aData);
                break;
            case "getMaterials":
                require_once __DIR__."../../cart/add_to_cart.php";
                $type = $this->options['type'];
                $id = $this->options['id'];
                $printer = $this->options['printer'];
                $add_to_cart = new AddToCart($this->oTemplate);
                $block = $add_to_cart->getMaterials($type, $printer, $id, TRUE);    
                $selector = "prime_materials_".$id;
                $aData = array("selector"=>$selector, "action"=>"replace_id", "value"=>$block);
                echo  json_encode($aData);
                break;
            case "getColors":
                require_once __DIR__."../../cart/add_to_cart.php";
                $id = $this->options['id'];
                $type = $this->options['type'];
                $printer = $this->options['printer'];
                $material =  $this->options['material'];
                $add_to_cart = new AddToCart($this->oTemplate);
                $selector = "prime_colors_".$id;
                $block = $add_to_cart->getColors($type, $printer, $material, $id, TRUE);    
        
                $aData = array("selector"=>$selector, "action"=>"replace_id", "value"=>$block);
                echo  json_encode($aData);
                break;
            case "getCopies":
                require_once __DIR__."../../cart/add_to_cart.php";
                $add_to_cart = new AddToCart($this->oTemplate);
                $id = $this->options['id'];
                $type = $this->options['type'];
                $printer = $this->options['printer'];
                $material = $this->options['material'];
                $color = $this->options['color'];
                $selector = "prime_copies_".$id;
                $block = $add_to_cart->getCopies(TRUE, $type, $printer, $material, $color, $id);
        
                $aData = array("selector"=>$selector, "action"=>"replace_id", "value"=>$block);
                echo  json_encode($aData);
                break;
            case "sendJobMessage":
                require_once __DIR__."../../messages/send_message.php";
                $job_id = $this->options['job_id'];
                $job_step_id = $this->options['job_step_id'];
                $message_text = $this->options['text'];
                $send_message = new SendMessage($this->oTemplate);
                $block = $send_message->sendJobMessage();
                $aData = array("selector"=>"message_status", "action"=>"replace", "value"=>$block);
                echo  json_encode($aData);
                break;
            case "sendJobMessageToStaff":
                require_once __DIR__."../../messages/send_message.php";
                $job_id = $this->options['job_id'];
                $job_step_id = $this->options['job_step_id'];
                $message_text = $this->options['text'];
                $send_message = new SendMessage($this->oTemplate);
                $block = $send_message->sendJobMessageToStaff();
                $aData = array("selector"=>"message_status", "action"=>"replace", "value"=>$block);
                echo  json_encode($aData);
                break;
                    
            case "addJobNote":
                require_once __DIR__."../../notes/add_notes.php";
                $job_id = $this->options['job_id'];
                $note_text = $this->options['text'];
                $add_note = new AddNotes($this->oTemplate, $job_id, $note_text);
                $block = $add_note->addJobNotes();
                $aData = array("selector"=>"prime_job_notes", "action"=>"replace", "value"=>$block);
                echo  json_encode($aData);
                break;
            case "prepareAnalytics":
                require_once __DIR__."../../stats/stats.php";
                $selected_year = $this->options['selected_year'];
                $dtype = $this->options['dtype'];
                $stats = new Stats($this->oTemplate, TRUE);
                $aData = $stats->prepareAnalytics($selected_year,$dtype);
                echo json_encode($aData);
                break;
            case "verifyShipping":
                require_once __DIR__."../../cart/add_to_cart.php";
                $add_to_cart = new AddToCart($this->oTemplate);
                $ship_array = $this->options['info'];
                $verification = $add_to_cart->verifyAddress($ship_array);
                echo $verification->asXML();

                break;
            case "default":
                break;
        }
    }
}
