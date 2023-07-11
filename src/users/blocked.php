<?php
# Class to show the blocked user view

class Blocked
{
    private $bTemplate;

    private static $user;
    private static $nav_array;


    /**
    * Constructor function for blocked page
    * @param Templater uTempl: Templater object
    */
    public function __construct(Templater &$bTempl)
    {
        $this->setUser();
        $this->setNavigation();
        
        LoggerPrime::info("Viewing blocked user , user_id: ". self::$user['user_id']);
        
        $this->bTemplate = $bTempl;
        $this->renderBLockedTemplate();
    }


    /**
    * Render search  blocked template
    */
    private function renderBLockedTemplate()
    {
        $this->bTemplate->setTemplate('blocked.html');
        $this->bTemplate->setVariables('page_title', "User blocked");


        $this->bTemplate->generate();
    }

    /**
    * Sets the user
    */

    private function setUser()
    {
        //lazy loading  user
        if (self::$user == null) {
            self::$user = AuthenticatedUser::getUser();
        }
    }

    /**
    * Sets the navigation for the page
    */

    protected function setNavigation()
    {
        if (self::$nav_array == null) {
            self::$nav_array = AuthenticatedUser::getUserNavBar();
        }
    }
}
