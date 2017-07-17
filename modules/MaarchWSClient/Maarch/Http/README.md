**maarch - Composants Http**
============================

Maarch Http fournit un jeu de composants qui permettent la communication entre 
des clients - navigateurs web, cURL, socket du langage... - et des serveurs 
d'application, selon le protocole HTTP. 
Ce mécanisme de communication est un aspect central dans le développement 
d'applications basées sur les technologies du web, notamment dans la 
publication de services selon une architecture de type REST, XML-RPC ou encore 
SOAP. 

La communication Http est basée sur des transactions d'échange des messages 
formalisés de deux types : Le client crée un message de requête Http qui est 
envoyé à un serveur, ce dernier lui retourne un message de réponse Http. 
De son côté, le serveur reçoit le message de requête Http et retourne un 
message de réponse Http.
L'utilisateur final n'est habituellement pas au fait de la nature de l'échange,
qui est seulement le moyen réaliser les cas d'usage de l'application publiée.

> Pour de plus amples informations à propos du protocole Http,
> consulter les RFC7230 à 7240 sur le site officiel de l'IETF
> https://tools.ietf.org, ou le site officiel du W3C à l'adresse
> https://www.w3.org/Protocols.

La mise en oeuvre d'une communication Http entre les clients et les serveurs 
d'application nécessite de prendre en charge trois aspects : 

  1. la composition des messages par l'émetteur, qu'il soit le client qui crée 
  des requêtes ou le serveur qui crée des réponses, 
  2. le transport des messages entre les deux parties, et 
  3. le traitement des messages par le destinataire, qu'il soit le serveur qui 
  doit traiter les requêtes ou le client qui doit traiter les réponses.

Les composants Http Maarch fournissent des solutions de base pour la réalisation
de ces trois types de responsabilités.

# Messages Http 
Maarch Http fournit un jeu de composants pour la représentation des messages 
échangés, qui implémentent les interfaces recommandées par les PSR-7.

> *Voir Maarch/Http/Message/README.md*

# Transport des messages Http 
Maarch Http fournit des interfaces et des implémentations pour la communication 
entre les clients et les serveurs applicatifs qui utilisent le formalisme Http 
dans leurs messages.

Ces composants envoient et reçoivent des messages Http représentés par les 
classes implémentant les interfaces recommandées par les PSR-7.

> *Voir Maarch/Http/Transport/README.md*

# Traitement des messages Http 
Maarch Http fournit des interfaces et des implémentations pour le traitement des 
messages par les applicatifs clients et serveurs.

Il s'agit notamment de 
  
  * décrire les messages de requête et de réponse reçus ou envoyés,
  * interpréter les messages de requête ou de réponse reçus,
  * valider les messages de requête ou de réponse reçus,
  * composer les messages de requête ou de réponse à envoyer,
  * gérer les erreurs, notamment pas des descriptions standards des erreurs 
  les plus courantes, à la manière des pages d'erreur des serveurs Http

## Décrire les messages 
Voir Maarch/Http/Description/README.md

## Interpréter les messages 

## Valider les message
