**Maarch - Messages Http**
==========================

Maarch Http fournit des composants pour la représentation des messages Http
échangés entre les parties. 

Un message Http est soit une requête, soit une réponse, qui sont respectivement 
représentés par deux composants Maarch distincts. Une distinction plus précise
est aussi faite entre les messages de requête créées par un client et ceux reçus
par un serveur, afin de prendre en charge les opérations liées au traitement.

Les deux types de message ont certaines propriétés en commun, notamment 
l'utilisation d'en-têtes et le transfert optionnel d'un contenu, parfois aussi 
appelé "corps", qui peut être vu comme un flux de données binaires, ou parfois 
contenir plusieurs flux qui correspondent à des fichiers téléversés.

Les composants de message Http Maarch implémentent les interfaces décrites dans
les PSR-7 intitulées *HTTP message interfaces*. 
Ce standard décrit des interfaces de programmation communes afin de permettre 
l'interopérabilité entre les logiciels, notamment les frameworks en langage PHP. 
Cela signifie que les composants de message Http Maarch sont réutilisables dans
toutes les applications basées sur des frameworks compatibles avec le standard
PSR-7.

Cela signifie aussi que le code source de ces interfaces doit être installé sur 
les serveurs d'application qui utilisent les composants Maarch, et chargé lors
de l'exécution de l'application, par un chargement automatique de classe
par exemple ou inclu dans le code applicatif par la commande PHP adéquate
(<code>include</code>, <code>include_once</code>, <code>require</code>, 
<code>require_once</code>). 

> Pour de plus amples informations à propos du PSR-7, se reporter au site 
officiel du PSR à l'adresse http://www.php-fig.org/psr/psr-7

> Dans la suite de ce document, lorsque sont évoquées les *propriétés* des 
messages, il s'agit avant tout d'un modèle conceptuel des données manipulées.
En effet, les interfaces des PSR-7 fournissent uniquement des méthodes d'accès 
aux données échangées via les messages, mais ne présument pas de leur 
représentation sous la forme de propriétés dans leurs implémentations dans des 
classes PHP.

# Généralités
Les messages de requête et de réponse partagent un ensemble de propriétés 
(conceptuelles, donc) et de méthodes de manipulation de celles-ci.

Tous les messages Http décrits dans le présent document sont composés de 
l'information suivante : 

  * la version de protocole utilisée
  * une **liste d'en-têtes** destinés à préciser le message
  * un **contenu** optionnel transmis


    Message : {
        protocolVersion : string,
        headers : Header*,
        body : Stream?
    }

    Header : {
        name : string,
        value : array|string
    }

    Stream : { 
        handler : resource
    }

## En-têtes
Les en-têtes précisent la requête ou la réponse en apportant l'information sur 
le transport, l'utilisation du cache, le contenu, les options disponibles, 
demandées ou sélectionnées pour la constitution ou le traitement du message.

Chaque en-tête possède un nom unique pour la requête, mais peut prendre 
plusieurs valeurs, ce qui implique que leur récupération est possible sous la 
formae d'une chaîne de caractères ou d'un tableau.

L'interface <code>Psr\Http\Message\MessageInterface</code> prévoit un ensemble 
de méthodes pour gérer les en-têtes au niveau des messages :

  * <code>getHeaders()</code> : retourne un tableau associatif des en-têtes du 
  message indexés par nom,
  * <code>hasHeader()</code> : vérifie la présence de l'en-tête dans le message,
  * <code>getHeader()</code> : retourne un tableau indexé des valeurs de 
  l'en-tête,
  * <code>getHeaderLine()</code> : retourne la valeur chaîne de caractère de 
  toute la ligne d'en-tête,
  * <code>withHeader()</code> : définit un en-tête par son nom et ses valeurs 
  fournies sous la forme d'un tableau indexé ou d'une chaîne de ligne complète
  * <code>withAddedHeader()</code> : ajoute une ou plusieurs valeurs à celles 
  déjà présente piur l'en-tête indiqué  
  * <code>withoutHeader()</code> : enlève l'en-tête du message

## Corps de message
Le corps des messages Http est un objet qui encapsule un flux de 
données binaires, représenté par la classe 
<code>Maarch\Http\Message\Stream</code>, qui implémente l'interface 
<code>Psr\Http\Message\StreamInterface</code>.

L'interface <code>Psr\Http\Message\MessageInterface</code> prévoit les méthodes 
<code>withBody()</code> et <code>getBody()</code> pour respectivement définir 
et récupérer le corps de la requête sous la forme d'un objet qui implémente 
l'interface de flux de données.

De plus, Maarch Http étend cette interface afin de permettre la construction de 
l'objet directement à partir d'une chaîne de caractères envoyée au message, avec 
la méthode 
<code>Maarch\Http\Message\MessageAbstract::withSerializedBody()</code>.

Cette méthode utilise le flux de données <code>php://temp</code> pour stocker 
la données scalaire reçue et créer un gestionnaire de flux qui sera encapsulé 
dans un nouvel objet <code>Maarch\Http\Message\Stream</code>, lui-même défini 
comme corps du message appelé, comme dans l'exemple ci-dessous :

    $httpRequest = new Maarch\Http\Message\Request();
    $httpRequest->withSerializedBody('foo');

    $stream = $httpRequest->getBody();

    echo (string) $stream;

Cet exemple affichera <code>foo</code>.

Ce mécanisme est pratique lorsque les données à envoyer ne sont pas directement 
disponibles sous la forme d'une ressource telle qu'un fichier ou un contenu 
qui peut être ouvert avec un gestionnaire de flux PHP. 
C'est notamment le cas pour le composant qui réalise la sérialisation du corps 
de la réponse à partir des données traitées dans l'application et manipulées 
sous la forme d'un objet, d'une variable scalaire ou d'un tableau.

## Texte du message 
A ne pas confondre avec le contenu du flux binaire du corps du message, le 
texte du message est la représentation scalaire de l'ensemble des données 
du message, qui incluent les en-têtes et le corps, mais aussi pour la requête 
la ligne de requête et pour la réponse la ligne de statut.

La classe <code>Maarch\Http\Message\MessageAbstract</code> implémente la 
méthode magique <code>__toString()</code> qui retourne le texte complet du 
message à plat tel que défini dans le standard Http.

Ceci permet par exemple d'enregistrer l'intégralité des données échangées dans 
un fichier de vidage, ou encore d'utiliser des transports asynchrones des 
messages (smtp/pop3, ftp...).

# Requête Http
Le message de requête est composé par le client et reçu par le serveur. 

Comme précisé plus haut, les PSR-7 font la distinction entre ces deux requêtes 
afin de permettre le traitement de celle-ci par le serveur lorsque l'application 
développée possède ce rôle.

La requête de base, telle que composée par le client, est représentée par la 
classe <code>Maarch\Http\Message\Request</code>, qui implémente l'interface 
<code>Psr\Http\Message\RequestInterface</code>.

Une requête Http est composée de l'information suivante :
  * une **cible** représentée par une URI, 
  * une **méthode** appliquée à la ressource cible,
  * une **liste d'en-têtes** destinés à préciser la requête
  * un **contenu** optionnel transmis au serveur


    Request : {
        protocolVersion : string,
        method :  string,
        uri : Uri,
        headers : Header*,
        body : Stream?
    }

    Uri : {
        scheme : string,
        user : string,
        password : string,
        host : string,
        port : int,
        path : string,
        query : string,
        fragment : string,
        userinfo: Userinfo?,
        authority: Authority?
    }

    Userinfo : user (':' password)?
    Authority : (userinfo '@')? host (':' port)?

## Construire une requête
Une nouvelle requête est créée par appel au constructeur.
La méthode de construction accepte un paramètre optionnel qui fournit la cible
de la requête sous la forme d'une URL.
Si ce paramètre est omis, la requête est initialisée avec une URI de base vide.

    // Instancie une nouvelle requête sans cible
    $httpRequest = new Maarch\Http\Message\Request();

    // Instancie une nouvelle requête avec une cible
    $httpRequest = new Maarch\Http\Message\Request('http://www.maarch.org/contact');


## URI et cible de la requête Http
L'URI de la requête, désignée dans la RFC7230 la seconde partie de la ligne de 
requête du protocole Http, identifie la cible de la requête.

L'URI comporte l'information suivante :
  
  * le schéma utilisé (http ou https par exemple)
  * l'information d'identification de l'utilisateur (facultative)
  * le nom d'hôte adressé
  * le port adressé (facultatif, 80 par défaut)
  * le chemin de la ressource
  * la chaîne de requête (facultative)
  * le fragment adressé (facultatif)

L'URI possède une représentation sous la forme d'une chaîne de caractères, 
aussi appelée *cible de la requête*, qui peut être schématisée comme suit : 
<code>schema://username:pwd@domain:port/path?query#fragment</code>

L'URI est représentée par la classe <code>Maarch\Http\Message\Uri</code>, qui 
implémente l'interface <code>Psr\Http\Message\UriInterface</code>.

Pour définir l'URI de la requête, trois méthodes sont disponibles : 
  
  * lors de la construction de l'objet requête (voir ci-dessus), 
  * par la méthode de définition de la cible de la requête, ou 
  * par la méthode de définition de l'objet Uri de la requête.

Dans ces trois méthodes, il est possible de ne définir qu'une partie de l'URI, 
en omettant le protocole, le nom de l'hôte ou le port par exemple.
La méthode ne met à jour que les informations effectivement transmises dans la 
chaîne reçue. 
Toutes les chaînes de requête suivantes sont acceptées :

    http://username:pwd@www.maarch.org/contact?test
    http://www.maarch.org/contact
    www.maarch.org/contact
    /contact

### Définir l'URI à la construction de l'objet requête
La méthode de construction de la classe permet de passer l'URI lors de la 
construction de l'objet de requête :

   $httpRequest = new Maarch\Http\Message\Request('http://www.maarch.org/contact');

### Définir la cible de requête
La méthode <code>Maarch\Http\Message\Request::withRequestTarget()</code> reçoit
en paramètre une chaîne de caractères qui représente l'URI ou l'URL de la 
requête, par exemple :

    $httpRequest->withRequestTarget('http://www.maarch.org/contact');

La méthode retourne l'objet requête avec une Uri modifiée. 

### Définir l'objet Uri de requête
La méthode <code>Maarch\Http\Message\Request::withUri()</code> reçoit
en paramètre un objet Uri implémentant l'interface d'une URI, par exemple :

    $uri = new Maarch\Http\Message\Uri('http://www.maarch.org/contact');
    $httpRequest->withUri($uri);

Un second argument, optionnel, permet de demander que l'hôte de l'URI soit
conservé, ce qui permet de ne pas transmettre une URL complète mais seulement
un chemin et une chaîne de requête relatives, par exemple :
    
    $uri = new Maarch\Http\Message\Uri('/contact');
    $httpRequest->withUri($uri, true);

La méthode retourne l'objet requête avec une Uri modifiée. 

### Récupérer la cible de requête
La méthode <code>Maarch\Http\Message\Request::getRequestTarget()</code> 
retourne la chaîne de requête complète:

    $requestTarget = $httpRequest->getRequestTarget();

    echo $requestTarget;

Affichera par exemple <code>http://www.maarch.org/contact</code>.

### Récupérer l'objet URI
L'URI d'une requête peut être récupérée en appelant la méthode 
<code>Maarch\Http\Message\Request::getUri()</code>, qui retourne un objet Uri:

    $uri = $httpRequest->getUri();
    $uri->withPath('/references')
        ->withFragment('form');

    echo (string) $uri;

Affichera par exemple <code>http://www.maarch.org/references#form</code>.

> Pour de plus amples informations sur l'interface de l'Uri de requête Http,
> se référer au PSR-7.


## Autres propriétés de la requête
Les autres propriétés de la requête sont accessibles via un jeu de méthodes 
de définition (setters) et de récupération (getters) largement explicitées dans
la documentation des PSR.

_____

# Requête côté serveur
Dans le cas où l'objet requête représente un message reçu par le serveur, il 
fournit une interface étendue par rapport à la requête de base.
La requête côté serveur est représentée par la classe 
<code>Maarch\Http\Message\ServerRequest</code>, qui implémente l'interface 
<code>Psr\Http\Message\ServerRequestInterface</code>.

La requête serveur Http comporte donc des propriétés complémentaires et les
méthodes pour les manipuler :
  
  * une liste de **cookies** transmis,
  * une liste de **paramètres de serveur**, 
  * une liste de **paramètres de requête** issus de l'interprétation de la chaîne 
  de requête,
  * une liste d'**attributs** issus par exemple des variables de la route REST ou 
  autres valeurs extraites par le traitement de la requête par le serveur, 
  * le **contenu interprété** de la requête reçue,
  * une liste de **fichiers téléversés**

    
    ServerRequest extends Request : {
        cookieParams : CookieParam*,
        serverParams : ServerParam*,
        queryParams : QueryParam*,
        attributes : Attribute*,
        parsedBody : [ string. | object? | EntityParam* ],
        uploadedFiles : UploadedFile*
    }

    UploadedFile : {
        name : string,
        type : string,
        tmpname : string,
        error : int,
        size : int,
    }

## Paramètres de requête 
Lorsque le serveur traite la requête, il peut être amené à décoder la chaîne de 
requête, originalement représentée comme une chaîne de caractères.
Le résultat du traitement peut être un tableau associatif, un objet ou une
chaîne de caractères.
L'interface de la requête côté serveur permet de stocker cette version décodée
de la chaîne de requête au travers de la méthode 
<code>Maarch\Http\Message\ServerRequest::withQueryParams()</code>.

Par exemple, une chaîne de requête encodée en URL pourra être décodée par les
instructions suivantes :

    $queryString = $httpServerRequest->getUri()->getQuery();
    $queryParams = array();
    url_decode($queryString, $queryParams);
    $httpServerRequest->withQueryParams($queryParams);

La méthode renvoie l'objet requête mis à jour avec les paramètres de requête.

Les paramètres de requête peuvent être récupérés par l'application grâce à la 
méthode <code>Maarch\Http\Message\ServerRequest::getQueryParams()</code>.

> Le langage PHP réalise automatiquement le décodage de la chaîne de requête
lorsque celle-ci est encodée en URL tel que décrit par la RFC1867, qui 
correspond à un type MIME application/x-www-form-urlencoded.
Le résultat de l'opération est placé dans la variable superglobale 
<code>$_GET</code>.
Si l'encodage n'est pas celui attendu par PHP, la variable n'est pas 
renseignée.

## Contenu interprété
Lorsque le serveur traite la requête, il peut être amené à décoder le corps 
reçu dans la requête, originalement représenté comme un flux de données 
binaires.
Le résultat du traitement peut être un tableau associatif, un objet ou une
chaîne de caractères.
L'interface de la requête côté serveur permet de stocker cette version décodée
de l'entité reçue au travers de la méthode 
<code>Maarch\Http\Message\ServerRequest::withDecodedBody()</code>.

Par exemple, un corps de requête encodé en JSON pourra être décodé par les
instructions suivantes :

    
    $stream = $httpServerRequest->getBody();
    $parsedBody = json_decode($stream->getContents());
    $httpServerRequest->withParsedBody($parsedBody);

La méthode renvoie l'objet requête mis à jour avec l'entité décodée.

L'entité ainsi décodée peut être récupérée par l'application grâce à la 
méthode <code>Maarch\Http\Message\ServerRequest::getParsedBody()</code>.

> Le langage PHP réalise automatiquement le décodage du corps de la requête
lorsque deux conditions sont remplies: celui-ci est encodé en URL tel que décrit
par la RFC1867, qui correspond à un type MIME application/x-www-form-urlencoded,
et la méthode Http utilisée est <code>POST</code>.
Le résultat de l'opération est placé dans la variable superglobale 
<code>$_POST</code>.
Dans tous les autres cas, quel que soit l'encodage utilisé ou la méthode, PHP 
ne renseigne pas cette variable.

## Autres propriétés de la requête côté serveur
Les autres propriétés de la requête du serveur sont accessibles via un jeu de 
méthodes de définition (setters) et de récupération (getters) largement 
explicitées dans la documentation des PSR.

_____


# Réponse Http
Le message de réponse est composé par le serveur et retourné au client. 

La réponse de base, telle que reçue par le client, est représentée par la 
classe <code>Maarch\Http\Message\Response</code>, qui implémente l'interface 
<code>Psr\Http\Message\ResponseInterface</code>.

Une réponse Http est composée de l'information suivante :
  * un **code d'état** accompagné d'un message lisible par l'humain, 
  * une **liste d'en-têtes** destinés à préciser la réponse
  * un **contenu** optionnel transmis au client

    Response : {
        protocolVersion : string,
        statusCode : int,
        reasonPhrase : string,
        headers : Header*,
        body : Stream?
    }

## Construire une réponse
Une nouvelle réponse est créée par appel au constructeur.
La méthode de construction accepte un paramètre optionnel qui fournit le code
de statut de la réponse sous la forme d'un nombre, suivi d'un second paramètre 
optionnel pour le message.
Si ce paramètre est omis, la requête est initialisée sans statut et message.

    // Instancie une nouvelle réponse sans statut
    $httpResponse = new Maarch\Http\Message\Response();

    // Instancie une nouvelle réponse avec statut 404
    $httpResponse = new Maarch\Http\Message\Response(404);

# Réponse côté serveur
Les PSR-7 ne font la distinction entre la réponse composée par le serveur et 
celle reçue par le client. 
Néanmoins, Maarch Http introduit la notion de réponse du serveur afin de 
permettre le traitement préalable de celle-ci par le serveur lorsque 
l'application développée possède ce rôle.

La réponse côté serveur est représentée par la classe 
<code>Maarch\Http\Message\ServerResponse</code>, qui ajoute la fonctionnalité
de gestion de l'entité transmise en réponse sous sa forme décodée.

Ceci permet au serveur de stocker l'entité à renvoyer dès son obtention par le 
code applicatif, pour une sérialisation ultérieure, grâce à la méthode 
<code>Maarch\Http\Message\ServerResponse::withEntity()</code>.
Par exemple, le serveur peut obtenir du code applicatif un tableau ou un objet 
de résultat et le stocker en attente de négociation de l'encodage souhaité par 
le client :

    // Code applicatif 1
    $entity = $app->process();
    $httpServerResponse->withEntity($entity);

    // Code applicatif 2, à un autre point de l'application
    $acceptHeader = $httpRequest->getHeader('Accept');
    $entity = $httpServerResponse->getEntity();
    foreach ($acceptHeader as $acceptArg) {
        $mimeCode = strtok($acceptArg, ';');
        switch ($mimeCode) {
            case 'application/json':
                $encodedEntity = json_encode($entity);
                break;

            case 'text/plain':
                $encodedEntity = print_r($entity, true);
                break;
        }
    }

    if (isset($encodedEntity)) {
        $httpServerResponse->withEncodedBody($encodedEntity);
    }