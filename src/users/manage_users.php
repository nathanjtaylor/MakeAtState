<?php

#Class to display admistrators , staff , student staff and provide otions to change their permissions

class ManageUsers
{
    private $user_id;
    private $access_level;
    private $mTemplate;
    private $user_results = array();
    private $user_type; #3- admin, 2- staff, 1-student staff(based on managelevel in db)
    #Variables for pagination
    private $pagination_array = array();
    private $rPage;
    
    private $dc;
    private $helper;


    private static $user;
    private static $nav_array;

    /**
    * Constructor function for manage users
    * @param Templater mTempl : Templater object for manage users class
    */
    public function __construct(Templater &$mTempl)
    {
        $this->setUser();
        $this->setNavigation();
        $this->setAccessLevel();
        $this->mTemplate = $mTempl;

        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();

        $this->user_type = UserData::create('type')->getInt(4);
        $this->rPage = UserData::create('page')->getInt(-1);
        
        
        $this->prepareDisplay();
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
    * Render manage users template
    */
    private function renderManageUsersTemplate()
    {
        $this->mTemplate->setTemplate('manage_users.html');
        $this->mTemplate->setVariables('page_title', "Manage users");
        $this->mTemplate->setVariables('error_messages', Alerts::getErrorMessages());
        $this->mTemplate->setVariables('nav_array', self::$nav_array);
        # Set all the user details for the template
        $this->mTemplate->setVariables('user_results', $this->user_results);
        $this->mTemplate->setVariables("pagination_array", $this->pagination_array);
        $this->mTemplate->setVariables("type", $this->user_type);
        
        $this->mTemplate->generate();
    }

    /**
    *Prepare manage users display
    * Show the search bar and list of Admistrators ,staff and student staff
    */
    private function prepareDisplay()
    {
        $error_messages = array();
        $rPagination = 	$this->helper->getPaginationValues($this->rPage);
        
        if ($this->access_level == "ADMIN") {
            if ($this->user_type == 1) {
                $this->user_results = $this->dc->getUserByStatus("users", $rPagination['skip'], $rPagination['limit']);
            } elseif ($this->user_type == 2) {
                $this->user_results = $this->dc->getUserByStatus("student_staff", $rPagination['skip'], $rPagination['limit']);
            } elseif ($this->user_type == 3) {
                $this->user_results = $this->dc->getUserByStatus("staff", $rPagination['skip'], $rPagination['limit']);
            } else {
                $this->user_results = $this->dc->getUserByStatus("admin", $rPagination['skip'], $rPagination['limit']);
            }
            //count for results
            $rTotal = $this->dc->getTotalRows();
            
            #Set the page number in session for back button
            $_SESSION['previous_page'] = "/?t=manage_users&type=".$this->user_type.'&page='.$rPagination['page'];
            $rPagination['total'] = $rTotal;
            $rPagination['target'] = "/?t=manage_users&type=".$this->user_type.'&page=';
            $this->pagination_array = $this->helper->preparePaginationArray($rPagination);

            
            $this->renderManageUsersTemplate();
        } else {
            LoggerPrime::info("Unauthorized user trying to view manage users page. user_id: ".$this->user_id);
            $error_messages[] = "Sorry, this operation is not allowed";
            Alerts::setErrorMessages($error_messages);
            header('Location: /?t=home');
        }
    }
}