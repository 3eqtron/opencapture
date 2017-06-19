***********************
Version
***********************
1.0
Copy from svn http://svn.maarch.org/custom/trunk/MaarchCapture/ (19/06/2017)

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