<?php

class UserRegistration{


	private $rTemplate;
	private $dc;
	private $error_messages = array();

	private $user_id;
	private $name;
	private $email;

	/**
	*Constructor function for User Registration
	* @param $rTempl : Templater object
	*/
	public function __construct(Templater &$rTempl){
		$this->rTemplate = $rTempl;
		$this->dc = new DataCalls();
		$rn = UserData::create('rn')->getString('');
		$pTarget = UserData::create('t')->getString();
		$resend_user_id =  UserData::create('rs_uid')->getString();
		$ott = UserData::create('ott')->getString();
		$cuid = UserData::create('cuid')->getString();
		if( !(empty($rn))  && !empty($_SESSION['auth']['sn'])  && ($_SESSION['auth']['sn']  == $rn) ){
			$this->registerUser();

		}
		else if($pTarget == 'resend' && !empty($resend_user_id)){

			$this->resendConfirmationLink($resend_user_id);
		}
		else if($pTarget == 'confirm' && !empty($ott) && !empty($cuid)){

			$this->confirmUser($ott , $cuid);
		}
		else{
			$this->rTemplate->setTemplate('register.html');
			$this->renderRegisterTemplate();
		}


	}

	/**
	*Render template for registration
	*/
	public function renderRegisterTemplate(){
		$this->rTemplate->setVariables('sn', AccessHandler::generateNonce());
		$this->rTemplate->setVariables("page_title" , "Register");

		$this->rTemplate->setVariables('error_messages', Alerts::getErrorMessages());
		$this->rTemplate->generate();
	}

	/**
	* Render Email sent template
	*/
	public function renderEmailSentTemplate(){

		$this->rTemplate->setTemplate('email_confirmation.html');
		$this->rTemplate->setVariables("name" , $this->name);
		$this->rTemplate->setVariables('error_messages', Alerts::getErrorMessages());
		$this->rTemplate->setVariables('success_messages', Alerts::getSuccessMessages());
		$this->rTemplate->setVariables("page_title" , "Email Confirmation");

		$this->rTemplate->setVariables("email", $this->email);
		$this->rTemplate->setvariables("user_id", $this->user_id);
		$this->rTemplate->generate();

	}

	/**
	* Render email confirmation template
	* @param string $message : The message for confirmation template
	*/
	public function renderEmailConfirmationTemplate($message){

		$this->rTemplate->setTemplate('confirmation.html');
		$this->rTemplate->setVariables('message', $message);
		$this->rTemplate->setVariables("page_title" , "Email confirmation");

		$this->rTemplate->setVariables('user_id', $this->user_id);
		$this->rTemplate->setVariables('email', $this->email);
		$this->rTemplate->setVariables('name', $this->name);
		$this->rTemplate->generate();
	}



	/**
	*Registers the user
	*/

	public function registerUser(){

		$error_messages = array();
		$cf = APP::config();
		// getting the affiliations from the config file
		$cf_afiliation = $cf->get("app.affiliation");
		$email = $_POST['email'];
		$first_name = $_POST['first_name'];
		$last_name = $_POST['last_name'];

		$ph = $_POST['phone'] ? str_replace('-','',$_POST['phone']) : null;
		$pw = $_POST['password'];
		$affiliation  = (isset($_POST['affiliation']) && !empty(intval($_POST['affiliation'])) && $cf_afiliation->get(intval($_POST['affiliation'])) )?intval($_POST['affiliation']) :1 ;// if un expected value is entered default them to community users
		$dep = (isset($_POST['dep']) && !empty($_POST['dep'])) ? strip_tags($_POST['dep']) : null;
		$hint = null; //set hint to null always. add the logic if we decide to use hint
		$uRow = $this->dc->getUserByEmail("users", $email);

		# if  user email already exists
	if(!empty($uRow)){
			$error_messages[] = "An account with this email address already exists. Try  <a href ='/?t=login'>Sign in</a> or if you forgot your password try  <a href ='/?t=reset'>password reset </a>";

		}


		else {
			#hashing the password
			$pwhash = AccessHandler::createPasswordHash($pw);
			#generating the one time token for initial registration
			$token = AccessHandler::generateOneTimeToken();
			# get permissions for the user . User will always have basic permissions on registration
			$pData = array('internal_name' => 'users');
			$pRow = $this->dc->getPermissionsUsingInternalName('permissions' , $pData);
			if(empty($pRow) ){
				LoggerPrime::debug("Permission does not exist on initial registration");
				$error_messages[] = "New user registration is not allowed at this time";
			}
			else {
				$perm_id = $pRow[0]['permission_id'];
				# one time token expires in a day

				$registration_array = array('email' => $email, 'permission_id'=>$perm_id, 'fullname'=>$first_name, 'lastname'=>$last_name, 'pw_hash'=>$pwhash, 'onetime_token' => $token, 'phone_num' =>$ph, 'hint' => $hint, "affiliation"=> $affiliation, "department"=>$dep);
				$this->email = $email;
				$this->name = $first_name;

				$this->dc->transactionStart();
				$uRow = $this->dc->insertUsers($registration_array);
				if(empty($uRow)){
					$error_messages[] = "New user registration is not allwoed at this time";
				}
				else{
					$this->user_id = $uRow;
					$mail_sent  = $this->sendEmail($token);

					if($mail_sent){
						#if the email was successfully sent
						LoggerPrime::debug("Successfully created a user with email :  ". $email);
						$this->dc->transactionCommit();
						$this->dc->insertExpDate($uRow);
						$this->renderEmailSentTemplate();


					}
					else{
						#if failed to send the eamil
						$this->dc->transactionRollback();
						LoggerPrime::debug("Unable to send email, provided email address  : ". $email );
						$error_messages[] = "we have having trouble reaching you at the email address you provided , please use a different email addrress";
					}

				}
			}


		}
		#Set error messages array

		if(!empty($error_messages)){
			Alerts::setErrorMessages($error_messages);
			$_SESSION['auth']['sn'] = null;
			header('Location: /?t=register');
		}
	}


	/**
	*Resend confirmation link to the user
	* @param int user_id : User_id of the user
	*/
	public function resendConfirmationLink($user_id){

		$error_messages = array();
		$success_messages = array();

		$aData = array('user_id'=> $user_id);
		$uRow = $this->dc->getRowsById('users', $aData);
		$this->email =$uRow[0]['email'];
		$this->name = $uRow[0]['fullname'];
		$this->user_id = $user_id;
		$verified  = $uRow[0]['verified'];
		// if the user is already verified
		if(!empty($verified)){
			$error_messages[] = "Please login to our account using your email and password" ;
			Alerts::setErrorMessages($error_messages);
			header('Location: /?t=home');

		}
		else {
			#Check if the user is already verified

			# Generate a new token for verifucation
			$token = AccessHandler::generateOneTimeToken();

			#update the the $token for that user

			$token_array = array('user_id'=>$user_id , 'onetime_token'=>$token);
			$tRow = $this->dc->updateUsingPrimaryKey('users' ,'user_id', $token_array);
			$eRow = $this->dc->insertExpDate($user_id);



			if(empty($tRow) ||  empty($eRow)){
				LoggerPrime::debug("Unable to update token for user");

				$error_messages[] = "Unable to resend confirmation link at this time , please try again later ";
			}

			else{

				$mail_sent  = $this->sendEmail($token);
				if($mail_sent){
					#if the email was successfully sent
					LoggerPrime::debug("Successfully sent a conformation email :  ". $this->email);

				}
				else{
					#if failed to send the eamil
					$this->dc->transactionRollback();
					LoggerPrime::debug("Unable to send email , provided email address  : ". $this->email );
					$error_messages[] = "we have having trouble reaching you at the email address you provided , please use a different email addrress";
				}

			}
		}
		if(!empty($error_messages)){
			Alerts::setErrorMessages($error_messages);
		}
		else{
			$success_messages[] = "We sent a new confirmation email , please check your email for the confirmation link";
			Alerts::setSuccessMessages($success_messages);
		}

		$this->renderEmailSentTemplate();
	}


	/**
	* Conformation of the user's email
	* @param string $token: token from the url
	* @param int $user_id : User id of the user
	*/
	public function confirmUser($token, $user_id){

		// Get the user based on the token
		$message_type = "";
		$this->user_id = $user_id;
		$aData = array('user_id'=> $user_id);
		$uRow = $this->dc->getRowsById('users', $aData);
		$current_date = date('Y-m-d H:i:s');

		if(empty($uRow) && !empty($uRow[0]['verified'])){
			$message = "error";
		}
		else {
			$this->name = $uRow[0]['fullname'];
			$this->email = $uRow[0]['email'];
			$token_exp_date = new DateTime($uRow[0]['onetime_token_expires']);
			if($current_date > $token_exp_date || $token != $uRow[0]['onetime_token']){
				$message = "expired";
			}
			else {
				# empty onetime token and set verified time stamp
				$uRow = $this->dc->removeOnetimeToken($this->user_id);
				$message = empty($uRow) ?'expired':'success';

			}

		}
		$this->renderEmailConfirmationTemplate($message);

	}

	/**Generates Email template
	* @param string token: onetime token for the user
	*/
	public function sendEmail($token){

		$cf = APP::config();
		$site = $cf->get('application.url');
		$confirm_url = $site. "/?t=confirm&ott=".$token."&cuid=".$this->user_id;
		$this->rTemplate->setTemplate('email_tmpl.html');
		$this->rTemplate->setBlockVariables('confirm_url', $confirm_url);
		$this->rTemplate->setBlockVariables('name', $this->name);
		$this->rTemplate->setBlock('email_template');
		$email_block = $this->rTemplate->generateBlock();

		$subject = "MakeAtState confirmation email";
		// To send HTML mail, the Content-type header must be set
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Content-type: text/html; charset=iso-8859-1';
		// Additional headers
		$headers[] = 'From: MakeAtState <'. $cf->get('application.email').'>';

		return mail($this->email, $subject, $email_block, implode("\r\n", $headers));

	}


}

?>
