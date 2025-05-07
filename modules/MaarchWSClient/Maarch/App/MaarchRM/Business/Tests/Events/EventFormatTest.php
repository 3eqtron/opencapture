<?php

namespace MaarchRM\Business\Tests\EventsFormat;

use PHPUnit\Framework\TestCase;

/**
 * Event Format class
 */

class EventFormatTest extends TestCase
{
    /**
     * Test of the create method
     */
     /*
   
    }*/

    public function testCreate()
    {
        $controller = new \MaarchRM\Business\Events\EventFormatImpl();

        $event = new \MaarchRM\Business\Events\Entities\EventFormat('recordsManagement/accessRuleModification');
        $event->format = "resId hashAlgorithm";
        $event->message = "Modification de la regle d_acces de l_archive";
        $event->notification = false;

        $controller->create($event);

        $acces = $controller->read($event->type);

        $compare = '{"format":"resId hashAlgorithm",'
        .'"message":"Modification de la regle d_acces de l_archive","notification":false}';

        $this->assertSame($compare, json_encode($acces));
    }
    /**
    * Test of the read method
    */
    
    public function testRead()
    {
        $controller = new \MaarchRM\Business\Events\EventFormatImpl();

        $acces = $controller->read('recordsManagement/accessRuleModification');

        $compare = '{"format":"resId hashAlgorithm",'
        .'"message":"Modification de la regle d_acces de l_archive","notification":false}';

        $this->assertSame($compare, json_encode($acces));
    }

    /**
     * Test of the update method
     */
     
    public function testUpdate()
    {
        $controller = new \MaarchRM\Business\Events\EventFormatImpl();

        $newService = new \MaarchRM\Business\Events\Entities\EventFormat('recordsManagement/accessRuleModification');
        $newService->format ="resId hashAlgorithm TEST";
        $newService->message = "Modification de la regle d_acces de l_archive";
        $newService->notification = false;
 
        $controller->update($newService);
        $access = $controller->read($newService->type);

        $compare = '{"format":"resId hashAlgorithm TEST",'
        .'"message":"Modification de la regle d_acces de l_archive","notification":false}';

        $this->assertSame($compare, json_encode($access));
    }

    /**
     * Test of the delete method
     */
     
    public function testDelete()
    {
        $this->expectException(\MaarchRM\Errors\EntityNotFound::class);
        $dataAccess = new \MaarchRM\DataAccess\EventsFormat();
        $controller = new \MaarchRM\Business\Events\EventFormatImpl();

        $controller->delete('recordsManagement/accessRuleModification');

        $dataAccess->read('recordsManagement/accessRuleModification');
    }
}
