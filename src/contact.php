<?php
# Class to show the contact view

class Contact{

	private $cTemplate;
	static private $user;
	static private $nav_array;


	/**
	* Constructor function for contact apge
	* @param Templater cTempl: Templater object
	*/
	public function __construct(Templater &$cTempl){
		
		$this->cTemplate = $cTempl;

		$this->setUser();
		$this->setNavigation();
		LoggerPrime::info("Viewing Contact info page , user_id: ". self::$user['user_id']);

		$this->renderContactTemplate();

	}


	/**
	* Render search  blocked template
	*/
	private function renderContactTemplate(){
		$this->cTemplate->setTemplate('contact.html');
		$this->cTemplate->setVariables('page_title', "Contact us");
		$this->cTemplate->setVariables('nav_array', self::$nav_array);	


		$this->cTemplate->generate();
		

	}

	/**
	* Sets the user 
	*/

	private function setUser(){
		//lazy loading  user
		if(self::$user == null){
			self::$user = AuthenticatedUser::getUser();
		}

	}

	/**
	* Sets the navigation for the page
	*/

	protected function setNavigation(){

		if(self::$nav_array == null){
			self::$nav_array = AuthenticatedUser::getUserNavBar();
		}
	}


}

?>

