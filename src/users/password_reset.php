<?php

class PasswordReset
{
    private $rTemplate;
    private $dc;
    private $rEmail;
    private $user_id;
    private $name;


    public function __construct(Templater &$rTempl)
    {
        $this->rTemplate = $rTempl;
        $this->dc = new DataCalls();
        $pTarget = UserData::create('t')->getString();
        $this->rEmail =  UserData::create('email', 'POST')->getString();
        $rn = UserData::create('rn', 'POST')->getString();

        $pass_token = UserData::create('rp')->getString();
        $this->user_id = UserData::create('rpuid')->getString();
        if ($pTarget== 'reset_email' && !empty($this->rEmail) && !empty($rn)  && $rn == $_SESSION['auth']['sn']) {
            $this->startResetProcess();
        } elseif ($pTarget=="reset" && !empty($pass_token) && !empty($this->user_id)) {
            $this->prepareForReset($pass_token);
        } elseif ($pTarget== 'reset_pass'  && !empty($rn)  && $rn == $_SESSION['auth']['sn'] && !empty($this->user_id)) {
            $this->resetPassword();
        } else {
            $this->renderResetTemplate();
        }
    }
    /**
    *Render reset template
    */
    public function renderResetTemplate()
    {
        $this->rTemplate->setTemplate('reset_email.html');
        $this->rTemplate->setVariables("page_title", "Reset password");
        
        $this->rTemplate->setVariables('pn', AccessHandler::generateNonce());
        $this->rTemplate->generate();
    }


    /**
    *Render reset password template
    */
    public function renderResetPasswordTemplate()
    {
        $this->rTemplate->setTemplate('reset_password.html');
        $this->rTemplate->setVariables("page_title", "Reset password");
        $this->rTemplate->setVariables('rpuid', $this->user_id);
        $this->rTemplate->setVariables('error_messages', Alerts::getErrorMessages());
        $this->rTemplate->setVariables('pn', AccessHandler::generateNonce());
        $this->rTemplate->generate();
    }

    /**
    * Render Confirmation template
    * @param string $message : message for the confirmation page
    */
    public function renderConfirmationTemplate($message)
    {
        $this->rTemplate->setTemplate('confirmation.html');
        $this->rTemplate->setVariables('page_title', 'Confirmation');
        $this->rTemplate->setVariables('message', $message);
        $this->rTemplate->generate();
    }

    /**
    * Starts the reset password process
    */
    public function startResetProcess()
    {
        $uRow = $this->dc->getUserByEmail('users', $this->rEmail);
        if (!empty($uRow)) {
            $this->user_id = $uRow[0]['user_id'];
            $this->name = $uRow[0]['fullname'];
            $token = AccessHandler::generateOneTimeToken();
            $sData = array("user_id"=>$this->user_id, 'onetime_token'=>$token);
            $this->dc-> updateUsingPrimaryKey("users", "user_id", $sData);
            $this->dc->insertExpDate($this->user_id);
            $mail_sent = $this->sendResetEmail($token);
        }
        $message = "password_reset";
        $this->renderConfirmationTemplate($message);
    }


    /**
    * Prepares for the password reset
    * @param string $token : Token recied from the url for password reset
    */
    public function prepareForReset($token)
    {
        $aData = array('user_id'=> $this->user_id);
        $uRow = $this->dc->getRowsById('users', $aData);
        $current_date = date('Y-m-d H:i:s');
        if (empty($uRow) || $current_date > $uRow[0]['onetime_token_expires'] || $token != $uRow[0]['onetime_token']) {
            $message = "error";
            $this->renderConfirmationTemplate($message);
        } else {
            $this->renderResetPasswordTemplate();
        }
    }

    /**
    * Resets password for the user
    */
    public function resetPassword()
    {
        $pass = $_POST['password'];
        $hint = $_POST['hint']? $_POST['hint'] : null;
        $pw_hash = AccessHandler::createPasswordHash($pass);
        $sData = array("user_id"=>$this->user_id, 'pw_hash'=>$pw_hash);
        if (!empty($hint)) {
            $sData['hint'] = $hint;
        }
        $uRow = $this->dc-> updateUsingPrimaryKey("users", "user_id", $sData);
    
        if (!empty($uRow)) {
            $uRow = $this->dc->removeOnetimeToken($this->user_id);
            $message = "reset_successful";
            $this->renderConfirmationTemplate($message);
        } else {
            $error_messages[] = "Sorry , we are unable to perform this operation ";
            Alerts::setErrorMessages($error_messages);
            $this->renderResetPasswordTemplate();
        }
    }






    /**
    *Generates Email template for password reset
    * @param string token: onetime token for the user
    */
    public function sendResetEmail($token)
    {
        $cf = APP::config();
        $site = $cf->get('application.url');
        $confirm_url = $site. "/?t=reset&rp=".$token."&rpuid=".$this->user_id;
        $this->rTemplate->setTemplate('password_reset_email.html');
        $this->rTemplate->setBlockVariables('confirm_url', $confirm_url);
        $this->rTemplate->setBlockVariables('name', $this->name);
        $this->rTemplate->setBlock('pass_reset_email_template');
        $email_block = $this->rTemplate->generateBlock();
    
        $subject = "MakeAtState confirmation email";
        // To send HTML mail, the Content-type header must be set
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=iso-8859-1';
        // Additional headers
        $headers[] = 'From: MakeAtState <'. $cf->get('application.email').'>';

        return mail($this->rEmail, $subject, $email_block, implode("\r\n", $headers));
    }
}
