<?php
#Class for searching the users and isplaying the results

class SearchUsers
{
    private $user_id;
    private $access_level;
    private $sTemplate;
    private $term;
    private $user_results = array();
    #Variables for pagination
    private $pagination_array = array();
    private $rPage;
    
    private $dc;
    private $helper;

    private static $user;
    private static $nav_array;



    /**
    *Constructor function for search users
    * @param Templater sTempl : Templater object
    */
    public function __construct(Templater &$sTempl)
    {
        $this->setUser();
        $this->setNavigation();
        $this->setAccessLevel();
        $this->sTemplate = $sTempl;

        //set the user id from session
    
        $this->rPage = UserData::create('page')->getInt(-1);
        
        $this->dc = new DataCalls();
        $this->helper =new  PrimeHelper();

        $this->term = UserData::create('q')->getString();
        if (!empty($this->term)) {
            $this->performSearch();
        }
    }
    /**
    * Render search  users template
    */
    private function renderSearchUsersTemplate()
    {
        $this->sTemplate->setTemplate('user_results.html');
        $this->sTemplate->setVariables('page_title', "Search results");
        $this->sTemplate->setVariables('error_messages', Alerts::getErrorMessages());
        $this->sTemplate->setVariables('nav_array', self::$nav_array);
        # Set all the user details for the template
        $this->sTemplate->setVariables('user_results', $this->user_results);
        $this->sTemplate->setVariables("pagination_array", $this->pagination_array);
        $this->sTemplate->setVariables("query", $this->term);

        $this->sTemplate->generate();
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
    * Performs the search ,
    * makes a db call and searches for term in email and fullname in the usrs table
    */
    private function performSearch()
    {
        $error_messages = array();
        if ($this->access_level == "ADMIN") {
            $this->user_results = $this->dc->searchUsers($this->term);
            if (!empty($this->user_results)) {
                $this->prepareDisplay();
            }
            $this->renderSearchUsersTemplate();
        } else {
            $error_messages[] = "Sorry this operation is not allowed";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=home');
        }
    }

    /**
    * Prepare results for display
    */
    private function prepareDisplay()
    {
        //count for results
        $rTotal = $this->dc->getTotalRows();
        $rPagination = 	$this->helper->getPaginationValues($this->rPage);
        
        #Set the page number in session for back button
        $_SESSION['previous_page'] ="/?t=search_users&q=".$this->term.'&page='. $rPagination['page'];
        $rPagination['total'] = $rTotal;
        $rPagination['target'] = "/?t=search_users&q=".$this->term.'&page=';
        $this->pagination_array = $this->helper->preparePaginationArray($rPagination);
    }
}
