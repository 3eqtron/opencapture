#!/bin/bash

# Chemin de maarch_capture
MAARCH_CAPTURE_PATH='/produit/maarch/maarch_scripts/maarch_capture/'

# Nom de votre fichier xml de capture (ex : Capture.xml)
CAPTURE_CONFIG_NAME='Capture_miviludes'

# Nom du batch à reprendre
BATCH_NAME_TARGET='MAIL_miviludes_1'

# Chemin des fichiers générés de march_capture
FILES_TO_SCAN_PATH='/exploit/maarch/maarch_capture/files/miviludes/'

# Date à laquelle commencer la reprise
START_DATE='2018-06-27'

cd $MAARCH_CAPTURE_PATH

# Recherche via la command find :
# des dossiers => -type d
# n'incluant pas les sous-dossier => -maxdepth 1
# plus récent que la date spécifié => -newermt $START_DATE
# affiche uniquement le nom du dossier => -printf '%f\n'
# filtre sur les dossiers générés avec le batch cible => grep -P "^B$BATCH_NAME_TARGET"
nbfolders="$(find $FILES_TO_SCAN_PATH -maxdepth 1 -type d -newermt $START_DATE -printf '%f\n'| grep -P "^B$BATCH_NAME_TARGET" | wc -l)";
echo ''
read -p "$nbfolders dossiers seront analysés (dossier datant du $START_DATE à aujourdhui), continuer ? (y/n) " -n 1 -r
echo ''

if [[ $REPLY =~ ^[Yy]$ ]]
then
    echo ''
    read -p "Voulez-vous faire une sauvegarde des dossiers traités ? (y/n) : " -e BACKUP_FOLDER
    echo ''
    if [[ $BACKUP_FOLDER =~ ^[Yy]$ ]]
    then
        mkdir -p $MAARCH_CAPTURE_PATH/backups_restore/
    fi

    echo "" >  result.log
    echo "Script de restauration en cours de traitement ..."
    if dirs="$(find $FILES_TO_SCAN_PATH -maxdepth 1 -type d -newermt $START_DATE -printf '%f\n'| grep -P "^B$BATCH_NAME_TARGET")"; then
        for dir in ${dirs}; do
            if [[ $BACKUP_FOLDER =~ ^[Yy]$ ]]
            then
                cp -Rf $FILES_TO_SCAN_PATH/$dir $MAARCH_CAPTURE_PATH/backups_restore/
            fi
            echo "Analyse du batch $dir ..."
            php MaarchCapture.php continue -ConfigName $CAPTURE_CONFIG_NAME -BatchId $dir -BatchName $BATCH_NAME_TARGET >> result.log
        done
    fi
    echo "Terminé ! Vous pouvez consulter les logs: $MAARCH_CAPTURE_PATH/result.log"
    if [[ $BACKUP_FOLDER =~ ^[Yy]$ ]]
    then
        echo "(Les fichiers d'origine ont été copiés dans $MAARCH_CAPTURE_PATH/backups_restore/)"
    fi
fi