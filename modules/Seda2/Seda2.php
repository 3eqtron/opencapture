<?php 

require_once __DIR__.'/xml/IncludeTrait.php';
require_once __DIR__.'/xml/TemplateParserTrait.php';
require_once __DIR__.'/xml/TemplateDataTrait.php';
require_once __DIR__.'/xml/TemplateTrait.php';
require_once __DIR__.'/xml/Comment.php';
require_once __DIR__.'/xml/CdataSection.php';
require_once __DIR__.'/xml/DocumentFragment.php';
require_once __DIR__.'/xml/Attr.php';
require_once __DIR__.'/xml/DocumentType.php';
require_once __DIR__.'/xml/Notation.php';
require_once __DIR__.'/xml/EntityReference.php';
require_once __DIR__.'/xml/Element.php';
require_once __DIR__.'/xml/Parser.php';
require_once __DIR__.'/xml/ProcessingInstruction.php';
require_once __DIR__.'/xml/Text.php';
require_once __DIR__.'/xml/XPath.php';
require_once __DIR__.'/xml/Document.php';

define('LAABS_NS_SEPARATOR', '/');

class Seda2
{
    private $Batch;

    public function export($documents, $template, $outfile)
    {
        $doc = new \xml\Document();

        $doc->load($template);
        $doc->XPath = new \xml\XPath($doc);

        // Get data from XML elements
        foreach ($documents as $documentNode) {
            $binaryDataObject = new StdClass();
            $binaryDataObject->id = $documentNode->getAttribute('id');
            $binaryDataObject->path = basename($documentNode->getAttribute('path'));
            $binaryDataObject->filename = $documentNode->getAttribute('filename').'.'.$documentNode->getAttribute('extension');
            $binaryDataObject->hash = hash_file('sha256', $documentNode->getAttribute('path'));
            $binaryDataObject->size = filesize($documentNode->getAttribute('path'));
            

            $archiveUnit = new stdClass();
            $archiveUnit->id = "AU_".$documentNode->getAttribute('id');

            $archiveUnit->content = new stdClass();
            $archiveUnit->content->acquiredDate = date('c');
            foreach ($documentNode->getMetadata() as $metadataElement) {
                $archiveUnit->content->{$metadataElement->nodeName} = $metadataElement->nodeValue;
            }
            $archiveUnit->dataObjectReference = [$binaryDataObject->id];

            $binaryDataObjects[] = $binaryDataObject;
            $archiveUnits[] = $archiveUnit;
        }

        // Merge
        $doc->setSource('binaryDataObjects', $binaryDataObjects);
        $doc->setSource('archiveUnits', $archiveUnits);
        $doc->setSource('date', date('c'));
        $doc->setSource('messageIdentifier', $_SESSION['capture']->Batch->id);

        $doc->merge();

        $doc->save($_SESSION['capture']->Batch->directory.'/'.$outfile);
        $_SESSION['capture']->Batch->addFile($_SESSION['capture']->Batch->directory.'/'.$outfile);
    }
}
