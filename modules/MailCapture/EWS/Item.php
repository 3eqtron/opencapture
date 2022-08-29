<?php
require '../../../vendor/autoload.php';

use \jamesiarmes\PhpEws\Request\GetAttachmentType;
use \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfRequestAttachmentIdsType;
use \jamesiarmes\PhpEws\Type\RequestAttachmentIdType;
use \jamesiarmes\PhpEws\Enumeration\ImportanceChoicesType;

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

	public function __construct($message, $client) {
		$this->itemId = $message->ItemId->Id;
		$this->senderName = $message->From->Mailbox->Name;
		$this->senderEmailAddress = $message->From->Mailbox->EmailAddress;
		$this->subject = $message->Subject;
		$this->isoDate = $message->DateTimeSent;
		$this->body = $message->Body->_;
		$this->urgent = $message->Importance === ImportanceChoicesType::HIGH;
		$this->urgent = $this->urgent || 1 === preg_match('/urgent/i', $this->subject);
		if (!empty($message->Attachments)) {
			$this->populateAttachments($message->Attachments, $client);
		} else {
			$this->attachments = [];
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

	public function getSenderAsMaarchContact() {
		return (object) [
			'lastname' => $this->senderName,
			'email' => $this->senderEmailAddress,
		];
	}

	public function getSubject($attI = null) {
		if (is_int($attI) && $attI >= 0 && $attI < count($this->attachments)) {
			return $this->subject.' ('.($attI+1).'/'.count($this->attachments).') : '.$this->attachments[$attI]['name'];
		}
		return $this->subject;
	}

	public function getISODate() {
		return $this->isoDate;
	}

	public function getBody() {
		return $this->body;
	}

	public function getAttachments() {
		return $this->attachments;
	}

	public function isUrgent() {
		return $this->urgent;
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
				$attachment = ['name' => $fa->Name];

				$request = new GetAttachmentType();
				$request->AttachmentIds = new NonEmptyArrayOfRequestAttachmentIdsType();
				$attachmentId = new RequestAttachmentIdType();
				$attachmentId->Id = $fa->AttachmentId->Id;
				$request->AttachmentIds->AttachmentId[] = $attachmentId;

				$response = $client->GetAttachment($request);
				$attRes = $response->ResponseMessages->GetAttachmentResponseMessage[0]->Attachments->FileAttachment[0];

				$this->attachments[] = [
					'id' => $attRes->AttachmentId->Id,
					'name' => $attRes->Name,
					'content' => $attRes->Content,
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
