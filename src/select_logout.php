<?php

class SelectSignout{


	private $rTemplate;

	/**
	*Constructor function for Signout method
	* @param $rTempl : Templater object
	*/
	public function __construct(Templater &$rTempl){
		$this->rTemplate = $rTempl;
        $this->renderSignoutMethodTemplate();
	}


	/**
	* Render Signout method template
	*/
	public function renderSignoutMethodTemplate(){

		$this->rTemplate->setTemplate('select_logout.html');
		$this->rTemplate->setVariables('error_messages', Alerts::getErrorMessages());
		$this->rTemplate->setVariables('success_messages', Alerts::getSuccessMessages());
		$this->rTemplate->setVariables("page_title" , "Select logout method");

		$this->rTemplate->generate();

	}
}

?>

