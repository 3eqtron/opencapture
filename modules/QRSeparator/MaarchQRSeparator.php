<?php

class QRSeparator 
{
    
    function __construct()
    {
        $this->Batch = $_SESSION['capture']->Batch;
    }
    
    function separatePDF($ScanSource,$ResultDirectory)
    {

        echo "Init process ...\n";
        $_SESSION['capture']->logEvent(
            "Init process ... "
        );

        if (!is_readable($ScanSource)) {
        	echo "Source directory is not valid !\n";
            $_SESSION['capture']->logEvent(
                "Source directory is not valid !"
            );
            exit();
        }

        if (!is_readable($ScanSource)) {
        	echo "Autoimport directory is not valid ! \n";
            $_SESSION['capture']->logEvent(
                "Autoimport directory is not valid !"
            );
            exit();
        }

        $files = array_diff(scandir($ScanSource), array('..', '.'));
        //print_r($files);

        if(empty($files)){
            echo "No files to process ! End of process ...\n";
            $_SESSION['capture']->logEvent(
                "No files to process ! End of process ..."
            );
            exit();
        }

        foreach ($files as $key => $value) {
            /*
             * Séparation des pages du pdf scanné
             */
            $this->split_pdf($ScanSource.$files[$key], 'tmp/'.$key.'/');

            /*
             * Restructuration des pdf en fonction des separateurs
             */
            $this->construct_pdf("tmp/".$key."/", $ResultDirectory);
            unlink($ScanSource.$files[$key]);
            rmdir("tmp/".$key."/");
        }
    }

    function split_pdf($filename, $end_directory = false)
    {
        require_once('fpdf/fpdf.php');
        require_once('fpdi/fpdi.php');
     
        $end_directory = $end_directory ? $end_directory : './';
        
        //print_r($end_directory);

        /*
         * Creation du repertoire split
         */
        if (!is_dir($end_directory))
        {
            // Will make directories under end directory that don't exist
            // Provided that end directory exists and has the right permissions
            mkdir($end_directory, 0777, true);
        }
        
        $pdf = new FPDI();
        $pagecount = $pdf->setSourceFile($filename); // How many pages?
        
        /*
         * Scinde les pages du pdf
         */ 
        for ($i = 1; $i <= $pagecount; $i++) {
            $new_pdf = new FPDI();
            $new_pdf->AddPage();
            $new_pdf->setSourceFile($filename);
            $new_pdf->useTemplate($new_pdf->importPage($i));
            
            try {
                $new_filename = $end_directory.$i.".pdf";
                $new_pdf->Output($new_filename, "F");
                // write message to the log file
                $_SESSION['capture']->logEvent(
                    "Page ".$i." split into ".$new_filename
                );
            } catch (Exception $e) {
                $_SESSION['capture']->logEvent(
                    "Caught exception: ".$e->getMessage()
                );
                //echo 'Caught exception: ',  $e->getMessage(), "\n";
            }
        }
    }

    function construct_pdf($split_directory, $end_directory = false)
    {
        require_once('fpdf/fpdf.php');
        require_once('fpdi/fpdi.php');
        include_once('qrReader/QrReader.php');
        
        $end_directory = $end_directory ? $end_directory : './';
        
        $new_pdf = new FPDI();
        
        $i=1;
        $z=0;
        $qr_label = '';
        while($i!=0){
            if(is_file($split_directory.$i.'.pdf')){

                $file = $i.'.pdf';
                /*
                 * Lecture du QRCODE
                 */
                $qrcode = new QrReader($split_directory.$file);
                $text = $qrcode->text();

                /*
                 * extraction du QRCODE
                 */
                if(!empty($text)){
                    $isCourrier = false;
                    echo "This is a separator ! Extract name ...";
                    echo "\n";
                    $_SESSION['capture']->logEvent(
		                "This is a separator ! Extract name ..."
		            );
                    $old_label = $qr_label;
                    $qr_label = $text;
                    $label[]=$text;

                /*
                 * fusion des pages incluses dans le séparateur
                 */ 
                }else if(empty($text) && !empty($qr_label)){
                    $isCourrier =true;
                    echo "This is a maarch document page ! Add page to pdf ...";
                    echo "\n";
                    $_SESSION['capture']->logEvent(
		                "This is a maarch document page ! Add page to pdf ..."
		            );
                    $new_pdf->AddPage();
                    $new_pdf->setSourceFile($split_directory.$file);    
                    $new_pdf->useTemplate($new_pdf->importPage(1));
                }   
                $next_filename = $i+1;

                /*
                 * Creation du pdf du separateur
                 */
                if((($isCourrier == false && $old_label != '' && $text != '') || (!is_file($split_directory.$next_filename.'.pdf')))){
                    echo "End of merge pdf for service : ".$label[$z];
                    echo "\n";
                    $_SESSION['capture']->logEvent(
		                "End of merge pdf for service : ".$label[$z]
		            );
                    $today = date("YmdHm");
                    $rand = rand();
                    
                
                    $filename = $label[$z].'_'.$today.$rand.".pdf";
                    $new_filename = $end_directory.$filename;

                    $z++;
                    $new_pdf->Output($new_filename, "F");


                    # add new document with first BODY part found
                    $Document = 
                        $this->Batch->addDocument(
                            $new_filename
                        );

                    $_SESSION['capture']->logEvent("Document " . $Document->id 
                        . " added with source " . $new_filename);


                    $Document->setMetadata(
                        "destination", 
                        $label[$z-1]
                    );

                    $new_pdf = new FPDI();
                }
                echo "Remove tmp file : ".$split_directory.$i.'.pdf';
                echo "\n";
                unlink($split_directory.$i.'.pdf');
            }else{
                break;
            }
            $text = '';
            $i++;
        }
    }
}
