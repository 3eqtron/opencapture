**Maarch Reflection**
=====================

Maarch Reflection fournit un jeu de composants de réflexion qui permet de faire
du *reverse-engineering* sur les composants développés par les concepteurs 
d'application.

Ces fonctions servent notamment de base pour les composants de description qui 
utilisent le PADL (PHP Application Definition Language), pour les messages Http, 
les entités du modèle métier ou encore les conteneurs de service.

> Pour de plus amples informations sur les bases de Reflection, se référer à la
documentation du langage PHP http://php.net/manual/book.reflection.php.

# Généralités
Les composants Maarch Reflection étendent les classes de l'extension PHP 
Reflection afin de leur ajouter des fonctionnalités complémentaires et palier à 
certains manques du langage PHP.

Il s'agit d'une part de méthodes complémentaires pour le *reverse-engineering*, 
qui permettent de compléter le jeu d'information sur les composants accessibles 
par réflexion, et d'autre part de méthodes de description de l'application
par la syntaxe PHP particulière nommée PADL, pour *PHP Application Description 
Language*.

Les fonctions de réflexion sont documentés dans le code des composants Maarch 
Reflection et des composants Maarch qui les étendent.

## Accès à PHP Reflection
Il est possible d'accéder à toutes les propriétés et méthodes des classes 
de base PHP Reflection au travers des classes étendues Maarch Reflection : 

  * ReflectionClass
  * ReflectionFunction
  * ReflectionMethod
  * ReflectionParameter
  * ReflectionProperty

## Classes ajoutées
L'extension Maarch introduit des objets complémentaires pour ajouter des 
fonctionnalités : 

  * ReflectionConstant fournit l'information sur les constantes de classe,
  * ReflectionDataType fournit l'information sur les types de données de base, 
    mais surtout étendus et personnalisés,
  * ReflectionDocComment fournit l'information sur les blocs de commentaire, 
    notamment les mots-clés qui permettent de compléter l'information standard 
    fournie par le code PHP.

### ReflectionDocComment 
La classe Maarch ReflectionDocComment rapporte des informations sur les blocs de 
commentaire.

Les blocs de commentaires sont placés directement au-dessus du code du 
composant commenté (classe, méthode, propriété, constante, fonction...).

Ils sont composés de 3 types d'information :

  * Le résumé
  * La description
  * Les mot-clés 

> Pour de plus amples informations à propos de la syntaxe des blocs de 
commentaires, voir le site https://phpdoc.org/docs/latest/index.html

Un object ReflectionDocComment est instancié lors de la construction de tous les 
objets Maarch Reflection lorsqu'un bloc de commentaire est présent dans le code 
du composant décrit.

#### Résumé
C'est un paragraphe de texte, habituellement court, qui fournit une introduction
pour les commentaires sur le composant documenté.
Il peut être utilisé comme un libellé ou un intitulé.

Il est constitué du premier paragraphe du bloc de commentaire, avant toute autre 
structure. 
Le premier paragraphe de texte sera toujours considéré comme le résumé lorsqu'il 
est présent.

#### Description
La description est un texte plus ou moins long, composé d'un ou plusieurs 
paragraphes, qui fournit l'information lisible par l'humain sur le composant 
documenté.

Il est constitué par l'ensemble des paragraphes qui suivent le résumé, 
immédiatement après une première ligne vide. 
La description se termine à la fin du bloc de commentaire ou à l'insertion 
d'un premier mot-clé.

#### Mots-clés
Les mot-clés fournissent l'information sur le composant documenté sous une forme 
exploitable par le code applicatif.

Il se présentent sous la forme de paires de clés et valeurs dans des lignes 
débutant par un symbole <code>@</code>.

La valeur d'un mot-clé peut être simple (une valeur scalaire), complexe avec 
plusieurs informations séparées par des espaces ou encadrées, et itérative 
lorsque le mot-clé est répété plusieurs fois dans le même bloc de commentaires.

    /** 
     * Intitulé du composant 
     * 
     * Description du composant 
     * ...
     * Suite de la description
     * 
     * @tag <valeur unitaire>
     * @tag2 <valeur> <description>
     * @tag3 <valeur1>
     * @tag3 <valeur2>
     */

### ReflectionAbstract 
La classe Maarch <code>ReflectionAbstract</code> fournit des fonctionnalités de 
base pour la plupart des composants Maarch Reflection.

#### getName 
La méthode <code>ReflectionAbstract::getName</code> retourne le nom du composant 
tel que présent dans la propriété <code>name</code> des classes PHP Reflection 
correspondantes.

#### getDocComment 
La méthode <code>ReflectionAbstract::getDocComment</code> retourne l'objet 
ReflectionDocComment associé au composant Maarch Reflection.

#### getReflection
La méthode <code>ReflectionAbstract::getReflection</code> retourne l'objet PHP 
Reflection associé au composant.


# Définition d'un schéma de données 
Maarch Reflection, étendu avec la syntaxe PADL, permet de définir un schéma de 
données qui utilise les concepts largement répandus dans toutes les notations 
et langages de définition des données (XSD, JSON-Schema, SQL DDL...).

Il s'agit notamment 
  
  * des classes / types de données complexes
  * des types de données simples, de base, étendus et personnalisés

## ReflectionClass 
La classe Maarch ReflectionClass implémente les méthodes de réflexion sur les 
schéma de données PADL, dont les types complexes sont décrits par des classes 
PHP.

### Propriété additionnelles
Le mot-clé <code>@additionnalProperties</code> du bloc de commentaires de la
classe indique que la classe autorise les propriétés non définies dans le 
modèle.

La méthode <code>ReflectionClass::allowsAdditionalProperties</code> indique si 
la classe autorise l'évaluation de propriétés non définies dans cette dernière.

### Evaluation en mode objet 
La méthode <code>ReflectionClass::setValues</code> permet de définir un ensemble
de propriétés pour un objet de la classe.

    public object setValues( mixed $source, [ object $target = null] )

Toutes les données présentes dans la source seront reportées dans l'objet cible, 
indépendamment de l'existeance ou de la visibilité des propriétés 
correspondantes dans le modèle de données.

#### Paramètres
Le paramètre *source* fournit le jeu de données à définir dans l'objet cible
sous la forme d'un objet ou d'un tableau associatif dont les clés fournissent 
le nom des propriétés à définir.

La paramètre *target* fournit un objet de la classe cible pour lequels les 
propriétés doivent être définies à partir des données source. 
Si ce paramètre est omis, la méthode construira un nouvel objet de la classe 
cible sans utilisation de la méthode de construction.

#### Retour 
La méthode renvoie l'objet cible mis à jour avec les données fournies dans le 
paramètre *source*. Il peut s'agir de l'objet passé en second argument ou d'un 
nouvel objet instancié par la méthode.

### Validation de l'objet
La méthode <code>ReflectionClass::validate</code> permet de valider la 
conformité d'un objet de la classe par rapport au modèle de données défini.

    public void validate( object $object );

La méthode reçoit en paramètre une instance de l'objet à valider.

La méthode valide chacune des propriétés de l'objet par rapport à la définition
en faisant appel à l'objet ReflectionProperty lorsque la propriété est définie.
Pour les propriétés non définies, la méthode valide que la classe autorise les 
propriétés additionnelles.

En cas de non conformité, la méthode lève une exception de la classe 
<code>ReflectionException</code> qui indique la nature de l'erreur et le 
contexte de données : nom de la propriété, nom de la classe, facette en erreur, 
etc.


## ReflectionProperty
La classe Maarch ReflectionProperty implémente les méthodes de réflexion sur les 
propriétés de classes de données PADL.

### Propriété requise
Le mot-clé <code>@required</code> du bloc de commentaires de la propriété 
indique que celle-ci doit être évaluée et n'accepte pas la valeur *null*.

La méthode <code>ReflectionProperty::isRequired</code> indique si la propriété
doit être évaluée et non nulle.

### Propriété en lecture seule
Le mot-clé <code>@readonly</code> du bloc de commentaires de la propriété 
indique que celle-ci est en lecture seule.
Cela signifie qu'elle ne peut être mise à jour que par une méthode de définition
(aussi appelée *setter*) ou lors de l'appel à la méthode de construction de 
l'objet qui contient la donnée.

La méthode <code>ReflectionProperty::isReadonly</code> indique si la propriété
est en lecture seule.

### Type de données 
Le mot-clé <code>@var</code> du bloc de commentaires de la propriété indique la 
représentation PHP de la valeur attendue. 
Elle sert de base pour la définition d'un type en ligne, qui peut étendre un 
type de base, PHP ou étendu.

La méthode <code>ReflectionProperty::getType</code> retourne un objet de la 
classe ReflectionDataType pour la définition du type de données.


### Validation de la propriété
La méthode <code>ReflectionProperty::validate</code> permet de valider la 
conformité d'une valeur de propriété par rapport au modèle de données défini.

    public void validate( mixed $value );

La méthode reçoit en paramètre une valeur à valider.

La méthode valide la valeur par rapport à la définition de son type de données
en faisant appel à l'objet ReflectionDataType lorsque celui-ci est défini.
Si la valeur passée est nulle, la méthode valide que la propriété n'est pas 
requise.

En cas de non conformité, la méthode lève une exception de la classe 
<code>ReflectionException</code> qui indique la nature de l'erreur et le 
contexte de données : nom de la propriété, nom de la classe, facette en erreur, 
etc.


## ReflectionDataType 
La classe Maarch ReflectionDataType implémente les méthodes de réflexion sur les 
types de données des propriétés de classes de données PADL.

### Type de base PHP 
Le mot-clé <code>@var</code> du bloc de commentaires de la propriété ou de la  
définition de type étendu indique la représentation PHP de la valeur attendue.

Il peut s'agir d'un type PHP de base ou d'un nom de classe.

La méthode <code>ReflectionDataType::getName</code> retourne le nom du type PHP 
qui sera utilisé pour la représentation des données.

La méthode <code>ReflectionDataType::isBuiltIn</code> indique si le type de base
est un type PHP de base :

  * string
  * bool
  * int
  * float
  * object 
  * array 
  * resource 
  * null 

### Type itérateur
Si le nom du type dans le motè-clé <code>@var</code> est suivi des caractères 
<code>[]</code>, il indique un tableau de valeurs du type fourni.
Par exemple <code>@var string[]</code> indique un tableau de chaînes de 
caractères et <code>@var DateTime[]</code> indique un tableau d'objets de la 
classe DateTime.

La méthode <code>ReflectionDataType::isArray</code> indique si le type de
données est fourni pour les éléments d'un tableau.

La méthode <code>ReflectionDataType::getItemType</code> retourne un objet de 
classe ReflectionDataType pour le type de données des éléments du tableau. 
Par exemple <code>@var string[]</code> retournera un objet qui aura les 
caractéristiques de <code>@var string</code> pour les éléments.

> Le type <code>array</code> est particulier: il indique un tableau sans fournir
le type de données de l'élément, ce qui indique que les éléments peuvent être 
de tout type. La méthode retournera donc *true* sans pour autant permettre de
recumérer l'objet ReflectionDataType des éléments via la méthode 
<code>ReflectionDataType::getItemType</code>.

### Type complexe
La méthode <code>ReflectionDataType::isClass</code> indique si le type de
données est une classe.

La méthode <code>ReflectionDataType::getClass</code> retourne un objet de la 
classe ReflectionClass.

### Extension
Le mot-clé <code>@base</code> du bloc de commentaires de la propriété ou de la  
définition de type étendu indique l'identifiant d'une définition de type de 
données étendu servant de base au type défini localement.

Deux modes d'identification sont possibles :
  * S'il s'agit d'un type étendu Maarch, seul le nom est fourni car les 
  définitions sont regroupées dans une seule classe de base interne Maarch. 
  Par exemple : date, datetime, positiveInteger, email. 
  * S'il s'agit d'un type utilisateur, on fournit un nom qualifié de propriété 
  sous la forme <code>vendor\namespace\class::$property</code>. Le type de 
  données étendu est alors défini par une propriété de classe du modèle, qui 
  apporte sa propre définition.

La méthode <code>ReflectionDataType::getBaseType</code> retourne un objet de 
classe ReflectionDataType pour le type de données de base.

### Facettes de restriction
Les facettes fournissent des restrictions et des précisions sur le type de 
données
dans des balises de bloc de commentaire de la propriété.

#### Communes

  * <code>@format</code> : format pré-défini (pattern)
  * <code>@enum</code> : liste de valeurs autorisées

#### Chaînes de caractère 

  * <code>@minLength</code> : nombre entier indiquant la longueur minimale de la
   chaîne de caractères.
  * <code>@maxLength</code> : nombre entier indiquant la longueur maximale de la
   chaîne de caractères.
  * <code>@pattern</code> : masque PCRE indiquant le format de la chaîne de 
  caractères.

#### Nombres entiers et à virgule flottante

  * <code>@minInclusive</code> : nombre indiquant la valeur minimale inclue du 
  nombre.
  * <code>@minExclusive</code> : nombre indiquant la valeur maximale inclue du 
  nombre.
  * <code>@maxInclusive</code> : nombre indiquant la valeur minimale exclue du 
  nombre.
  * <code>@maxExclusive</code> : nombre indiquant la valeur maximale exclue du 
  nombre.

#### Nombres à virgule flottante
  
  * <code>@scale</code> : nombre entier indiquant le nombre total de chiffres 
  significatifs.
  * <code>@precision</code> : nombre entier indiquant le nombre de positions 
  décimales.

#### Tableaux
  
  * <code>@minItems</code> : nombre entier indiquant le nombre minimal 
  d'éléments dans le tableau.
  * <code>@maxItems</code> : nombre entier indiquant le nombre maximal 
  d'éléments dans le tableau.
  * <code>@uniqueItems</code> : indicateur d'unicité des valeurs d'éléments 
  dans le tableau.

### Transtypage des données
La méthode <code>ReflectionDataType::cast</code> permet de jongler avec les 
valeurs et définitions de types.

    public mixed cast( mixed $value )

Elle permet de convertir les valeurs vers le type de représentation PHP indiqué
au regard du mot-clé <code>@var</code>, soit en type PHP scalaire, soit en 
objet, soit en tableau.

Elle reçoit en paramètre la valeur source à convertir vers le type de données 
représenté.

Elle retourne la valeur convertie, ou lève une exception de la classe 
<code>ReflectionException</code> si la conversion est impossible.

### Validation de données
La méthode <code>ReflectionDataType::validate</code> valide une donnée par
rapport à la définition du type.

    public void validate( mixed $value )

La méthode reçoit en paramètre une valeur à valider.

La méthode valide la valeur par rapport à la définition de son type de données.

En cas de non conformité, la méthode lève une exception de la classe 
<code>ReflectionException</code> qui indique la nature de l'erreur et le 
contexte de données.
