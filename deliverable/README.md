# MaarchCapture PDF Import Module

## Overview

MaarchCapture is a document capture and processing solution that integrates with MaarchCourrier. This deliverable focuses on the PDF import functionality, which allows for automated importing of PDF documents with their metadata into MaarchCourrier.

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Directory Structure](#directory-structure)
4. [XML Metadata Format](#xml-metadata-format)
5. [Running the Import Process](#running-the-import-process)
6. [Troubleshooting](#troubleshooting)
7. [Examples](#examples)

## Installation

### Prerequisites

- PHP 7.4 or higher
- MaarchCourrier instance with REST API enabled
- Required PHP extensions:
  - curl
  - xml
  - mbstring

### Installation Steps

1. Copy the MaarchCapture directory to your server (e.g., `/opt/maarch/MaarchCapture`)
2. Ensure proper permissions:
   ```bash
   chmod -R 755 /opt/maarch/MaarchCapture
   ```
3. Create required directories:
   ```bash
   mkdir -p /opt/maarch/MaarchCapture/files/TEST_IMPORT/backup
   mkdir -p /opt/maarch/MaarchCapture/files/TEST_IMPORT/failed
   chmod -R 777 /opt/maarch/MaarchCapture/files
   ```

## Configuration

The main configuration for the PDF import process is located in the script file:
`/opt/maarch/MaarchCapture/scripts/process_import_pdfs.php`

You need to modify the following parameters:

```php
// Configuration
$importDir = '/opt/maarch/MaarchCapture/files/TEST_IMPORT';
$backupDir = '/opt/maarch/MaarchCapture/files/TEST_IMPORT/backup';
$failedDir = '/opt/maarch/MaarchCapture/files/TEST_IMPORT/failed';
$logFile = '/opt/maarch/MaarchCapture/files/TEST_IMPORT/import_log.txt';

// MaarchCourrier REST API configuration
$wsUri = "http://ws_user:password@localhost/gedmaarch/rest";
$resourcesEndpoint = "/resources";
```

Adjust these values according to your environment, especially:
- The `$wsUri` should point to your MaarchCourrier REST API with proper credentials
- The `$importDir`, `$backupDir`, and `$failedDir` paths if you want to use different locations

## Directory Structure

The PDF import process uses the following directory structure:

- `TEST_IMPORT/`: Main directory where PDF files and their XML metadata files are placed for processing
- `TEST_IMPORT/backup/`: Successfully processed files are moved here
- `TEST_IMPORT/failed/`: Files that failed to process are moved here
- `TEST_IMPORT/import_log.txt`: Log file recording all import activities

## XML Metadata Format

Each PDF file must have a corresponding XML metadata file with the same name (but .xml extension). For example, if you have `document.pdf`, you need `document.xml` in the same directory.

### Required XML Structure

```xml
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
    <documentDate>2025-05-04</documentDate>
    <processLimitDate>2025-06-04</processLimitDate>
    <subject>Document Subject</subject>
    <chrono>MAARCH-REFERENCE</chrono>
    <senders>
        <sender>
            <id>15</id>
            <type>entity</type>
        </sender>
    </senders>
</document>
```

### XML Fields Explanation

| Field | Description | Default |
|-------|-------------|---------|
| modelId | Document model ID in MaarchCourrier | 1 |
| doctype | Document type ID | 108 |
| destination | Destination entity ID | 15 |
| priority | Priority level (1-3) | 2 |
| status | Document status | INIT |
| type | Document type (incoming, outgoing, internal) | incoming |
| typist | User ID of the typist | 1 |
| initiator | Entity ID of the initiator | 15 |
| documentDate | Document date (YYYY-MM-DD) | Current date |
| processLimitDate | Processing deadline (YYYY-MM-DD) | Current date + 30 days |
| subject | Document subject | "Imported Document [date]" |
| chrono | Chronological reference | "MAARCH-[timestamp]" |
| senders | List of sender entities | Entity ID 15 |

## Running the Import Process

To run the PDF import process:

```bash
cd /opt/maarch/MaarchCapture
php scripts/process_import_pdfs.php
```

You can automate this process using a cron job:

```bash
# Run the import process every 5 minutes
*/5 * * * * cd /opt/maarch/MaarchCapture && php scripts/process_import_pdfs.php >> /var/log/maarch_import.log 2>&1
```

## Troubleshooting

### Common Issues

1. **XML file not found**: Ensure the XML file has exactly the same name as the PDF file (with .xml extension)
2. **Failed to parse XML**: Check the XML structure against the required format
3. **API connection error**: Verify the MaarchCourrier REST API URL and credentials
4. **Permission issues**: Ensure proper file permissions on the import directories

### Checking Logs

The import process logs all activities to the `import_log.txt` file. Check this file for detailed information about any errors.

## Examples

See the `examples` directory for sample XML files that can be used as templates.
