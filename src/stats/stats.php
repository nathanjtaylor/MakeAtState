<?php

class Stats{

	private $dc;
	private $helper;
	private $access_level;
	private $sTemplate;
	private $user_id;
	private $stats_type =1 ; # 1 for jobs 2 for users
	private $display_type =1; # 1 for numbers 2 for bar charts
	private $years = array();// years for analytics

	private $job_stats;
	private $user_stats;

	static private $user;
	static private $nav_array;

    static private $indexColors = array(
        "#FFFFFF", "#28a745", "#516184", "#8FAFD5", "#FF0059", "#FFE230",
        "#FFFF00", "#1CE6FF", "#FF34FF", "#FF4A46", "#008941", "#006FA6", "#A30059",
        "#FFDBE5", "#7A4900", "#0000A6", "#63FFAC", "#B79762", "#004D43", "#8FB0FF", "#997D87",
        "#5A0007", "#809693", "#FEFFE6", "#1B4400", "#4FC601", "#3B5DFF", "#4A3B53", "#FF2F80",
        "#61615A", "#BA0900", "#6B7900", "#00C2A0", "#FFAA92", "#FF90C9", "#B903AA", "#D16100",
        "#DDEFFF", "#000035", "#7B4F4B", "#A1C299", "#300018", "#0AA6D8", "#013349", "#00846F",
        "#372101", "#FFB500", "#C2FFED", "#A079BF", "#CC0744", "#C0B9B2", "#C2FF99", "#001E09",
        "#00489C", "#6F0062", "#0CBD66", "#EEC3FF", "#456D75", "#B77B68", "#7A87A1", "#788D66",
        "#885578", "#FAD09F", "#FF8A9A", "#D157A0", "#BEC459", "#456648", "#0086ED", "#886F4C",

        "#34362D", "#B4A8BD", "#00A6AA", "#452C2C", "#636375", "#A3C8C9", "#FF913F", "#938A81",
        "#575329", "#00FECF", "#B05B6F", "#8CD0FF", "#3B9700", "#04F757", "#C8A1A1", "#1E6E00",
        "#7900D7", "#A77500", "#6367A9", "#A05837", "#6B002C", "#772600", "#D790FF", "#9B9700",
        "#549E79", "#FFF69F", "#201625", "#72418F", "#BC23FF", "#99ADC0", "#3A2465", "#922329",
        "#5B4534", "#FDE8DC", "#404E55", "#0089A3", "#CB7E98", "#A4E804", "#324E72", "#6A3A4C",
        "#83AB58", "#001C1E", "#D1F7CE", "#004B28", "#C8D0F6", "#A3A489", "#806C66", "#222800",
        "#BF5650", "#E83000", "#66796D", "#DA007C", "#FF1A59", "#8ADBB4", "#1E0200", "#5B4E51",
        "#C895C5", "#320033", "#FF6832", "#66E1D3", "#CFCDAC", "#D0AC94", "#7ED379", "#012C58"
    );


	/**
	* Constructor function for stats
	* @param Templater $sTempl: Templater object for stats
	* @param bool ajax : to determine if the call was made by ajax
	*/
	public function __construct(Templater &$sTempl, $ajax=FALSE){
		$this->sTemplate = $sTempl;
		$this->dc = new DataCalls();
		$this->helper = new PrimeHelper();
		$this->setUser();
		$this->setAccessLevel();
		$this->setNavigation();
		$this->display_type = (UserData::create('dtype')->getInt() == 2) ? 2 :1;
		$this->stats_type = (UserData::create('stype')->getInt() == 3) ? 3 :( (UserData::create('stype')->getInt() == 2 ) ? 2 :1);
		if(empty($ajax)){
			$this->prepareStats();
		}
	}

	/**
	* Sets the user
	*/

	private function setUser(){
		//lazy loading  user
		if(self::$user == null){
			self::$user = AuthenticatedUser::getUser();
			$this->user_id = self::$user['user_id'];
		}

	}
	/**
	* Get access level for the user
	*/
	private function setAccessLevel(){
		$this->access_level = AuthenticatedUser::getUserPermissions();
	}

	/**
	* Sets the navigation for the page
	*/

	private function setNavigation(){

		if(self::$nav_array == null){
			self::$nav_array = AuthenticatedUser::getUserNavBar();
		}
	}

	/**
	* Render manage stats template
	*/
	private function renderStatsTemplate(){
		$this->sTemplate->setTemplate('stats.html');
		$this->sTemplate->setVariables('page_title', "Statistics");
		$this->sTemplate->setVariables('error_messages' , Alerts::getErrorMessages());
		$this->sTemplate->setVariables('nav_array', self::$nav_array);
		$this->sTemplate->setVariables('stats_type', $this->stats_type);
		$this->sTemplate->setVariables('display_type', $this->display_type);
		$this->sTemplate->setVariables('job_stats', $this->job_stats);
		$this->sTemplate->setVariables('user_stats', $this->user_stats);
		$this->sTemplate->setVariables('years', $this->years);
        $this->sTemplate->setVariables('colors', self::$indexColors );
        # Set all the user details for the template

		$this->sTemplate->generate();


	}


	/**
	* prepare for stats display
	*/
	public function prepareStats(){
		$error_messages = array();
		if($this->access_level == "ADMIN" || $this->access_level == "STAFF" || $this->access_level == "STUDENT STAFF"){
			if($this->stats_type == 1){
				$detail_job_stats = $this->dc->getDetailJobStats();
				$basic_job_stats = $this->dc->getBasicJobStats();
				$cancellation_job_stats = $this->dc->getCancellationJobStats();
				if(isset($detail_job_stats) && isset($basic_job_stats[0])){
					$this->prepareJobStats($basic_job_stats[0],$detail_job_stats, $cancellation_job_stats);

				}
				else{
					$error_messages[] = "Sorry, an unexpected error has occured, we canot retrive stats at this time";
					Alerts::setErrorMessages($error_messages);

				}
			}
			else if($this->stats_type == 2){
				$user_stats = $this->dc->getUserStats();
				if(isset( $user_stats[0] )){
					$this->prepareUserStats($user_stats[0]);

				}else{
					$error_messages[] = "Sorry, an unexpected error has occured, we canot retrive stats at this time";
					Alerts::setErrorMessages($error_messages);

				}
			}

			$this->renderStatsTemplate();

		}else {
			$error_messages[] = "Sorry, this operation is not allowed";
			Alerts::setErrorMessages($error_messages);
			header('Location: /?t=home');
		}

	}

	/**
	* Prepare stats for jobs
	* @param array $basic_stats : basic stats from db
	* @param array $detail_stats: detail stats from db
	*/
	public function prepareJobStats($basic_stats, $detail_stats, $cancellation_stats){
        $this->job_stats['Basic']['Open jobs'] = $basic_stats['open'];
        $this->job_stats['Basic']['Closed jobs'] = $basic_stats['closed'];

		$this->job_stats['Detailed']['Completed jobs'] = $detail_stats['completed_job'];
		$this->job_stats['Detailed']['Jobs in progress'] = $detail_stats['staff_jobs'];
		$this->job_stats['Detailed']['Jobs awaiting user action'] = $detail_stats['user_jobs'];
        $this->job_stats['Detailed']['Jobs cancelled by users'] = $detail_stats['user_cancelled_job'];
        $this->job_stats['Detailed']['Jobs cancelled by makerspace'] = $detail_stats['staff_cancelled_job'];

        $this->job_stats['Cancellation'] = $cancellation_stats;
	}

	/**
	* Prepare stats for users
	* @param array $user_stats :  user stats from the db
	*/
	public function prepareUserStats($user_stats){
		$this->user_stats['All User']['Total users'] = $user_stats['all_users'];
		$this->user_stats['All User']['Public users'] = $user_stats['public_users'];
		$this->user_stats['All User']['Student staff'] = $user_stats['student_staff'];
		$this->user_stats['All User']['Staff'] = $user_stats['staff'];
		$this->user_stats['All User']['Administrators'] = $user_stats['admin'];

		$this->user_stats['Public User']['Active users'] = $user_stats['active_users'];
		$this->user_stats['Public User']['Verified users'] = $user_stats['verified_users'];
		$this->user_stats['Public User']['Unverified users'] = $user_stats['un_verified_users'];
		$this->user_stats['Public User']['Blocked users'] = $user_stats['blocked_users'];
	}


	/**
	* Called by ajax
	* Prepares data for analytics
	* @param int selected_year : User selected year from the interface
	* @param int type : if the selected type is job analytics , if the selected type is price analytics it is set to 2
	* returns a joson for values for the entire year including jobs submitted
	*/
	public function prepareAnalytics($selected_year, $type=1){
		$error_messages = array();
		if($this->access_level == "ADMIN" || $this->access_level == "STAFF" || $this->access_level == "STUDENT STAFF"){

			$this->display_type  = (intval($type) == 2)?2:1;
			if(!isset($selected_year)){
				$selected_year = date('Y');
			}
			// get the start year from the config file
			$analytics_array =  array();
			$cf = APP::config();
			$start_year = $cf->get('app.start_year')->get('start_year');

			// get the next 10 years
			for ($x=0; $x <=10; $x++) {$this->years[] = intval($start_year) + $x ; }

			$selected_year= (in_array(intval($selected_year), $this->years)) ? intval($selected_year) : intval($start_year);

			$dates_array = $this->getDatesArrayForAnalytics($selected_year);

			// db call for each month of the year
			if($this->display_type == 2){
				foreach($dates_array as $k=>$range){
                    LoggerPrime::info("getting analytics from start date: " . $range['start_date'] . " to end date: ". $range['end_date']);
					$analytics_array['completed'][$k] = $this->dc->getPriceAnalytics($range, $type = "Completed step");
				}
				foreach($dates_array as $k=>$range){
                    LoggerPrime::info("getting analytics from start date: " . $range['start_date'] . " to end date: ". $range['end_date']);
					$analytics_array['user_cancelled'][$k] = $this->dc->getPriceAnalytics($range, $type = "Cancelled by user step");
				}
				foreach($dates_array as $k=>$range){
                    LoggerPrime::info("getting analytics from start date: " . $range['start_date'] . " to end date: ". $range['end_date']);
					$analytics_array['cancelled'][$k] = $this->dc->getPriceAnalytics($range, $type = "Cancelled step");
				}

				$analytics = $this->preparePriceAnalytics($selected_year ,$analytics_array);

			}
			else {
				foreach($dates_array as $k=>$range){
                    LoggerPrime::info("getting analytics from start date: " . $range['start_date'] . " to end date: ". $range['end_date']);
					$analytics_array[$k] = $this->dc->getPrimeAnalytics($range);
				}
				$analytics = $this->prepareJobAnalytics($selected_year, $analytics_array);
			}


			return $analytics;
		}else {
			$error_messages[] = "Sorry, this operation is not allowed";
			Alerts::setErrorMessages($error_messages);
			header('Location: /?t=home');
		}
		return 0;
	}

	/**
	* Function to get  dates for a selected year
	* @param int $selected_year : user selected year
	*/
	public function getDatesArrayForAnalytics($selected_year){
		$dates_array = array();
		// get the start dates and end dates of each month for the entire year
		for($i=1 ; $i<=12 ; $i++){
			$start_date = date_format(new DateTime($i.'/01/'.$selected_year), 'Y-m-d');
			$end_date = ($i == 12) ? date_format(new DateTime('01/01/'.($selected_year+1) ), 'Y-m-d') : date_format(new DateTime(($i+1).'/01/'.$selected_year), 'Y-m-d') ;
			//$end_date =($i ==12)? date_format(new DateTime('01/01/'.intval($selected_year)+1), 'Y-m-d') : '';
			$date_array[$i] = array('start_date'=>$start_date, 'end_date'=>$end_date);


		}
		return $date_array;


	}

	/**
	* prepare date for job analytics graph
	* @param array $analytics_array : data from the db contating counts for each month
	* @param int $selected_year : selected year from the user interface
	*/
	public function prepareJobAnalytics($selected_year, $analytics_array){
		// prepare data for front end display
		$submitted_counts = array();
		$completed_counts = array();
		$staff_cancelled_counts = array();
		$user_cancelled_counts = array();

		foreach($analytics_array as $k=>$counts){
			foreach($counts as $type=>$count){
				switch ($type){

					case 'submitted':
						$submitted_counts[$k] = $count;
						break;
					case 'completed':
						$completed_counts[$k] = $count;
						break;
					case 'staff_canclled':
						$staff_cancelled_counts[$k] = $count;
						break;
					case 'user_cancelled':
						$user_cancelled_counts[$k] = $count;
						break;
					default:
						break;
				}
			}

		}

		$analytics['selected_year'] = $selected_year ;
		$analytics['years'] = $this->years ;
		$analytics['submitted'] = $submitted_counts ;
		$analytics['completed'] = $completed_counts ;
		$analytics['staff_cancelled'] = $staff_cancelled_counts ;
		$analytics['user_cancelled'] = $user_cancelled_counts ;

		return $analytics;


	}

	/**
	* prepare date for job analytics graph
	* @param array $analytics_array : data from the db contating price data for each month
	* @param int $selected_year : selected year from the user interface
	*/
	public function preparePriceAnalytics($selected_year, $analytics_array){
		$completed_price = array();
		$user_cancelled_price = array();
		$cancelled_price = array();
        LoggerPrime::info("Preparing data for price analytics: ");
		foreach($analytics_array['completed'] as $key=>$val){
			$completed_price[$key] = (isset($completed_price[$key])) ?$completed_price[$key] :0;
			foreach($val as $k=>$v){
				if(isset($v['data'])){
					$data = unserialize($v['data']);
					$completed_price[$key] += (isset($data['price']['total_price'])) ? $data['price']['total_price'] : (isset($data['price']['total']) ? $data['price']['total'] : 0);
				}
			}

			$completed_price[$key] = isset($completed_price[$key]) ? $completed_price[$key] : 0;
		}
		foreach($analytics_array['user_cancelled'] as $key=>$val){
			$user_cancelled_price[$key] = (isset($user_cancelled_price[$key])) ?$user_cancelled_price[$key] :0;

			foreach($val as $k=>$v){
				if(isset($v['data'])){
					$data = unserialize($v['data']);
					$user_cancelled_price[$key] += (isset($data['price']['total_price'])) ? $data['price']['total_price'] : (isset($data['price']['total']) ? $data['price']['total'] : 0);
				}
			}
			$user_cancelled_price[$key] = isset($user_cancelled_price[$key]) ? $user_cancelled_price[$key] : 0;
		}
		foreach($analytics_array['cancelled'] as $key=>$val){
			$cancelled_price[$key] = (isset($cancelled_price[$key]))?$cancelled_price[$key]:0;
			foreach($val as $k=>$v){
				if(isset($v['data'])){
					$data = unserialize($v['data']);
					$cancelled_price[$key] += (isset($data['price']['total_price'])) ? $data['price']['total_price'] : (isset($data['price']['total']) ? $data['price']['total'] : 0);
				}
			}
			$cancelled_price[$key] = isset($cancelled_price[$key]) ? $cancelled_price[$key] : 0;
		}
		$analytics['selected_year'] = $selected_year ;
		$analytics['years'] = $this->years ;
		$analytics['completed'] = $completed_price ;
		$analytics['staff_cancelled'] = $cancelled_price ;
		$analytics['user_cancelled'] = $user_cancelled_price ;

		return $analytics;

	}




}


