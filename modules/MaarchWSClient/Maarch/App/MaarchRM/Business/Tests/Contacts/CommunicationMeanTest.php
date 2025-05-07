<?php

namespace MaarchRM\Business\Tests\CommunicationMean;

use PHPUnit\Framework\TestCase;

/**
 * CommunicationMean test class
 */

class CommunicationMeanTest extends TestCase
{
    /**
     * Test of the create method
     */
     /*
   
    }*/

    public function testCreate()
    {
        $controller = new \MaarchRM\Business\Contacts\CommunicationMeanImpl();

        $communication = new \MaarchRM\Business\Contacts\Entities\CommunicationMean('AH');
        $communication->name = "World Wide";
        $communication->enabled = false;
    
        $controller->create($communication);

        $acces = $controller->read($communication->code);

        $compare = '{"name":"World Wide","enabled":false}';
       
        $this->assertSame($compare, json_encode($acces));
    }
    /**
    * Test of the read method
    */
    
    public function testRead()
    {
        $controller = new \MaarchRM\Business\Contacts\CommunicationMeanImpl();

        $acces = $controller->read('AH');

        $compare = '{"name":"World Wide","enabled":false}';

        $this->assertSame($compare, json_encode($acces));
    }

    /**
     * Test of the update method
     */
     
    public function testUpdate()
    {
        $controller = new \MaarchRM\Business\Contacts\CommunicationMeanImpl();

        $newCommunication = new \MaarchRM\Business\Contacts\Entities\CommunicationMean('AH');
        $newCommunication->name = "World Wide Test";
        $newCommunication->enabled = false;
 
        $controller->update($newCommunication);
        $access = $controller->read($newCommunication->code);

        $compare = '{"name":"World Wide Test","enabled":false}';

        $this->assertSame($compare, json_encode($access));
    }

    /**
     * Test of the delete method
     */
     
    public function testDelete()
    {
        $this->expectException(\MaarchRM\Errors\EntityNotFound::class);
        $dataAccess = new \MaarchRM\DataAccess\CommunicationsMean();
        $controller = new \MaarchRM\Business\Contacts\CommunicationMeanImpl();

        $controller->delete('AH');

        $dataAccess->read('AH');
    }
}
