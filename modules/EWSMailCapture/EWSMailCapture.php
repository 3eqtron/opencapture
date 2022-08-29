<?php

// Microsoft Exchange mailbox client classes
require 'modules/EWSMailCapture/EWS/Mailbox.php';
require 'modules/EWSMailCapture/EWS/Item.php';

class EWSMailCapture
{
    public function CaptureMails($account, $action, $configFile, $folder)
    {
         echo 'LOAD ' . $configFile . ' FOR CAPTURE' . PHP_EOL;
        $xmlConfig = simplexml_load_file(__DIR__ . DIRECTORY_SEPARATOR . $configFile);

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
        echo count($ewsItems) . ' messages in mailbox';

        foreach ($ewsItems as $ewsItem) {
            // capture mail for MaarchWSClient input
            // TODO

            // move or delete mail
            if ($action === 'move') {
                $ewsMailbox->moveItemToNamedFolder($ewsItem, $folder);
            } elseif ($action === 'delete') {
                $ewsMailbox->deleteItem($ewsItem);
            }
        }
    }
}