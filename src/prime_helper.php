<?php
#Helper class , where all the helper functions reside
class PrimeHelper
{
    private $rowsPerPage = 50;

    /**
     * function to prepare job notes for steps
     * @param object $dc : data_calls object
     * @param int $job_id : job id of current job
     */
    public function prepareJobNotes($dc, $job_id)
    {
        $notesRow = $dc->getJobNotes($job_id);

        $notes = array();
        foreach ($notesRow as $k=>$row) {
            $notes[$k]['note_text'] = $row['note_text'];
            $notes[$k]['created'] = $this->convertToPrettyDateAndTime($row['note_created']);
            $notes[$k]['first_name'] = $row['fullname'];
            $notes[$k]['last_name'] = $row['lastname'];
        }
        return $notes;
    }

    /**
    *Size conversion for file size
    * @param int $size : file size that needs conversion
    */
    public function convertFileSizes($size)
    {
        $size =  intval($size);

        $kb = 1024;
        $mb = $kb * 1024;
        $gb = $mb * 1024;
        $tb = $gb * 1024;
        $formatted_size  = $size . 'B';
        if ($size >= $tb) {
            $formatted_size = round(($size/$tb), 2) . ' TB';
        } elseif ($size >= $gb) {
            $formatted_size = round(($size/$gb), 2) . ' GB';
        } elseif ($size >= $mb) {
            $formatted_size = round(($size/$mb), 2) . ' MB';
        } elseif ($size >= $kb) {
            $formatted_size = round(($size/$kb), 2) . ' KB';
        }
        return $formatted_size;
    }

    /**
    * Convert date to a specific format for display
    * Example  Wednesday, May 26 , 2018
    * @param srtring $date : date from the sql db
    */
    public function convertToPrettyDate($date)
    {
        $days_array = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday" , "Friday", "Saturday");
        $formatted_date = "";
        if (!empty($date)) {
            $month = date("F", strtotime($date));
            $day = (int)date("w", strtotime($date));
            $day_of_the_week =(empty($day)) ? $days_array[0] : $days_array[$day];
            $year = date("Y", strtotime($date));
            $d = date("d", strtotime($date));
            if (!empty($month) && !empty($day_of_the_week)  && !empty($year) && !empty($d)) {
                $formatted_date = $day_of_the_week . ", " . $month . " " .$d . ", " . $year;
            } else {
                $formatted_date = $date;
            }
        }
        return $formatted_date;
    }

    /**
    * Convert date to a specific format for display
    * Example  Wednesday, May 26 , 2018 , 9:30 am
    * @param srtring $date : date from the sql db
    */
    public function convertToPrettyDateAndTime($date)
    {
        $days_array = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday" , "Friday", "Saturday");
        $formatted_date = "";
        if (!empty($date)) {
            $month = date("F", strtotime($date));
            $day = (int)date("w", strtotime($date));
            $day_of_the_week =(empty($day)) ? $days_array[0] : $days_array[$day];
            $year = date("Y", strtotime($date));
            $d = date("d", strtotime($date));
            $hour = date("h", strtotime($date));
            $mins = date("i", strtotime($date));
            $ampm = date("a", strtotime($date));
            if (!empty($month) && !empty($day_of_the_week)  && !empty($year) && !empty($d)) {
                $formatted_date = $day_of_the_week . ", " . $month . " " .$d . ", " . $year .  ", " . $hour.":".$mins . " ". $ampm;
            } else {
                $formatted_date = $date;
            }
        }
        return $formatted_date;
    }

    /**
    *
    *
    */
    public function getExpiryDurationForFile($created, $expires)
    {
        $days_array = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday" , "Friday", "Saturday");
        $formatted_exp_date = "";
        if (!empty($created) && !empty($expires)) {
            $now = new DateTime(date('Y-m-d H:i:s'));
            $expires_date = date('Y-m-d H:i:s', strtotime($created." + ".$expires." days"));
            $expires_in = new DateTime(date('Y-m-d H:i:s', strtotime($created." + ".$expires." days")));
            if ($now < $expires_in) {
                $month = date("F", strtotime($expires_date));
                $day = (int)date("w", strtotime($expires_date));
                $day_of_the_week =(empty($expires_date)) ? $days_array[0] : $days_array[$day];
                $year = date("Y", strtotime($expires_date));
                $d = date("d", strtotime($expires_date));
                if (!empty($month) && !empty($day_of_the_week)  && !empty($year) && !empty($d)) {
                    $formatted_exp_date = $day_of_the_week . ", " . $month . " " .$d . ", " . $year;
                }
            }
        }
        return $formatted_exp_date;
    }

    /**
    * Checks if a file can be viewed in browser. Returns 0
    * @param string $file_name:  name of the file
    */
    public function fileViewingStatusOnBrowser($file_name)
    {
        $file_ext = explode(".", $file_name);
        $file_ext = strtolower(array_pop($file_ext));
        $file_type_array = APP::getFileTypes();
        $type_array = $file_type_array->get($file_ext);
        $allow_viewing= $type_array->get('view'); //returns 0 if viewing is not allowed
        return $allow_viewing;
    }

    /**
    * Convert date to a specific format for display
    * Example 3 days ago , 2 hrs ago , 5 min ago , 30 sec ago
    * @param srtring $date : date from the sql db
    */
    public function convertDateForDisplay($date)
    {
        $formatted_date = "";
        $now = new DateTime(date('Y-m-d H:i:s'));
        if (!empty($date)) {
            $d = new DateTime($date);
            $diff = $d->diff($now);
            $year = $diff->format('%y');
            $month = $diff->format('%m');
            $days = $diff->format('%d');
            $hour = $diff->format('%h');
            $min = $diff->format('%i');
            $sec = $diff->format('%s');
            # Set year
            $formatted_date .= ($year > 1)? $year." years " : "";
            $formatted_date .= ($year == 1)? $year." year " : "";

            # Set month
            $formatted_date .= ($month > 1)? $month." months " : "";
            $formatted_date .= ($month == 1)? $month." month " : "";
            # Set days
            $formatted_date .= ($days > 1)? $days." days " : "";
            $formatted_date .= ($days == 1)? $days." day " : "";
            # Set hours
            $formatted_date .= ($hour > 1)? $hour." hrs " : "";
            $formatted_date .= ($hour == 1)? $hour." hr " : "";
            # Set mins
            if ($year ==0 && $month ==0 && $days ==0) {
                $formatted_date .= ($min > 1)? $min." mins " : "";
                $formatted_date .= ($min == 1)? $min." min " : "";
            }
            if ($year ==0 && $month ==0 && $days ==0 && $hour == 0) {
                # Set seconds
                $formatted_date .= ($sec > 1)? $sec." secs " : "";
                $formatted_date .= ($sec == 1)? $sec." sec " : "";
            }
            $formatted_date .= (!empty($formatted_date)) ? "ago" :" right now";
        }
        return $formatted_date;
    }

    /**
    * Returns the skip and limit values for a sql query
    * @param int $mPage :  The page number from the pagination of the current page
    */
    public function getPaginationValues($mPage)
    {
        $hPagination = array();
        if ($mPage <= 0) {
            $hPagination['limit'] = $this->rowsPerPage;
            $hPagination['skip'] = 0;
            $hPagination['page'] = 1;
        } else {
            $hPagination['limit'] = $this->rowsPerPage;
            $hPagination['skip'] = $this->rowsPerPage * $mPage - $this->rowsPerPage;
            $hPagination['page'] = $mPage;
        }
        return $hPagination;
    }
    /**
    *Preapge  array for pagination, set the array with pages and targets
    * @param array $pagination : array containg total , currentpage, skip and limit values
    */
    public function preparePaginationArray($pagination)
    {
        $pagination_array = array();
        $page_array = array();
        $first_page_array = array();
        $current_page_array = array();
        $last_page_array = array();
        $current_target = $pagination['target'];
        $current_page = $pagination['page'];
        $total_pages = ceil($pagination['total']/ $this->rowsPerPage);
        $pager_numbers =  array();
        for ($i=1; $i<=$total_pages; $i++) {
            array_push($pager_numbers, $i);
        }
        #logic for more than 5 pages
        #set both the first and last pages in this case
        if (count($pager_numbers) > 5) {
            if ($current_page == $pager_numbers[0] || $current_page == $pager_numbers[1] || $current_page == $pager_numbers[2]) {
                # setting the last page into the pagination array
                $last_page_array['page'] = $pager_numbers[count($pager_numbers) -1];
                $last_page_array['target'] =$current_target . $pager_numbers[ count($pager_numbers) -1];
                $pagination_array['last'] = $last_page_array;
                $pager_numbers = array_slice($pager_numbers, 0, 5, true);
            } elseif ($current_page == $pager_numbers[count($pager_numbers) -1] || $current_page == $pager_numbers[count($pager_numbers) -2]  || $current_page == $pager_numbers[count($pager_numbers) -3]) {
                # setting the first page into the pagination array
                $first_page_array['page'] = $pager_numbers[0];
                $first_page_array['target'] =$current_target . $pager_numbers[0];
                $pagination_array['first'] = $first_page_array;

                $pager_numbers = array_slice($pager_numbers, -5);
            } else {
                # setting the last page into the pagination array
                $first_page_array['page'] = $pager_numbers[0];
                $first_page_array['target'] =$current_target . $pager_numbers[0];
                $pagination_array['first'] = $first_page_array;

                # setting the last page into the pagination array
                $last_page_array['page'] = $pager_numbers[count($pager_numbers) -1];
                $last_page_array['target'] =$current_target . $pager_numbers[ count($pager_numbers) -1];
                $pagination_array['last'] = $last_page_array;

                $num = array_search($current_page, $pager_numbers) ;
                $new_array = [$pager_numbers[$num-2], $pager_numbers[$num-1], $pager_numbers[$num], $pager_numbers[$num+1], $pager_numbers[$num+2] ];
                $pager_numbers = $new_array;
            }
        }
        #setting all the pages into the pagination array
        foreach ($pager_numbers as $k=>$page) {
            $page_array['page'] = $page;
            $page_array['target'] =  $current_target . $page;
            if ($current_page === $page) {
                $page_array['current'] = true;
            }
            $pagination_array['pages'][] = $page_array;
            $page_array = [];
        }
        // remove the pahes from pagination array if there is only one page
        if (empty($pagination_array['pages'])  || count($pagination_array['pages']) == 1) {
            $pagination_array['pages'] = [];
        }
        // show the status message if there are more than 1 item
        if ($pagination['total'] > 1) {
            $pagination_array['status'] = "Showing " .((($current_page -1) * $this->rowsPerPage) +1) . " - ".((($current_page * $this->rowsPerPage) >$pagination['total']) ? $pagination['total'] : $current_page * $this->rowsPerPage). " of total " . $pagination['total'] ;
        }
        return $pagination_array;
    }

    /**
     *Send message to the user
     * @param array $jRow : Details about the job that is associated with the message
     * @param $message_text
     * @param $admin_email
     * @param bool $send_to_admin : When set to true the message is send to adminstrators as apposed to the users
     * @param string $button_text
     * @return bool
     */
    public function sendMessage($jRow, $message_text, $admin_email , $send_to_admin = false, $button_text = "View Job Information")
    {
        $cf = APP::config();
        $mTemplate = new Templater();
        $mTemplate->setTemplate('send_message.html');
        $mTemplate->setBlockVariables('project_name', $jRow['project_name']);
        $mTemplate->setBlockVariables('job_id', $jRow['job_id']);
        $mTemplate->setBlockVariables('user_id', $jRow['user_id']);
        $mTemplate->setBlockVariables('message_text', $message_text);
        $mTemplate->setBlockVariables('button_text', $button_text);
        $mTemplate->setBlockVariables('app_url', $cf->get('application.url'));

        $mTemplate->setBlock('send_message_template');
        $email_block = $mTemplate->generateBlock();
        $subject = "Message from MakeCentral MakeAtState";
        // To send HTML mail, the Content-type header must be set
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        // Additional headers
        $headers[] = 'From: MakeAtState <'. $cf->get('application.email').'>';
        if ($send_to_admin) {
            if(!($admin_email)){
                $admin_email = $cf->get('application.updates_email');
            }
            return mail($admin_email, $subject, $email_block, implode("\r\n", $headers));
        } else {
            return mail($jRow['email'], $subject, $email_block, implode("\r\n", $headers));
        }
    }

    /**
    * Set email text when the price is set
    * @param array price_array: Details of the pice for the job
    */
    public function setJobSubmittedMessage($file_name)
    {
        $cf = APP::config();
        $message_text = "<br><br>Your job request has been submitted to MakeCentral. In 1-3 business days, you will receive a message with pricing and an approximate print completion date, at which point you can submit payment, then we will begin completing your project.";
        return $message_text;
    }

    /**
    * Set email text for the job step
    * @param String user_status: User status corresponding to the job
    */
    public function setJobStepMessage($user_status)
    {
        $cf = APP::config();
        $message_text = "Updates regarding the current status of the job.
    			<br><br>
    			Status : <i>".$user_status. "</i>
    			<br><br>";

        return $message_text;
    }

    /**
     * Set email text for the job step
     * @param String user_status: User status corresponding to the job
     */
    public function setJobCancellationMessage($user_status, $reason, $more_info)
    {
        if(!isset($reason)){
            $reason = "No reason given";
        }
        $cf = APP::config();
        $message_text = "Updates regarding the current status of the job.
    			<br><br>
    			Status : <i>".$user_status. "</i>
    			<br><br>
    			Reason : <i>".$reason. "</i><br><br>";
        if(isset($more_info)){
            $message_text .= "More Info : <i>".$more_info."</i><br><br>";
        }

        return $message_text;
    }

    /**
    * Set email text when the price is set
    * @param array price_array: Details of the pice for the job
    * @param Int job_id: Job id for the job
    * @param String group_code: First letter of the group tag to associate the job with a group
    */
    public function setPriceMessage($price_array, $job_id, $group_code)
    {
        $message_text = <<<EOT
<p>The price has been set for your job. Printing will not begin until payment is received.</p>
<p style="margin-bottom: .5em">
Total before tax: <span style="color: #009D6C;">\${$price_array['grand_total_price_before_discount']}</span><br>
Sales Tax: <span style="color: #009D6C;">\${$price_array['tax']}</span><br></p>
<p style="font-size: 2em; margin-bottom: 1em; margin-top: 0em">Total: <span style="color: #009D6C;">\${$price_array['grand_total']}</span><br>
</p>
<p><span style="color: #40407a;">We are no longer accepting in person payments.</span> We are only accepting payment through CashNet or MSU department accounts. Once payment is received, we will begin your job. Once your job is complete you will get an automated email from MakeAtState that your order is ready for pick up or shipped.</p>
<p>If you selected <strong>Pickup at Luxer Contactless Lockers</strong>, you will get an email from Luxer with information on how to open the locker. Please note, the lockers have moved to the north lobby of the Main Library, also called the Beaumont entrance.<p>
<p>If you selected <strong> Pickup at MakeCentral</strong>, you will get an email from 3Dprime when your order is ready for pick up. You can pick up your order from the MakeCentral: Service Desk, located on 2 West, during their <a href="https://lib.msu.edu/general/library-hours-cal/hollander-makecentral-service-desk/">open hours.</a><p>
<p>If you selected <strong>Ship to your address</strong>, you will get an additional email from UPS when your item is shipped and delivered</p>
<p>If you selected <strong>Ship to a campus mail address</strong> you will not receive any additional emails but your item has been sent to University Services.</p>
<p style="color: black; font-size: 22px;line-height: 25px;">To use CashNet and pay with a credit card:</p>
<div style="font-size: 18px">
<ol>
<li><a href="https://commerce.cashnet.com/msu_3978">Go to our CashNet Store</a></li>
<li>Enter your Order Number: <span style="color: #009D6C;">{$group_code}{$job_id}</span></li>
<li>Enter your Total: <span style="color: #009D6C;">\${$price_array['grand_total']}</span></li>
<li>Fill out your information</li>
<li>Don’t forget to click submit at the end!</li>
</ol>
</div>

<p style="color: black; font-size: 22px; line-height: 25px;">If you are paying with a department account, please fill out <a href="https://msu.co1.qualtrics.com/jfe/form/SV_bPiMIy0B87DB6AZ">this form.</a></p>
<div style="font-size: 18px">
<ol>
<li>Make sure to include your Order Number: <span style="color: #009D6C;">M$job_id</span></li>
<li>Use the pre-tax total of <span style="color: #009D6C;">\${$price_array['grand_total_price_before_discount']}</span></li>
</ol>
</div>
<p style = 'font-size:12px;'>If you pay the amount listed above, you are agreeing to the quoted price. All sales are final. If you choose a pick up method, you must pick up your finished object(s) at the Main Library within 14 days or your item will be disposed. Please see the library’s <a href="https://lib.msu.edu/hours/main-library/">hours</a> for the most up to date times for pick up. See our full policies <a href="https://lib.msu.edu/makerspace/policies/">here</a>.
</p>
EOT;

        return $message_text;
    }

    /**
    * Set email text when the delivery date  is set
    * @param array delivery_array: Details of the delivery date  for the job
    */
    public function setDeliveryDateMessage($delivery_array)
    {
        $message_text = "Estimated delivery date  has been set for your job.
    	        <br><br>
    			Estimated delivery date : " . $delivery_array['estimated_delivery'].
                "<br><br>";
        return $message_text;
    }

    /**
    * Format phone # for display
    * @param string phone_num : Phone number to be formatted
    */
    public function formatPhoneNumber($phone_num)
    {
        $formatted_phone_num = "";
        if (!empty($phone_num)) {
            $formatted_phone_num = substr($phone_num, 0, 3). '-'.substr($phone_num, 3, 3).'-'.substr($phone_num, 6, 4);
        }
        return $formatted_phone_num;
    }

    /*
    * Function to determine if a workflow for a printer has all the necessary steps
    * @param array workflow_steps : array of workflow steps for the printer from db
    * @param array step_types
    */
    public function determineReadinessOfPrinterWorkflow($workflow_steps, $step_types)
    {
        $no_step_type_warnings = array() ;

        // add warning messages for all step types
        foreach ($step_types as $type) {
            $no_step_type_warnings[$type['workflow_step_type_id']] = $type['workflow_step_type_name'] ." not present in the workflow";
        }
        // loop through all steps in a workflow for a printer
        foreach ($workflow_steps as $step) {
            foreach ($step_types as $type) {
                // check if
                if ($type['workflow_step_type_id'] == $step['step_type_id'] && isset($no_step_type_warnings[$type['workflow_step_type_id']])) {
                    // if step type exists remove it from the warnings array
                    unset($no_step_type_warnings[$type['workflow_step_type_id']]);
                }
            }
        }
        return $no_step_type_warnings;
    }

    /*
    * Deserialize the workflow data and return printers
    * @param array workflows: All non displabed workflows from the db
    *
    */
    public function getPrintersFromWorkflow($workflows)
    {
        $all_printers = array();
        if(isset($workflows) && !empty($workflows)) {
            foreach ($workflows as $workflow) {
                $data = unserialize($workflow["data"]);
                $all_printers[] = array_key_first($data );
            }
        }
        return $all_printers;
    }

}
