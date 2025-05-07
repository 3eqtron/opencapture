# MaarchCapture PDF Import - User Guide

This guide explains how to use the MaarchCapture PDF import functionality to automatically import documents into MaarchCourrier.

## Overview

The PDF import process allows you to:
- Automatically import PDF documents into MaarchCourrier
- Include metadata with each document (sender, type, subject, etc.)
- Process documents in batches
- Track the import status through logs

## How to Import Documents

### Step 1: Prepare Your Documents

1. Gather the PDF files you want to import
2. For each PDF file, create a corresponding XML metadata file with the same name:
   - For `invoice.pdf`, create `invoice.xml`
   - For `contract.pdf`, create `contract.xml`

### Step 2: Create XML Metadata Files

Each XML file must follow this structure:

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
    <subject>Your Document Subject</subject>
    <chrono>MAARCH-REFERENCE</chrono>
    <senders>
        <sender>
            <id>15</id>
            <type>entity</type>
        </sender>
    </senders>
</document>
```

Important fields to customize:
- `subject`: A descriptive title for your document
- `chrono`: A unique reference number
- `documentDate`: The date of the document (YYYY-MM-DD format)
- `doctype`: The document type ID (see your administrator for correct values)

### Step 3: Place Files in the Import Directory

1. Copy both the PDF file and its XML metadata file to the import directory:
   ```
   /opt/maarch/MaarchCapture/files/TEST_IMPORT/
   ```

2. The system will automatically process these files (typically every 5 minutes)

### Step 4: Check Import Status

After processing, files will be moved to one of two directories:
- `backup/`: Successfully imported files
- `failed/`: Files that failed to import

You can check the import log for details:
```
/opt/maarch/MaarchCapture/files/TEST_IMPORT/import_log.txt
```

## Common Issues and Solutions

### XML File Not Found
- Ensure the XML file has exactly the same name as the PDF file (only the extension differs)
- Check that both files are in the correct import directory

### Metadata Errors
- Verify that your XML file follows the correct structure
- Ensure all required fields are present
- Check that date fields use the YYYY-MM-DD format

### Document Already Exists
- Each document should have a unique reference (chrono)
- If a document with the same reference already exists, the import may fail

## Batch Processing Tips

For efficient batch processing:
1. Use a consistent naming convention for your files
2. Consider using a script to generate XML files for multiple PDFs
3. Process documents in manageable batches (50-100 at a time)
4. Check the import log regularly to identify and resolve any issues

## Need Help?

Contact your system administrator or refer to the technical documentation for more detailed information.
