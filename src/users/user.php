<?php

class AuthenticatedUser
{
    private static $user;

    private static $permissions;
    private static $nav_array;
    private static $total_allocated_size;
    private static $file_retention_duration;
    
    /**
    * Init the user class
    @param array User : User array
    */
    public function __construct($user)
    {
        if (self::$user == null) {
            self::$user = $user;
        }
        $this->setUserNavBar();
        $this->setTotalAllocatedSize();
        $this->setFileRetentionDuration();
    }

    /**
    * Returns the user array
    */

    public static function getUser()
    {
        return self::$user;
    }
    /**
    * Returns the user nav array
    */
    public static function getUserNavbar()
    {
        return self::$nav_array;
    }
    
    /**
    * returns the total allocated size for the user
    */
    public static function getTotalAllocatedSize()
    {
        return self::$total_allocated_size;
    }
    /**
    * returns the total allocated size for the user
    */
    public static function getFileRetentionDuration()
    {
        return self::$file_retention_duration;
    }
    

    /**
    * Get user permissions
    */
    public static function getUserPermissions()
    {
        # set the permissions object
        if (self::$permissions == null) {
            self::setUserPermissions();
        }
        return self::$permissions->getAccessLevel();
    }

    /**
    * Get user permissions
    */
    public static function getNumericUserPermissions()
    {
        # set the permissions object
        if (self::$permissions == null) {
            self::setUserPermissions();
        }

        return self::$permissions->getNumericAccessLevel();
    }

    
    /**
    * Set user permissions
    */
    private static function setUserPermissions()
    {
        # set the permissions object
        self::$permissions = new UserPermissions(self::$user);
    }
    

    /**
    * Get Nav Bar for the user
    * returns navigation array for the user
    */
    private function setUserNavBar()
    {
        if (self::$permissions == null) {
            $this->setUserPermissions();
        }
        self::$nav_array = self::$permissions->generateNavbarArray();
    }

    /**
    * Check user access for the user
    */
    public function checkUserAccess()
    {
        #-TODO
    }

    /**
    * Get user by id
    * @param Int user_id : user_id of the user
    */
    public function getUserById($user_id)
    {
        #-TODO
    }

    /**
    * sets the total allocated size for the user
    */
    private function setTotalAllocatedSize()
    {
        if (self::$permissions == null) {
            $this->getUserPermissions();
        }
        self::$total_allocated_size = self::$permissions->getTotalAllocatedSize();
    }
    /**
    * sets the file retention duration for the user
    */
    private function setFileRetentionDuration()
    {
        if (self::$permissions == null) {
            $this->getUserPermissions();
        }
        self::$file_retention_duration = self::$permissions->getFileRetentionDuration();
    }
}
