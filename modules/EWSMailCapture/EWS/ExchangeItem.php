<?php
require 'vendor/autoload.php';

use jamesiarmes\PhpEws\Request\GetAttachmentType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfRequestAttachmentIdsType;
use jamesiarmes\PhpEws\Type\RequestAttachmentIdType;
use jamesiarmes\PhpEws\Enumeration\ImportanceChoicesType;

/**
 * ExchangeItem class: an Exchange item (an email message) wrapper
 * @author Quentin RIBAC <quentin.ribac@xelians.fr>
 * @since October 2020
 */
class ExchangeItem {
	private $itemId;
	private $senderName;
	private $senderEmailAddress;
	private $subject;
	private $isoDate;
	private $body;
	private $attachments;
	private $urgent;
	private $toAddress;
	private $ccAddress;

	public function __construct($message, $client) {
		$this->itemId = $message->ItemId->Id;
		$this->senderName = $message->From->Mailbox->Name;
		$this->senderEmailAddress = $message->From->Mailbox->EmailAddress;
		if (empty($message->ToRecipients)) {
			$this->toAddress = '';
		} else {
			$this->toAddress = implode('; ', array_map(function ($emailAddress) {
				return $emailAddress->EmailAddress;
			}, $message->ToRecipients->Mailbox));
		}
		if (empty($message->CcRecipients)) {
			$this->ccAddress = '';
		} else {
			$this->ccAddress = implode('; ', array_map(function ($emailAddress) {
				return $emailAddress->EmailAddress;
			}, $message->CcRecipients->Mailbox));
		}
		$this->subject = $message->Subject;
		$this->isoDate = $message->DateTimeSent;
		$this->body = $message->Body->_;
		$this->urgent = $message->Importance === ImportanceChoicesType::HIGH;
		$this->urgent = $this->urgent || 1 === preg_match('/urgent/i', $this->subject);
		$this->importance = $message->Importance;
		if (!empty($message->Attachments)) {
			$this->populateAttachments($message->Attachments, $client);
		} else {
			$this->attachments = [];
		}
	}

	public function get($key) {
		switch ($key) {
			case 'date':
				return $this->getISODate();
				break;
			case 'subject':
				return $this->getSubject();
				break;
			case 'fromaddress':
				return $this->getSenderEmailAddress();
				break;
			case 'from[0]/personal':
				return $this->getSenderName();
				break;
			case 'toaddress':
				return $this->getToAddress();
				break;
			case 'xpriority':
				return $this->getImportance();
				break;
			case 'message_id':
				return $this->getItemId();
				break;
			case 'ccaddress':
				return $this->getCcAddress();
				break;
			default:
				return '';
				break;
		}
	}

	public function getItemId() {
		return $this->itemId;
	}

	public function setItemId($itemId) {
		$this->itemId = $itemId;
	}

	public function getSenderName() {
		return $this->senderName;
	}

	public function getSenderEmailAddress() {
		return $this->senderEmailAddress;
	}

	public function getToAddress() {
		return $this->toAddress;
	}

	public function getCcAddress() {
		return $this->ccAddress;
	}

	public function getSubject($attI = null) {
		if (is_int($attI) && $attI >= 0 && $attI < count($this->attachments)) {
			return $this->subject . ' (' . ($attI + 1) . '/' . count($this->attachments) . ') : ' . $this->attachments[$attI]['name'];
		}
		return $this->subject;
	}

	public function getISODate() {
		return $this->isoDate;
	}

	public function getBody() {
		return $this->body;
	}

	public function getImportance() {
		return $this->importance;
	}

	public function isUrgent() {
		return $this->urgent;
	}

	public function getAttachments() {
		return $this->attachments;
	}

	public function getAttachmentsCount() {
		return count($this->attachments);
	}

	/**
	 * this function fetches attachments ids, names and contents
	 */
	private function populateAttachments($rawAttachments, $client) {
		$this->attachments = [];
		if (empty($rawAttachments)) {
			return;
		}
		if (!empty($rawAttachments->FileAttachment)) {
			foreach ($rawAttachments->FileAttachment as $fa) {
				$request = new GetAttachmentType();
				$request->AttachmentIds = new NonEmptyArrayOfRequestAttachmentIdsType();
				$attachmentId = new RequestAttachmentIdType();
				$attachmentId->Id = $fa->AttachmentId->Id;
				$request->AttachmentIds->AttachmentId[] = $attachmentId;

				$response = $client->GetAttachment($request);
				$attRes = $response->ResponseMessages->GetAttachmentResponseMessage[0]->Attachments->FileAttachment[0];

				$this->attachments[] = [
					'id'       => $attRes->AttachmentId->Id,
					'filename' => $attRes->Name,
					'content'  => $attRes->Content
				];
			}
		}
		/* ItemAttachment’s don’t seem useful
		if (!empty($rawAttachments->ItemAttachment)) {
			foreach ($rawAttachments->ItemAttachment as $ia) {
				var_dump($ia);
			}
		}
		//*/
	}
}
