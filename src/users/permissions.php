<?php


    class UserPermissions
    {
        const BASIC_MANAGE_LEVEL    = 0X0000;
        const DESK_STAFF_MANAGE_LEVEL = 0X0005; #TODO create a permission level for the desk staff 
        const STUDENT_STAFF_MANAGE_LEVEL= 0X0001;
        const STAFF_MANAGE_LEVEL    = 0x0002;
        const ADMIN_MANAGE_LEVEL      = 0x0003;
        const BLOCKED_PERMISSIONS     = 0x0004;

        private $dc ;
        private static $user;
        private static $permissions;

        public function __construct($user)
        {
            //lazy loading of the user
            $this->dc =  new DataCalls();
            if (self::$user  == null) {
                self::$user = $user;
            }
        }

        /**
        * Make navigation bar for the user based on their permissions
        */
        public function generateNavbarArray()
        {
            $navigation_array = array();
            if (self::$user) {
                $perm_id = self::$user["permission_id"];
                $permissions_level = $this->getPermissionLevel($perm_id);
                switch ($permissions_level) {
                    case self::ADMIN_MANAGE_LEVEL:
                        $navigation_array = array("Home" => "?t=home" , "Jobs" => "?t=all_jobs" , "Messages"=>"?t=messages", "Manage Infrastructure"=>"?t=manage_infrastructure","Manage Project Workflows"=>"/?t=manage_steps", "Manage Groups"=>"?t=manage_groups","Manage Users"=>"?t=manage_users", "Stats"=>"?t=stats", "Contact us"=>"?t=contact");
                        break;
                    case self::STAFF_MANAGE_LEVEL:
                        $navigation_array = array("Home" => "?t=home" , "Jobs" => "?t=all_jobs" , "Messages"=>"?t=messages", "Stats"=>"?t=stats", "Contact us"=>"?t=contact");
                        break;
                    case self::STUDENT_STAFF_MANAGE_LEVEL:
                        $navigation_array = array("Home" => "?t=home" , "Jobs" => "?t=all_jobs" , "Messages"=>"?t=messages", "Manage Users"=>"?t=manage_users", "Stats"=>"?t=stats","Contact us"=>"?t=contact" );
                        break;
                    case self::DESK_STAFF_MANAGE_LEVEL: #TODO create a permission level for the desk staff 
                        $navigation_array = array("Home" => "?t=home" , "Jobs" => "?t=all_jobs");
                        break;
                    case self::BASIC_MANAGE_LEVEL:
                        $navigation_array = array("Home" => "?t=home" , "Jobs" => "?t=my_jobs" , "Messages"=>"?t=messages", "Contact us"=>"?t=contact");
                        break;
                        
                        
                    # --TODO other permissions
                    default:
                        #-- TODO
                        break;
                }
            }
            return $navigation_array;
        }

        /**
        * Gets the access level for the user
        */
        public function getAccessLevel()
        {
            $access_level = 0;
            if (self::$user) {
                $perm_id = self::$user["permission_id"];
                $permissions_level = $this->getPermissionLevel($perm_id);
                switch ($permissions_level) {
                    case self::ADMIN_MANAGE_LEVEL:
                        $access_level= "ADMIN";
                        break;
                    case self::STAFF_MANAGE_LEVEL:
                        $access_level= "STAFF";
                        break;
                    case self::STUDENT_STAFF_MANAGE_LEVEL:
                        $access_level = "STUDENT STAFF";
                        break;
                    case self::DESK_STAFF_MANAGE_LEVEL:
                        $access_level = "DESK STAFF";
                        break;
                    case self::BASIC_MANAGE_LEVEL:
                        $access_level= "BASIC";
                        break;
                    default:
                        $access_level = "BASIC";
                        break;
                }
            }
            return $access_level;
        }

        /**
        * Gets the access level for the user
        */
        public function getNumericAccessLevel()
        {
            $numeric_access_level = 0;
            if (self::$user) {
                $perm_id = self::$user["permission_id"];
                $permissions_level = $this->getPermissionLevel($perm_id);
                switch ($permissions_level) {
                    case self::ADMIN_MANAGE_LEVEL:
                        $numeric_access_level = self::ADMIN_MANAGE_LEVEL;
                        break;
                    case self::STAFF_MANAGE_LEVEL:
                        $numeric_access_level= self::STAFF_MANAGE_LEVEL;
                        break;
                    case self::STUDENT_STAFF_MANAGE_LEVEL:
                        $numeric_access_level = self::STUDENT_STAFF_MANAGE_LEVEL;
                        break;
                    case self::DESK_STAFF_MANAGE_LEVEL:
                        $numeric_access_level = self::DESK_STAFF_MANAGE_LEVEL;
                        break;
                    case self::BASIC_MANAGE_LEVEL:
                        $numeric_access_level= self::BASIC_MANAGE_LEVEL;
                        break;
                    default:
                        $numeric_access_level = self::BASIC_MANAGE_LEVEL;
                        break;
                }
            }
            return $numeric_access_level;
        }

        /**
        * get the internal name of the permission
        * @param int $perm_id : permission id of the permission
        */
        public function getPermissionLevel($perm_id)
        {
            if (self::$permissions == null) {
                self::$permissions = $this->dc->getPermissions($perm_id);
            }
            return self::$permissions[0]['manage_level'];
        }

        /**
        * get the total_allocated_size for the user
        */
        public function getTotalAllocatedSize()
        {
            $perm_id = self::$user["permission_id"];
            $override_perm_id = self::$user["override_perm_id"];
            if (self::$permissions == null) {
                self::$permissions = $this->dc->getPermissions($perm_id);
            }
            // check if there are override permissions for the user
            if (!empty($override_perm_id)) {
                $override_permissions = $this->dc->getPermissions($override_perm_id);
                $total_allocated_size = $override_permissions[0]['total_allocated_size'];
            } else {
                $total_allocated_size  = self::$permissions[0]['total_allocated_size'];
            }
            return $total_allocated_size;
        }
    
        /**
        * get the total_allocated_size for the user
        */
        public function getFileRetentionDuration()
        {
            $perm_id = self::$user["permission_id"];
            $override_perm_id = self::$user["override_perm_id"];
            if (self::$permissions == null) {
                self::$permissions = $this->dc->getPermissions($perm_id);
            }
            // check if there are override permissions for the user
            if (!empty($override_perm_id)) {
                $override_permissions = $this->dc->getPermissions($override_perm_id);
                $file_retention_duration = $override_permissions[0]['files_expire_after'];
            } else {
                $file_retention_duration  = self::$permissions[0]['files_expire_after'];
            }
            return $file_retention_duration;
        }
    }
