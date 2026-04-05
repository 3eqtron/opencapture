# MaarchCapture Installation Guide

This guide provides step-by-step instructions for installing and configuring the MaarchCapture PDF import module at your client's site.

## System Requirements

- Linux server (Debian/Ubuntu recommended)
- PHP 7.4 or higher
- Apache or Nginx web server
- MaarchCourrier instance with REST API enabled
- Required PHP extensions:
  - php-curl
  - php-xml
  - php-mbstring
  - php-gd

## Installation Steps

### 1. Prepare the Environment

```bash
# Install required PHP extensions if not already installed
sudo apt-get update
sudo apt-get install -y php-curl php-xml php-mbstring php-gd

# Restart web server to apply changes
sudo systemctl restart apache2   # or nginx
```

### 2. Deploy MaarchCapture

```bash
# Create the installation directory
sudo mkdir -p /opt/maarch/MaarchCapture

# Extract the provided archive to the installation directory
sudo tar -xzf maarchcapture.tar.gz -C /opt/maarch/MaarchCapture

# Set proper permissions
sudo chown -R www-data:www-data /opt/maarch/MaarchCapture
sudo chmod -R 755 /opt/maarch/MaarchCapture
```

### 3. Create Required Directories

```bash
# Create directories for PDF import
sudo mkdir -p /opt/maarch/MaarchCapture/files/TEST_IMPORT/backup
sudo mkdir -p /opt/maarch/MaarchCapture/files/TEST_IMPORT/failed
sudo chmod -R 777 /opt/maarch/MaarchCapture/files
```

### 4. Configure the PDF Import Process

Edit the configuration in the PDF import script:

```bash
sudo nano /opt/maarch/MaarchCapture/scripts/process_import_pdfs.php
```

Update the following parameters:

```php
// Configuration
$importDir = '/opt/maarch/MaarchCapture/files/TEST_IMPORT';
$backupDir = '/opt/maarch/MaarchCapture/files/TEST_IMPORT/backup';
$failedDir = '/opt/maarch/MaarchCapture/files/TEST_IMPORT/failed';
$logFile = '/opt/maarch/MaarchCapture/files/TEST_IMPORT/import_log.txt';

// MaarchCourrier REST API configuration
$wsUri = "http://ws_user:password@client-maarch-server/maarch/rest";
$resourcesEndpoint = "/resources";
```

Replace `ws_user`, `password`, and `client-maarch-server` with the actual values for the client's MaarchCourrier instance.

### 5. Set Up Automated Processing

Create a cron job to run the import process automatically:

```bash
sudo crontab -e
```

Add the following line to run the process every 5 minutes:

```
*/5 * * * * cd /opt/maarch/MaarchCapture && php scripts/process_import_pdfs.php >> /var/log/maarch_import.log 2>&1
```

### 6. Test the Installation

1. Place a test PDF file and its corresponding XML metadata file in the import directory:

```bash
cp /path/to/test.pdf /opt/maarch/MaarchCapture/files/TEST_IMPORT/
cp /path/to/test.xml /opt/maarch/MaarchCapture/files/TEST_IMPORT/
```

2. Run the import process manually:

```bash
cd /opt/maarch/MaarchCapture
php scripts/process_import_pdfs.php
```

3. Check the log file to verify successful processing:

```bash
cat /opt/maarch/MaarchCapture/files/TEST_IMPORT/import_log.txt
```

## Troubleshooting

### Common Issues

1. **Permission Errors**

If you encounter permission errors, ensure the directories have proper permissions:

```bash
sudo chown -R www-data:www-data /opt/maarch/MaarchCapture
sudo chmod -R 755 /opt/maarch/MaarchCapture
sudo chmod -R 777 /opt/maarch/MaarchCapture/files
```

2. **API Connection Errors**

If the script fails to connect to MaarchCourrier API:
- Verify the API URL is correct
- Check that the API credentials are valid
- Ensure the MaarchCourrier server is accessible from the MaarchCapture server

3. **XML Parsing Errors**

If XML files fail to parse:
- Validate the XML syntax using an XML validator
- Ensure the XML file uses UTF-8 encoding
- Check that all required fields are present

## Support

For additional support, please contact:
- Email: support@maarch.com
- Phone: +XX XXX XXX XXX
