# XML Metadata Field Reference

This document provides a detailed reference for all fields that can be included in the XML metadata files for the MaarchCapture PDF import process.

## Core Fields

| Field | Type | Required | Description | Default Value |
|-------|------|----------|-------------|---------------|
| modelId | Integer | Yes | Document model ID in MaarchCourrier | 1 |
| doctype | Integer | Yes | Document type ID | 108 |
| destination | Integer | Yes | Destination entity ID | 15 |
| priority | Integer | Yes | Priority level (1=high, 2=normal, 3=low) | 2 |
| status | String | Yes | Document status | "INIT" |
| type | String | Yes | Document type | "incoming" |
| typist | Integer | Yes | User ID of the typist | 1 |
| initiator | Integer | Yes | Entity ID of the initiator | 15 |
| documentDate | Date | Yes | Document date (YYYY-MM-DD) | Current date |
| processLimitDate | Date | Yes | Processing deadline (YYYY-MM-DD) | Current date + 30 days |
| subject | String | Yes | Document subject | "Imported Document [date]" |
| chrono | String | Yes | Chronological reference | "MAARCH-[timestamp]" |

## Sender Information

The `senders` element can contain multiple `sender` elements, each with the following structure:

| Field | Type | Required | Description | Default Value |
|-------|------|----------|-------------|---------------|
| id | Integer | Yes | Entity or contact ID | 15 |
| type | String | Yes | Type of sender ("entity" or "contact") | "entity" |

## MaarchCourrier Entity and Document Type IDs

When deploying to a client, you'll need to adjust the IDs to match their MaarchCourrier configuration. Here are common entity and document type IDs you might need to update:

### Entity IDs
These IDs refer to departments or organizational units in MaarchCourrier:

- Check the client's MaarchCourrier administration panel for the correct entity IDs
- The default value of 15 should be replaced with an actual entity ID from the client's system

### Document Type IDs
These IDs refer to the types of documents in MaarchCourrier:

- Common document types include:
  - 102: Contract
  - 103: Incoming Mail
  - 104: Outgoing Mail
  - 105: Invoice
  - 106: Receipt
  - 107: Internal Note
  - 108: General Document
- Check the client's MaarchCourrier administration panel for the correct document type IDs

## Example: Complete XML with All Fields

```xml
<?xml version="1.0" encoding="UTF-8"?>
<document>
    <!-- Core Document Information -->
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
    <subject>Invoice #12345 - Company XYZ</subject>
    <chrono>MAARCH-INV12345</chrono>
    
    <!-- Multiple Senders Example -->
    <senders>
        <sender>
            <id>15</id>
            <type>entity</type>
        </sender>
        <sender>
            <id>16</id>
            <type>entity</type>
        </sender>
    </senders>
</document>
```

## Validation

To ensure your XML files are properly formatted, you can validate them against this structure. Common issues include:

1. Missing required fields
2. Incorrect data types (e.g., text in numeric fields)
3. Invalid date formats (must be YYYY-MM-DD)
4. XML syntax errors

We recommend testing your XML files with a few sample documents before deploying the solution in production.
