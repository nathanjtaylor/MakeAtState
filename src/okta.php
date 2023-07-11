<?php

/**
* wrapper for okta API
*/


class Okta{

    // Okta app credentials. This contains issuer, client_id, and client_secret
    protected static $credentials;
    private $access_token           = null;
    private $id_token               = null;
    private $client_id              = null;
    private $client_secret          = null;
    private $redirect_url           = null;
    private $logout_redirect_url    = null;
    private $okta_issuer            = null;
    private $metadata               = null;
    private $is_user_active         = false;


    /**
     * Load Okta credentials ino a static variable. This saves resources reloading the credentials from the config file everytime
     *
     */
    function __construct(){

        //lazy loading of credentails
        if(!isset($credentials) || empty($credentials)) {
            $credentials = APP::oktaCredentials();
            if (!empty ($credentials)) {
                $this->client_id     = $credentials['client_id'];
                $this->client_secret = $credentials['client_secret'];
                $this->okta_issuer = $credentials['issuer'];
                $this->redirect_url  = $credentials['redirect_url'];
                $this->logout_redirect_url  = $credentials['logout_redirect_url'];
            }
        }
    }


    /**
     * Get Okta configuration endpoint
     *
     * @return string
     */
     public function getConfigurationEndopint() {
        $configuration_endpoint = null;
        if ( $this->okta_issuer != null ) {
            $configuration_endpoint = $this->okta_issuer . '/.well-known/openid-configuration';
        }
        return $configuration_endpoint;
     }

    /**
     * Use the Okta auth api to get the metadata and set it to a class variable
     *
     * @param string $configuration_endpoint: Okta configuration endpoint
     *
     * @return string
     */
     public function setMetadata($configuration_endpoint) {
        if ( $this->metadata == null ) {
            $api_req = new ApiRequest($configuration_endpoint);
            $api_req->setGetArg("client_id", $this->client_id);
            $api_req->call();
            $this->metadata = $api_req->json();
        }
     }

    /**
     * Authorize a user using Okta. This redirects the user to an Okta page asking for login credentials
     *
     */
    public function authorize($auth_endpoint=null) {
        $auth_endpoint = $auth_endpoint ?? $this->metadata->authorization_endpoint;
        $_SESSION['state'] = bin2hex(random_bytes(5));
        header('Location: ' . $auth_endpoint .
            '?' . http_build_query([
                'response_type' => 'code',
                'client_id' => $this->client_id,
                'redirect_uri' => $this->redirect_url,
                'state' => $_SESSION['state'],
                'scope' => 'openid profile lib3dprime',
            ])
        );
        exit(0);
    }

    /**
     * Handle Okta authenticate response, setting the access token
     */
    public function handleOktaAuthenticateResponse() {
        if (isset($_GET['code']) && isset($_GET['state'])) {
            if($_SESSION['state'] != $_GET['state']) {
                $error_messages[] = "An invalid state has returned form Okta authentication provider. Please contact the adminstrator for more help!";
				$_SESSION['ident'] = array();
				header('Location: /?t=login');
            }
            $this->setAccessToken($_GET['code']);
        }
    }

    /**
     * Set the Access Token from Okta using the token end point. This verifies the code returned by the user
     *
     * @param String $code: Code sent via Okta
     * @param String $token_endpoint: Token endpoint
     *
     */
    public function setAccessToken($code=null, $token_endpoint=null) {
        if ($code !== null) {
            $token_endpoint = $token_endpoint ?? $this->metadata->token_endpoint;
            $api_req = new ApiRequest($token_endpoint);
            $api_req->setPostArg('grant_type', 'authorization_code');
            $api_req->setPostArg('code', $code);
            $api_req->setPostArg('redirect_uri', $this->redirect_url);
            $api_req->setPostArg('client_id', $this->client_id);
            $api_req->setPostArg('client_secret', $this->client_secret);
            if ($api_req->call()) {
                $response = $api_req->json();
            }
            $this->access_token = $response->access_token ?? null;
            $this->id_token = $response->id_token ?? null;
            if ($this->access_token === null) {
                $error_messages[] = "Failed to retrive an access token from Okta . Please contact the adminstrator for more help!";
				$_SESSION['ident'] = array();
				header('Location: /?t=login');
            }
        }
    }

    /**
     * Returns the access token
     */
    public function getAccessToken($code=null, $token_endpoint=null) {
        return $this->access_token;
    }

    /**
     * Returns the id token
     */
    public function getIdToken() {
        return $this->id_token;
    }

    /**
     * Introspect API. This API returns information about the token that is associated with teh user
     *
     */
    public function instrospect($introspection_endpoint=null) {
        $introspection_endpoint = $introspection_endpoint ?? $this->metadata->introspection_endpoint;
        $api_req = new ApiRequest($introspection_endpoint);
        $api_req->setPostArg('token_type_hint', 'access_token');
        $api_req->setPostArg('token', $this->access_token);
        $api_req->setPostArg('client_id', $this->client_id);
        $api_req->setPostArg('client_secret', $this->client_secret);
        if ($api_req->call()) {
            $response = $api_req->json();
        }
        if($response != null && $response->active) {
            // update the active status of the user
            // if the user is not active this is set to false
            $this->is_user_active = $response->active;
        }
    }

    /**
     * Return the status of the user. The status is either true or false
     */
    public function getUserStatus() {
        return  $this->is_user_active;
    }

    /**
     * User info API. This API returns information about the user
     *
     */
    public function getUserinfo($userinfo_endpoint=null) {
        $user_info = null;
        $userinfo_endpoint = $userinfo_endpoint ?? $this->metadata->userinfo_endpoint;
        $api_req = new ApiRequest($userinfo_endpoint);
        $api_req->setHeader('Authorization: Bearer ' . $this->access_token);
        if ($api_req->call()) {
            $response = $api_req->json();
            if (isset($response->uuid)) {
                $user_info = $response;
            }
        }
        return $user_info;

    }

    /**
     * Revoke API. This API revokes the access token
     *
     */
    public function revoke($access_token) {
        $auth_bearer = base64_encode($this->client_id .':'.$this->client_secret);
        $revocation_endpoint = $introspection_endpoint ?? $this->metadata->revocation_endpoint;
        $api_req = new ApiRequest($revocation_endpoint);
        $api_req->setPostArg('token', $access_token);
        $api_req->setPostArg('token_type_hint', 'access_token');
        $api_req->setPostArg('client_id', $this->client_id);
        $api_req->setPostArg('client_secret', $this->client_secret);
        if ($api_req->call()) {
            $response = $api_req->json();
        }
    }

    /**
     * Log user out of Okta
     *
     * @param string access_token: Access token form the token endpoint
     * @param string id_token: Id token form the token endpoint
     *
     */
    public function logoutUser($access_token, $id_token) {
        header('Location: ' . $this->metadata->end_session_endpoint .
            '?' . http_build_query([
                'id_token_hint' => $id_token,
                'post_logout_redirect_uri' => $this->logout_redirect_url,
            ])
        );
    }

}
