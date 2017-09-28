<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

interface Sendmail_MailerAbstract_Interface {

	public function setSMTPParams(array $aArgs = []);
	public function setSSLOptions(array $aArgs = []);
	public function setMailerType(array $aArgs = []);
	public function setFrom(array $aArgs = []);
	public function setReplyTo(array $aArgs = []);
	public function setDispositionNotificationTo(array $aArgs = []);
	public function setReturnPath(array $aArgs = []);
	public function setTo(array $aArgs = []);
	public function setCc(array $aArgs = []);
	public function setBcc(array $aArgs = []);
	public function setSubject(array $aArgs = []);
	public function setBody(array $aArgs = []);
	public function setCharset(array $aArgs = []);
	public function setEncoding(array $aArgs = []);
	public function addAttachment(array $aArgs = []);
	public function sign(array $aArgs = []);
	public function send(array $aArgs = []);
	public function getErrors();

}

