<?php

// Microsoft Exchange mailbox client classes
require 'modules/EWSMailCapture/EWS/ExchangeMailbox.php';
require 'modules/EWSMailCapture/EWS/ExchangeItem.php';

class EWSMailCapture
{
    private $logFile;

    public function CaptureMails($account, $action, $configFile, $folder)
    {
        $batch = $_SESSION['capture']->Batch;
        $this->logFile = $batch->directory . DIRECTORY_SEPARATOR . 'EWSMailCapture.log';

        $this->writeLog('loading config file: ' . $configFile);
        $xmlConfig = simplexml_load_file(__DIR__ . DIRECTORY_SEPARATOR . $configFile);
        if (empty($xmlConfig)) {
            $_SESSION['capture']->sendError('Configuration file ' . $configFile . ' not found');
        }

        // load config
        $accountConfig = $xmlConfig->xpath('/EWSMailCapture/accounts/account[@name="'.$account.'"]')[0] ?? null;
        if (!$accountConfig) {
            $_SESSION['capture']->sendError("E-mail account $account is not defined in configuration!");
        }

        $mailbox = (string) ($accountConfig->xpath('//mailbox')[0] ?? '');
        $captureFolder = preg_replace('/\{.+\}(.+)/', '\1', $mailbox);
        $mailbox = preg_replace('/\{(.+)\}.+/', '\1', $mailbox);

        $username = (string) ($accountConfig->xpath('//username')[0] ?? '');
        $password = (string) ($accountConfig->xpath('password')[0] ?? '');
        $exchangeVersion = (string) ($accountConfig->xpath('exchangeversion')[0] ?? '');

        $messageOutputs = $xmlConfig->xpath('/EWSMailCapture/messageoutputs/messageoutput');
        $messageMetadata = [];
        foreach ($messageOutputs as $messageOutput) {
            $name = (string) ($messageOutput->attributes()['name'] ?? null);
            if (empty($name)) {
                continue;
            }

            $value = (string) $messageOutput;
            if (!empty($value)) {
                $messageMetadata[$name] = ['type' => 'const', 'value' => $value];
                continue;
            }

            $info = $messageOutput->attributes()['info'];
            if (!empty($info)) {
                $messageMetadata[$name] = ['type' => 'var', 'value' => $info];
            }
        }

        $attachmentOutputs = $xmlConfig->xpath('/EWSMailCapture/attachmentoutputs/attachmentoutput');
        $attachmentMetadata = [];
        foreach ($attachmentOutputs as $attachmentOutput) {
            $name = (string) ($attachmentOutput->attributes()['name'] ?? null);
            if (empty($name)) {
                continue;
            }

            $value = (string) $attachmentOutput;
            if (!empty($value)) {
                $attachmentMetadata[$name] = ['type' => 'const', 'value' => $value];
                continue;
            }

            $info = $attachmentOutput->attributes()['info'];
            if (!empty($info)) {
                $attachmentMetadata[$name] = ['type' => 'var', 'value' => $info];
            }
        }

        if (empty($mailbox) || empty($captureFolder) || empty($exchangeVersion) || empty($username) || empty($password)) {
            $_SESSION['capture']->sendError('MS Exchange mailbox configuration is invalid!');
        }

        // load Exchange mailbox
        $ewsMailbox = new ExchangeMailbox($mailbox, $username, $password, $exchangeVersion);

        // load emails in mailbox
        $ewsItems = $ewsMailbox->getItemsByFolderName($captureFolder);
        $itemCount = count($ewsItems);
        $this->writeLog($itemCount . ' messages in mailbox');

        foreach ($ewsItems as $ewsItemI => $ewsItem) {
            // capture mail for MaarchWSClient input
            $this->writeLog('processing email ' . ($ewsItemI + 1) . '/' . $itemCount . ': ' . $ewsItem->getSubject());
            $filePath = $batch->directory . DIRECTORY_SEPARATOR . 'BODY_' . $ewsItemI . '.html';
            if (file_put_contents($filePath, $ewsItem->getBody()) === false) {
                $_SESSION['capture']->sendError('failed to save email body as file: ' . $ewsItem->getSubject());
            }
            $document = $batch->addDocument($filePath);

            foreach ($messageMetadata as $name => $metadata) {
                if ($metadata['type'] === 'const') {
                    $document->setMetadata($name, $metadata['value']);
                } elseif ($metadata['type'] === 'var') {
                    switch ($metadata['value']) {
                        case 'date':
                            $value = $ewsItem->getISODate();
                            break;
                        case 'subject':
                            $value = $ewsItem->getSubject();
                            break;
                        case 'fromaddress':
                            $value = $ewsItem->getSenderEmailAddress();
                            break;
                        case 'from[0]/personal':
                            $value = $ewsItem->getSenderName();
                            break;
                        case 'toaddress':
                            $value = $ewsItem->getToAddress();
                            break;
                        case 'xpriority':
                            $value = $ewsItem->getImportance();
                            break;
                        case 'message_id':
                            $value = $ewsItem->getItemId();
                            break;
                        case 'ccaddress':
                            $value = $ewsItem->getCcAddress();
                            break;
                        default:
                            $value = '';
                            break;
                    }
                    $document->setMetadata($name, $value);
                }
            }

            $attCount = $ewsItem->getAttachmentsCount();
            foreach ($ewsItem->getAttachments() as $ewsAttI => $ewsAttachment) {
                $this->writeLog('  processing attachment ' . ($ewsAttI + 1) . '/' . $attCount . ': ' . $ewsAttachment['name']);
                $filePath = $batch->directory . DIRECTORY_SEPARATOR . 'BODY_' . $ewsItemI . '_ATT_' . $ewsAttI . '.' . pathinfo($ewsAttachment['name'], PATHINFO_EXTENSION);
                if (file_put_contents($filePath, $ewsAttachment['content']) === false) {
                    $_SESSION['capture']->sendError('failed to save email attachment as file: ' . $ewsItem->getSubject($ewsAttI));
                }
                $attachment = $document->addAttachment($filePath);
                $attachment->setMetadata('filename', pathinfo($ewsAttachment['name'], PATHINFO_BASENAME));
                $attachment->setMetadata('extension', pathinfo($ewsAttachment['name'], PATHINFO_EXTENSION));

                foreach ($attachmentMetadata as $name => $metadata) {
                    if ($metadata['type'] === 'const') {
                        $attachment->setMetadata($name, $metadata['value']);
                    }
                }
            }

            // move or delete mail
            if ($action === 'move') {
                $this->writeLog('  moving email to purge folder: ' . $folder);
                $ewsMailbox->moveItemToNamedFolder($ewsItem, $folder);
            } elseif ($action === 'delete') {
                $this->writeLog('  moving email to trash');
                $ewsMailbox->deleteItem($ewsItem);
            }
        }
    }

    private function writeLog($str) {
        $str = '[' . date('c') . '] EWSMailCapture: ' . $str . PHP_EOL;
        echo $str;
        file_put_contents($this->logFile, $str, FILE_APPEND);
    }
}