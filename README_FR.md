# MaarchCapture

## À propos
MaarchCapture est une solution de capture et de traitement de documents qui permet d'automatiser la numérisation, l'importation et le traitement des documents. Cette solution s'intègre parfaitement avec MaarchCourrier pour une gestion complète des documents électroniques.

## Version
`1.9`

## Prérequis

### Système
- Serveur Linux (Debian/Ubuntu recommandé)
- PHP 7.4 ou supérieur
- Base de données (MySQL/MariaDB)
- Serveur web (Apache/Nginx)

### Modules PHP requis
- php-xml
- php-mbstring
- php-curl
- php-gd
- php-zip
- php-mysql
- php-imap (pour la capture d'emails)

### Logiciels tiers
- Tesseract OCR (pour la reconnaissance optique de caractères)
- ImageMagick (pour la manipulation d'images)
- Ghostscript (pour le traitement des PDF)
- Xpdf (pour l'analyse des PDF)

## Installation

```bash
# Cloner le dépôt
git clone -b 1.9 https://labs.maarch.org/maarch/MaarchCapture

# Se positionner dans le répertoire
cd MaarchCapture

# Installer les dépendances via Composer
composer install

# Configurer les permissions
chmod -R 775 files/
chmod -R 775 logs/
```

## Configuration

Les fichiers de configuration principaux se trouvent dans le répertoire `/config`. Vous devrez configurer :

1. Les paramètres de connexion à la base de données
2. Les paramètres de connexion à MaarchCourrier (si intégration)
3. Les répertoires de travail pour l'importation et le traitement des documents

## Structure des répertoires

- `/class` : Classes principales du système
- `/config` : Fichiers de configuration
- `/modules` : Modules fonctionnels (OCR, séparation, analyse PDF, etc.)
- `/scripts` : Scripts d'automatisation et de traitement
- `/tools` : Outils et bibliothèques
- `/files` : Répertoire de stockage des fichiers traités
- `/logs` : Journaux d'activité

## Commandes principales

### Initialisation d'un traitement par lot

```bash
php MaarchCapture.php init [NomDuLot] [NomDuWorkflow] [Arguments...]
```

### Importation de fichiers PDF

```bash
php scripts/process_import_pdfs_generic.php
```
Ce script surveille le répertoire `/files/IMPORT_GENERIC` pour les fichiers PDF à importer.

### Capture d'emails

```bash
php MaarchCapture.php init EmailCapture MailCapture
```

### Numérisation vers MaarchCapture

```bash
./scripts/MAARCH_SCAN_TO_MC.sh
```

## Modules disponibles

- **BarcodeOCR** : Reconnaissance des codes-barres
- **Classifier** : Classification automatique des documents
- **MailCapture** : Capture des emails
- **PDFAnalyzer** : Analyse des PDF
- **PDFExtractor** : Extraction de contenu des PDF
- **PDFSpliter** : Division des PDF
- **QRSeparator** : Séparation par QR code
- **TesseractOCR** : OCR via Tesseract
- **Separator** : Séparation de documents
- **MaarchWSClient** : Client pour l'API MaarchCourrier

## Intégration avec MaarchCourrier

MaarchCapture peut s'intégrer avec MaarchCourrier via son API REST. Pour configurer cette intégration :

1. Modifier le fichier de configuration `/config/MaarchWSClient.xml`
2. Définir l'URL, le nom d'utilisateur et le mot de passe pour l'API
3. Configurer les correspondances entre les métadonnées capturées et les champs MaarchCourrier

## Dépannage

Les journaux d'activité se trouvent dans le répertoire `/logs`. En cas de problème :

1. Vérifier les permissions des répertoires
2. Consulter les journaux d'erreurs
3. Vérifier la configuration des modules
4. S'assurer que les prérequis système sont correctement installés

## Support et documentation

Pour plus d'informations, consultez la documentation officielle :
[Documentation Maarch](http://docs.maarch.org/MaarchCapture/)
