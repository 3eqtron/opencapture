<?php 


// CLIENT
//*********************************************************************************************************
$httpClientRequest = new Maarch\Http\Message\Request();
$httpClientRequest->withProtocolVersion(1.1)->withMethod('GET')->withRequestTarget('http://maarch.com');

$httpClient = new Maarch\Http\Transport\StreamClient();
$httpClientResponse = $httpClient->send($httpClientRequest);

if (!headers_sent()) {
    http_response_code($httpClientResponse->getStatusCode());

    foreach ($httpClientResponse->getHeaders() as $name => $value) {
        header($name.": ".implode(',', $value));
    }
}

echo $httpClientResponse->getBody();
exit;