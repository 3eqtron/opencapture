<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

require_once 'tools/interfaces/Mailer.php';

/**
 * Manage mail sending via phpMailer
 */
class Sendmail_PhpMailerAbstract_Service implements Sendmail_Mailer_Interface {

	public $pathToLib = 'tools/PHPMailer/PHPMailerAutoload.php';
	public $MailObj;
	public $to;
	public $mailerType;
	public $errorInfo = [];

	public function __construct(array $aArgs = [])
	{
		if (!empty($aArgs['pathToLib'])) {
			$this->pathToLib = $aArgs['pathToLib'];
		} else {
			if (!file_exists($this->pathToLib)) {
				$this->pathToLib = $_SESSION['config']['corepath']
					. $this->pathToLib;
			}
		}

		if (file_exists($this->pathToLib)) {
			require_once $this->pathToLib;
			$this->MailObj = new PHPMailer();
			$this->MailObj->setLanguage(
				'fr', 
				$_SESSION['config']['corepath'] 

					. 'tools/PHPMailer/language/'
			);
			//put 2 if necesserary
			$this->MailObj->SMTPDebug = 0;
		}
	}
	/**
     * Récupération de la liste des méthodes disponibles via api
     * 
     * @return string[] La liste des méthodes
     */
    public static function getApiMethod() {
        $aApiMethod = parent::getApiMethod();
        $aApiMethod['setSMTPParams']                 = 'setSMTPParams';
        return $aApiMethod;
    }
	
	public function setSMTPParams(array $aArgs = []) {

		$this->MailObj->isSMTP();                                      		// Set mailer to use SMTP
		$this->MailObj->Host = $aArgs['smtp_host'];     					// Specify main and backup SMTP servers
		//$this->MailObj->Host = 'smtp.gmail.com;smtp2.example.com';     	// Specify main and backup SMTP servers
		$this->MailObj->SMTPAuth = $aArgs['smtp_auth'];                  	// Enable SMTP authentication
		$this->MailObj->Username = $aArgs['smtp_user'];                 	// SMTP username
		$this->MailObj->Password = $aArgs['smtp_password'];              	// SMTP password
		$this->MailObj->SMTPSecure = $aArgs['smtp_secure'];              	// Enable TLS encryption, `ssl` also accepted
		$this->MailObj->Port = $aArgs['smtp_port'];                      	// TCP port to connect to
		
	}

	public function setSSLOptions(array $aArgs = []) {

		//var_dump($aArgs);
		$this->MailObj->SMTPOptions = array (
		    'ssl' => $aArgs
		);

		//sample connection options
		// $this->MailObj->SMTPOptions = array (
		//     'ssl' => array(
		//         'verify_peer'  => true,
		//         'verify_depth' => 3,
		//         'allow_self_signed' => true,
		//         'peer_name' => 'smtp.example.com',
		//         'cafile' => '/etc/ssl/ca_cert.pem',
		//     )
		// );

		//var_dump($this->MailObj->SMTPOptions);
		
	}

	//not usefull for PHPMailer
	public function setMailerType(array $aArgs = []) {

		$this->mailerType = $aArgs['mailerType'];

	}
	
	public function setFrom(array $aArgs = []) {

		$this->MailObj->setFrom($aArgs['from']);

	}

	public function setReplyTo(array $aArgs = []) {

		$this->MailObj->addReplyTo($aArgs['replyTo']);

	}
	
	public function setDispositionNotificationTo(array $aArgs = []) {

		$this->MailObj->ConfirmReadingTo = $aArgs['dispositionNotificationTo'];

	}

	public function setReturnReceipt(array $aArgs = []) {

		$this->MailObj->AddCustomHeader("Return-Receipt-To: <".$aArgs['returnReceipt'].">");;

	}
	
	public function setReturnPath(array $aArgs = []) {

		$this->MailObj->ReturnPath = $aArgs['returnPath'];

	}

	public function setTo(array $aArgs = []) {

		$this->MailObj->addAddress($aArgs[0]['mail']);

	}

	public function setCc(array $aArgs = []) {

		if (empty($aArgs['cc_simple'])) {
			foreach ($aArgs['cc'] as $key => $value) {
				$this->MailObj->addCC($value['mail'], $value['name']);
			}
		} else {
			$aArgs['cc_simple'] = explode (',', $aArgs['cc_simple']);
			foreach ($aArgs['cc_simple'] as $key => $value) {
				$this->MailObj->addCC($value);
			}
		}

	}
	
	public function setBcc(array $aArgs = []) {
		
		if (empty($aArgs['bcc_simple'])) {
			foreach ($aArgs['bcc'] as $key => $value) {
				$this->MailObj->addBCC($value['mail'], $value['name']);
			}
		} else {
			$aArgs['bcc_simple'] = explode (',', $aArgs['bcc_simple']);
			foreach ($aArgs['bcc_simple'] as $key => $value) {
				$this->MailObj->addBCC($value);
			}
		}

	}
	
	public function setSubject(array $aArgs = []) {
		
		$this->MailObj->Subject = $aArgs['subject'];

	}

	public function setBody(array $aArgs = []) {

		if ($aArgs['body'][0]['isHTML']) {
			$this->MailObj->isHTML = true;
			$this->MailObj->ContentType = 'text/html';
			$this->MailObj->Body = $aArgs['body'][0]['htmlContent'];
		} else {
			$this->MailObj->isHTML = false;
			$this->MailObj->Body = $aArgs['body'][0]['textContent'];
		}

		if (!empty($aArgs['body'][0]['textContent'])) {
			$this->MailObj->AltBody = $aArgs['body'][0]['textContent'];
		}
	}
	
	public function setCharset(array $aArgs = []) {

		if ($this->MailObj->isHTML) {
			$this->MailObj->CharSet = $aArgs['charset'][0]['htmlCharset'];
		} else {
			$this->MailObj->CharSet = $aArgs['charset'][0]['textCharset'];
		}

	}

	public function setEncoding(array $aArgs = []) {

		if ($this->MailObj->isHTML) {
			$this->MailObj->Encoding = $aArgs['encoding'][0]['htmlEncoding'];
		} else {
			$this->MailObj->Encoding = $aArgs['encoding'][0]['textEncoding'];
		}
		
	}

	public function setPriority(array $aArgs = []) {
		$this->MailObj->Priority = $aArgs['priority'];
		// MS Outlook custom header
		// May set to "Urgent" or "Highest" rather than "High"
		// $this->MailObj->AddCustomHeader("X-MSMail-Priority: Urgent");
		// Not sure if Priority will also set the Importance header:
		// $this->MailObj->AddCustomHeader("Importance: Urgent");
	}

	public function setPriorityTtl(array $aArgs = []){
		$this->MailObj->AddCustomHeader("X-Priority-TTL: " . $aArgs['priority']);
	}

	public function setPriorityLabel(array $aArgs = []){
		$this->MailObj->AddCustomHeader("X-Priority-Label: " . $aArgs['priority']);
	}

	public function addAttachment(array $aArgs = []) {

		if (file_exists($aArgs['attachment'][0]['path'])) {
			if (!empty($aArgs['attachment'][0]['fileName'])) {
				$this->MailObj->addAttachment(
					$aArgs['attachment'][0]['path'],
					$aArgs['attachment'][0]['fileName']
				);
			} else {
				$this->MailObj->addAttachment(
					$aArgs['attachment'][0]['path']
				);
			}
		}

	}

	public function sign(array $aArgs = []) {

		//echo PHP_EOL . 'SIGN' . PHP_EOL;

		$this->MailObj->sign(
			$aArgs['certificate_path'], //The location of your certificate file
			$aArgs['private_key_path'], //The location of your private key file
			$aArgs['secret_private_key_password'], //The password you protected your private key with (not the Import Password! may be empty but parameter must not be omitted!) 
			$aArgs['cert_chain_path'] //The location of your chain file
		);

	}

	public function send(array $aArgs = []) {
		
		//var_export($this->MailObj);exit;
		$return = false;
		if (!$this->MailObj->send()) {
		    $this->errorInfo = $this->MailObj->ErrorInfo;
		} else {
			$return = true;
		}

		return $return;
		 
	}

	public function getErrors() {

		return $this->errorInfo;

	}
}
