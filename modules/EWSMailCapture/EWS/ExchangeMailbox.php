<?php
require 'vendor/autoload.php';

use jamesiarmes\PhpEws\Client;
use jamesiarmes\PhpEws\Request\FindFolderType;
use jamesiarmes\PhpEws\Request\FindItemType;
use jamesiarmes\PhpEws\Request\GetItemType;
use jamesiarmes\PhpEws\Request\MoveItemType;
use jamesiarmes\PhpEws\Request\DeleteItemType;
use jamesiarmes\PhpEws\Enumeration\FolderQueryTraversalType;
use jamesiarmes\PhpEws\Enumeration\ItemQueryTraversalType;
use jamesiarmes\PhpEws\Enumeration\BodyTypeResponseType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseFolderIdsType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseItemIdsType;
use jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use jamesiarmes\PhpEws\Type\TargetFolderIdType;
use jamesiarmes\PhpEws\Type\ItemIdType;
use jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use jamesiarmes\PhpEws\Type\FolderResponseShapeType;
use jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use jamesiarmes\PhpEws\Enumeration\DisposalType;
use jamesiarmes\PhpEws\Type\ConnectingSIDType;
use jamesiarmes\PhpEws\Type\ExchangeImpersonationType;

/**
 * ExchangeMailbox class: an Exchage mailbox wrapper
 * @author Quentin RIBAC <quentin.ribac@xelians.fr>
 * @since October 2020
 */
class ExchangeMailbox {
	private $client;
	private $folders;
	private $logFile;

	public const BASIC_AUTH = "Basic";
	public const O_AUTH_2 = "OAuth2";
	private const BASE_TOKEN_URL = 'https://login.microsoftonline.com/';

	public function __construct(array $args)
	{
		$batch = $_SESSION['capture']->Batch;
        $this->logFile = $batch->directory . DIRECTORY_SEPARATOR . 'EWSMailCapture.log';

		switch ($args['authMethod']) {
			case ExchangeMailbox::BASIC_AUTH:
				$this->writeLog(sprintf("Authenticating using '%s' method ", ExchangeMailbox::BASIC_AUTH));
				try {
					$this->initBasicAuth($args['mailbox'], $args['username'], $args['password'], $args['exchangeversion']);
					$this->discoverFolders();
				} catch (\Exception $e) {
					$log = "Exception occurred while trying Basic auth, you may want to setup OAuth2:\n\n" . (string)$e;
					$this->writeLog($log);
					$_SESSION['capture']->sendError($log);
				}
				break;
			case ExchangeMailbox::O_AUTH_2:
				$this->writeLog(sprintf("Authenticating using '%s' method ", ExchangeMailbox::O_AUTH_2));
				try {
					$this->initOauth2($args['mailbox'], $args['username'], $args['exchangeversion'], $args['tenantID'], $args['clientID'], $args['clientSecret']);
					$this->discoverFolders();
				} catch (\Exception $e) {
					$log = "Exception occurred while trying OAuth2:\n\n" . (string)$e;
					$this->writeLog($log);
					$_SESSION['capture']->sendError($log);
				}
				break;
			default:
				$log = sprintf("\n\nUnknown auth method '%s'.\nAvailable auth methods are %s or %s\n\n", $args['authMethod'], ExchangeMailbox::BASIC_AUTH, ExchangeMailbox::O_AUTH_2);
				$this->writeLog($log);
				$_SESSION['capture']->sendError($log);
		}
	}

	private function initBasicAuth($host, $address, $password, $version)
	{
		$this->client = new Client($host, $address, $password, $version);
		$this->client->setCurlOptions([CURLOPT_SSL_VERIFYPEER => false]);
	}

	private function initOauth2($host, $address, $version, $tenantID, $clientID, $clientSecret)
	{
		$curl = curl_init(ExchangeMailbox::BASE_TOKEN_URL . $tenantID . '/oauth2/v2.0/token');
		curl_setopt_array($curl, [
			CURLOPT_POST           => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true,
			CURLOPT_POSTFIELDS     => [
				'grant_type'    => 'client_credentials',
				'client_id'     => $clientID,
				'client_secret' => $clientSecret,
				'scope'         => 'https://' . $host . '/.default'
			]
		]);
		$response = curl_exec($curl);
		curl_close($curl);

		$response = explode("\r\n\r\n", $response);
		$responseHeaders = $response[0] ?? '';
		$responseBody    = $response[1] ?? '';
		$responseBody    = json_decode($responseBody, true);
		if (empty($responseBody['access_token'])) {
			$this->writeLog("Error while fetching access token, return transfer written to " . $this->logFile);
			$error = "\n\nHeaders: " . $responseHeaders;
			if (!empty($responseBody['error'])) {
				$error .= "\n\nError: " . $responseBody['error'] . "\n\nError description: " . ($responseBody['error_description'] ?? '');
			}
			$this->writeLog($error);
			$_SESSION['capture']->sendError("Error while fetching access token, return transfer written to " . $this->logFile . "\n");
		}

		$this->client = new Client($host, $address, '-', $version);
		$this->client->setCurlOptions([
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $responseBody['access_token']]
		]);
		$exim = new ExchangeImpersonationType();
		$csid = new ConnectingSIDType();
		$csid->PrimarySmtpAddress = $address;
		$exim->ConnectingSID = $csid;
		$this->client->setImpersonation($exim);
	}

	private function writeLog($str)
    {
        $str = '[' . date('c') . '] ExchangeMailbox: ' . $str . PHP_EOL;
        echo $str;
        file_put_contents($this->logFile, $str, FILE_APPEND);
    }

	private function discoverFolders()
	{
		$request = new FindFolderType();
		$request->Traversal = FolderQueryTraversalType::DEEP;
		$request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();
		$parent = new DistinguishedFolderIdType();
		$parent->Id = DistinguishedFolderIdNameType::ROOT;
		$request->ParentFolderIds->DistinguishedFolderId[] = $parent;
		$request->FolderShape = new FolderResponseShapeType();
		$request->FolderShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;

		$response = $this->client->FindFolder($request);

		$folders = $response->ResponseMessages->FindFolderResponseMessage[0]->RootFolder->Folders->Folder;

		$foldersPaths = [];
		foreach ($folders as $folder) {
			$displayName = !empty($folder->FolderClass) ? $folder->DisplayName : '';
			$displayName = str_replace('/', '\/', $displayName);

			foreach ($foldersPaths as $folderPath) {
				if ($folder->ParentFolderId->Id == $folderPath['id']->Id) {
					$foldersPaths[] = [
						'id'       => $folder->FolderId,
						'parentId' => $folder->ParentFolderId,
						'path'     => $folderPath['path'] . '/' . $displayName
					];
					continue 2;
				} elseif ($folder->FolderId->Id == $folderPath['parentId']->Id) {
					$folderPath['path'] = $displayName . '/' . $folderPath['path'];
					continue 2;
				}
			}
			$foldersPaths[] = [
				'id'       => $folder->FolderId,
				'parentId' => $folder->ParentFolderId,
				'path'     => $displayName
			];
		}

		$foldersPaths = array_map(function ($folderPath) {
			$folderPath['path'] = trim($folderPath['path'], '/');
			return $folderPath;
		}, $foldersPaths);
		$foldersPaths = array_filter($foldersPaths, function ($folderPath) {
			return !empty($folderPath['path']);
		});

		foreach ($foldersPaths as $folderPath) {
			$this->folders[$folderPath['path']] = $folderPath['id'];
		}
	}

	private function getFolderIdByName($folderPathNames)
	{
		$folderPathNames = trim($folderPathNames, '/');
		$foldersName = explode('/', $folderPathNames);
		$tmpPath = $folderPathNames;

		if (count($foldersName) > 0 ) {
			$tmpPath = $foldersName[0];
			for ($index = 0; $index < count($foldersName); $index++) {

				$value = $foldersName[$index];
				if ($index == 0 && ($value == 'inbox' || $value == 'INBOX')) {
					continue;
				}
				if (array_key_exists($value, $this->folders)) {
					$tmpPath .= "/$value";
				}
			}
		}
		return ($folderPathNames == $tmpPath ? true : false);
	}

	public function getItemsByFolderName($folderName)
	{
		$folderId = $this->getFolderIdByName($folderName);
		$useInbox = false;
		if (mb_strtolower($folderName) === 'inbox') {
			$useInbox = true;
		}
		if (!$useInbox && empty($folderId)) {
			return ['error' => "Mailbox folder '" . $folderName . "' does not exist."];
		}
		$request = new FindItemType();
		$request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();
		if (!$useInbox) {
			$request->ParentFolderIds->FolderId[] = $folderId;
		} else {
			$inboxId = new DistinguishedFolderIdType();
			$inboxId->Id = DistinguishedFolderIdNameType::INBOX;
			$request->ParentFolderIds->DistinguishedFolderId[] = $inboxId;
		}
		$request->Traversal = ItemQueryTraversalType::SHALLOW;
		$request->ItemShape = new ItemResponseShapeType();
		$request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;

		$response = $this->client->FindItem($request);

		$rawItems = $response->ResponseMessages->FindItemResponseMessage[0]->RootFolder->Items->Message;
		if (empty($rawItems)) {
			return ['error' => "Could not get items from mailbox folder '" . $folderName . "'."];
		}
		$items = [];
		foreach ($rawItems as $rawItem) {
			$request = new GetItemType();
			$request->ItemShape = new ItemResponseShapeType();
			$request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;
			$request->ItemShape->BodyType = BodyTypeResponseType::BEST;
			$request->ItemIds = new NonEmptyArrayOfBaseItemIdsType();
			$itemId = new ItemIdType();
			$itemId->Id = $rawItem->ItemId->Id;
			$request->ItemIds->ItemId[] = $itemId;
			
			$response = $this->client->GetItem($request);

			foreach ($response->ResponseMessages->GetItemResponseMessage[0]->Items->Message as $gottenItem) {
				$items[] = new ExchangeItem($gottenItem, $this->client);
			}
		}
		return $items;
	}

	public function moveItemToNamedFolder(&$item, $folderName)
	{
		$folderId = $this->getFolderIdByName($folderName);
		$useInbox = false;
		if (mb_strtolower($folderName) === 'inbox') {
			$useInbox = true;
		}
		if (!$useInbox && empty($folderId)) {
			return false;
		}
		$request = new MoveItemType();
		$request->ToFolderId = new TargetFolderIdType();
		if (!$useInbox) {
			$request->ToFolderId->FolderId = $folderId;
		} else {
			$inboxId = new DistinguishedFolderIdType();
			$inboxId->Id = DistinguishedFolderIdNameType::INBOX;
			$request->ToFolderId->DistinguishedFolderId = $inboxId;
		}
		$request->ItemIds = new NonEmptyArrayOfBaseItemIdsType();
		$itemId = new ItemIdType();
		$itemId->Id = $item->getItemId();
		$request->ItemIds->ItemId[] = $itemId;
		$request->ReturnNewItemIds = true;

		$response = $this->client->MoveItem($request);

		$item->setItemId($response->ResponseMessages->MoveItemResponseMessage[0]->Items->Message[0]->ItemId->Id);

		return true;
	}

	public function deleteItem($item)
	{
		$request = new DeleteItemType();
		$request->DeleteType = DisposalType::MOVE_TO_DELETED_ITEMS;
		$request->ItemIds = new NonEmptyArrayOfBaseItemIdsType();
		$itemId = new ItemIdType();
		$itemId->Id = $item->getItemId();
		$request->ItemIds->ItemId[] = $itemId;

		$this->client->DeleteItem($request);

		return true;
	}
}
