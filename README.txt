***********************
Version
***********************
1.3

- developpment of a webservice client REST.

- Can choose custom Capture.xml in script
Ex: php MaarchCapture.php init -ConfigName Capture_custom -BatchName  CAPTURE_MAIL

- Can choose custom MaarchWSClient.xml in Capture.xml
Ex: <step function="processBatch" module="MaarchWSClient" name="SendToMaarch">
      <input name="WSDL">maarchcourrier</input>
      <input name="Process">IMPORT_MAIL_1</input>
      <input name="CatchError">false</input>
      <input name="configFile">MaarchWSClient.xml</input>
    </step>

- Fix warnings :

PHP Warning:  Declaration of Workflow::load($id, $directory) should be compatible with DOMDocument::load($source, $options = NULL)
PHP Warning:  Declaration of Batch::load($BatchId, $envDirectory) should be compatible with DOMDocument::load($source, $options = NULL)


***********************
Prerequisit
***********************
PHP 5.3 or greater

PHP Modules:
* fileinfo (php_finfo)
* pdf (php_pdf)
* imap (php_imap)
* PDFLib for PDFSplitter (php_pdflib)
* PDFLib TET for PDFExtractor (php_tet)

Maarch modules:
* Maarch CLI_Tools

***********************
Install 
***********************
git clone -b 1.2 https://labs.maarch.org/maarch/MaarchCapture

***********************
PDFLib 
***********************
Download at www.pdflib.com
Extract
Copy binds/php_pdflib.dll in php/ext
add extension in php.ini
Windows : 
[HKEY_LOCAL_MACHINE\SOFTWARE\PDFlib\PDFlib9]
[HKEY_LOCAL_MACHINE\SOFTWARE\PDFlib\PDFlib9\9.0.0]
"license"="W000000-000000-000000-000000-000000"


PDFLib TET
***********************
Download at www.pdflib.com
Extract
Copy binds/php_tet.dll in php/ext
add extension in php.ini
