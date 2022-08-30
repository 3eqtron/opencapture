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
            $document->setMetadata('subject', $ewsItem->getSubject());
            $document->setMetadata('doc_date', $ewsItem->getISODate());

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