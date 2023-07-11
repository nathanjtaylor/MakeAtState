<?php
require_once 'initialize.php';

/**
* Main handles page requests

*/

function main(){
	$oTemplate = new Templater();
	$oTarget = UserData::create('t');
	$pTarget = $oTarget->getString('home');
	$db = new DataCalls();
	$post_urls_array = array('add_files' , 'delete_files');
	$oAuth = new AccessHandler();
	// if the user is not autenticated send them to login page
    
    // check all available authtypes
    $auth_options = array();
    $credentials = APP::oktaCredentials();
    if(!empty( $credentials)) {
         $auth_options[] = "Okta";
    }
	$oTemplate->setVariables("auth_options" , $auth_options);

	if(!$oAuth->isAuthenticatedUser()){
		if($pTarget == "register" || $pTarget == "resend" || $pTarget == "confirm"){
			require_once __DIR__."/register.php";
			$register = new UserRegistration($oTemplate);
		}
		else if($pTarget == "reset" || $pTarget == "reset_pass"|| $pTarget == "reset_email"){
			require_once __DIR__."/users/password_reset.php";
			$pass_reset = new PasswordReset($oTemplate);
		}
		else {
			// set the destination url in the session if the user is not logged in
			$email = UserData::create('email','POST')->getString();
			$_SESSION['destination'] = $_SERVER['REQUEST_URI'];
			$oTemplate->setTemplate('login.html');
			$oTemplate->setVariables('email', $email);
			$oTemplate->setVariables('sn', AccessHandler::generateNonce());
			$oTemplate->setVariables('error_messages', Alerts::getErrorMessages()); 
			$oTemplate->generate();
			
		}
	}

	// the user is autenticated 
	else {
		# Sets the user user array in the user class 	
		$user_object = new AuthenticatedUser($_SESSION['ident']);
		$oTemplate->setVariables("fullname" , $_SESSION['ident']['fullname']);
		if(in_array($pTarget , $post_urls_array)  && isset($_SESSION['POST']['pn'])  && isset($_POST['pn']) &&  $_SESSION['POST']['pn'] != $_POST['pn'] ){
			$pTarget = "home";	
			$urls = new Url($pTarget, $oTemplate);
		}
		// logic to take the user to the destination page after login
		else if($pTarget == "signin"){
			if(isset($_SESSION['destination']) && $_SESSION['destination'] != '/?t=signout'){
				$dest_url = $_SESSION['destination'];
				LoggerPrime::info( 'destination url' .$dest_url);
				unset($_SESSION['destination']);
				header('Location: '.$dest_url);
            }
			else {
				$pTarget = "home";	
				$urls = new Url($pTarget, $oTemplate);

			}
		} else if($pTarget == 'select_logout') {
            require_once __DIR__."/select_logout.php";
            $select_signout = new SelectSignout($oTemplate);
        } else{
			// to redirect to the destination page
			$urls = new Url($pTarget, $oTemplate);
		}
	}
}

?>
