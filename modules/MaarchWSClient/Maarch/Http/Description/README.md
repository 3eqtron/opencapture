**Maarch - Description Http**
=============================

Maarch Http Description fournit un jeu de composants pour la description des 
messages Http échangés par les applications clientes et serveurs.

L'objectif est de permettre aux développeurs d'applications basées sur les 
technologies du web de décrire les messages échangés pour les besoins de 
documentation, d'interprétation, de validation et de composition de ces 
messages.

Les composants s'appuient sur la syntaxe des classes PHP, avec leurs membres 
propriétés et méthodes, complétée par des descriptions et mot-clés placés dans 
les blocs de commentaire des différents composants.

    Description
     |_ IdentityProviders
     |_ Requests
     |   |_ HeaderRefs
     |   |_ QueryParamRefs
     |   |_ Representations
     |_ Responses
     |   |_ ResponseStatus
     |   |_ HeaderRefs
     |   |_ Representations
     |_ Errors
     |_ Headers
     |_ Statuses

> Dans la suite de ce document, lorsque sont évoquées les *propriétés* des 
composants décrit, il s'agit avant tout d'un modèle conceptuel des données 
manipulées.
En effet, les interfaces fournissent uniquement des méthodes d'accès aux données 
décrites, mais ne présument pas de leur représentation sous la forme de 
propriétés dans leurs implémentations dans des classes PHP.

# Généralités
## Composant de base 
Maarch Http Description utilise une interface de base pour tous les composants, 
nommée <code>Maarch\Http\Description\ComponentInterface</code>. 
Cette interface ne devrait pas être implémentée directement par des classes de 
description, mais elle est étendue par toutes les autres interfaces.

    Component : {
        summary : string?,
        description : string?,

        id : string,
        name : string
    }

## Références
Une seconde interface sert de base pour la description des 
composants représentés par des membres des composants principaux. Il s'agit de 
l'interface nommée <code>Maarch\Http\Description\refInterface</code>. 

    Ref extends Component : {
        summary : string?,
        description : string?,

        id : string,
        name : string,

        type : DataType?,
        required : bool,
        default : string|int|bool|float|null
    }

    DataType : {
        name : string,
        base : DataType?,
        facets : facet*
    }

# Messages
Les messages Http sont de deux types : les requêtes et les réponses.
Ces messages possèdent des propriétés communes, ils sont notamment composés 
d'une liste d'en-têtes et d'un contenu (ou corps) optionnels.

    Message extends Component : {
        summary : string?,
        description : string?,

        id : string,
        name : string,

        headers : Ref*,

        representations : Representation*
    }

Les descriptions de messages doivent implémenter l'interface 
<code>Maarch\Http\Description\MessageInterface</code>. 
Cette interface ne devrait pas être implémentée directement par des classes de 
description, ele est en quelque sorte "abstraite", mais elle est étendue par les
deux interfaces 
<code>Maarch\Http\Description\RequestInterface</code> et 
<code>Maarch\Http\Description\ResponseInterface</code>.

## Références aux en-têtes
La description des références aux en-tête de message implémente l'interface 
<code>Maarch\Http\Description\RefInterface</code>.


## Représentations
La description des représentation de la ressource implémente l'interface 
<code>Maarch\Http\Description\HeaderRefInterface</code>.

    Representation extends Component : { 
        summary : string?,
        description : string?,

        id : string,
        name : string, 

        mediaType : string,
        element : string?,
        profile : string?,
    }

# Requête
La description des requêtes implémente l'interface 
<code>Maarch\Http\Description\RequestInterface</code>.

    Request extends Message : {
        summary : string?,
        description : string?,

        id : string,
        name : string,

        queryType : string?,
        queryParams : Ref*,

        headers : Ref*,

        representations : Representation*
    }

## Références aux paramètres de requête 
La description des références aux paramètres de chaîne de requête implémente 
l'interface 
<code>Maarch\Http\Description\RefInterface</code>.

# Réponse 
La description des réponses implémente l'interface 
<code>Maarch\Http\Description\ResponseInterface</code>.
    
    Request extends Message : {
        summary : string?,
        description : string?,
        
        id : string,
        name : string,

        status : int|Ref,

        headers : Ref*,

        representations : Representation*
    }

## Status de réponse
Le statut de réponse est soit un code de statut (entier), soit la référence à 
un composant Status.

# En-têtes
Les description d'en-têtes implémentent l'interface 
<code>Maarch\Http\Description\HeaderInterface</code>.

    Header extends Component : {
        summary : string?,
        description : string?,
        
        id : string,
        name : string,

        type : request|response|generic
    }

# Paramètres de chaîne de requête
Les description de paramètres de chaîne de requête implémentent l'interface 
<code>Maarch\Http\Description\QueryParamInterface</code>.

    QueryParam extends Component : {
        summary : string?,
        description : string?,
        
        id : string,
        name : string
    }

# Statut
Les description de statut de réponse implémentent l'interface 
<code>Maarch\Http\Description\StatusInterface</code>.

    Status extends Component : {
        summary : string?,
        description : string?,
        
        id : string,
        name : string,

        code : int,
        reasonPhrase : string?,
        detail : mixed?
    }