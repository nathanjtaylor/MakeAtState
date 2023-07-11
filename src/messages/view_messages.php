<?php
# class to view all messages
class ViewMessages{
    private $user_messages = array();
    private $mTemplate;
    private $dc;
    private $helper;
    private $access_level;
    #Variables for pagination
    private $mPage;
    private $pagination_array = array();
    static private $user;
    static private $nav_array;
    /**
    * Constructor function for messages
    */
    public function __construct(Templater &$mTempl){
        $this->mTemplate = $mTempl;
        $this->dc = new DataCalls();
        $this->helper = new PrimeHelper();
        $this->mPage = UserData::create('page')->getInt(-1);
        $this->setUser();
        $this->setNavigation();
        $this->prepareMessages();
    }
    /**
    * Render job updates template
    */
    public function renderMessagesTemplate(){
        $this->mTemplate->setTemplate('messages.html');
        $this->mTemplate->setVariables('page_title', "Messages");
        $this->mTemplate->setVariables('error_messages' , Alerts::getErrorMessages());
        $this->mTemplate->setVariables('nav_array', self::$nav_array);    
        # Set aml the jobs for the template
        $this->mTemplate->setVariables("user_messages", $this->user_messages);
        $this->mTemplate->setVariables("pagination_array", $this->pagination_array);
        $this->mTemplate->generate();
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
    /**
    * Prepare messages for display
    */
    public function prepareMessages (){
        $uData = array(self::$user['user_id']);
        $mPagination =  $this->helper->getPaginationValues($this->mPage);
        $mRow = $this->dc->getUserMessageJobs($uData, $mPagination['skip'], $mPagination['limit']);
        $mTotal = $this->dc->getTotalRows();
        if(!empty($mTotal)){
            #Set the page number in session for back button 
            $_SESSION['previous_page'] = $mPagination['page'];
            $mPagination['total'] = $mTotal;
            $mPagination['target'] = "/?t=messages&page=";
            $this->pagination_array = $this->helper->preparePaginationArray($mPagination);
        }
        foreach($mRow as $k=>$m){
            if(!empty($m['job_id'])){
                $this->user_messages[$k]['job_id'] = $m['job_id'];
                $this->user_messages[$k]['project_name'] = $m['project_name'];
                $this->user_messages[$k]['message_count'] = $m['message_count'];
                $this->user_messages[$k]['user_id'] = self::$user['user_id'];
            }
        }
        $this->renderMessagesTemplate();
    }
}
