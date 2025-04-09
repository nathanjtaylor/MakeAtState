<?php
class ViewCart{
    private $vcTemplate;
    private $dc;
    private $cart_details = array();
    private $submit_all_ids = array();
    static private $user;
    static private $nav_array = array();
    /**
    *Constructor function 
    * @param $Templ : sets the template file
    */
    public function __construct(Templater &$vcTempl){
        $this->vcTemplate =  $vcTempl;
        $this->dc = new DataCalls();
        $this->setCartNavigation();
        if(self::$user == null){
            $this->setUserForCart();
        }
        $rpid = UserData::create('rpid')->getString();
        $upid = UserData::create('upid')->getString();
        $uacid = UserData::create('uacid')->getString();
        $racid = UserData::create('racid')->getString();
        if(!empty($rpid)){
            $this->deleteCartItem($rpid);
        }
        else if(!empty($upid)){
            $this->undoRemove($upid);
        }
        else if(!empty($racid)){
            $this->deleteAllCartItems($racid);
        }
        else if(!empty($uacid)){
            $this->undoRemoveAll($uacid);
        }
        else{
            $this->getProjects();
        }
    }
    /**
    *Set the user for the cart 
    */
    public function setUserForCart(){
        if(self::$user == null){
            self::$user = AuthenticatedUser::getUser(); 
        }
    }
    /**
    *Sets the navbar for the user
    */
    public function setCartNavigation(){
        if(self::$nav_array == null ){
            self::$nav_array = AuthenticatedUser::getUserNavBar();
        }
    }
    /**
    *Function to get projects
    */
    public function getProjects(){
        $user_id = self::$user['user_id'];
        $sTable = "projects";
        $aData = array("user_id"=>$user_id, "removed"=>null, "submitted"=>null);
        $projects = $this->dc->getRowsById($sTable, $aData);
        $this->getItemsInCart($projects);
    }
    /**
    *Function to get items in cart
    * @param array $projects : projects associated to the user
    */
    public function getItemsInCart($projects){
        $user_id = self::$user['user_id'];
        $sTable = "cart";
        foreach($projects as $key=>$project) {
            $aData = array("user_id"=>$user_id, "removed"=>null, "submitted"=>null, "project_id" =>$project['project_id']);
            $cRow = $this->dc->getRowsById($sTable, $aData);
            $this->extractCartData($cRow, $project['project_name']);
        }
        $this->renderCartTemplate();
    }
    /**
    *Function to extract cart data and prepare it for display
    * @param array $cRow : Array of cart data from db
    * @param array $project_name : projecy name of the project 
    */
    public function extractCartData($cRow, $project_name){
        $cart_details = array();
        $cart_ids = "";
        if(!empty($cRow)){
            foreach($cRow as $v){
                $file_id =  $v['file_id'];
                $fRow = $this->dc->getFile($file_id);
                $file_name = $fRow[0]['file_name'];
                $c_data = unserialize($v['cart_data']);
                $cart_detail['data'] = $c_data;
                $cart_detail['file_name'] = $file_name;
                $cart_detail['file_id'] = $file_id;
                $cart_detail['cart_id'] = $v['cart_id'];
                $cart_detail['project_id'] = $v['project_id'];
                $cart_detail['project_name'] = $project_name;
                $cart_details[] = $cart_detail;
                // add cart id's for submit all into the array
                $this->submit_all_ids[]= $v['cart_id'];
            }
        $this->cart_details[$v['project_id']] = $cart_details;
        }
        else{
            #--TODO if cart is empty
        }
    }
    /**
    *Render cart template
    */
    public function renderCartTemplate(){
        $this->vcTemplate->setTemplate('view_cart.html');
        $this->vcTemplate->setVariables('page_title', "View cart");
        $this->vcTemplate->setVariables("nav_array" , self::$nav_array);
        $this->vcTemplate->setVariables("user_id" , self::$user['user_id']);
        $this->vcTemplate->setVariables('cart_details', $this->cart_details);
        $this->vcTemplate->setVariables('questions', $this->getQuestions());
        $this->vcTemplate->setVariables('submit_all_ids', $this->submit_all_ids);
        $this->vcTemplate->setVariables('error_messages', Alerts::getErrorMessages());
        $this->vcTemplate->setVariables('success_messages', Alerts::getSuccessMessages());
        $this->vcTemplate->generate();
    }
    /**
    *Function to get assessment questions
    */
    public function getQuestions(){
        $questions = $this->dc->getAssessmentQuestions(); //getAssessmentQuestions currently retrives static questions
        return $questions;
    }
    /**
    *Delete item in cart
    * @param int $project_id : database id of the project row
    */
    public function deleteCartItem($project_id){
        $error_messages = array();
        $success_messages = array();
        $user_id = self::$user['user_id'];
        $pData = array("project_id"=>$project_id);
        $pRow = $this->dc->getRowsById("projects", $pData);
        if(empty($pRow[0]) || $pRow[0]['user_id'] != $user_id){
            $error_messages[] = "Remove operation is not allowed";
        }
        else{
            $this->dc->transactionStart();    
            $pVal = $this->dc->markProjectAsDeleted($project_id); 
            $cVal = $this->dc->markCartItemAsDeleted($project_id); 
            if(!empty($cVal) && !empty($pVal)){
                $success_messages[] ="Item successfully removed from your cart <a href='/?t=undo_remove_citem&upid={$project_id}'>Undo</a>";
                $this->dc->transactionCommit();
            }
            else {
                $error_messages[] = "Unable to remove this Item from your cart";
                $this->dc->transactionRollback();
            }    
        }
        if(!empty($error_messages)){
            Alerts::setErrorMessages($error_messages);
        }
        else {
            Alerts::setSuccessMessages($success_messages);
        }
        header('Location: /?t=view_cart');
    }
    /**
    *Delete all items in cart
    * @param int $u_id : user id of the user to empty cart
    */
    public function deleteAllCartItems($u_id){
        $error_messages = array();
        $success_messages = array();
        $user_id = self::$user['user_id'];
        if($u_id != $user_id){
            $error_messages[] = "Remove all items  operation is not allowed";
        }
        else{
            $this->dc->transactionStart();    
            $cVal = $this->dc->markAllCartItemAsDeleted($user_id); 
            if(!empty($cVal)){
                $success_messages[] ="All Items successfully removed from your cart <a href='/?t=undo_remove_all_citem&uacid={$user_id}'>Undo</a>";
                $this->dc->transactionCommit();
            }
            else {
                $error_messages[] = "Unable to remove all Items from your cart";
                $this->dc->transactionRollback();
            }    
        }
        if(!empty($error_messages)){
            Alerts::setErrorMessages($error_messages);
        }
        else {
            Alerts::setSuccessMessages($success_messages);
        }
        header('Location: /?t=view_cart');
    }
    /**
    * Undo remove of a cart item
    * @param int project_id: project id of the item to undo remove action
    */
    public function undoRemove($project_id){
        $error_messages = array();
        $success_messages = array();
        $user_id = self::$user['user_id'];
        $pData = array("project_id"=>$project_id);
        $pRow = $this->dc->getRowsById("projects", $pData);
        if(empty($pRow[0]) || $pRow[0]['user_id'] != $user_id){
            $error_messages[] = "Undo remove  operation is not allowed";
        }
        else{
            $this->dc->transactionStart();
            $pData = array('project_id'=>$project_id, 'removed'=>null);
            $puRow = $this->dc->updateUsingPrimaryKey('projects','project_id',$pData);
            $pConditions = array('project_id');
            $cuRow = $this->dc->updateUsingConditions("cart", $pConditions,  $pData);
            if(!empty($cuRow) && !empty($puRow)){
                $success_messages[] = "Undo operation successful";
                $this->dc->transactionCommit();    
            }
            else{
                $error_messages[] = "We are sorry. Unable to perform undo at this time";
                $this->dc->transactionRollback();        
            }
        }
        if(!empty($error_messages)){
            Alerts::setErrorMessages($error_messages);
        }
        else {
            Alerts::setSuccessMessages($success_messages);
        }
        header('Location: /?t=view_cart');
    }
    /**
    * Undo remove of a cart item
    * @param int u_id: user id of the user for undo action
    */
    public function undoRemoveAll($u_id){
        $error_messages = array();
        $success_messages = array();
        $user_id = self::$user['user_id'];
        if($u_id != $user_id){
            $error_messages[] = "Undo remove  operation is not allowed";
        }
        else{
            $this->dc->transactionStart();
            $cVal = $this->dc->markAllCartItemAsDeleted($user_id, $undo=TRUE); 
            if(!empty($cVal)){
                $success_messages[] = "Undo operation successful";
                $this->dc->transactionCommit();    
            }
            else{
                $error_messages[] = "We are sorry. Unable to perform undo at this time";
                $this->dc->transactionRollback();        
            }
        }
        if(!empty($error_messages)){
            Alerts::setErrorMessages($error_messages);
        }
        else {
            Alerts::setSuccessMessages($success_messages);
        }
        header('Location: /?t=view_cart');
    }
}
?>