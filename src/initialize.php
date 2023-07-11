<?php

require __DIR__ . '/../vendor/autoload.php';

require_once 'app.php';
require_once 'prime_helper.php';
require_once 'alerts/alerts.php';
require_once 'logger_prime.php';
require_once 'access_handler.php';
require_once 'templater.php';
require_once 'data_calls.php';
require_once 'urls.php';
require_once 'api_request.php';
require_once 'users/user.php';
require_once 'users/permissions.php';


#start the session
session_start();

#load the config file 
$cf = APP::config();

#set the default time zone
date_default_timezone_set($cf->get('application.timezone'));

# Initialize Logger
$iLogFile = $cf->get('logging')->get('file');
LoggerPrime::initialize($iLogFile,LoggerPrime::LOG_DEBUG);

# Set session active time
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    // last request was more than 30 minutes ago
    session_unset();     // unset $_SESSION variable for the run-time
    session_destroy();   // destroy session data in storage
}
$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp

?>
