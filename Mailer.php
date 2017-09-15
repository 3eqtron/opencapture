<?php

include_once "tools/services/PhpMailer.php";

function object2array($object)
{
    $return = NULL;
    if (is_array($object)) {
        foreach ($object as $key => $value) {
            $return[$key] = object2array($value);
        }
    } else {
        if (is_object($object)) {
            $var = get_object_vars($object);
            if ($var) {
                foreach ($var as $key => $value) {
                    $return[$key] = ($key && !$value) ? NULL : object2array($value);
                }
            } else {
                return $object;
            }
        } else {
            return $object;
        }
    }
    return $return;
}

// Load config
$mailParam = simplexml_load_file('config/Mailer.xml');
$mailParam = object2array($mailParam);

$aArgsClass = [];

$aArgs['pathToLib'] = 'tools/PHPMailer/PHPMailerAutoload.php';

$theParam = $mailParam;
$theParam['mailer_class'] = 'Sendmail_PhpMailer_Service';

$mailer = new $theParam['mailer_class']($aArgsClass);

//smtp params
$aArgsSmtpParams = [];
$aArgsSmtpParams['smtp_host']     = $theParam['smtp_host'];
$aArgsSmtpParams['smtp_port']     = $theParam['smtp_port'];
$aArgsSmtpParams['domain']        = $theParam['domain'];
$aArgsSmtpParams['smtp_auth']     = (boolean) $theParam['smtp_auth'];
$aArgsSmtpParams['smtp_user']     = $theParam['smtp_user'];
$aArgsSmtpParams['smtp_password'] = $theParam['smtp_password'];
$aArgsSmtpParams['smtp_secure']   = $theParam['smtp_secure'];

$mailer->setSMTPParams($aArgsSmtpParams);
if (isset($theParam['ssl_options'])) {
    //smtp options for ssl
    $mailer->setSSLOptions($theParam['ssl_options']);
}

$aArgsMailerType['mailerType'] = $theParam['type'];
$mailer->setMailerType($aArgsMailerType);

//smtp params
$aArgsSignedEmailsParams = [];

if (
    (boolean) $theParam['signed_email']['enabled'] &&
    $theParam['signed_email']['enabled'] <> 'false'
) {
    $aArgsSignedEmailsParams['enabled'] = true;
    $aArgsSignedEmailsParams['certificate_path'] = $theParam['signed_email']['certificate_path'];
    $aArgsSignedEmailsParams['private_key_path'] = $theParam['signed_email']['private_key_path'];
    $aArgsSignedEmailsParams['secret_private_key_password'] = $theParam['signed_email']['secret_private_key_password'];
    $aArgsSignedEmailsParams['cert_chain_path'] = $theParam['signed_email']['cert_chain_path'];
} else {
    $aArgsSignedEmailsParams['enabled']= false;
}

$aArgsFrom['from'] = $theParam['mailfrom'];
//setFrom
$mailer->setFrom($aArgsFrom);
$body = 'Error : <br/><br/>' . $message 
    . '<br/><br/>See the batch directory errors for more details : <br/><br/>' 
    . $batchInfos->directory . ' <br/>';

//setReplyTo
$aArgsReplyTo['replyTo'] = $theParam['mailto'];
$mailer->setReplyTo($aArgsReplyTo);

//--> Set the return path
$aArgsReturnPath['returnPath'] = $userInfo['mail'];
$mailer->setReturnPath($aArgsReturnPath);

//--> To
$to = [
    'mail' => $theParam['mailto']
];

//--> Set subject
$aArgsSubj['subject'] = 'Error with MaarchCapture';
$mailer->setSubject($aArgsSubj);

$body = '<html><body>' . $body . '</body></html>';
$aArgsBody['body'] = [
    [
        'htmlContent' => $body,
        'textContent' => '',
        'isHTML' => true,
    ],
];

//setBody
$mailer->setBody($aArgsBody);

$aArgsCharset['charset'] = [
    [
        'htmlCharset' => $theParam['charset'], 
        'textCharset' => $theParam['charset'],
        'headCharset' => $theParam['charset'],
    ],
];
//--> Set charset
$mailer->setCharset($aArgsCharset);

// $aArgsAttach['attachment'] = [
//     [
//         'path' => $resFile['file_path'], 
//         'fileName' => $resFilename,
//         'mimeType' => $resFile['mime_type'],
//     ],
// ];

// //Add file
// $mailer->addAttachment($aArgsAttach); 

$mailer->setTo([$to]);

if ($aArgsSignedEmailsParams['enabled']) {
    $mailer->sign(
        $aArgsSignedEmailsParams
    );
}

//var_dump($mailer);

$return = $mailer->send();

if (
    ($return == 1 && 
    ($theParam['type'] == "smtp" || $theParam['type'] == "mail" )) || 
    ($return == 0 && $theParam['type'] == "sendmail") || empty($to)
) {
        $execResult = 'S';
} else {
    $error = $mailer->getErrors();
    $execResult = 'E';
}

