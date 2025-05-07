<?php
$arrayTest = array();
/************** PARAMETERS ************/

array_push(
    $arrayTest,
    array (
        'hostname'  => "{127.0.0.1:143/imap}INBOX",
        'login'     => "test1@fake",
        'password'  => "yourPassword",
    )
);

// array_push(
//     $arrayTest,
//     array (
//         'hostname'  => "{msgadress:993/imap/ssl}Courriels &#038;AOA- capturer dans MaGEC",
//         'login'     => "LOGIN",
//         'password'  => "PASSWORD",
//     )
// );


/************** LAUNCH TESTS ************/

for ($cpt=0;$cpt<count($arrayTest);$cpt++) {
    echo '******************* TEST ' . $cpt . ' *********************' . PHP_EOL;
    echo "hostname : "     . $arrayTest[$cpt]['hostname'] . PHP_EOL;
    echo "login : "     . $arrayTest[$cpt]['login'] . PHP_EOL;
    echo "password : "     . $arrayTest[$cpt]['password'] . PHP_EOL;
    echo "launch imap_open" . PHP_EOL;
    $mailbox = imap_open(
        $arrayTest[$cpt]['hostname'], 
        $arrayTest[$cpt]['login'], 
        $arrayTest[$cpt]['password']
    );
    if (!$mailbox) {
        echo "CONNECTION FAILED !!!!!!!!!!!!!!!!!!!!!!" . PHP_EOL;
        echo "var_dump imap_errors" . PHP_EOL;
        var_dump(imap_errors());
    } else {
        echo "CONNECTION SUCCESS !!!!!!!!!!!!!!!!!!!!!" . PHP_EOL;
        echo "var_dump imap_errors" . PHP_EOL;
        var_dump(imap_errors());
        exit;
    }

    echo "************************************************" . PHP_EOL;
}
