<?php

/**

* Access the applications config file - 3dprime.cfg
* Will trigger a E_USER_ERROR if config is not accessible
*
*/

class APP{

	static private $CONFIG_ACCESS = null;

	static public function config(){
		if(APP::$CONFIG_ACCESS == null){
			$sConfigFilePath = dirname(__DIR__)."/makeatstate.cfg";
			$cf = new ConfigFile($sConfigFilePath);
			if($cf->load()){
				APP::$CONFIG_ACCESS = $cf;
			}
			else{
			        print_r($cf->errors());
				trigger_error("Could not open the config file '{$sConfigFilePath}'.", E_USER_ERROR);
				exit(1);
			}
		}

		return APP::$CONFIG_ACCESS;
	}

	static public function database(){
		$cf = APP::config();
		$SERVERS = array();
		$cfdb = $cf->get("sql.auth.primary");
		if($cfdb != null){
			$dbAuth = array(
				'host' 		=> $cfdb->get('host'),
				'username' 	=> $cfdb->get('user'),
				'password'	=> $cfdb->get('password'),
				'database'	=> $cfdb->get('database'),
				'port'		=> $cfdb->get('port')
			);
		$SERVERS[] = $dbAuth;

		}
		return $SERVERS;
	}

	/**
	* Get credentails for Okta from the config file
    *
    * @return array
	*/
	static public function oktaCredentials(){
        $credentails = array();
		$cf = APP::config();
        // get okta credentials from the config file
        $okta_cred = $cf->get("authentication.okta");
        if($okta_cred != null && ($okta_cred->get('active')) ){
             $credentails['issuer'] = $okta_cred->get('issuer');
             $credentails['client_id'] = $okta_cred->get('client_id');
             $credentails['client_secret'] = $okta_cred->get('client_secret');
             $credentails['redirect_url'] = $okta_cred->get('redirect_url');
             $credentails['logout_redirect_url'] = $okta_cred->get('logout_redirect_url', 'https://makeatstate.lib.msu.edu');

        }
		return $credentails;
	}

	/**
	* Get the path of the upload directory
	*/
	static public function uploadPath(){
		$cf = APP::config();
		$upfp = $cf->get("upload");
		$upload_path = array();
		if($upfp !=null){
			$upload_path = $upfp;
		}
		return $upload_path;
	}

	static public function getFileTypes(){
		$cf = APP::config();
		$file_type_array= $cf->get('file_type');
		if($file_type_array != null){
			return $file_type_array;
		}
		return array();
	}

	static public function getWorkflowTags(){
		$cf = APP::config();
		$workflow_tags_array= $cf->get('workflow_tags');
		return $workflow_tags_array;
	}


	/**
	* Get log file from the config
	*/

	static public function logfile(){
		$cf = APP::config();
		$cffp = $cf->get("logging")->get("file");
		$filepath = array();
		if($cffp != null){
			$filepath = $cffp;

		}
		return $filepath;
	}
	/**
	* function used for development to print variables
	* Remove from prod --TODO
	*/

	static public function printVar($myVar=null){
        if(empty($myVar)){
            echo '<p> ###############################</p>'.PHP_EOL;
            echo '<pre> The variable is empty </pre>';
            echo '<p> ###############################</p>'.PHP_EOL;
        }
        else if(is_array($myVar)){
            echo '<p>  ########### Array Variable ####################</p>'.PHP_EOL;
            APP::printArray($myVar);
            echo '<p> ################################################</p>'.PHP_EOL;

        }
        else if(is_object($myVar)){
            echo '<p> ##################### Object ###################</p>'.PHP_EOL;
            echo '<pre>'.var_dump($myVar).' </pre>';
            echo '<p> ################################################</p>'.PHP_EOL;

        }
        else {
            echo '<p> ################### Variable ###################</p>'.PHP_EOL;
            echo '<pre> The variable: '.$myVar. ' </pre>';
            echo '<p> ################################################</p>'.PHP_EOL;

        }



    }
	/**
	* function used for development to print variables
	* Remove from prod --TODO
	*/

    /**
     * function used for development to print variables
     * Remove from prod --TODO
     */

    static public function printArray($myArray, $level = 0){
        foreach($myArray as $key=>$val){
            $level_spacing = str_repeat("\t", $level);
            if(is_array($val) || is_object($val)){
                echo '<pre>'.$level_spacing.'key: '. $key. '</pre>';
                APP::printArray($val, $level+1);

            }

            else{
                echo '<pre>'.$level_spacing.'key : '.$key. ' value: ' .$val. '</pre>';
            }
        }


    }

    }





