# Version
`develop`

# Prerequisit
`PHP 7 or greater`

## PHP Modules:
- fileinfo (php_finfo)
- pdf (php_pdf)
- imap (php_imap)
- PDFLib for PDFSplitter (php_pdflib)
- PDFLib TET for PDFExtractor (php_tet)

## Maarch modules:
- Maarch CLI_Tools

# Install 
```
git clone -b develop https://labs.maarch.org/maarch/MaarchCapture
```

# PDFLib 

- Download at www.pdflib.com
- Extract
- Copy binds/php_pdflib.dll in php/ext
- add extension in php.ini
> Windows : 
> [HKEY_LOCAL_MACHINE\SOFTWARE\PDFlib\PDFlib9]
> [HKEY_LOCAL_MACHINE\SOFTWARE\PDFlib\PDFlib9\9.0.0]
> "license"="W000000-000000-000000-000000-000000"


# PDFLib TET
- Download at www.pdflib.com
- Extract
- Copy binds/php_tet.dll in php/ext
- add extension in php.ini


