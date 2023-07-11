<?php /** @noinspection SpellCheckingInspection */

/**
* handles authentication
*/


class AccessHandler{

	const PERMISSION_READ 		=0x0001;
	const PERMISSION_WRITE		=0x0002;
	const PERMISSION_MODIFY		=0x0004;
	const PERMISSION_EVAL		=0x0008;

	private $dc;




	/**
	*Constructor function for the Access Handler object
	*/
	public function __construct(){
		$this->dc = new DataCalls();

	}

	/**
	* Generate nonce and store it in the session
	*/
	public static function generateNonce(){
		$sNonce = bin2hex(random_bytes(24));
		if(empty($_SESSION['auth'])){
			$_SESSION['auth'] = array();
		}
		$_SESSION['auth']['sn'] = $sNonce;
		return $sNonce;

	}

	/**
	* Generate nonce and store in the session for all post requests
	*/
	public static function generatePostNonce(){
		$pNonce = bin2hex(random_bytes(24));
		if(empty($_SESSION['POST'])){
			$_SESSION['POST'] = array();
		}
		$_SESSION['POST']['pn'] = $pNonce;
		return $pNonce;
	}

	/**
	* Generate onetime toke for password reset and initial registration
	*/
	public static function generateOneTimeToken(){
		/** @noinspection SpellCheckingInspection */
		$chars = 'abcedfghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$len = strlen($chars) - 1;
		$token ="";
		for($i=0; $i<=30; $i++){
			$token .=$chars[random_int(0,$len)];
		}

		return $token;

	}

	/**
	* Function to logout the user
	*/
    public function logoutUser() {
        // if access token is present in the url the user has used Okta to login
        $current_user = $this->dc->getUserByEmail("users", $_SESSION['ident']['email']);
        if(isset($current_user[0]['okta_token']) && !empty($current_user[0]['okta_token'])) {
            header('Location: /?t=select_logout');
            die;
        } else {
            $this->logoutCommunityUser();
        }
    }

	/**
	* Function to logout the Okta user
	*/
    public function logoutOktaUser() {

        // if access token is present in the url the user has used Okta to login
        if(isset($_SESSION['id_token']) && !empty($_SESSION['id_token'])) {
            require_once('okta.php');
            $access_token = $_SESSION['access_token'];
            $id_token = $_SESSION['id_token'];
            $okta = new Okta();
            // call the Okta config endpoint. This is the first call which sets the metadata
            $config_endpoint = $okta->getConfigurationEndopint();
            $okta->setMetadata($config_endpoint);
            unset($_SESSION['ident']);
            unset($_SESSION['access_token']);
            unset($_SESSION['id_token']);
            
            session_regenerate_id();
            //logout user
            $okta->logoutUser($access_token, $id_token);
        }


    }

	/**
	* Function to logout community  user
	*/
    public function logoutCommunityUser() {
        
        unset($_SESSION['ident']);
        unset($_SESSION['access_token']);
        unset($_SESSION['id_token']);
        session_regenerate_id();
        LoggerPrime::debug("user successfully loggedout");
        header('Location: /');

    }



	/**
	* Function to login user via Okta
	*/
    public function oktaLogin() {
        // check if the okta file exists
        if (! @include_once( 'okta.php' )) {
            $error_messages[] = "We have encountered a problem with this authentication method. Please try again later";
            LoggerPrime::error("Trying to load okta.php. This file does not exist");
        } else {
            require_once('okta.php');
            $okta = new Okta();

            // call the Okta config endpoint. This is the first call which sets the metadata
            $config_endpoint = $okta->getConfigurationEndopint();
            $okta->setMetadata($config_endpoint);
            $okta->authorize();

        }
    }

	/**
	* Handle authorization callback for Okta
	*/
    public function handleOktaAuthorizationCallback() {
        $isAuthenticated =  false;
        // check to see if the app has Okta files included
        if (! @include_once( 'okta.php' )) {
            $error_messages[] = "We have encountered a problem with this authentication method. Please try again later";
            LoggerPrime::error("Trying to load okta.php. This file does not exist");
        } else {
            require_once('okta.php');
            $okta = new Okta();

            // call the Okta config endpoint. This is the first call which sets the metadata
            $config_endpoint = $okta->getConfigurationEndopint();
            $okta->setMetadata($config_endpoint);

            // verify the status and code params and get the access token
            $okta->handleOktaAuthenticateResponse();

            // set the access token in session
            $_SESSION['access_token'] = $okta->getAccessToken();

            //set the id token in the session
            $_SESSION['id_token'] = $okta->getIdToken();

            //call the tntrospection API if the user is not already in session
            if(empty($_SESSION['ident']['user_id'])) {
                $okta->instrospect();
                // if the user status is active then call the user info API
                if($okta->getUserStatus()) {
                    $user_info = $okta->getUserinfo();
                    
                    // check if the user info object is not null
                    if($user_info != null && isset($user_info->uuid)) {
                        $uRow = $this->addOktaUser($user_info);
                        // set the user in session
                        $isAuthenticated = true;

                        // on first login uRow is the id of the user
                        if(!is_array($uRow)) {
                            $uRow = $this->dc->getUserById($uRow);
                        }
                        $_SESSION['ident'] = $uRow[0];

                        // if desitination url is set in the session
                        if(isset($_SESSION['destination']) && $_SESSION['destination'] != '/?t=signout'){
                            $dest_url = $_SESSION['destination'];
                            LoggerPrime::info( 'destination url for okta users' .$dest_url);
                            unset($_SESSION['destination']);
                            header('Location: '.$dest_url);
                        }


                    } else {
                        $error_messages[] = "We were unable to authenticate you with your MSU Credentials. Please try again or contact MSU IT for more information.";
                        LoggerPrime::error("Unable to retrive user information from Okta");
                        $this->handleAuthErrors($error_messages);

                    }
                } else {
                    $error_messages[] = "We were unable to authenticate you with your MSU Credentials. Please try contact MSU IT for more information.";
                    LoggerPrime::error("Retrived an inactive user from Okta");
                    $this->handleAuthErrors($error_messages);
                }
            }
        }

        return $isAuthenticated;
    }

	/**
	*Function to verify if the user is authenticated
	*/
	public function isAuthenticatedUser(){
		$isAuthenticated = FALSE;
		$error_messages = array();
		$pTarget = UserData::create('t')->getString();

		// functionality for logout
		if($pTarget == 'signout'){
            $this->logoutUser();
		}

		// functionality for user signout. This will not sign a user out of Okta
		if($pTarget == 'user_signout'){
            $this->logoutCommunityUser();
		}

		// functionality for user okta signout.
		if($pTarget == 'okta_signout'){
            $this->logoutOktaUser();
		}

        // functionality to authenticate the user via Okta
        elseif($pTarget == 'okta-login'){
            $this->oktaLogin();
        }

        // functionality to authenticate the user via Okta
        // this is rediercted via Okta and should contain state and code params
        // 3DPrime verifies state and code params to authenticate users
        elseif($pTarget == 'okta-authorization-code-callback'){
            $isAuthenticated = $this->handleOktaAuthorizationCallback();
        }

		// functionality to authenticate the user via 3DPrime
		elseif($pTarget == 'signin'){
			$email = UserData::create('email','POST')->getString();
			$pass = UserData::create('signin_password', 'POST')->getString();
			$sn = UserData::create('sn', 'POST')->getString();
			if( empty($_SESSION['auth']['sn']) ||  $sn != $_SESSION['auth']['sn']){
				$error_messages[] = "Session expired , please try again";
			}
			else{

				$dummyHash = self::createPasswordHash("DUMMY_HASH");
				$uRow = $this->dc->getUserByEmail("users", $email);
				if(empty($uRow)){
					// compute the hash  for a dummy password
					$error_messages[] = "Incorrect email or password. If you have logged in via MSU Okta Login please use the \"  MSU Users (Sign in with MSU Credentials) \" option." ;
					$dummyPass = "DUMMY";
					$isAuthenticated = password_verify($dummyPass, $dummyHash);
				}
				// if password is empty, possible when the user is created by an administrator
				else if(empty($uRow[0]['pw_hash'] )){
					#--TODO

				}
				else {
					$pHash = $uRow[0]['pw_hash'] ;
					//Both email and password are a match
					if(password_verify($pass, $pHash)){
						if(!empty($uRow[0]['removed']) || !empty($uRow[0]['blocked'])){
							$error_messages[] = "Your account has been locked. Please contact Makerspace at the MSU Library for further information";
						}
						// if user is not verified send to verification screen
						elseif(empty($uRow[0]['verified'])){
							header('Location: /?t=resend&rs_uid='.$uRow[0]['user_id']);
						}
						else {
							$isAuthenticated = TRUE;
							$_SESSION['ident'] = $uRow[0];
							// set user password and phone_num in the session
							unset($_SESSION['ident']['pw_hash']);
							unset($_SESSION['ident']['phone_num']);
						}
					}
					else {
						$error_messages[] = "Incorrect email or password. If you have logged in via MSU Okta Login please use the \"  MSU Users (Sign in with MSU Credentials) \" option.";

					}

				}

			}

		}
		// if the user is already logged in
		else if(!empty($_SESSION['ident']['user_id'])){
			$user_id = $_SESSION['ident']['user_id'];
			$uData = array('user_id' =>$user_id);
			$uRow = $this->dc->getRowsById('users', $uData);

			if(!empty($uRow[0]['blocked'])){
				$error_messages[] = "Your account has been locked. Please contact Makerspace at the MSU Library for further information";
                $this->handleAuthErrors($error_messages);
			}
			if(empty($uRow) || !empty($uRow[0]['removed']) ){
				$isAuthenticated = FALSE;
				$_SESSION['ident'] = array();
				$error_messages[] = "We have encountered a problem with your account please contact the administrator";
			}
			else{
				$isAuthenticated = TRUE;
			}

		}
		if(!empty($error_messages)){
			 Alerts::setErrorMessages($error_messages);
		}

	    return $isAuthenticated;
	}

    /**
     * Add a user signed in via okta into the db
     *
     * @param object $user_info: User infor object from the okta API
     */
    public function addOktaUser($user_info) {

        // check if the user in the db based on user_info->sub which is mapped to the okta_token column in the db
        $uRow = $this->dc->getUserByOktaToken("users", $user_info->uuid);

        if(!empty($uRow)){

            if(!empty($uRow[0]['removed']) || !empty($uRow[0]['blocked'])){
                $error_messages[] = "Your account has been locked. Please contact Makerspace at the MSU Library for further information";
                $this->handleAuthErrors($error_messages);
            }

            // if  user_info->sub which is mapped to okta_token column is a match check if the user email is a match
            if($user_info->netid.'@msu.edu' !== $uRow[0]['email']) {
                // log the user in but let them know that their email has changed
                // TODO
            }
        } else {
            // insert the user object in db if it is thier first time login
            // insertOktaUser function handles errors if there are any
            $uRow = $this->insertUser($user_info);
        }

        return $uRow;
    }

    /**
     * Insert a new user signed in via okta into the db
     *
     * @param object $user_info: User infor object from the okta API
     */
     public function insertUser($user_info) {
        $registration_array = array();
        $error_messages = array();
		$okta_token = $user_info->uuid;
		$email = $user_info->netid.'@msu.edu';
		$first_name = $user_info->given_name;
		$last_name = $user_info->family_name;
		$cf = APP::config();
		$cf_afiliation = $cf->get("app.affiliation");
        $user_affiliation = 1;
        foreach($cf_afiliation->getAll() as $key=>$affiliation)
        {
            if(strcasecmp($affiliation[0], $user_info->lib3d_primary_affiliation) == 0) {
                $user_affiliation = $key;
            }
        }


		$ph = isset($user_info->phone) ? $user_info->phone : null;
        // Add a random password for okta users. They cannot sign in using password
		$pw = bin2hex(random_bytes(32));
		$pwhash = AccessHandler::createPasswordHash($pw);

        $hint = null; //set hint to null always. add the logic if we decide to use hint

        // set the permissions of the user. Give them a user status. They can be upgraded using the UI by an admin
        $pData = array('internal_name' => 'users');

        // check if the user with that email already exits in the system
        $existing_user = $this->dc->getUserByEmail("users", $email);
        $pRow = $this->dc->getPermissionsUsingInternalName('permissions' , $pData);
        if(empty($pRow) ){
            LoggerPrime::debug("Permission does not exist on initial registration");
            $error_messages[] = "New user registration is not allowed at this time";
        }
        else {
            $perm_id = $pRow[0]['permission_id'];
            $registration_array = array('email' => $email, 'permission_id'=>$perm_id, 'fullname'=>$first_name, 'lastname'=>$last_name, 'pw_hash'=>$pwhash, 'phone_num' =>$ph, 'hint' => $hint, "okta_token"=> $okta_token, "verified"=>"NOW()", "affiliation"=>$user_affiliation);
        }
        $this->dc->transactionStart();

        // update the existing user if they alreay exists
        if (isset($existing_user) && !empty($existing_user)) {

            // unset permissions and verification
            $registration_array = array('email' => $email, 'fullname'=>$first_name, 'lastname'=>$last_name, 'pw_hash'=>$pwhash, 'hint' => $hint, "okta_token"=> $okta_token, "affiliation"=>$user_affiliation);
            $registration_array['user_id'] = $existing_user[0]['user_id'];
            $uRow = $this->dc->updateUsingPrimaryKey("users", "user_id", $registration_array); 
            if(!empty($uRow)) {
                $uRow = $existing_user[0]['user_id'];
            }

        } else {
            $uRow = $this->dc->insertUsers($registration_array);
        }
        if(empty($uRow)){
            $error_messages[] = "New user registration is not allwoed at this time";
        }
        else{
            $this->user_id = $uRow;
            $mail_sent  = $this->sendWelcomeEmail($first_name, $email);

            if($mail_sent){
                #if the email was successfully sent
                LoggerPrime::debug("Successfully created a user with email :  ". $email);
                $this->dc->transactionCommit();
            }
            else{
                #if failed to send the eamil
                $this->dc->transactionRollback();
                LoggerPrime::debug("Unable to send email, provided email address  : ". $email );
                $error_messages[] = "we have having trouble reaching you at the email address you provided , please use a different email addrress";
            }

        }
        if(!empty($error_messages)) {
            $this->handleAuthErrors($error_messages);
        }
        return $uRow;

     }

	/** Sends confirmation email to the user when they sign up via okta
     *
     * @param string $name: First name of the user
     * @param string $email: Email of the user
     *
	 */
	public function sendWelcomeEmail($name, $email){
		$cf = APP::config();
        $eTemplate = new Templater();
		$site = $cf->get('application.url');
		$eTemplate->setTemplate('email_tmpl.html');
		$eTemplate->setBlockVariables('name', $name);
		$eTemplate->setBlock('email_template');
		$email_block = $eTemplate->generateBlock();

		$subject = "MakeAtState confirmation email";
		// To send HTML mail, the Content-type header must be set
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Content-type: text/html; charset=iso-8859-1';
		// Additional headers
		$headers[] = 'From: MakeAtState <'. $cf->get('application.email').'>';

		return mail($email, $subject, $email_block, implode("\r\n", $headers));

	}


	/**
 	 * Display error message and redirect the user to a login page
 	 * @param string $pw : password provided by the user
 	 */
    public function handleAuthErrors($messages) {
        $_SESSION['ident'] = array();
		Alerts::setErrorMessages($messages);
        header('Location: /?t=login');

    }

	/**
	 * Generates the password hash
	 * @param string $pw : password provided by the user
	 */
	public static  function createPasswordHash($pw) {
       	return password_hash($pw, PASSWORD_BCRYPT, array('cost'=>12));
    }

}
