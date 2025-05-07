<?php

namespace MaarchRM\Business\Tests\Contacts;

use PHPUnit\Framework\TestCase;
use App\MaarchRM\DataAccess;
use App\MaarchRM\Business\Contacts;

/**
 * Contact test class
 */

class CommunicationtTest extends TestCase
{
    /**
     * Test of the create method
     */
     /*
   
    }*/

    public function testCreate()
    {
        // Create a contact

        $controller1 = new \MaarchRM\Business\Contacts\ContactImpl();

        $contact = new \MaarchRM\Business\Contacts\Entities\Contact('Contact2ID');
        $contact->contactType = "Type1";
        $contact->orgName = "MAARCHRM";
        $contact->firstName = "Marche";
        $contact->lastName = "MAARCH";
        $contact->title = "title";
        $contact->function = "function1";
        $contact->service = "Support";
        $contact->displayName = "Supp";

        $controller1->create($contact);


        // Create a communicationMean

        $controller2 = new \MaarchRM\Business\Contacts\CommunicationMeanImpl();

        $communication = new \MaarchRM\Business\Contacts\Entities\CommunicationMean('BE');
        $communication->name = "World Wide";
        $communication->enabled = false;
    
        $controller2->create($communication);


        // Create a contact communication

        $controller = new \MaarchRM\Business\Contacts\CommunicationImpl();

        $communication = new \MaarchRM\Business\Contacts\Entities\Communication('Contact2ID');
        $communication->communicationId = "COM1";
        $communication->purpose = "purpose";
        $communication->comMeanCode = "BE";
        $communication->value = "function1";
        $communication->info = "Ok";
        

        $controller->create($communication);

        $acces = $controller->read($communication->contactId);

        $compare = '{"communicationId":"COM1","purpose":"purpose","comMeanCode":"BE",'
        . '"value":"function1","info":"Ok"}';

        $this->assertSame($compare, json_encode($acces));
    }
    /**
    * Test of the read method
    */
    
    public function testRead()
    {
        $controller = new \MaarchRM\Business\Contacts\CommunicationImpl();

        $acces = $controller->read('Contact2ID');

        $compare = '{"communicationId":"COM1","purpose":"purpose","comMeanCode":"BE",'
        . '"value":"function1","info":"Ok"}';

        $this->assertSame($compare, json_encode($acces));
    }

    /**
     * Test of the update method
     */
     /*
    public function testUpdate()
    {
        $controller = new \MaarchRM\Business\Contacts\ContactImpl();

        $newCommunication = new \MaarchRM\Business\Contacts\Entities\Communication('Contact1ID');
        $newCommunication->communicationId = "COM1_TEST";
        $newCommunication->purpose = "purpose";
        $newCommunication->comMeanCode = "Code1";
        $newCommunication->value = "function1";
        $newCommunication->info = "Ok";
 
        $controller->update($newCommunication);
        $access = $controller->read($newCommunication->contactId);

        $compare = '{"communicationId":"COM1_TEST","purpose:"purpose","comMeanCode":"Code1,'
       . '"value":"function1","info":"Ok"}';


        $this->assertSame($compare, json_encode($access));
    }*/

    /**
     * Test of the delete method
     */
     
    public function testDelete()
    {

        // Delete a contact, a contact communication and a contact communicationMean
        
        $this->expectException(\MaarchRM\Errors\EntityNotFound::class);

        $dataAccess = new \MaarchRM\DataAccess\Communications();
        $dataAccess2 = new \MaarchRM\DataAccess\CommunicationsMean();
        $dataAccess3 = new \MaarchRM\DataAccess\Contacts();

        
        $controller1 = new \MaarchRM\Business\Contacts\ContactImpl();
        $controller2 = new \MaarchRM\Business\Contacts\CommunicationMeanImpl();
        $controller = new \MaarchRM\Business\Contacts\CommunicationImpl();

        $controller->delete('Contact2ID');
        $controller1->delete('Contact2ID');
        $controller2->delete('BE');

        $dataAccess->read('Contact2ID');
        $dataAccess2->read('BE');
    }
}
