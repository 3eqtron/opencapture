**Maarch - Http Transport**
===========================

Maarch Http Transport décrit les composants nécessaires à la prise en compte du
transport des messages Http entre les clients et les serveurs.

La communication entre les parties utilise le formalisme des messages Http tels 
que décrits par les RFC7230 à 7240 et représentés dans les PSR-7.
L'architecture la plus répandue fait communiquer des clients Http tels que des 
navigateurs internet, des clients de données utilisant cURL ou des sockets 
TCP/IP gérées dans le language d'implémentation, avec des serveurs Http tels que 
Apache Http, Ngnix ou encore Microsoft IIS.

Les composants représentés et implémentés par Maarch Http Transport sont soit 
des clients, soit des serveurs capables d'échanger des messages écrits selon le 
formalisme Http.

Les composants de Maarch permettent de brancher l'application sur les différents 
modes de transport disponibles lorsqu'elle se comporte comme un serveur ou 
comme un client.
Ces modes s'appuient sur des composants tiers qui servent d'intergiciel entre le
client et l'application PHP pour le transport des messages Http, qui peuvent 
être très divers.
Il est donc nécessaire d'implémenter des services qui s'adaptent à ce tiers 
et échangent avec lui les messages Http tels que représentés dans le code 
applicatif PHP.

Par exemple, une application métier PHP qui propose une architecture orientée 
service de type REST peut être publiée sur un serveur applicatif avec une couche
de présentation utilisant un serveur Apache Http, un serveur Ngnix ou encore 
Microsoft IIS. Ces trois serveurs Http n'ont pas exactement les mêmes capacités 
et leur interface avec le langage PHP peut présenter des variantes : 
la fonction PHP <code>getallheaders()</code> est un alias de laa fonction 
<code>apache_request_headers()</code>, elle ne sera opérante que si le serveur 
Http est un Apache Http server.

Autre exemple : L'application pourrait être publiée en simulant RPC via un 
échange de courier électronique dans lequel les messages Http seraient 
transportés selon le protocole SMTP/POP ou IMAP. Idem pour des échanges en file 
d'attente des messages (Message Queuing).

# Serveur Http 
Le serveur Http est responsable de la réception et du traitement des requêtes
Http, puis de la composition et de l'envoi des réponses associées.

## L'interface au serveur 
Le serveur de transport possède la double responsabilité de la réception des 
requêtes et de l'envoi des réponses. Il est décrit par l'interface 
<code>Maarch\Http\Transport\ServerInterface</code>.

### Réception des requêtes
Le serveur de transport doit collecter les données du message de requête Http 
à partir du serveur de communication et fournir à l'application une 
représentation de la requête conforme à celle décrite dans les interfaces PSR-7.

Cette opération est réalisée par la méthode <code>receiveRequest()</code> qui 
peut recevoir des paramètres facultatifs mais renvoie un objet de requête 
serveur qui implémente l'interface 
<code>Psr\Http\Message\ServerRequestInterface</code>.
    
    public \Psr\Http\Message\ServerRequestInterface receiveRequest();

Si l'envoi de la réponse échoue, le serveur de transport doit lever une erreur 
de type <code>Maarch\Http\Transport\Exception</code>.

### Envoi des réponses
Le traitement de la requête par l'application produit un message de réponse 
conforme à celle décrite dans les interfaces PSR-7. 
Le serveur de transport doit envoyer les données au travers du serveur de 
communication.

Cette opération est réalisée par la méthode <code>sendResponse()</code> qui 
reçoit en paramètre un objet de réponse qui implémente l'interface 
<code>Psr\Http\Message\ServerRequestInterface</code>.

    public void sendResponse(\Psr\Http\Message\ResponseInterface $httpResponse);

Si l'envoi de la réponse échoue, le serveur de transport doit lever une erreur 
de type <code>Maarch\Http\Transport\Exception</code>.

## Serveur Apache
Maarch Http fournit une implémentation de l'interface serveur de transport 
adaptée au serveur Apache Http.

Exemple :

    <?php 

    // Instanciation du service de transport Apache
    $apacheServer = new Maarch\Http\Transport\ApacheServer();

    // Réception de la requête Http à partir du serveur Apache
    $httpRequest = $apacheServer->receiveRequest();

    // Le code de l'application fournit une réponse http
    $httpResponse = $myApp->processRequest($httpRequest);

    // Le service de transport envoie les données au serveur Apache
    $apacheServer->sendResponse($httpResponse);

## Serveur de fichiers
Maarch Http fournit une implémentation de l'interface serveur de transport 
adaptée à des requpetes Http à plat dans des fichiers texte, à des fins de test
principalement...

Exemple :

    <?php 

    // Instanciation du service de transport fichiers
    $fileServer = new Maarch\Http\Transport\FileServer();

    // Réception de la requête Http à partir du serveur
    $httpRequest = $fileServer->receiveRequest('/var/tmp/myrequest.txt');

    // Le code de l'application fournit une réponse http
    $httpResponse = $myApp->processRequest($httpRequest);

    // Le service de transport écrit la réponse dans un fichier
    $fileServer->sendResponse($httpResponse, '/var/tmp/myresponse.txt');


_____


# Client Http
Le client Http est responsable de l'envoi du message de requête Http via le 
composant tiers de communication et de la réception du message de réponse.

## Interface client
Le client possède la double responsabilité de l'envoi des requêtes et de la 
réception des réponses. Il est décrit par l'interface 
<code>Maarch\Http\Transport\ClientInterface</code>.

### Envoi de la requête
L'application a produit un message de requête conforme à celle décrite dans les 
interfaces PSR-7. 
Le client doit envoyer les données au travers du protocole de communication, 
puis se placer en attente de la réponse.

Cette opération est réalisée par la méthode <code>sendRequest()</code> qui 
reçoit en paramètre un objet de requête qui implémente l'interface 
<code>Psr\Http\Message\RequestInterface</code>

    public sendRequest(\Psr\Http\Message\RequestInterface $httpRequest);

Si l'envoi de la requête échoue, le client doit lever une erreur de type 
<code>Maarch\Http\Transport\Exception</code>.

### Envoi des réponses
Le client doit collecter les données du message de réponse Http reçue et fournir
à l'application une représentation de la réponse conforme à celle décrite dans 
les interfaces PSR-7.

Cette opération est réalisée par la méthode <code>receiveResponse()</code> qui 
retourne un objet de réponse qui implémente l'interface 
<code>Psr\Http\Message\ResponseInterface</code>.

    public Psr\Http\Message\ResponseInterface receiveResponse();

Si la réponse ne parvient pas, ou encore que les données de réponse ne 
permettent pas la réception de la réponse, le client de transport doit lever une 
erreur de type <code>Maarch\Http\Transport\Exception</code>.

## Implémentation flux Http 
Maarch fournit une implémentation de client Http utilisant le gestionnaire de 
flux Http du langage PHP.

Exemple :

    <?php 

    // Le code de l'application fournit une requête http
    $httpRequest = $myApp->composeRequest();

    // Instanciation du service de transport flux
    $streamClient = new Maarch\Http\Transport\StreamClient();

    // Envoi de la requête Http à partir du serveur
    $streamClient->sendRequest($httpRequest);

    // Le service de transport reçoit la réponse
    $httpResponse = $streamClient->receiveResponse();

    // Le code de l'application utilise la réponse
    $myApp->processResponse($httpResponse);