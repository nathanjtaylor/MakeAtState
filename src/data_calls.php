<?php
class DataCalls{
    // Instance of DBConnect
    protected $db = null;
    // static variable for lazy loading
    static protected $stdb = null;
    function __construct(){
        //lazy loading of DBConnect
        if(DataCalls::$stdb == null){
            DataCalls::$stdb = new DBConnect(APP::database());
            //DataCalls::$stdb->enableDebugInfo();
        }
        $this->db = DataCalls::$stdb;
    }
    /**
    * Gets the user row based on the user email
    * @param string $sValue - user_id of the user
    */
    public function getUserById($sValue){
        $sTable = "users";
        $sMatches = array("user_id"=>$sValue);
        $aRow = $this->getTableRowsByColumnMatch($sTable, $sMatches);
        return $aRow;
    }
    /**
    * Gets the user row based on the user email
    * @param string $sTable - name of the table
    * @param string $sValue - email of the user
    */
    public function getUserByEmail($sTable, $sValue){
        $sMatches = array("email"=>$sValue);
        $aRow = $this->getTableRowsByColumnMatch($sTable, $sMatches);
        return $aRow;
    }
    /**
    * Gets the user row based on the okta token
    * @param string $sTable - name of the table
    * @param string $sValue - okta_token of the user
    */
    public function getUserByOktaToken($sTable, $sValue){
        $sMatches = array("okta_token"=>$sValue);
        $aRow = $this->getTableRowsByColumnMatch($sTable, $sMatches);
        return $aRow;
    }
    /**
    * Gets the workflow  row based on the workflow type
    * @param string $sType : type of the workflow
    */
    public function getWorkflowByType($sType){
        $sTable = "workflows";
        $sMatches = array("name"=>$sType, 'workflow_removed'=>null);
        $aRow = $this->getTableRowsByColumnMatch($sTable, $sMatches);
        return $aRow;
    }

    /**
     * Gets the workflow  row based on the id
     * @param string $wid : id of the workflow
     */
    public function getWorkflowById($wid){
        $sTable = "workflows";
        $sMatches = array("workflow_id"=>$wid);
        $aRow = $this->getTableRowsByColumnMatch($sTable, $sMatches);
        return $aRow[0];
    }


    /**
    * Gets the user workflow_step row based on the printer_name
    * @param string $pName : Name of the printer
    * @param string int workflow_id : workflow id of the workflow
    */
    public function getWorkflowStepByPrinter($pName, $workflow_id){
        $sTable = "workflow_steps";
        $sMatches = array("printer_name"=>$pName ,"workflow_id" =>$workflow_id, 'step_removed'=>null);
        $aRow = $this->getTableRowsByColumnMatch($sTable, $sMatches);
        return $aRow;
    }
    /**
    * Get all unique permissions
    */
    public function getAllPermissions(){
        $pQuery = "SELECT * FROM permissions where internal_name is NOT NULL ";
        $permissions = $this->db->query($pQuery);
        return $permissions;
    }

    /**
    * Get all step types
    */
    public function getAllStepTypes(){
        $pQuery = "SELECT * FROM workflow_step_type ";
        $step_types = $this->db->query($pQuery);
        return $step_types;
    }

    /**
    * Get all workflow steps
    */
    public function getAllWorkflowSteps(){
        $pQuery = "SELECT * FROM workflow_steps WHERE step_removed IS NULL ORDER BY ordering";
        $steps = $this->db->query($pQuery);
        return $steps;
    }

    /**
    * adjust ordeing of steps
    * when an order number in the step is increased , increase the following step's order # by 1
    * @param int order_num : order number that needs to be adjusted
    */
    public function adjustWorkflowStepOrder($order_num){
        $aQuery = "UPDATE workflow_steps set ordering = ordering+1 where ordering >=".$order_num." AND step_removed IS NULL";
        #APP::printVar($this->db->queryDump($aQuery, $aValues));
        $aRows = $this->db->query($aQuery);
        return $aRows;
    }
    /**
    * Gets the next step in the workflow
    * @param array $aData: Data to be passed into the where clause
                $aData = array("workflow_id"=> 2, "printer_name"=>"Makerbot")
    * @param int $curr_step: current workflow step
    * @param int $skip: number of rows to skip
    * @param int $limit : number of rows to limit
    * @param array $order : columns names for ordering
                $order = array("ordering", "step_id")
    * @param bool $next : Set to true if we want to get the next step, set to false for previous step
    */
    public function getWorkflowStep($aData,$curr_step, $skip=0, $limit = null , $order, $next=False){

        $query = "SELECT s.*, t.* from workflow_steps s  left join workflow_step_type t on t.workflow_step_type_id = s.step_type_id";
        $sConditions = array();
        $iValues = array();

        if(!empty($aData)){
            foreach($aData as $aCol=>$aVal){
                $oColVal = ($aVal === null ? "IS NULL": '= ?');
                $sConditions["{$this->db->escapeIdentifier($aCol)} {$oColVal}"] = $aVal;
            }
        }
        if(!empty($sConditions)){
            $query .=  " WHERE " .implode(" AND ", array_keys($sConditions)) ;
            foreach($sConditions as $sCol=>$sVal){
                if(!empty($sVal)){
                    $iValues[] = $sVal;
                }
            }
        }
        // do not get the cancelled and user canclled step as  the part of the workflow
        if(isset($curr_step)){
            $query .= ($next) ? " AND ordering > ".$curr_step : " AND ordering < ".$curr_step;
        }
        if(!empty($order) && is_array($order)){

            $query .= " ORDER BY ";
            foreach($order as $k=>$o){
                $query .= $o;
                $query .= ((count($order) -1) == $k) ?" ": " ,";
            }
        }
        if(!empty($limit)){
             $query .=  "LIMIT {$skip}, {$limit} ";
        }
        //APP::printVar($this->db->queryDump($query, $iValues));
        $wRows = $this->db->query($query,$iValues);
        return $wRows;
    }
    /**
    ** Get the data from the table based on the field
    * @param string $sTable : Name of the table
    * @param array $aData Array of data with keys as column names and values as  values
                                $sData = array("file_id" => 1)
    */
    public function getRowsById($sTable , $aData, $skip = 0 , $limit = null, $order = null , $order_dir="ASC"){
        $aRows = $this->getTableRowsByColumnMatch($sTable, $aData, $skip , $limit , $order  , $order_dir);
        return $aRows;
    }
    /**
    * Updates the file path in the files tables
    * @param string $sTable : Name of the table to be updated
    * @param string $sPrimaryKey : Primary key of the table
    * @param array  $sData: Array of data with keys as column names and values as  values that need to be updated
                $sData = array("file_id" => 1 , "file_name"=> "name of the file")
    */
    public function updateUsingPrimaryKey($sTable, $sPrimaryKey, $sData){
        return $this->updateRowsInTable($sTable, $sPrimaryKey, $sData);
    }
    /**
    * Updates the file path in the files tables
    * @param string $sTable : Name of the table to be updated
    * @param array $sConditionsCol : array of column names to go into cwhere conditions
                     $sConditionsCol = array("workflow_id"m "printer_name")
    * @param array  $sData: Array of data with keys as column names and values as  values that need to be updated
                $sData = array("file_id" => 1 , "file_name"=> "name of the file")
    */
    public function updateUsingConditions($sTable, $sConditionsCol, $sData){
        return $this->updateRowsMultipleConditions($sTable, $sConditionsCol, $sData);
    }
    /**
    * Get all the files uploaded bu users
    * @param Int user_id : user id of the user
    */
    public function getUserFiles($user_id){
        $sTable = "files";
        $sMatches = array("user_id" => $user_id , "deleted" => null );
        $order = array('created');
        return $this->getTableRowsByColumnMatch($sTable, $sMatches, $skip = 0 , $limit = null, $order , $order_dir="DESC");
    }
    /**
    *Get files based on file_id
    * @param Int file_id : file id of the file
    */
    public function getFile($file_id){
        $sTable = "files";
        $sMatches = array("file_id"=>$file_id );
        return $this->getTableRowsByColumnMatch($sTable, $sMatches);
    }
    /**
    * Get permissions based on the permission_id
    * @param Int perm_id : permission_id of the permission
    */
    public function getPermissions($perm_id){
        $sTable = "permissions";
        $sMatches = array("permission_id" => $perm_id);
        return $this->getTableRowsByColumnMatch($sTable, $sMatches);
    }
    /**
    ** Get the permissions based on the internal permission name
    * @param string $sTable : Name of the table
    * @param array $aData Array of data with keys as column names and values as  values
                                $sData = array("internal_name" => "users")
    */
    public function getPermissionsUsingInternalName($sTable , $aData){
        $aRows = $this->getTableRowsByColumnMatch($sTable, $aData);
        return $aRows;
    }
    /**
    * Get all admin users or staff users or student staff
    * @param string status: Status of the user
                eg: "admin" or "staff", "student_staff"
    */
    public function getUserByStatus($status ,$skip=0, $limit=null){
        $uQuery = "select  SQL_CALC_FOUND_ROWS u.* , p.* from users u LEFT JOIN permissions p ON  u.permission_id = p.permission_id WHERE p.internal_name = ?";
        if(!empty($limit)){
            $uQuery .=  " LIMIT {$skip}, {$limit} ";
        }
        $uValues = array($status);
        #APP::printVar($this->db->queryDump($uQuery, $uValues));
        $uRow = $this->db->query($uQuery, $uValues);
        return $uRow;
    }
    /**
    * Get all the work flows
    */
    public function getWorkflows($hide_disabled=True){
        $query = "SELECT w.*, g.group_tag, g.group_name FROM workflows w LEFT JOIN groups g on w.group_id=g.group_id where w.workflow_removed is NULL";

        if ($hide_disabled) {
            $query .= " AND disabled IS NULL";
        }
        //APP::printVar($this->db->queryDump($query));
        $wRows = $this->db->query($query);

        return $wRows;
    }

    public function getGroups(){
        $query = "SELECT * FROM groups WHERE removed IS NULL";
        $wRows = $this->db->query($query);
        return $wRows;
    }

    public function getGroupAdminEmail($gid){
        $query = "SELECT admin_email FROM groups WHERE group_id=?";
        $gRow = $this->db->query($query, array($gid));
        return $gRow[0];
    }

    /**
    * Get all the initial steps of all printers in all workflows
    * these results are passed in as params to get all the jobs that are newly submitted (new jobs)
    * These are the jobs which are not yet prcessed by staff
    */
    public function getInitialSteps(){
        $sQuery = "SELECT * from workflow_steps WHERE step_removed is NULL ORDER BY ordering ASC LIMIT 1";
        $sRows =  $this->db->query($sQuery);
        return $sRows;
    }


    /**
    * Get all workflow steps for the job
    */
    public function getWorkflowStepsForJob(){
        $query = "SELECT w.* from workflow_steps w  LEFT JOIN workflow_step_type t ON w.step_type_id = t.workflow_step_type_id WHERE w.step_removed IS NULL AND  NOT (t.workflow_step_type_name= 'Cancelled by user step' OR t.workflow_step_type_name = 'Cancelled step')   ORDER BY ordering ASC" ;
        $sRow = $this->db->query($query, $pData);
        return $sRow;
    }
    /**
    * gets all distinct printers for all workflows
    */
    public function getAllPrinters(){
        $query = "SELECT DISTINCT printer_name from workflow_steps";
        $pRow = $this->db->query($query);
        return $pRow;
    }

    /**
    * gets all distinct printers for all workflows in a group
    */
    public function getGroupPrinters($group_id){

        $query = "SELECT DISTINCT ws.printer_name from workflow_steps ws
left join workflows w on w.workflow_id = ws.workflow_id
where w.group_id = ?";
        $pRow = $this->db->query($query, array($group_id));
        return $pRow;

    }

    /**
    * gets all distinct workflows in a group
    */
    public function getGroupWorkflows($group_id){

        $query = "SELECT * from workflows where group_id = ?";
        $wRow = $this->db->query($query, array($group_id));
        return $wRow;

    }

    /**
    * Get all the work flows
    * @param int skip: number of records to skip
    * @param int $limit : Limit for number of records
    * @param array $aData : Array of data with keys as column names and values as  values
                                $sData = array("user_id" => 10, "closed"=> null)
    * @param string $filter_by_printer: name of the printer to apply the filter on
    * @param array $sort_array: array contating sort field and sort dir
                                $sort_array = array("sort_field"=>"j.created", "sort_dir"=>"ASC");
     * @param int $hold: 0 - display all, 1 - display no-hold, 2 - display holds only
    */
    public function  getActiveJobs($aData, $skip=null, $limit=null,$filter_by_printer=null, $sort_array=array(), $hold = 0, $filter_by_group=null){
        $query = "SELECT SQL_CALC_FOUND_ROWS  j.* , p.project_id, p.project_name, count( jf.job_file_id) as file_count , w.* ,s.* ,t.*, u.user_id, u.email, u.fullname, u.lastname, u.phone_num
                from jobs j
                left join projects p on p.project_id = j.project_id
                LEFT JOIN job_files jf on jf.job_id = j.job_id AND jf.removed IS NULL
                left join workflow_steps w on  w.work_step_id = j.curr_work_step_id
                left join job_steps s on s.job_id = j.job_id AND  s.work_step_id =j.curr_work_step_id
                left join workflow_step_type t on t.workflow_step_type_id = w.step_type_id
                left join users u on u.user_id = j.user_id";


        if($hold == 2){
            //include holds
            $query .= " inner join job_holds h on j.job_id = h.job_id AND h.hold_released is NULL ";
        }
        else if($hold == 1) {
            //exclude holds
            $query .= " left outer join job_holds h on j.job_id = h.job_id AND h.hold_released is NULL ";
        }

        $sConditions = array();
        $iValues = array();
        if(!empty($aData)){
            foreach($aData as $aCol=>$aVal){
                $oColVal = ($aVal === null ? "IS NULL":( ($aVal === 'NOT NULL')? " IS NOT NULL " : ((strpos($aVal, "IN") === false) ? "=?" : $aVal )) );
                $aVal = ($aVal === null ? null:( ($aVal === 'NOT NULL')? null  : ((strpos($aVal, "IN") === false) ? $aVal : null )    ) );
                $sConditions["j.{$aCol} {$oColVal}"] = $aVal;
            }
        }
        if(!empty($sConditions)){
            $query .=  " WHERE " .implode(" AND ", array_keys($sConditions)) ;
            foreach($sConditions as $sCol=>$sVal){
                if(!empty($sVal)){
                    $iValues[] = $sVal;
                }
            }
        }

        if($hold==1) {
            $query .= " AND h.job_id IS NULL";
        }


        $query .= " AND s.reset IS NULL";
        $query .= " GROUP BY jf.job_id";

        if(!empty($sort_array)){
            $query .= " ORDER BY {$sort_array['sort_field']} {$sort_array['sort_dir']} ";
        }
        else{
            #default to job created time in ASC order
            $query .= " ORDER BY j.created ASC";
        }
        if( !empty($limit)){
             $query .=  " LIMIT {$skip}, {$limit} ";
        }
        //APP::printVar($this->db->queryDump($query,$iValues));
        $wRows = $this->db->query($query,$iValues);
        return $wRows;
    }
    /**
    * Get total number of jobs for each printer
    * @param string $printer_name: name of the printer to get the count for the jobs corresponding to the printer
    * @param int skip: number of records to skip
    * @param int $limit : Limit for number of records
    * @param array $aData : Array of data with keys as column names and values as  values
                                $sData = array("user_id" => 10, "closed"=> null)
     * @param int $hold: 0 - count all, 1 - count no-hold, 2 - count holds only
     */

    public function getCountsForJobsonPrinters($aData, $printer_name, $skip=null, $limit=null, $hold=0){
        $query = "SELECT COUNT(j.job_id) as job_count
                from jobs j
                left join workflow_steps w on  w.work_step_id = j.curr_work_step_id
                left join job_steps s on s.job_id = j.job_id AND  s.work_step_id =j.curr_work_step_id
                left join workflow_step_type t on t.workflow_step_type_id = w.step_type_id
                left join users u on u.user_id = j.user_id ";

        if($hold == 2){
            //include holds
            $query .= " inner join job_holds h on j.job_id = h.job_id AND h.hold_released is NULL ";
        }
        else if($hold == 1) {
            //exclude holds
            $query .= " left outer join job_holds h on j.job_id = h.job_id AND h.hold_released is NULL ";
        }
        $sConditions = array();
        $iValues = array();
        if(!empty($aData)){
            foreach($aData as $aCol=>$aVal){
                $oColVal = ($aVal === null ? "IS NULL":( ($aVal === 'NOT NULL')? " IS NOT NULL " : ((strpos($aVal, "IN") === false) ? "=?" : $aVal )) );
                $aVal = ($aVal === null ? null:( ($aVal === 'NOT NULL')? null  : ((strpos($aVal, "IN") === false) ? $aVal : null )    ) );
                $sConditions["j.{$aCol} {$oColVal}"] = $aVal;
            }
        }
        if(!empty($sConditions)){
            $query .=  " WHERE " .implode(" AND ", array_keys($sConditions)) ;
            foreach($sConditions as $sCol=>$sVal){
                if(!empty($sVal)){
                    $iValues[] = $sVal;
                }
            }
        }
        $query .= " AND s.reset IS NULL";
        $query .= " AND w.printer_name =  '{$printer_name}' ";

        if($hold==1) {
            $query .= " AND h.job_id IS NULL";
        }

        $query .= " ORDER BY j.created ASC";
        if( !empty($limit)){
             $query .=  " LIMIT {$skip}, {$limit} ";
        }
        #APP::printVar($this->db->queryDump($query,$iValues));
        $wRows = $this->db->query($query,$iValues);
        return $wRows;
    }
    /**
    *get user and file details for job
    * @param Int job_id: job id of the job
    */
    public function getJobUserDetails($job_id) {
        $job_id = intval($job_id);
        $query = "SELECT j.*, u.* FROM jobs j LEFT JOIN users u on j.user_id = u.user_id WHERE j.job_id = ?";
        $jData = array($job_id);
        $jRow = $this->db->query($query, $jData);
        return $jRow;
    }
    /**
    * get all the step updates for the job
    * @param Int job_id: job id of the job
    */
    public function getJobStepUpdates($job_id) {
        $job_id = intval($job_id);
        $query = "SELECT  j.*,  s.*, u.*, w.* FROM jobs j LEFT JOIN job_steps s on s.job_id=j.job_id  LEFT JOIN users u on u.user_id = s.completed_user_id  LEFT JOIN workflow_steps w on w.work_step_id=s.work_step_id  where j.job_id = ? AND s.completed IS NOT NULL " ;
        $jData = array($job_id);
        $jRow = $this->db->query($query, $jData);
        //APP::printVar($jRow);
        return $jRow;
    }

    /**
     * get all the holds for the job
     * @param Int job_id: job id of the job
     */
    public function getJobHolds($job_id) {
        $job_id = intval($job_id);
        $query = "SELECT  j.*, u.*, h.* FROM jobs j LEFT JOIN job_holds h on h.job_id=j.job_id LEFT JOIN users u on u.user_id = h.completed_user_id where hold_placed is not NULL and j.job_id=?";
        $jData = array($job_id);
        //APP::printVar($this->db->queryDump($query, $jData));
        $hRow = $this->db->query($query, $jData);
        return $hRow;
    }
    /**
    * get all the messages for the job
    * @param Int job_id: job id of the job
    */
    public function getJobMessages($job_id) {
        $job_id = intval($job_id);
        $query = "SELECT  j.*, u.* , e.*  FROM jobs j LEFT JOIN job_updates u on j.job_id =u.job_id LEFT JOIN emails e on e.email_id = u.email_id  where j.job_id = ? AND e.sent IS NOT NULL ORDER BY e.sent DESC" ;
        $jData = array($job_id);
        $jRow = $this->db->query($query, $jData);
        return $jRow;
    }

    /**
     * retrieve the open hold for a given job
     * @param $job_id id of the job to find hold of
     * @return mixed array of the hold for the given job
     */
    public function getJobOpenHold($job_id) {
        $job_id = intval($job_id);
        $query = "SELECT * from job_holds WHERE job_id = ? AND hold_released is NULL";
        $jData = array($job_id);
        //APP::printVar($this->db->queryDump($query, $jData));
        $jRow = $this->db->query($query, $jData);
        return $jRow;
    }

    /**
     * release any open holds for the jobs
     * @param $job_id id of the job to release hold of
     */
    public function releaseJobHold($job_id) {
        $job_id = intval($job_id);
        $query = "UPDATE job_holds
                  SET hold_released = ?
                  WHERE job_id = ? and hold_released is NULL";
        $jData = array(date('Y-m-d H:i:s'), $job_id);
        $jRow = $this->db->query($query, $jData);
    }
    /**
    * get all the notes for the job
    * @param Int job_id: job id of the job
    */
    public function getJobNotes($job_id) {
        $job_id = intval($job_id);
        $query = "SELECT  n.*, u.*   FROM notes n LEFT JOIN users u on n.added_user_id =u.user_id   where n.job_id = ? ORDER BY n.note_created DESC" ;
        $jData = array($job_id);
        $nRow = $this->db->query($query, $jData);
        return $nRow;
    }

    /**
     * get all cancellation reasons of a given type
     * @param $for_staff 0 for patrons, 1 for staff
     * @return result row of query
     */
    public function getCancellationReasons($for_staff) {
        $query = "SELECT * FROM cancellation_reasons where for_staff=?;";
        $rRow = $this->db->query($query,array($for_staff));
        return $rRow;
    }

    /**
     * add a new cancellation reason
     * @param $for_staff 0 for patrons, 1 for staff
     * @param $reason string - reason that will be displayed to user
     * @return mixed result row of query
     */
    public function insertCancellationReason($for_staff, $reason, $more){
        $query = "INSERT INTO cancellation_reaons (for_staff, reason, more_information) VALUES(?,?,?);";
        $rRow = $this->db->query($query,array($for_staff, $reason, $more));
        return $rRow;
    }

    /**
     * remove a cancellation reason
     * @param $reason_id id of the reason to be removed
     * @return mixed result array of query
     */
    public function removeCancellationReason($reason_id) {
        $reason_id = intval($reason_id);
        $query = "DELETE FROM cancellation_reasons where cancellation_reason_id=?";
        $rRow = $this->db->query($query,array($reason_id));
        return $rRow;
    }


    /**
     * insert a new cancellation instance into database
     * @param $job_id id of job that has been cancelled
     * @param $reason_id id of the reason selected by user
     * @param $more_reason string of user input for 'other' option, if applicable
     * @return mixed result array of query
     */
    public function insertCancellation($job_id, $reason_id, $more_reason) {
        $query = "INSERT INTO cancellations (job_id, reason_id, more_reason)
VALUES(?, ?, ?);";
        $cRow = $this->db->query($query,array($job_id, $reason_id, $more_reason));
        return $cRow;
    }

    /**
     * get the cancellation instance row for a particular job
     * @param $job_id id of job
     * @return mixed array of the cancellation instance for the given job
     */
    public function getJobCancellation($job_id) {
        $job_id = intval($job_id);
        $query = "SELECT r.reason, c.more_reason FROM cancellations as c LEFT JOIN cancellation_reasons as r ON c.reason_id = r.cancellation_reason_id WHERE c.job_id=?";

        $cRow = $this->db->query($query,array($job_id));
        return $cRow[0];
    }

    /**
     * Get the text reason for a given reason ID
     * @param $reason_id cancellation_reason_id to retrieve
     * @return the text cancellation reason
     */
    public function getCancellationReasonText($reason_id) {
        $query = "SELECT reason FROM cancellation_reasons where cancellation_reason_id=?;";
        $cRow = $this->db->query($query,array($reason_id));
        return $cRow[0]['reason'];
    }

    /**
     * removes any cancellation for a given job from the database if it exists
     * @param $job_id job id to remove cancellations of
     * @return mixed query result array
     */
    public function removeJobCancellation($job_id) {
        $job_id = intval($job_id);
        $query = "DELETE FROM cancellations where job_id=?";
        $cRow = $this->db->query($query,array($job_id));
        return $cRow;
    }

    /**
     * get the stats for the stats page regarding cancellation reasons
     * @return array of the number of instances in the cancellation for each respective reason
     */
    public function getCancellationJobStats() {
        $data = array();
        $reasons_query = "SELECT cancellation_reason_id, reason FROM cancellation_reasons;";
        $reasons = $this->db->query($reasons_query);
        $count_query = "SELECT COUNT(job_id) FROM cancellations where reason_id=?;";
        foreach($reasons as $reason){
            $count = $this->db->query($count_query, array($reason['cancellation_reason_id']))[0]['COUNT(job_id)'];
            if($count >0) {
                if(!isset( $data[$reason['reason']])){
                    $data[$reason['reason']] = 0;
                }
                $data[$reason['reason']] += $count;
            }
        }


        return $data;

    }

    public function getExportStats() {
       $query = "SELECT c.created, r.reason, c.more_reason from cancellations c
left join cancellation_reasons r
on c.reason_id=r.cancellation_reason_id
ORDER BY created;";

        return $this->db->query($query);

    }
    /**
    * get assessment questions
    * currently hardcoded for testing purposes
    */
    public function getAssessmentQuestions() {
        return [
            ['qid' => 'q1', 'text' => 'Can MSU Libraries post a picture of your work on its Social Media accounts?', 'type' => "2"],
            ['qid' => 'q2', 'text' => 'List Instagram accounts you want to be tagged with:', 'type' => "1"],
            ['qid' => 'q3', 'text' => 'Is this project part of an MSU class?', 'type' => "2"],
            ['qid' => 'q4', 'text' => 'What class or course# or section# is the project associated with?', 'type' => "1"],
            ['qid' => 'q5', 'text' => 'Is the project you are submitting associated with any of these items?', 'type' => "3", 'options' => [
                                                                                            ['option_text' => 'This is a gift, for fun, or personal project', 'oid' => 'o1'], 
                                                                                            ['option_text' => 'This is a homework assignment', 'oid' => 'o2'], 
                                                                                            ['option_text' => 'Part of a graduate thesis or dissertation', 'oid' => 'o3'],
                                                                                            ['option_text' => 'Research related', 'oid' => 'o4'],
                                                                                            ['option_text' => 'A work-related job or task (e.g. exhibition, promotions or giveaways)', 'oid' => 'o5'],
                                                                                            ['option_text' => 'Prototyping for Business or Entrepreneurship', 'oid' => 'o6'],
                                                                                            ['option_text' => 'Other', 'oid' => 'o7'],
                                                                                            ['option_text' => 'I prefer not to say', 'oid' => 'o8']
                                                                                            ]],
            ['qid' => 'q6', 'text' => 'We would love to hear more about what you are working on. Please feel free to share more details.', 'type' => "4"]
        ];
    }

    /**
    * get message and job details
    * @param int email_id : email id for the message
    * @param int user_id : user id of the user
    */
    public function getMessageDetails($email_id, $user_id){
        $query = "SELECT u.* , j.*, e.* , s.*, w.* from job_updates u
            LEFT JOIN jobs j on j.job_id = u.job_id
            LEFT JOIN job_steps s on s.job_step_id = u.job_step_id
            LEFT JOIN workflow_steps w on s.work_step_id = w.work_step_id
            LEFT JOIN emails e on e.email_id = u.email_id where u.email_id = ? AND e.user_id = ?";
        $eData = array($email_id, $user_id);
        #APP::printVar($this->db->queryDump($query, $eData));
        $eRow = $this->db->query($query, $eData);
        return $eRow;
    }
    /**
    * get messages for the job
    * @param int job_id :job id for the job
    * @param int user_id : user id of the user
    */
    public function getJobMessageDetails($job_id){
        $query = "SELECT user.*, u.* , j.*, e.* , s.*, w.* from job_updates u
            LEFT JOIN jobs j on j.job_id = u.job_id
            LEFT JOIN job_steps s on s.job_step_id = u.job_step_id
            LEFT JOIN workflow_steps w on s.work_step_id = w.work_step_id
            LEFT JOIN emails e on e.email_id = u.email_id
            LEFT JOIN users user on e.sent_user_id = user.user_id where u.job_id = ?";
        $eData = array($job_id);
        #APP::printVar($this->db->queryDump($query, $eData));
        $eRow = $this->db->query($query, $eData);
        return $eRow;
    }
    /**
    * get all the messages of the user
    * @param Int user_id: user _id of the user
    */
    public function getUserMessages($user_id) {
        $user_id = intval($user_id);
        $query = "SELECT e.*, u.*, j.* , d.*  from users u LEFT JOIN jobs j on u.user_id=j.user_id LEFT JOIN job_updates d on j.job_id=d.job_id LEFT JOIN emails e on d.email_id = e.email_id WHERE u.user_id = ? AND e.sent IS NOT NULL ORDER BY e.sent DESC";
        $uData = array($user_id);
        $uRow = $this->db->query($query, $uData);
        return $uRow;
    }
    /**
    * get all job_id associated with messages for the user
    * @param array uData : array conatining user _id of the user eg:- $uData = array("user_id" => $user_id)
    * @param Int skip: number of rows to skip in the sql query
    * @param Int limit: number of rows to limit to in the sql query
    */
    public function getUserMessageJobs($uData, $skip =0, $limit = null) {
        $query = "SELECT p.project_id, p.project_name, ju.update_id, ju.email_id, COUNT(ju.job_id) message_count, ju.job_id from job_updates ju 
                    LEFT JOIN jobs j on j.job_id = ju.job_id 
                    LEFT JOIN projects p on p.project_id = j.project_id 
                    LEFT join users u on u.user_id = j.user_id where u.user_id=?
                    GROUP BY ju.job_id
                    ORDER BY ju.created  DESC";
        if($limit !== null ){
            $skip = intval($skip);
            $limit = intval($limit);
            $query .=  " LIMIT {$skip}, {$limit} ";
        }
        //APP::printVar($this->db->queryDump($query, $uData));
        $uRow = $this->db->query($query, $uData);
        return $uRow;
    }

    /**
    * get all the attachments for the email
    * @param Int email_id : email id for the email
    */
    public function getEmailAttachments($email_id){
        $query = "SELECT a.* , f.*
                    FROM attachments a
                    LEFT JOIN files f on a.file_id = f.file_id
                    WHERE a.email_id = ?";
        $eData = array($email_id);
        #APP::printVar($this->db->queryDump($query, $eData));
        $aRow = $this->db->query($query, $eData);
        return $aRow;
    }

    /**
    *Mark  the files as deleted
    * @param Int file_id: file id of the file that needs to be marked as deleted
    */
    public function setFileAsDeleted($file_id) {
        $file_id = intval($file_id);
        $query = "UPDATE  files SET deleted = NOW()  WHERE file_id = ?" ;
        $fData = array($file_id);
        $fRow = $this->db->query($query, $fData);
        return $fRow;
    }

    /**
    *Mark  the items in cart as deleted
    * @param int project_id: project_id of the item that needs to be marked as deleted
    */
    public function markProjectAsDeleted($project_id) {
        $cart_id = intval($project_id);
        $query = "UPDATE  projects SET removed = NOW()  WHERE project_id = ?" ;
        $cData = array($project_id);
        $cRow = $this->db->query($query, $cData);
        return $cRow;
    }

    /**
    *Mark  the items in cart as deleted
    * @param int project_id: project_id of the item that needs to be marked as deleted
    */
    public function markCartItemAsDeleted($project_id) {
        $cart_id = intval($project_id);
        $query = "UPDATE  cart SET removed = NOW()  WHERE project_id = ?" ;
        $cData = array($project_id);
        $cRow = $this->db->query($query, $cData);
        return $cRow;
    }

    /**
    *Mark  the items in project as submitted
    * @param int project_id: project id of the item that needs to be marked as deleted
    */
    public function markProjectAsSubmitted($project_id) {
        $cart_id = intval($project_id);
        $query = "UPDATE  projects SET submitted = NOW()  WHERE project_id = ?" ;
        $cData = array($project_id);
        $cRow = $this->db->query($query, $cData);
        return $cRow;
    }
    
    /**
    *Mark  the items in cart as submitted
    * @param int cart_id: cart id of the item that needs to be marked as deleted
    */
    public function markCartItemAsSubmitted($cart_id) {
        $cart_id = intval($cart_id);
        $query = "UPDATE  cart SET submitted = NOW()  WHERE cart_id = ?" ;
        $cData = array($cart_id);
        $cRow = $this->db->query($query, $cData);
        return $cRow;
    }
    /**
    *Mark  the items in cart as deleted
    * @param int file_id: file id of the item that needs to be marked as deleted
    */
    public function markCartFileAsDeleted($file_id) {
        $file_id = intval($file_id);
        $query = "UPDATE  cart SET removed = NOW()  WHERE file_id = ? AND removed = NULL" ;
        $cData = array($file_id);
        $cRow = $this->db->query($query, $cData);
        return $cRow;
    }
    /**
    *Mark message as read
    * @param int email_id: email id of the message that needs to be marked as read
    */
    public function markEmailsAsRead($email_id) {
        $email_id = intval($email_id);
        $query = "UPDATE  emails SET message_read = NOW()  WHERE email_id = ? " ;
        $eData = array($email_id);
        #APP::printVar($this->db->queryDump($query, $eData));
        $eRow = $this->db->query($query, $eData);
        return $eRow;
    }

    /**
    *Mark all  the items in cart as deleted
    * @param Int user_id: user id  of the cart  items that needs to be marked as deleted
    * @param bool $undo : is set to true if the action is undo
    */
    public function markAllCartItemAsDeleted($user_id, $undo = FALSE) {
        $user_id = intval($user_id);
        if($undo){
            $query = "UPDATE  cart SET removed = NULL  WHERE user_id = ?" ;
        }
        else{
            $query = "UPDATE  cart SET removed = NOW()  WHERE user_id = ?" ;
        }
        $cData = array($user_id);
        $cRow = $this->db->query($query, $cData);
        return $cRow;
    }

    /**
    *Mark job files as removed
    * @param Int job_id: job id that needs to be marked as removed
    * @param Int file_id: file id that needs to be marked as removed
    * @param bool $undo : is set to true if the action is undo
    */
    public function markJobFilesAsDeleted($job_id, $file_id, $undo = FALSE) {
        $user_id = intval($user_id);
        if($undo){
            $query = "UPDATE  job_files SET removed = NULL  WHERE job_id = ? AND file_id = ?" ;
        }
        else{
            $query = "UPDATE  job_files SET removed = NOW()  WHERE job_id = ? AND file_id = ?" ;
        }
        $cData = array($job_id, $file_id);
        $cRow = $this->db->query($query, $cData);
        return $cRow;
    }
    /**
    * Mark current step as completed
    * @param array sData : data to se inserted into the jobs table
             array('job_id'=>$job_id, 'job_updated'=>'NOW()', 'curr_work_step_id'=>$curr_work_step_id);
    **/
    public function updateStepsInJob($sData){
        $sTable = "jobs";
        $sPrimaryKeyCol = "job_id";
        return $this->updateRowsInTable($sTable, $sPrimaryKeyCol, $sData);
    }
    /**
    * Mark current step as completed
    * @param array sData : data to se inserted into the job_steps table
             array('job_step_id'=>$job_step_id, 'completed'=>'NOW()', 'completed_user_id'=>$this->user_id);
    **/
    public function markStepAsCompleted($sData){
        $sTable = "job_steps";
        $sPrimaryKeyCol = "job_step_id";
        return $this->updateRowsInTable($sTable, $sPrimaryKeyCol, $sData);
    }
    /**
    * Mark current step as completed
    * @param array aData : data to se inserted into the job_steps table
                $aData = array($this->job_id, $this->current_step, $data, $this->user_id)
    **/
    public function insertNextStep($aData){
        $query = "INSERT INTO job_steps SET
                        job_id = ?,
                        work_step_id = ?,
                        data =?,
                        completed_user_id = ?";
        #APP::printVar($this->db->queryDump($query, $aData));
        return $this->db->query($query, $aData);
    }
    /**
    * Mark cancel step as completed
    * @param array aData : data to se inserted into the job_steps table
                $aData = array($this->job_id, $this->current_step, $data, $this->user_id)
    **/
    public function insertSpecialStep($aData){
        $query = "INSERT INTO job_steps SET
                        job_id = ?,
                        work_step_id = ?,
                        data =?,
                        completed_user_id = ?,
                        completed =?";
        //APP::printVar($this->db->queryDump($query, $aData));

        return $this->db->query($query, $aData);
    }

    public function insertJobHold($aData){
        $query = "INSERT INTO job_holds
                  SET
                  job_id = ?,
                  on_hold_step_id = ?,
                  hold_placed = ?,
                  completed_user_id = ?
                  ";
       // APP::printVar($this->db->queryDump($query, $aData));

        return $this->db->query($query, $aData);

    }
    /**
    * Get the row of the step
    * @param array $sMatches : Array of columns and values for the where clause
                    $sMatches =  array("job_id" => $job_id, "work_step_id"=>$work_step_id)
    */
    public function getStepRow($sMatches){
        $sTable = 'job_steps';
        $sRow = $this->getTableRowsByColumnMatch($sTable, $sMatches);
        return $sRow;
    }

    /**
    * Resets steps for the job in job_steps after they have been completed
    * @param array $mSteps : array of steps that need to be reset
    * @param array $job_id : job_id for the job that needs resetting of steps
    */
    public function resetJobSteps($mSteps, $job_id){
        $sQuery = "UPDATE job_steps set reset = NOW() WHERE job_id = ".$job_id ;
        if(!empty($mSteps)){
            $sQuery .= " AND (";
            foreach ($mSteps as $k=>$step ){
                $sQuery .= ($step == end($mSteps)) ?  " work_step_id = " .$step . ")": " work_step_id = " .$step . " OR ";
             }
        }
        #APP::printVar($this->db->queryDump($sQuery));
        return $this->db->query($sQuery);
    }

    /**
    * Get the row of the job
    * @param array $sMatches : Array of columns and values for the where clause
                    $sMatches =  array("job_id" => $job_id)
    */
    public function getJobRow($sMatches){
        $sTable = 'jobs';
        $sRow = $this->getTableRowsByColumnMatch($sTable, $sMatches);
        return $sRow;
    }

    /**
    * Get the row of the job
    * @param array $sMatches : Array of columns and values for the where clause
                    $sMatches =  array("job_id" => $job_id)
    */
    public function getJobFiles($sMatches){
        $sTable = 'job_files';
        $sRow = $this->getTableRowsByColumnMatch($sTable, $sMatches);
        return $sRow;
    }

    /**
    * Get the row of the workflow step
    * @param array $sMatches : Array of columns and values for the where clause
                    $sMatches =  array("workflow_id" => $workflow_id, "work_step_id"=> $work_step_id)
    */
    public function getWorkflowStepRow($sMatches){
        $sTable = 'workflow_steps';
        $sRow = $this->getTableRowsByColumnMatch($sTable, $sMatches);
        return $sRow;
    }

    /**
    * Get the emails of the user
    * @param array $sMatches : Array of columns and values for the where clause
                    $sMatches =  array("user_id" => $user_id)
    */
    public function getUserEmails($sMatches ,$skip=0, $limit=null){
        $sTable = 'emails';
        $order = array('sent');
        $order_dir = "DESC";
        $sRow = $this->getTableRowsByColumnMatch($sTable, $sMatches, $skip, $limit, $order, $order_dir);
        return $sRow;
    }
    /**
    * Gets the total number of rows of the frevious query
    * Used to get the total number of rows for pagination
    * FLag `SQL_CALC_FOUND_ROWS` should be given in previous query to get the total count instead of just getting the limit
    */
    public function getTotalRows(){
        $query = "SELECT FOUND_ROWS()";
        $aFound = $this->db->query($query);
        if(isset($aFound[0]['FOUND_ROWS()'])){
            return $aFound[0]['FOUND_ROWS()'];
        }
        return 0;
    }
    /**
    * Given table name , column name , column values returns the rows from the table which match the criteria
    * @param string sTable  - name of the table
    * @param array sMatches - Key value pair of the column name and column values
            $sMatches =  array('col1'=>'val1', 'col2', 'val2')
    * @param int skip - number of records to be skipped
    * @param int limit - limit for the total number of records retrived;
    * @param array order - column names for ORDER BY
                $order = array("step_number", "order_number")
    * @param string order_dir = Direction of the order "ASC" or "DESC"
    */
    protected function getTableRowsByColumnMatch($sTable, $sMatches , $skip = 0 , $limit = null, $order = null , $order_dir="ASC"){
        // TODO -- $sOrderby , pass in array to order the results column names
        $aRows = array();
        //Add backticks for escaping table name and column names
        $sTable = $this->db->escapeIdentifier($sTable);
        $sColNames = array_keys($sMatches);
        for($i=0;$i<count($sMatches); $i++){
            $myVal = $sMatches[$sColNames[$i]];
            $sEscapedColName =  $this->db->escapeIdentifier($sColNames[$i]);
            unset($sMatches[$sColNames[$i]]);
            $sMatches[$sEscapedColName] = $myVal;
        }
        if(empty($sTable) || empty($sMatches) || array_key_exists('', $sMatches)){
            $aRows = null;
        }
        else {
            $sConditions = array();
            foreach($sMatches as $sCol=>$aVal){
                $oColVal = ($aVal === null ? "IS NULL":( ($aVal === 'NOT NULL')? " IS NOT NULL " : '= ?') );
                $aVal = ($aVal === null ? null:( ($aVal === 'NOT NULL')? null  : $aVal) );
                $sConditions["{$sCol} {$oColVal}"] = $aVal;
            }
            $query = "SELECT SQL_CALC_FOUND_ROWS * FROM ". $sTable . " WHERE " .implode(" AND ", array_keys($sConditions)) ;
            $sValues = array();
            foreach($sConditions as $key=>$val){
                if(!empty($val)){
                    $sValues[] = $val;
                }
            }
            if(!empty($order) && is_array($order)){
                $query .= " ORDER BY ";
                foreach($order as $k=>$o){
                    $query .= $o;
                    $query .= ((count($order) -1) == $k) ?" ".$order_dir. " ": $order_dir. " , ";
                }
            }
            if(!empty($limit)){
                $query .=  "LIMIT {$skip}, {$limit} ";
            }
            #APP::printVar($this->db->queryDump($query, $sValues));
            $aRows = $this->db->query($query, $sValues);
        }
        return $aRows;
    }
    /**
    * Update tabe with values in tables
    * @param string $sTableName: name of the table to be updated
    * @param string $PrimaryKeyCol : primary key of the table
    * @param array $sDataRow : key values of table columms and values
    */
    protected function updateRowsInTable($sTableName, $sPrimaryKeyCol, $sDataRow){
        $rUpdated = false;
        if(is_array($sDataRow) && array_key_exists($sPrimaryKeyCol, $sDataRow) && count($sDataRow)> 1){
            $iPrimaryKeyVal = $sDataRow[$sPrimaryKeyCol];
            unset($sDataRow[$sPrimaryKeyCol]);
            # Update query
            $query = "UPDATE ".$this->db->escapeIdentifier($sTableName). " SET ";
            $aValues = array();
            $nValues = array();
            foreach($sDataRow as $sCol=>$sVal){
                if( count($aValues) >=  1 || count($nValues)>=1){
                    $query .= ",";
                }
                if($sVal == "NULL"){
                    $query .= $this->db->escapeIdentifier($sCol) . " = NULL ";
                    $nValues[] = $sVal;
                }
                else{
                    $query .= $this->db->escapeIdentifier($sCol) . " = ? ";
                    $aValues[] = $sVal;
                }
            }
            # Primary key where clause
            $query .= "WHERE " . $this->db->escapeIdentifier($sPrimaryKeyCol). " = ?";
            $aValues[] = $iPrimaryKeyVal;
            //APP::printVar($this->db->queryDump($query, $aValues));
            $iUpdated =  $this->db->query($query, $aValues);
            $rUpdated = ($iUpdated >= 1);
        }
        return $rUpdated ;
    }
    /**
    * Update table with values in tables
    * @param string $sTableName: name of the table to be updated
    * @param array $sConditionsCol : array of columum names for where condition
    * @param array $sDataRow : key values of table columms and values
    */
    protected function updateRowsMultipleConditions($sTableName, $sConditionsCol, $sDataRow){
        $rUpdated = false;
        $rConditionsColexists = false;
        $iConditionValues = array();
        //check if all the conditions columns exist in $sDataRow
        foreach( $sConditionsCol as $col_name){
            if(array_key_exists($col_name, $sDataRow)){
                $rConditionsColexists = true;
                // set the condition values in a new array
                $iConditionValues[$col_name] = $sDataRow[$col_name];
                // remove the condition values from the data array , data array showld only consit of values that need to be updated
                unset($sDataRow[$col_name]);
            }
            else{
                $rConditionsColexists = false;
                break;
            }
        }
        if(is_array($sDataRow) && $rConditionsColexists  && count($sDataRow)>= 1){
        # Update query
            $query = "UPDATE ".$this->db->escapeIdentifier($sTableName). " SET ";
            $aValues = array();
            $nValues = array();
            foreach($sDataRow as $sCol=>$sVal){
                if( count($aValues) >=  1 || count($nValues)>=1){
                    $query .= ",";
                }
                if($sVal == "NULL"){
                    $query .= $this->db->escapeIdentifier($sCol) . " = NULL ";
                    $nValues[] = $sVal;
                }
                else{
                    $query .= $this->db->escapeIdentifier($sCol) . " = ? ";
                    $aValues[] = $sVal;
                }
            }
            # Conditions key where clause
            $where_clause_count = count($iConditionValues);
            $i = 0;
            $query .= " WHERE ";
            foreach($iConditionValues as $colName=>$colValue){
                $query .= $this->db->escapeIdentifier($colName). " = ?";
                if(++$i !==  $where_clause_count ){
                     $query .= " AND ";
                }
                $aValues[] = $colValue;
            }
            //APP::printVar($this->db->queryDump($query, $aValues));
            $iUpdated =  $this->db->query($query, $aValues);
            $rUpdated = ($iUpdated >= 1);
            }
        return $rUpdated ;
    }
    /**
    * Update printer name in workflow steps
    * @param array pValues =  array of values to be send to the db
                     $pValues = array("new_printer_name" , 2 , "old_printer_name")
    */
    public function updatePrinterInWorkflowSteps($pValues){
        $pUpdated = false;
        $query = "UPDATE workflow_steps SET printer_name = ? WHERE workflow_id = ? AND printer_name=?";
        $iUpdated =  $this->db->query($query, $pValues);
        $pUpdated = ($iUpdated >= 1);
        return $pUpdated ;
    }
    /**
    Insert file neams and path into the files table
    * @param int user_id : user id of the user uploading the file
    * @param string file_name : file name of the yploaded file
    * @param string  sys_file_path : path of the file strord in the system
    * @param file_size : size of the file uploaded
    */
    public function insertFiles($user_id, $file_name, $sys_file_path, $file_size){
        $query = "INSERT INTO files
                 SET      user_id = ?,
                     file_name = ?,
                     sys_file_path = ?,
                     file_size = ?";
        $aValues =  array($user_id, $file_name, $sys_file_path, $file_size);
        return $this->db->query($query, $aValues);
    }
    /**
    Create a new project
    * @param int user_id : user id of the user uploading the file
    * @param string project_name : Project name of the project
    */
    public function createProjects($user_id, $project_name){
        $query = "INSERT INTO projects
                    SET user_id = ? , project_name = ?";
        $aValues =  array($user_id, $project_name);
        //APP::printVar($this->db->queryDump($query, $aValues));
        return $this->db->query($query, $aValues);
    }
    /**
    Insert files into cart
    * @param int user_id : user id of the user uploading the file
    * @param int file_id : file id of the uploaded file
    * @param int file_id : project id for the file
    * @param array cData : Cart data for the file which has printers materials colors
    */
    public function insertFilesInCart($user_id, $file_id, $project_id, $cData){
        $query = "INSERT INTO cart
                 SET      user_id = ?,
                     file_id = ?,
                     project_id = ?,
                     cart_data = ?";
        $aValues =  array($user_id, $file_id, $project_id, $cData);
        //APP::printVar($this->db->queryDump($query, $aValues));
        return $this->db->query($query, $aValues);
    }
    /*
    * Insert new user into the user table
    * @param $aData  Array of column names and values
             array("column_name"=> value)
    */
    public function insertUsers($aData){
        $sTable = "users";
        $iRows = $this->insertIntoTable($sTable, $aData);
        return $iRows;
    }
    /*
    * Insert onetime token expiration date
    * @param int $user_id : user id for the expiration date
    */
    public function insertExpDate($user_id){
        $query = "UPDATE users SET onetime_token_expires = NOW() + INTERVAL 1 DAY WHERE user_id = ?";
        $aValues = array($user_id);
        $eRows = $this->db->query($query, $aValues);
        return $eRows;
    }
    /**
    * Insert jobs
    * $param array $aData : Data to insert into jobs table
                eg: array("user_id"=>2,"file_id"=>10)
    */
    public function insertJobs($aData){
        $sTable = "jobs";
        $jRow = $this->insertIntoTable($sTable ,$aData);
        return $jRow;
    }

    /**
    * Insert into job setps
    * $param array $aData : Data to insert into jobs steps table
                eg: array("job_id"=>2,"work_setp_id"=>1, "completed_user_id"=>2, "data"= serialize(array()))
    */
    public function insertJobSteps($aData){
        $sTable = "job_steps";
        $jRow = $this->insertIntoTable($sTable ,$aData);
        return $jRow;
    }

    /**
    * Insert into job setps
    * $param array $aData : Data to insert into jobs steps table
                eg: array("job_id"=>2,"user_id"=>1, "file_id"=>2, "created"=>"NOW()", "data"= serialize(array()))
    */
    public function insertJobFiles($aData){
        $sTable = "job_files";
        $jRow = $this->insertIntoTable($sTable ,$aData);
        return $jRow;
    }

    /**
    * Insert into email
    * $param array $eData : Data to insert into email table
        $eData = array("user_id"=>$jRow[0]["user_id"] , "receipients"=>$jRow[0]['email'], "subject"=>"MakeAtState message reagrding your job", "message"=>$this->message_text , "created"=>date('Y-m-d H:i:s') );
    */
    public function insertEmails($eData){
        $sTable = "emails";
        $eRow = $this->insertIntoTable($sTable ,$eData);
        return $eRow;
    }
    /**
    * Insert into email
    * $param array $aData : Data to insert into attachments table
        $aData = array("file_id"=>$file_id , "email"=>$email_id);
    */
    public function insertAttachments($aData){
        $sTable = "attachments";
        $aRow = $this->insertIntoTable($sTable ,$aData);
        return $aRow;
    }
    /**
    * Insert into notes
    * $param array $nData : Data to insert into email table
        $nData = array("job_id" => $this->job_id , "add_user_id"=>$seld::user['user_id'] , "notes_text"=>$this->notes_text );
    */
    public function insertNotes($nData){
        $sTable = "notes";
        $nRow = $this->insertIntoTable($sTable ,$nData);
        return $nRow;
    }
    /**
    * Insert into job updates
    * $param array $uData : Data to insert into job updates table
        $uData = array("job_id" => $jRow[0]["job_id"], "created" => date("Y-m-d H:i:s") , "public_view"=>1);
    */
    public function insertJobUpdates($uData){
        $sTable = "job_updates";
        $uRow = $this->insertIntoTable($sTable ,$uData);
        return $uRow;
    }
    /**
    * Insert into permissions
    * $param array $pData : Data to insert into job updates table
        $pData = array("total_allocated_size" =>268435456 , "files_expire_after" => 30);
    */
    public function insertPermissions($pData){
        $sTable = "permissions";
        $pRow = $this->insertIntoTable($sTable ,$pData);
        return $pRow;
    }


    public function insertGroups($wData){
        $sTable = 'groups';
        $iRows = $this->insertIntoTable($sTable,$wData);
        return $iRows;
    }

    /*
    * Insert new workflow into the workflows table
    * @param $wData  Array of column names and values
             array("column_name"=> value)
    */
    public function insertWorkflows($wData){
        $sTable = "workflows";
        $iRows = $this->insertIntoTable($sTable, $wData);
        return $iRows;
    }
    /*
    * Insert new workflow step into the workflow  step  table
    * @param $wData  Array of column names and values
             array("column_name"=> value)
    */
    public function insertWorkflowSteps($wData){
        $sTable = "workflow_steps";
        $iRows = $this->insertIntoTable($sTable, $wData);
        return $iRows;
    }
    /*
    * Remove onetime token after password reset and confirmation and update verified timestamp
    * @param int $user_id : user id for the expiration date
    */
    public function removeOnetimeToken($user_id){
        $query = "UPDATE users SET onetime_token = NULL , verified = NOW()  WHERE user_id = ?";
        $aValues = array($user_id);
        $eRows = $this->db->query($query, $aValues);
        return $eRows;
    }
    /**
    * Insert into a table
    * @param string : $sTable: Name of the table
    * @param array $aData: Array of column names and values
            array("column_name"=> value)
    */
    protected function insertIntoTable($sTable ,$aData){
        $iRows = array();
        # Table name
        $sTable = $this->db->escapeIdentifier($sTable);
        $query = "INSERT INTO " . $sTable . " SET " ;
        $iValues = array();
        foreach($aData as $key =>$value){
            $aCol = ($value === null ? " =  NULL":  (($value === 'NOW()') ? "= NOW()" : " = ?"));
            $query .= (count($iValues) == count($aData) -1 ) ?  $this->db->escapeIdentifier($key) . $aCol : $this->db->escapeIdentifier($key) . $aCol ;
            $keys = array_keys($aData);
            $last_key = end($keys);
            if ($last_key !== $key) {
                $query .= ',';
            }
            if($value != null && $value != "NOW()"){
                $iValues[] = $value;
            }
        }
        #APP::printVar($this->db->queryDump($query, $iValues));
        $iRows = $this->db->query($query, $iValues);
        return $iRows;
    }
    /**
    * Search users
    * @param string term : search term
    * @param int skip : number of records to be skipped
    * @param int limit : limt of records to be retrived
    */
    public function searchUsers($term, $skip=0, $limit=null){
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS u. *, p.* FROM users u
                 LEFT JOIN permissions p on u.permission_id = p.permission_id
                WHERE u.email LIKE ? or u.fullname LIKE ?";
        if(!empty($limit)){
            $sQuery .= " LIMIT {$skip}, {$limit}  ";
        }
        #values array
        $sValues = array("%{$term}%", "%{$term}%");
        #APP::printVar($this->db->queryDump($sQuery, $sValues));
        return $this->db->query($sQuery, $sValues);
    }
    /**
    * Get counts for stats ,
    * get staff_jobs, user_jobs, user_cancelled_job,  staff_cancelled_job, completed_job
    */
    public function getDetailJobStats(){
        $sQuery = 'SELECT DISTINCT
                COUNT(CASE WHEN  (t.workflow_step_type_name = "General step" AND w.manage_level >  0  AND s.completed IS  NULL  AND j.closed IS  NULL AND  w.step_removed IS  NULL) OR (t.workflow_step_type_name = "Price step" AND w.manage_level >  0  AND s.completed IS  NULL  AND j.closed IS  NULL AND  w.step_removed IS  NULL) OR (t.workflow_step_type_name = "Delivery date step" AND w.manage_level >  0  AND s.completed IS  NULL  AND j.closed IS  NULL AND  w.step_removed IS  NULL) OR (t.workflow_step_type_name = "Completed step" AND w.manage_level >  0  AND s.completed IS  NULL  AND j.closed IS  NULL AND  w.step_removed IS  NULL)  THEN 1 END)  AS staff_jobs   ,
                COUNT(CASE WHEN  t.workflow_step_type_name = "General step" AND w.manage_level =  0  AND s.completed IS  NULL  AND j.closed IS  NULL AND  w.step_removed IS  NULL THEN 1 END) AS user_jobs ,
                COUNT(CASE WHEN t.workflow_step_type_name = "Cancelled by user step" AND s.completed IS NOT NULL THEN 1 END ) as user_cancelled_job ,
                COUNT(CASE WHEN t.workflow_step_type_name = "Completed step" AND s.completed IS NOT NULL THEN 1 END ) as completed_job,
                COUNT(CASE WHEN t.workflow_step_type_name = "Cancelled step" AND s.completed IS NOT NULL  THEN 1 END ) as staff_cancelled_job
                FROM   job_steps s LEFT JOIN  workflow_steps w on   w.work_step_id = s.work_step_id LEFT JOIN workflow_step_type t  on w.step_type_id= t.workflow_step_type_id LEFT JOIN jobs j on j.job_id = s.job_id WHERE s.reset IS NULL';
        $dCount = $this->db->query($sQuery);
        return $dCount[0];
    }
    /**
    * Get counts for stats
    * get total jobs , open jobs , closed jobs
    */
    public function getBasicJobStats(){
        $sQuery = 'Select COUNT(CASE WHEN closed IS NOT NULL THEN 1 END) AS closed,
                COUNT(CASE WHEN closed IS NULL THEN 1 END) AS open,
                COUNT(*) AS total from jobs j ';
        $bCount = $this->db->query($sQuery);
        return $bCount;
    }
    /**
    * Get counts for user stats
    * Get count for admins, staff, student staff, users , verified users and blocked users
    */
    public function getUserStats(){
        $sQuery = 'SELECT DISTINCT
                COUNT(CASE WHEN p.internal_name="users" THEN 1 END) as public_users,
                COUNT(CASE WHEN p.internal_name="student_staff" THEN 1 END) as student_staff,
                COUNT(CASE WHEN p.internal_name="staff" THEN 1 END) as staff,
                COUNT(CASE WHEN p.internal_name="admin" THEN 1 END ) as admin,
                COUNT(CASE WHEN p.internal_name="users" AND u.verified IS NOT  NULL THEN 1 END ) as verified_users,
                COUNT(CASE WHEN p.internal_name="users" AND u.verified IS NULL THEN 1 END ) as un_verified_users,
                COUNT(CASE WHEN p.internal_name="users" AND u.blocked IS NULL AND u.verified IS NOT NULL THEN 1 END ) as active_users,
                COUNT(CASE WHEN p.internal_name="users" AND u.blocked IS NOT NULL THEN 1 END ) as blocked_users,
                COUNT(CASE WHEN  u.user_id IS NOT NULL THEN 1 END ) as all_users
                FROM users u LEFT JOIN permissions p on p.permission_id=u.permission_id';
        $uCount = $this->db->query($sQuery);
        return $uCount;
    }
    /**
    * Get data on a monthly basis for analytics
    * @param array date_array : array contating the start and end date
    */
    public function getPrimeAnalytics($date_array){
        $this->db->query("SET SQL_BIG_SELECTS=1");

        $sQuery = 'SELECT  submitted_jobs.*, completed_jobs.*, cancelled_jobs.*, user_cancelled_jobs.*  FROM
                ( SELECT  COUNT(A.job_id) as user_cancelled FROM (SELECT DISTINCT j.job_id FROM jobs j LEFT JOIN job_steps s on j.job_id= s.job_id   LEFT JOIN  workflow_steps w on   w.work_step_id = s.work_step_id LEFT JOIN workflow_step_type t  on w.step_type_id= t.workflow_step_type_id  WHERE j.created  BETWEEN "'.$date_array['start_date'].'" AND "'.$date_array['end_date'].'"  AND t.workflow_step_type_name = "Cancelled by user step"   ) A) user_cancelled_jobs ,
                ( SELECT  COUNT(A.job_id) as staff_canclled FROM (SELECT DISTINCT j.job_id FROM jobs j LEFT JOIN job_steps s on j.job_id= s.job_id   LEFT JOIN  workflow_steps w on   w.work_step_id = s.work_step_id LEFT JOIN workflow_step_type t  on w.step_type_id= t.workflow_step_type_id  WHERE j.created  BETWEEN "'.$date_array['start_date'].'" AND "'.$date_array['end_date'].'"  AND t.workflow_step_type_name = "Cancelled step"   ) A) cancelled_jobs,
                ( SELECT  COUNT(A.job_id) as completed FROM (SELECT DISTINCT j.job_id FROM jobs j LEFT JOIN job_steps s on j.job_id= s.job_id   LEFT JOIN  workflow_steps w on   w.work_step_id = s.work_step_id LEFT JOIN workflow_step_type t  on w.step_type_id= t.workflow_step_type_id  WHERE j.created  BETWEEN "'.$date_array['start_date'].'" AND "'.$date_array['end_date'].'"  AND t.workflow_step_type_name = "Completed step"   ) A) completed_jobs  ,
                ( SELECT  COUNT(A.job_id) as submitted FROM (SELECT DISTINCT j.job_id FROM jobs j  WHERE j.created  BETWEEN "'.$date_array['start_date'].'" AND "'.$date_array['end_date'].'"  ) A) submitted_jobs';
        #APP::printvar($this->db->queryDump($sQuery));
        $aCount = $this->db->query($sQuery);
        return array_shift($aCount);
    }
    /**
    * Get price on a monthly basis
    * @param array date_array : array contating the start and end date
    * @param string type : type of the workflow step "Completed step" or "Cancelled by user step" or "Cancelled step"
    */
    public function getPriceAnalytics($date_array, $type = "Completed step"){
        $aValues = array($type);
        $aQuery = 'SELECT  s.* from jobs j LEFT JOIN job_steps s ON s.job_id = j.job_id LEFT JOIN workflow_steps w ON w.work_step_id = s.work_step_id LEFT JOIN workflow_step_type t ON t.workflow_step_type_id = w.step_type_id WHERE t.workflow_step_type_name = ? AND j.closed  IS NOT NULL AND  j.closed  BETWEEN "'.$date_array['start_date'].'" AND "'.$date_array['end_date'].'"';
        #APP::printVar($this->db->queryDump($aQuery, $aValues));
        $aRows = $this->db->query($aQuery, $aValues);
        return $aRows;
    }
     /********************************************************************************
     * TRANSACTIONS
     ********************************************************************************/
    public function transactionStart($bReadCommitted=null) {
        $this->db->startTransaction($bReadCommitted);
    }
    public function transactionCommit() {
        $this->db->commitTransaction();
    }
    public function transactionRollback() {
        $this->db->rollbackTransaction();
    }
}

