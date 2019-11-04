<?php 

set_include_path(get_include_path().PATH_SEPARATOR.'C:\xampp\htdocs\MaarchRM\dependency\xml');
require_once 'IncludeTrait.php';
require_once 'TemplateParserTrait.php';
require_once 'TemplateDataTrait.php';
require_once 'TemplateTrait.php';
require_once 'Comment.php';
require_once 'CdataSection.php';
require_once 'DocumentFragment.php';
require_once 'Attr.php';
require_once 'DocumentType.php';
require_once 'Notation.php';
require_once 'EntityReference.php';
require_once 'Element.php';
require_once 'Parser.php';
require_once 'ProcessingInstruction.php';
require_once 'Text.php';
require_once 'XPath.php';
require_once 'Document.php';

define('LAABS_NS_SEPARATOR', '/');

class Seda2
{
    private $Batch;



    public function export($documents, $template, $outfile)
    {
        $doc = new \dependency\XML\Document();

        $doc->load($template);
        $doc->XPath = new \dependency\XML\XPath($doc);

        // Get data from XML elements
        foreach ($documents as $documentNode) {
            $binaryDataObject = new StdClass();
            $binaryDataObject->id = $documentNode->getAttribute('id');
            $binaryDataObject->path = $documentNode->getAttribute('path');
            $binaryDataObject->filename = $documentNode->getAttribute('filename').'.'.$documentNode->getAttribute('extension');
            $binaryDataObject->hash = hash_file('sha256', $documentNode->getAttribute('path'));
            $binaryDataObject->size = filesize($documentNode->getAttribute('path'));
            

            $archiveUnit = new stdClass();
            $archiveUnit->id = "AU_".$documentNode->getAttribute('id');
            $archiveUnit->numfac = $documentNode->getMetadata('NUMFAC');
            $archiveUnit->numcli = $documentNode->getMetadata('NUMCLI');
            $archiveUnit->date = $documentNode->getMetadata('DATE');

            $binaryDataObjects[] = $binaryDataObject;
            $archiveUnits[] = $archiveUnit;
        }

        // Merge
        $doc->setSource('binaryDataObjects', $binaryDataObjects);
        $doc->setSource('archiveUnits', $archiveUnits);
        $doc->setSource('date', date('c'));
        $doc->setSource('messageIdentifier', $_SESSION['capture']->Batch->id);

        $doc->merge();

        $doc->save($outfile);
    }
}
