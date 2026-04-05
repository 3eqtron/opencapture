<?php
/**
 * MaarchCapture Generic PDF Import Script
 * 
 * This script monitors the IMPORT_GENERIC directory for PDF files,
 * uses a single generic XML file for metadata, and integrates
 * all PDF documents into MaarchCourrier using the REST API.
 * 
 * Successful imports are backed up, and failed imports are
 * moved to a separate directory for troubleshooting.
 */

// Configuration
$importDir = '/opt/maarch/MaarchCapture/files/IMPORT_GENERIC';
$backupDir = '/opt/maarch/MaarchCapture/files/IMPORT_GENERIC/backup';
$failedDir = '/opt/maarch/MaarchCapture/files/IMPORT_GENERIC/failed';
$logFile = '/opt/maarch/MaarchCapture/files/IMPORT_GENERIC/import_log.txt';
$genericXmlFile = '/opt/maarch/MaarchCapture/files/IMPORT_GENERIC/generic_metadata.xml';

// MaarchCourrier REST API configuration
$wsUri = "http://ws_user:securepassword123@localhost/gedmaarch/rest";
$resourcesEndpoint = "/resources";

// Ensure directories exist
if (!is_dir($importDir)) {
    mkdir($importDir, 0777, true);
    logMessage("Created import directory: $importDir");
}

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
    logMessage("Created backup directory: $backupDir");
}

if (!is_dir($failedDir)) {
    mkdir($failedDir, 0777, true);
    logMessage("Created failed directory: $failedDir");
}

// Check if generic XML file exists
if (!file_exists($genericXmlFile)) {
    logMessage("ERROR: Generic XML metadata file not found at $genericXmlFile");
    logMessage("Creating a default generic XML file...");
    createDefaultGenericXml($genericXmlFile);
    logMessage("Default generic XML file created. Please review and modify as needed.");
}

// Log start of processing
logMessage("Starting generic PDF import process");

// Get all PDF files in the import directory
$pdfFiles = glob("$importDir/*.pdf");
logMessage("Found " . count($pdfFiles) . " PDF files to process");

// Read metadata from generic XML file
$genericMetadata = readMetadataFromXml($genericXmlFile);
if ($genericMetadata === false) {
    logMessage("ERROR: Failed to parse metadata from generic XML file");
    exit(1);
}

// Process each PDF file
foreach ($pdfFiles as $pdfFile) {
    $filename = basename($pdfFile);
    
    logMessage("Processing file: $filename");
    
    // Create document-specific metadata based on generic metadata
    $metadata = $genericMetadata;
    
    // Customize metadata for this specific document
    $metadata['subject'] = $metadata['subject'] . ' - ' . $filename;
    $metadata['chrono'] = 'MAARCH-' . date('YmdHis') . '-' . pathinfo($filename, PATHINFO_FILENAME);
    
    // Attempt to integrate the PDF into MaarchCourrier
    $result = integrateIntoMaarchCourrier($pdfFile, $metadata);
    
    if ($result['success']) {
        logMessage("SUCCESS: Integrated $filename into MaarchCourrier with ID: " . $result['resId']);
        moveToBackup($pdfFile, $backupDir);
    } else {
        logMessage("ERROR: Failed to integrate $filename: " . $result['error']);
        moveToFailed($pdfFile, $failedDir);
    }
}

logMessage("Generic PDF import process completed");

/**
 * Create a default generic XML file
 * 
 * @param string $xmlFilePath Path where the XML file should be created
 * @return bool Success status
 */
function createDefaultGenericXml($xmlFilePath) {
    $defaultXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<document>
    <modelId>1</modelId>
    <doctype>108</doctype>
    <destination>15</destination>
    <priority>2</priority>
    <status>INIT</status>
    <type>incoming</type>
    <typist>1</typist>
    <initiator>15</initiator>
    <documentDate>2025-05-07</documentDate>
    <processLimitDate>2025-06-07</processLimitDate>
    <subject>Generic Imported Document</subject>
    <chrono>MAARCH-GENERIC</chrono>
    <senders>
        <sender>
            <id>15</id>
            <type>entity</type>
        </sender>
    </senders>
</document>
XML;

    return file_put_contents($xmlFilePath, $defaultXml) !== false;
}

/**
 * Read metadata from XML file
 * 
 * @param string $xmlFile Path to the XML file
 * @return array|false Metadata array or false on failure
 */
function readMetadataFromXml($xmlFile) {
    try {
        $xml = simplexml_load_file($xmlFile);
        if ($xml === false) {
            return false;
        }
        
        // Default values
        $metadata = [
            'modelId' => 1,
            'doctype' => 108,
            'destination' => 15,
            'priority' => 2,
            'status' => 'INIT',
            'type' => 'incoming',
            'typist' => 1,
            'initiator' => 15,
            'documentDate' => date('Y-m-d'),
            'processLimitDate' => date('Y-m-d', strtotime('+30 days')),
            'subject' => 'Generic Imported Document ' . date('Y-m-d H:i:s'),
            'chrono' => 'MAARCH-' . date('YmdHis'),
            'senders' => [
                [
                    'id' => 15,
                    'type' => 'entity'
                ]
            ]
        ];
        
        // Override with values from XML if they exist
        if (isset($xml->modelId)) $metadata['modelId'] = (int)$xml->modelId;
        if (isset($xml->doctype)) $metadata['doctype'] = (int)$xml->doctype;
        if (isset($xml->destination)) $metadata['destination'] = (int)$xml->destination;
        if (isset($xml->priority)) $metadata['priority'] = (int)$xml->priority;
        if (isset($xml->status)) $metadata['status'] = (string)$xml->status;
        if (isset($xml->type)) $metadata['type'] = (string)$xml->type;
        if (isset($xml->typist)) $metadata['typist'] = (int)$xml->typist;
        if (isset($xml->initiator)) $metadata['initiator'] = (int)$xml->initiator;
        if (isset($xml->documentDate)) $metadata['documentDate'] = (string)$xml->documentDate;
        if (isset($xml->processLimitDate)) $metadata['processLimitDate'] = (string)$xml->processLimitDate;
        if (isset($xml->subject)) $metadata['subject'] = (string)$xml->subject;
        if (isset($xml->chrono)) $metadata['chrono'] = (string)$xml->chrono;
        
        // Handle senders if defined in XML
        if (isset($xml->senders->sender)) {
            $metadata['senders'] = [];
            foreach ($xml->senders->sender as $sender) {
                $metadata['senders'][] = [
                    'id' => (int)$sender->id,
                    'type' => (string)$sender->type
                ];
            }
        }
        
        return $metadata;
    } catch (Exception $e) {
        logMessage("Exception reading XML: " . $e->getMessage());
        return false;
    }
}

/**
 * Integrate PDF into MaarchCourrier using REST API
 * 
 * @param string $pdfFile Path to the PDF file
 * @param array $metadata Document metadata
 * @return array Result with success status and message
 */
function integrateIntoMaarchCourrier($pdfFile, $metadata) {
    global $wsUri, $resourcesEndpoint;
    
    try {
        // Encode the PDF file
        $encodedFile = base64_encode(file_get_contents($pdfFile));
        
        // Add the encoded file to the metadata
        $metadata['encodedFile'] = $encodedFile;
        $metadata['format'] = 'pdf';
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set cURL options
        $fullUrl = $wsUri . $resourcesEndpoint;
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($metadata));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // Execute cURL request
        $response = curl_exec($ch);
        
        // Check for errors
        if (curl_errno($ch)) {
            $error = "cURL Error: " . curl_error($ch);
            curl_close($ch);
            return ['success' => false, 'error' => $error];
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            
            if (isset($responseData['resId'])) {
                return [
                    'success' => true,
                    'resId' => $responseData['resId'],
                    'response' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "No resource ID returned from API",
                    'response' => $responseData
                ];
            }
        } else {
            return [
                'success' => false,
                'error' => "HTTP Error $httpCode: $response"
            ];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => "Exception: " . $e->getMessage()];
    }
}

/**
 * Move a file to the backup directory
 * 
 * @param string $file Path to the file
 * @param string $backupDir Backup directory path
 * @return bool Success status
 */
function moveToBackup($file, $backupDir) {
    $filename = basename($file);
    $timestamp = date('YmdHis');
    
    // Create date-based directory (format: DDMMYYYY)
    $dateDir = date('dmY');
    $dateDirPath = "$backupDir/$dateDir";
    
    // Create the directory if it doesn't exist
    if (!is_dir($dateDirPath)) {
        if (!mkdir($dateDirPath, 0777, true)) {
            logMessage("Failed to create directory: $dateDirPath");
            return false;
        }
        logMessage("Created date directory: $dateDirPath");
    }
    
    $backupPath = "$dateDirPath/{$timestamp}_{$filename}";
    
    if (copy($file, $backupPath)) {
        unlink($file);
        logMessage("Moved file to backup: $backupPath");
        return true;
    } else {
        logMessage("Failed to move file to backup: $file");
        return false;
    }
}

/**
 * Move a file to the failed directory
 * 
 * @param string $file Path to the file
 * @param string $failedDir Failed directory path
 * @return bool Success status
 */
function moveToFailed($file, $failedDir) {
    $filename = basename($file);
    $timestamp = date('YmdHis');
    
    // Create date-based directory (format: DDMMYYYY)
    $dateDir = date('dmY');
    $dateDirPath = "$failedDir/$dateDir";
    
    // Create the directory if it doesn't exist
    if (!is_dir($dateDirPath)) {
        if (!mkdir($dateDirPath, 0777, true)) {
            logMessage("Failed to create directory: $dateDirPath");
            return false;
        }
        logMessage("Created date directory: $dateDirPath");
    }
    
    $failedPath = "$dateDirPath/{$timestamp}_{$filename}";
    
    if (copy($file, $failedPath)) {
        unlink($file);
        logMessage("Moved file to failed: $failedPath");
        return true;
    } else {
        logMessage("Failed to move file to failed directory: $file");
        return false;
    }
}

/**
 * Log a message to the log file
 * 
 * @param string $message Message to log
 */
function logMessage($message) {
    global $logFile;
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    
    // Output to console
    echo $logEntry;
    
    // Write to log file
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
?>
