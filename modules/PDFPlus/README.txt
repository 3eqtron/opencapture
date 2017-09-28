***********************
Version
***********************
1.0

- Module of Maarch Capture tool which move PDF files from a directory to another. PDFPlus process is executed for each file : separation with barcode (S task), convert image PDF file to text PDF file (M task).

***********************
Configuration file
***********************
5 attributes for the tasks : 
- Directory_in : Input directory 
- Directory_out : Output directory
- Config_File : pdfplus process configuration file
- Prefix (optional): prefix which be added on output filename
- Extensions (optional): filter files by extension (separated by comma)