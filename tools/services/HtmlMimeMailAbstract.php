<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/
require_once 'modules/sendmail/interfaces/Mailer.php';

/**
 * Manage mail sending via phpMailer
 */
class Sendmail_HtmlMimeMailAbstract_Service implements Sendmail_Mailer_Interface {
	
	public $pathToLib = 'tools/mails/htmlMimeMail.php';
	public $MailObj;
	public $to = [];
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
			$this->MailObj = new htmlMimeMail();
		}
	}

	/**
     * Récupération de la liste des méthodes disponibles via api
     * 
     * @return string[] La liste des méthodes
     */
    public static function getApiMethod() {
        $aApiMethod = parent::getApiMethod();
        $aApiMethod['setSMTPParams'] = 'setSMTPParams';
        return $aApiMethod;
    }

	public function setSMTPParams(array $aArgs = []) {

		$this->MailObj->setSMTPParams(
			$host = $aArgs['smtp_host'], 
			$port = $aArgs['smtp_port'],
			$helo = $aArgs['domain'],
			$auth = $aArgs['smtp_auth'],
			$user = $aArgs['smtp_user'],
			$pass = $aArgs['smtp_password']
		);

	}

	public function setSSLOptions(array $aArgs = []) {

		//not available in htmlMimeMail
		
	}

	public function setMailerType(array $aArgs = []) {

		$this->mailerType = $aArgs['mailerType'];

	}
	
	public function setFrom(array $aArgs = []) {

		$this->MailObj->setFrom($aArgs['from']);

	}

	public function setReplyTo(array $aArgs = []) {

		$this->MailObj->setReplyTo($aArgs['replyTo']);

	}
	
	public function setDispositionNotificationTo(array $aArgs = []) {

		$this->MailObj->setDispositionNotificationTo($aArgs['dispositionNotificationTo']);

	}

	public function setReturnReceipt(array $aArgs = []) {
		
		$this->MailObj->setHeader('Return-Receipt-To', '<'.$aArgs['returnReceipt'].'>');

	}
	
	public function setReturnPath(array $aArgs = []) {
		
		$this->MailObj->setReturnPath($aArgs['returnPath']);

	}

	public function setTo(array $aArgs = []) {
		
		if (empty($aArgs['to'])) {
			$this->to = $aArgs;
		} else {
			foreach ($aArgs['to'] as $key => $value) {
				array_push($this->to, $value['mail']);
			}
		}

	}

	public function setCc(array $aArgs = []) {

		$cc = '';
		if (empty($aArgs['cc_simple'])) {
			foreach ($aArgs['cc'] as $key => $value) {
				$cc .= $value['mail'] . ',';
			}
		} else {
			$cc .= $aArgs['cc_simple'];
		}
		
		$this->MailObj->setCc($cc);

	}

	public function setBcc(array $aArgs = []) {

		$bcc = '';
		if (empty($aArgs['bcc_simple'])) {
			foreach ($aArgs['bcc'] as $key => $value) {
				$bcc .= $value['mail'] . ',';
			}
		} else {
			$bcc .= $aArgs['bcc_simple'];
		}
		
		$this->MailObj->setBcc($bcc);

	}
	
	
	public function setSubject(array $aArgs = []) {

		$this->MailObj->setSubject($aArgs['subject']);

	}

	public function setBody(array $aArgs = []) {
		
		if ($aArgs['body'][0]['isHTML']) {
			$this->MailObj->setHtml($aArgs['body'][0]['htmlContent']);
		} else {
			$this->MailObj->setText($aArgs['body'][0]['textContent']);
		}

	}
	
	public function setCharset(array $aArgs = []) {

		if (!empty($aArgs['charset'][0]['htmlCharset'])) {
			$this->MailObj->setHtmlCharset($aArgs['charset'][0]['htmlCharset']);
		}

		if (!empty($aArgs['charset'][0]['textCharset'])) {
			$this->MailObj->setTextCharset($aArgs['charset'][0]['textCharset']);
		}

		if (!empty($aArgs['charset'][0]['headCharset'])) {
			$this->MailObj->setHeadCharset($aArgs['charset'][0]['headCharset']);
		}

	}

	public function setEncoding(array $aArgs = []) {

		if (!empty($aArgs['encoding'][0]['htmlEncoding'])) {
			$this->MailObj->setHtmlEncoding($aArgs['encoding'][0]['htmlEncoding']);
		}

		if (!empty($aArgs['encoding'][0]['textEncoding'])) {
			$this->MailObj->setTextEncoding($aArgs['encoding'][0]['textEncoding']);
		}
		
	}

	public function setPriority(array $aArgs = []) {
		$this->MailObj->setHeader('X-Priority', $aArgs['priority']);

	}

	public function setPriorityTtl(array $aArgs = []){
		// $this->MailObj->setHeader("X-Priority-TTL", $aArgs['priority']);
	}

	public function setPriorityLabel(array $aArgs = []){
		// $this->MailObj->setHeader("X-Priority-Label", $aArgs['priority']);
	}

	public function addAttachment(array $aArgs = []) {
		
		if (file_exists($aArgs['attachment'][0]['path'])) {
			$fileContent = $this->MailObj->getFile($aArgs['attachment'][0]['path']);
			$this->MailObj->addAttachment(
				$fileContent,
				$aArgs['attachment'][0]['fileName'],
				$aArgs['attachment'][0]['mimeType']
			);
		}
		
	}

	public function sign(array $aArgs = []) {

		//not available in htmlMimeMail

	}

	public function send(array $aArgs = []) {
		
		$return = false;
		if (!$this->MailObj->send($this->to, $this->mailerType)) {
		 	$this->errorInfo = "Errors when sending message through SMTP :" . $this->MailObj->errors[0].': '.$this->MailObj->errors[1];
		} else {
			$return = true;
		}

		return $return;

	}

	public function getErrors() {

		return $this->errorInfo;

	}
}
