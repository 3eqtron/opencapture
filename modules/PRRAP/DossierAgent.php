<?php
/**
 * Copyright (C) 2015 Maarch
 *
 * This file is part of Maarch Capture for PRRAP
 *
 * Maarch Capture is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Dependency xml is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Maarch Capture.  If not, see <http://www.gnu.org/licenses/>.
 */
class DossierAgent
{
    /**
     * Import files
     * @param string $directory
     * 
     * @return bool
     */
    public function importFiles($directory) 
    { 
        $batch = $_SESSION['capture']->Batch;

        $_SESSION['capture']->logEvent("Scanning directory $directory for file import...");
       
        /********************************************************************************
        ** Open Directory and import files to batch on new nodes
        ********************************************************************************/
        if (!is_dir($directory)) {
            $_SESSION['capture']->logEvent("Unable to open directory '$directory' !", 2);
            trigger_error("Unable to open directory '$directory' !", E_USER_ERROR);
        }

        
        // Recherche d'un dossier
        foreach (scandir($directory) as $batchDirname) {
            $batchDirpath = $directory  . DIRECTORY_SEPARATOR . $batchDirname;
            
            if (!is_dir($batchDirpath) || $batchDirname == '.' || $batchDirname == '..' || substr($batchDirname, 0, 2) == '__') {
                continue;
            }

            $documents = 0;

            //DIA_operateur_wkstation_date_time
            list($date, $time) = explode('_', $batchDirname);

            $batch->documentElement->setAttribute('dirname', $batchDirname);
            
            $isodate = sprintf('%s-%s-%sT%s:%s:%s', substr($date, 0, 4), substr($date, 4, 2), substr($date, 6, 2), substr($time, 0, 2), substr($time, 2, 2), substr($time, 4, 2));
            $batch->documentElement->setAttribute('date', $isodate);

            // Recherche d'un dossier
            foreach (scandir($batchDirpath) as $filename) {
                $filepath = $batchDirpath  . DIRECTORY_SEPARATOR . $filename;
                
                if (is_dir($filepath) || $filename == '.' || $filename == '..') {
                    continue;
                }         
                
                /* If extensions filtered, check extension
                ********************************************************************************/
                if (substr($filename, -4) !== '.pdf') {
                    continue;
                }

                $_SESSION['capture']->logEvent("Adding new document with source '$filepath'");
                
                $document = $batch->addDocument($filepath);

                $nameparts = explode('_', $filename);
                if (count($nameparts) >= 3) {
                    list($dia, $operateur, $station) = $nameparts;
                    $batch->documentElement->setAttribute('operateur', $operateur);
                    $batch->documentElement->setAttribute('station', $station);
                }

                $documents++;
            }

            rename($batchDirpath, $directory . DIRECTORY_SEPARATOR . '__' . $batchDirname);

            //process one batch at a time
            break;
        }

        return $documents;
    }

    /**
     * Get the full text from PDF documents
     * @param DOMNodeList $documents
     */
    public function getFullText($documents)
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'PdfParser.php';

        foreach ($documents as $document) {
            $_SESSION['capture']->logEvent("Extracting fulltext from document 'document->id'");
            
            $path = $document->path;

            $text = PdfParser::parseFile($path);

            $corr = [
                '/[^[:print:]]/' => '',
                '/\^/' => '-',
                '/;/' => ':',
                '/"/' => '°',
                '/XH/' => 'XM',
                '/XMl/' => 'XM1',
            ];

            $text = preg_replace(array_keys($corr), array_values($corr), $text);

            file_put_contents($path . '.txt', $text);
        }
    }

    /**
     * Get the indexes
     * @param DOMNodeList $documents
     * @param array       $patterns
     */
    public function getIndexes($documents, $patterns)
    {
        foreach ($documents as $document) {
            $_SESSION['capture']->logEvent("Extracting indexes from document '$document->id'");
            
            $path = $document->path;
            $text = file_get_contents($path . '.txt');

            // Recherche des groupes de chiffres
            $numGroups = [];
            preg_match_all('/\d+/', $text, $numGroups, PREG_OFFSET_CAPTURE, PREG_SET_ORDER);
            $numGroups = $numGroups[0];

            $indexes = [
                'numOrdre' => null,
                'numIdentification' => null,
                'numFiche' => null,
                'matricule' => null,
                'nom' => null,
                'dateDeNaissance' => null,
            ];
            
            for ($i=0, $l=count($numGroups); $i<$l; $i++) {
                $numGroupOffset = $numGroups[$i][1];
                // Elargissement de la fenêtre de recherche
                $numGroupText = substr($text, $numGroupOffset-5, 40);
                // Nettoyage simple
                $numGroupText = str_replace(['O', 'i', 'l', 'S', 'B'], ['0', '1', '1', '5', '8'], $numGroupText);

                if ($numGroupOffset < 200) {
                    // Numéro d'ordre 0000000
                    if (empty($indexes['numOrdre']) && $numGroupOffset) {
                        if (preg_match($patterns['numOrdre'], $numGroupText, $matches)) {
                            $indexes['numOrdre'] = trim($matches[0]);

                            continue;
                        }
                    }

                    // Numéro d'identification 00-00000-000000-0000000 avec séparateur non numérique
                    if (empty($indexes['numIdentification'])) {
                        if (preg_match($patterns['numIdentification'], $numGroupText, $matches)) {
                            //nettoyage spécifique : replacement des séparateurs
                            $indexes['numIdentification'] = preg_replace('/[^\d]+/', '-', $matches[0]);

                            continue;
                        }
                    }

                    // Numéro de fiche XM0000000
                    if (empty($indexes['numFiche'])) {
                        if (preg_match($patterns['numFiche'], $numGroupText, $matches)) {
                            $prefix = strtoupper($matches[1]);
                            $number = $matches[2];
                            $indexes['numFiche'] = $prefix . $number;

                            continue;
                        }
                    }
                } else {

                    if (empty($indexes['matricule'])) {
                        if (preg_match($patterns['matricule'], $numGroupText, $matches)) {
                            $number = strtoupper($matches[1]);
                            if (isset($indexes['numOrdre']) && $number == $indexes['numOrdre']) {
                                continue;
                            }
                            $suffix = '';
                            if (isset($matches[2])) {
                                $suffix = $matches[2];
                            }
                            if (ctype_digit($number)) {
                                $indexes['matricule'] = $number . $suffix;

                                continue;
                            }
                        }
                    }

                    if (empty($indexes['dateDeNaissance'])) {
                        if (preg_match($patterns['dateDeNaissance'], $numGroupText, $matches)) {
                            $dateDMY = strtoupper($matches[1]);

                            if (ctype_digit($dateDMY) && strlen($dateDMY) == 8) {
                                $y = substr($dateDMY, 4, 4);
                                $m = substr($dateDMY, 2, 2);
                                $d = substr($dateDMY, 0, 2);
                                if ($y < date('Y') && in_array($m, range(1, 12)) && in_array($d, range(1, 31))) {
                                    $indexes['dateDeNaissance'] = $y . '-' . $m . '-' . $d;

                                    continue;
                                }
                            }
                        }
                    }
                }
                
            }

            foreach ($indexes as $name => $value) {
                if (!empty($value)) {
                    $document->setMetadata($name, $value);
                }
            }
        }
    }

    /**
     * Get the refs from SI
     * @param DOMNodeList $documents
     * @param string      $dsn
     * @param string      $errordir
     */
    public function getRefs($documents, $dsn, $errordir)
    {
        $metanames = ['numOrdre', 'numIdentification', 'numFiche', 'matricule', 'dateDeNaissance'];

        $db = new PDO($dsn);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (!is_dir($errordir)) {
            mkdir($errordir, 0775, true);
        }

        foreach ($documents as $document) {
            $_SESSION['capture']->logEvent("Getting ref from database for document '$document->id'");
            
            $asserts = $indexes = [];

            foreach ($metanames as $metaname) {
                if ($metavalue = $document->getMetadata($metaname)) {
                    $indexes[$metaname] = $metavalue;
                }
            }

            if (count($indexes) < 2) {
                $_SESSION['capture']->logEvent("Not enough indexes to match with reference for document '$document->id'");
            
                $this->reject($document, $errordir, $indexes);

                continue;
            }

            foreach ($indexes as $name => $value) {
                foreach ($indexes as $name2 => $value2) {
                    if ($name == $name2) {
                        continue;
                    }
                    if (isset($asserts[$name . '-' . $name2]) || isset($asserts[$name2 . '-' . $name])) {
                        continue;
                    }

                    $asserts[$name . '-' . $name2] = sprintf('"%s" = \'%s\' AND "%s" = \'%s\'', $name, $value, $name2, $value2);
                }
            }

            $queryString = '(' . implode(') OR (', $asserts) . ')';
            
            $stmt = $db->query('SELECT * FROM "Recensement" WHERE ' . $queryString);

            if ($stmt && $line = $stmt->fetch(PDO::FETCH_ASSOC)) {
                foreach ($line as $name => $value) {
                    $document->setMetadata($name, $value);
                }
            } else {
                $_SESSION['capture']->logEvent("No reference found for document '$document->id'");
            
                $this->reject($document, $errordir, $indexes);
            }
        }
    }

    protected function reject($document, $errordir, $indexes)
    {
        $document->setAttribute('status', 'error');
        $outfilename = basename($document->getAttribute('resourcepath'));
        
        $batchId = $_SESSION['capture']->Batch->id;
        $errordir = $errordir . DIRECTORY_SEPARATOR . basename(dirname($document->getAttribute('resourcepath')));
        if (!is_dir($errordir)) {
            mkdir($errordir, 0775, true);
        }

        copy($document->path, $errordir . DIRECTORY_SEPARATOR . $outfilename);
        copy($document->path . '.txt', $errordir . DIRECTORY_SEPARATOR . $outfilename . '.txt');
        file_put_contents($errordir . DIRECTORY_SEPARATOR . $outfilename . '.idx', json_encode($indexes, JSON_PRETTY_PRINT)); 
    }

    /**
     * Get the medona:ArchiveTransfer document
     * @param DOMNodeList $documents
     * @param string      $template
     * @param string      $outdir
     */
    public function generateArchiveTransfer($documents, $template, $outdir)
    {
        $batch = $_SESSION['capture']->Batch;

        $outdir = $outdir . DIRECTORY_SEPARATOR . 'ArchiveTransfer.' . $batch->id;

        if (!is_dir($outdir)) {
            mkdir($outdir, 0775, true);
        }

        $_SESSION['capture']->logEvent("Creating the message envelop...");
        
        $archiveTransfer = new DOMDocument();
        $archiveTransfer->load($template);
        
        $archiveTransfer->getElementsByTagName('Date')->item(0)->nodeValue = $batch->date;
        $archiveTransfer->getElementsByTagName('MessageIdentifier')->item(0)->nodeValue = $batch->id;

        $binaryDataObjectTemplateElement = $archiveTransfer->getElementsByTagName('BinaryDataObject')->item(0);
        $archiveTemplateElement = $archiveTransfer->getElementsByTagNameNS('maarch.org:laabs:recordsManagement', 'archive')->item(0);

        foreach ($documents as $document) {
            if ($document->getAttribute('status') == 'error') {
                $_SESSION['capture']->logEvent("Document '$document->id' in error, skipping...");
            
                continue;
            }

            $_SESSION['capture']->logEvent("Adding document '$document->id' on message...");

            $outfilename = basename($document->getAttribute('resourcepath'));
            $outid = basename($document->getAttribute('resourcepath'), '.pdf');

            $binaryDataObjectElement = $binaryDataObjectTemplateElement->cloneNode(true);
            $binaryDataObjectElement->setAttribute('xml:id', $outid);
            $binaryDataObjectElement->getElementsByTagName('Attachment')->item(0)->setAttribute('filename', $outfilename);
            $binaryDataObjectElement->getElementsByTagName('Size')->item(0)->nodeValue = $document->getAttribute('size');
            $binaryDataObjectElement->getElementsByTagName('MessageDigest')->item(0)->nodeValue = md5_file($document->path);

            $binaryDataObjectTemplateElement->parentNode->insertBefore($binaryDataObjectElement, $binaryDataObjectTemplateElement);

            $archiveElement = $archiveTemplateElement->cloneNode(true);
            $archiveElement->getElementsByTagNameNS('maarch.org:laabs:recordsManagement', 'retentionStartDate')->item(0)->nodeValue = $document->getMetadata('dateDeNaissance');
            $archiveElement->getElementsByTagNameNS('maarch.org:laabs:documentManagement', 'document')->item(0)->setAttribute('oid', $outid);

            $description = $archiveElement->getElementsByTagNameNS('maarch.org:laabs:fonctionPublique', 'DossierAgent')->item(0);
            foreach ($document->getMetadata() as $metaElement) {
                $descriptionElement = $archiveTransfer->createElementNS('maarch.org:laabs:fonctionPublique', $metaElement->tagName, $metaElement->nodeValue);
                $description->appendChild($descriptionElement);
            }
            
            $archiveTemplateElement->parentNode->insertBefore($archiveElement, $archiveTemplateElement);

            copy($document->path, $outdir . DIRECTORY_SEPARATOR . $outfilename);
        }

        // Remove templates
        $binaryDataObjectTemplateElement->parentNode->removeChild($binaryDataObjectTemplateElement);
        $archiveTemplateElement->parentNode->removeChild($archiveTemplateElement);

        $_SESSION['capture']->logEvent("Saving the message...");
        
        $archiveTransfer->save($outdir . DIRECTORY_SEPARATOR . 'ArchiveTransfer.' . $batch->id . '.xml');
    }

    /**
     * Send a REST request 
     * @param string $url
     * @param string $auth
     * @param string $outdir
     *
     * @return array The response headers and body
     */
    public static function sendArchiveTransfer($url, $auth, $outdir)
    {
        
        $batch = $_SESSION['capture']->Batch;
        $outdir = $outdir . DIRECTORY_SEPARATOR . 'ArchiveTransfer.' . $batch->id;

        $url = $url . '/medona/ArchiveTransfer?'
            . 'messageFile=' . urlencode($outdir . DIRECTORY_SEPARATOR . 'ArchiveTransfer.' . $batch->id . '.xml')
            . '&attachments=' . urlencode($outdir);

        $header = [
            'Cookie:' . 'LAABS-AUTH='.urlencode($auth)
        ];

        $body = '';

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => $header,
                'ignore_errors' => true,
                'timeout' => 30
            )
        );

        $request = 'POST ' . $url . PHP_EOL 
            . implode(PHP_EOL, $header) . PHP_EOL 
            . $body;

        $_SESSION['capture']->logEvent("Create stream context on POST...");
        $context = stream_context_create($opts);

        $_SESSION['capture']->logEvent("Open socket to $url...");
        $socket = fopen($url, 'r', false, $context);        

        $_SESSION['capture']->logEvent("Getting response headers..."); 
        $metadata = stream_get_meta_data($socket)['wrapper_data'];
        $protocol = strtok($metadata[0], ' ');

        $responseCode = strtok(' ');
        $responseHeaders = [];
        while ($header = next($metadata)) {
            $responseHeaders[strtok($header, ':')] = trim(strtok(''));
        }

        $_SESSION['capture']->logEvent("Getting response contents...");
        $responseBody = stream_get_contents($socket);

        $_SESSION['capture']->logEvent("Transfer sending response code = " . $responseCode);

        if ($responseCode != 200) {
            $output = implode(PHP_EOL, $responseHeaders) . PHP_EOL . $responseBody;

            $_SESSION['capture']->sendError($output);
        }       

        $_SESSION['capture']->logEvent("Response body = " . $responseBody);
    }
}