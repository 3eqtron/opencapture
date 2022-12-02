<?php

// Microsoft Exchange mailbox client classes
require 'modules/EWSMailCapture/EWS/ExchangeMailbox.php';
require 'modules/EWSMailCapture/EWS/ExchangeItem.php';

class EWSMailCapture
{
    private $logFile;

    public function CaptureMails($account, $action, $configFile, $folder, $addHeaderInMailContent)
    {
        $batch = $_SESSION['capture']->Batch;
        $this->logFile = $batch->directory . DIRECTORY_SEPARATOR . 'EWSMailCapture.log';

        $this->writeLog('loading config file: ' . $configFile);
        $xmlConfig = simplexml_load_file(__DIR__ . DIRECTORY_SEPARATOR . $configFile);
        if (empty($xmlConfig)) {
            $_SESSION['capture']->sendError('Configuration file ' . $configFile . ' not found');
        }

        $accountConfig = $xmlConfig->xpath('/EWSMailCapture/accounts/account[@name="'.$account.'"]')[0] ?? null;
        if (!$accountConfig) {
            $_SESSION['capture']->sendError("E-mail account $account is not defined in configuration!");
        }

        $mailbox = (string) ($accountConfig->xpath('mailbox')[0] ?? '');
        $captureFolder = preg_replace('/\{.+\}(.+)/', '\1', $mailbox);
        $mailbox = preg_replace('/\{(.+)\}.+/', '\1', $mailbox);

        $exchangeMailboxArgs = [];
        $exchangeMailboxArgs['mailbox'] = $mailbox;
        $exchangeMailboxArgs['username'] = (string) ($accountConfig->xpath('username')[0] ?? '');
        $exchangeMailboxArgs['password'] = (string) ($accountConfig->xpath('password')[0] ?? '');
        $exchangeMailboxArgs['tenantID'] = (string) ($accountConfig->xpath('tenantID')[0] ?? '');
        $exchangeMailboxArgs['clientID'] = (string) ($accountConfig->xpath('clientID')[0] ?? '');
        $exchangeMailboxArgs['clientSecret'] = (string) ($accountConfig->xpath('clientSecret')[0] ?? '');

        if (!empty($exchangeMailboxArgs['password'])) {
            $exchangeMailboxArgs['authMethod'] = ExchangeMailbox::BASIC_AUTH;
        } elseif (!empty($exchangeMailboxArgs['tenantID']) && !empty($exchangeMailboxArgs['clientID']) && !empty($exchangeMailboxArgs['clientSecret'])) {
            $exchangeMailboxArgs['authMethod'] = ExchangeMailbox::O_AUTH_2;
        } else {
            $log = sprintf("\n\nUnable to set auth method\nCheck the account '%s' configuration in %s\n\n", $account, __DIR__ . DIRECTORY_SEPARATOR . $configFile);
            $this->writeLog($log);
            $_SESSION['capture']->sendError($log);
        }

        $exchangeMailboxArgs['exchangeversion'] = (string) ($accountConfig->xpath('exchangeversion')[0] ?? '');

        $messageRules = $xmlConfig->xpath('/EWSMailCapture/messagerules/messagerule') ?: [];
        $attachmentRules = $xmlConfig->xpath('/EWSMailCapture/attachmentrules/attachmentrule') ?: [];

        $messageOutputs = $xmlConfig->xpath('/EWSMailCapture/messageoutputs/messageoutput') ?: [];
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

        $attachmentOutputs = $xmlConfig->xpath('/EWSMailCapture/attachmentoutputs/attachmentoutput') ?: [];
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

        if ($exchangeMailboxArgs['authMethod'] == ExchangeMailbox::BASIC_AUTH && (
            empty($exchangeMailboxArgs['mailbox'])
            || empty($exchangeMailboxArgs['exchangeversion'])
            || empty($exchangeMailboxArgs['username'])
            || empty($exchangeMailboxArgs['password'])
            || empty($captureFolder)
        )) {
            $_SESSION['capture']->sendError(sprintf("MS Exchange mailbox configuration for %s is invalid!\n%s", ExchangeMailbox::BASIC_AUTH, json_encode($exchangeMailboxArgs)));
        } elseif ($exchangeMailboxArgs['authMethod'] == ExchangeMailbox::O_AUTH_2 && (
            empty($exchangeMailboxArgs['mailbox'])
            || empty($exchangeMailboxArgs['exchangeversion'])
            || empty($exchangeMailboxArgs['username'])
            || empty($exchangeMailboxArgs['tenantID'])
            || empty($exchangeMailboxArgs['clientID'])
            || empty($exchangeMailboxArgs['clientSecret'])
            || empty($captureFolder)
        )) {
            $_SESSION['capture']->sendError(sprintf("\n\nMS Exchange mailbox configuration for %s is invalid!\n%s\n\n", ExchangeMailbox::O_AUTH_2, json_encode($exchangeMailboxArgs)));
        }

        $ewsMailbox = new ExchangeMailbox($exchangeMailboxArgs);

        $ewsItems = $ewsMailbox->getItemsByFolderName($captureFolder);
        if (!empty($ewsItems['error'])) {
            $this->writeLog("ERROR: {$ewsItems['error']}");
            $_SESSION['capture']->sendError($ewsItems['error']);
        }
        $itemCount = count($ewsItems);
        $this->writeLog($itemCount . ' messages in mailbox folder \'' . $captureFolder . '\'');

        foreach ($ewsItems as $ewsItemI => $ewsItem) {
            $this->writeLog('processing email ' . ($ewsItemI + 1) . '/' . $itemCount . ': ' . $ewsItem->getSubject());

            foreach ($messageRules as $messageRule) {
                $skip = $this->applyMessageRule($messageRule, $ewsItem, $ewsMailbox);
                if ($skip) {
                    $this->writeLog('SKIPPING due to rule: ' . ($messageRule->attributes()['name'] ?? 'UNKNOWN'));
                    continue 2;
                }
            }

            $body = $ewsItem->getBody();
            $extension = stripos($body, '</html>') !== false ? 'html' : 'txt';
            if (!empty($addHeaderInMailContent) && $addHeaderInMailContent == 'true') {
                $headerStr = "\n\n";
                $headerStr .= 'Le : ' . $ewsItem->getISODate() . "\n";
                $headerStr .= 'De : ' . $ewsItem->getSenderName() . ' &lt;' . $ewsItem->getSenderEmailAddress() . '&gt;' . "\n";
                $headerStr .= 'À : ' . $ewsItem->getToAddress() . "\n";
                $headerStr .= 'Cc : ' . $ewsItem->getCcAddress() . "\n";
                $headerStr .= 'Objet : ' . $ewsItem->getSubject() . "\n";
                if ($extension == 'html') {
                    $headerStr = str_replace(['<', '>', "\n"], ['&lt;', '&gt;', '<br>'], $headerStr) . '<hr><br><br>';
                    $body = preg_replace('/(<body.+?>)/', '\1' . $headerStr, $body);
                } else {
                    $body = $headerStr . "------\n\n" . $ewsItem->getBody();
                }
            }

            $filePath = $batch->directory . DIRECTORY_SEPARATOR . 'BODY_' . $ewsItemI . '.' . $extension;
            if (file_put_contents($filePath, $body) === false) {
                $_SESSION['capture']->sendError('failed to save email body as file: ' . $ewsItem->getSubject());
            }
            $document = $batch->addDocument($filePath);

            foreach ($messageMetadata as $name => $metadata) {
                if ($metadata['type'] === 'const') {
                    $document->setMetadata($name, $metadata['value']);
                } elseif ($metadata['type'] === 'var') {
                    $document->setMetadata($name, $ewsItem->get($metadata['value']));
                }
            }

            $attCount = $ewsItem->getAttachmentsCount();
            $this->writeLog('inline attachments: ' . $ewsItem->getInlineAttachmentsCount());
            foreach ($ewsItem->getAttachments() as $ewsAttI => $ewsAttachment) {
                $this->writeLog('processing attachment ' . ($ewsAttI + 1) . '/' . $attCount . ': ' . $ewsAttachment['filename']);

                $filePath = $batch->directory . DIRECTORY_SEPARATOR . 'BODY_' . $ewsItemI . '_ATT_' . $ewsAttI . '.' . pathinfo($ewsAttachment['filename'], PATHINFO_EXTENSION);
                if (file_put_contents($filePath, $ewsAttachment['content']) === false) {
                    $_SESSION['capture']->sendError('failed to save email attachment as file: ' . $ewsItem->getSubject($ewsAttI));
                }

                if (!empty($attachmentRules)) {
                    $skip = true;
                    foreach ($attachmentRules as $attachmentRule) {
                        $skip = $skip && $this->applyAttachmentRule($attachmentRule, $filePath);
                    }
                    if ($skip) {
                        $this->writeLog('SKIPPING attachment due to attachment rules!');
                        continue;
                    }
                }

                $attachment = $document->addAttachment($filePath);
                $attachment->setMetadata('filename', pathinfo($ewsAttachment['filename'], PATHINFO_BASENAME));
                $attachment->setMetadata('extension', pathinfo($ewsAttachment['filename'], PATHINFO_EXTENSION));

                foreach ($attachmentMetadata as $name => $metadata) {
                    if ($metadata['type'] === 'const') {
                        $attachment->setMetadata($name, $metadata['value']);
                    }
                }
            }

            if ($action === 'move') {
                $this->writeLog('moving email to purge folder: ' . $folder);
                $moved = $ewsMailbox->moveItemToNamedFolder($ewsItem, $folder);
                if ($moved === false) {
                    $this->writeLog('ERROR: could not move item: mailbox folder \'' . $folder . '\' does not exist.');
                    $_SESSION['capture']->sendError('could not move item: mailbox folder \'' . $folder . '\' does not exist.');
                }
            } elseif ($action === 'delete') {
                $this->writeLog('moving email to trash');
                $ewsMailbox->deleteItem($ewsItem);
            }
        }
        $this->writeLog('done');
    }

    // return true to skip message
    private function applyMessageRule($messageRule, $ewsItem, $ewsMailbox)
    {
        $test     = (string) $messageRule;
        $value    = $ewsItem->get((string) ($messageRule->attributes()['info'] ?? ''));
        $operator = (string) ($messageRule->attributes()['op'] ?? '=');
        $action   = (string) ($messageRule->attributes()['action'] ?? 'none');
        $name     = (string) ($messageRule->attributes()['name'] ?? 'UNKNOWN');
        if (empty($test) || empty($value)) {
            return false;
        }

        $applies = false;
        switch ($operator) {
            case "=":
                if ($value == $test) {
                    $applies = true;
                }
                break;
            case "&gt;=":
                if ($value >= $test) {
                    $applies = true;
                }
                break;
            case "&lt;=":
                if ($value <= $test) {
                    $applies = true;
                }
                break;
            case "&gt;":
                if ($value > $test) {
                    $applies = true;
                }
                break;
            case "&lt;":
                if ($value < $test) {
                    $applies = true;
                }
                break;
            case "!=":
            case "&lt;&gt;":
                if ($value != $test) {
                    $applies = true;
                }
                break;
            case "in":
                if (in_array($value, explode(' ', $test))) {
                    $applies = true;
                }
                break;
            case "notin":
                if (!in_array(strtolower($value), explode(' ', strtolower($test)))) {
                    $applies = true;
                }
                break;
            case "contains":
                if (strripos($value, $test) !== false) {
                    $applies = true;
                }
                break;
            case "nocontains":
                if (strripos($value, $test) === false) {
                    $applies = true;
                }
                break;
        }

        if (!$applies) {
            return false;
        }

        $this->writeLog('Rule ' . $name . ' applied');

        switch ($action) {
            case 'delete':
                $this->writeLog('moving mail to trash.');
                $ewsMailbox->deleteItem($ewsItem);
                $skip = true;
                break;
            case 'move':
                $folder = (string) ($messageRule->attributes()['folder'] ?? '');
                if (!empty($folder)) {
                    $this->writeLog('moving mail to ' . $folder);
                    $moved = $ewsMailbox->moveItemToNamedFolder($ewsItem, $folder);
                    if ($moved === false) {
                        $this->writeLog('ERROR: could not move item: mailbox folder \'' . $folder . '\' does not exist.');
                        $_SESSION['capture']->sendError('could not move item: mailbox folder \'' . $folder . '\' does not exist.');
                    }
                } else {
                    $this->writeLog('WARNING: move action with no specified folder!');
                }
                $skip = true;
                break;
            default:
                $this->writeLog('no rule action');
                $skip = false;
                break;
        }

        return $skip;
    }

    // return true to skip attachment
    private function applyAttachmentRule($attachmentRule, $filePath)
    {
        $name   = (string) $attachmentRule->attributes()['name'] ?? '';
        $info   = (string) $attachmentRule->attributes()['info'] ?? '';
        $op     = (string) $attachmentRule->attributes()['op'] ?? '';
        $values = explode(' ', (string) $attachmentRule ?? '');
        if (empty($name) || $info !== 'format' || $op !== 'in') {
            $this->writeLog('invalid rule: ' . ($name ?: 'UNKNOWN'));
        }
        $mime = explode('/', mime_content_type($filePath))[1] ?? null;
        if (empty($mime) || !in_array($mime, $values)) {
            return true;
        }

        return false;
    }

    private function writeLog($str)
    {
        $str = '[' . date('c') . '] EWSMailCapture: ' . $str . PHP_EOL;
        echo $str;
        file_put_contents($this->logFile, $str, FILE_APPEND);
    }
}