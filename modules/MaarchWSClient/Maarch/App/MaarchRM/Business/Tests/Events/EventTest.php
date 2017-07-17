<?php

namespace MaarchRM\Business\Tests\Events;

use PHPUnit\Framework\TestCase;

/**
 * Event class
 */

class EventTest extends TestCase
{
    /**
     * Test of the create method
     */
     /*
   
    }*/

    public function testCreate()
    {
        $controller = new \MaarchRM\Business\Events\EventImpl();

        $event = new \MaarchRM\Business\Events\Entities\Event('EventID1');
        $event->eventType = "resId hashAlgorithm";
        $event->timestamp = "2011-01-07 00:00:00";
        $event->instanceName = "Name";
        $event->orgRegNumber = "5";
        $event->orgUnitRegNumber = "7";
        $event->accountId = "bblier";
        $event->objectClass = "resId hashAlgorithm";
        $event->objectId = "None";
        $event->operationResult = false;
        $event->description = null;
        $event->eventInfo = null;

        $controller->create($event);

        $acces = $controller->read($event->eventId);

        $compare = '{"eventType":"resId hashAlgorithm","timestamp":"2011-01-07 00:00:00","instanceName":"Name",'
        .'"orgRegNumber":"5","orgUnitRegNumber":"7","accountId":"bblier","objectClass":"resId hashAlgorithm"'
        .',"objectId":"None","operationResult":false,"description":null,"eventInfo":null}';

        $this->assertSame($compare, json_encode($acces));
    }
    /**
    * Test of the read method
    */
    
    public function testRead()
    {
        $controller = new \MaarchRM\Business\Events\EventImpl();

        $acces = $controller->read('EventID1');

        $compare = '{"eventType":"resId hashAlgorithm","timestamp":"2011-01-07 00:00:00","instanceName":"Name",'
        .'"orgRegNumber":"5","orgUnitRegNumber":"7","accountId":"bblier","objectClass":"resId hashAlgorithm",'
        .'"objectId":"None","operationResult":false,"description":null,"eventInfo":null}';

        $this->assertSame($compare, json_encode($acces));
    }

    /**
     * Test of the update method
     */
     
    public function testUpdate()
    {
        $controller = new \MaarchRM\Business\Events\EventImpl();

        $newEvent = new \MaarchRM\Business\Events\Entities\Event('EventID1');
        $newEvent->eventType = "resId hashAlgorithm TEST";
        $newEvent->timestamp = "2011-01-07 00:00:00";
        $newEvent->instanceName = "Name";
        $newEvent->orgRegNumber = "5";
        $newEvent->orgUnitRegNumber = "7";
        $newEvent->accountId = "bblier";
        $newEvent->objectClass = "resId hashAlgorithm";
        $newEvent->objectId = "None";
        $newEvent->operationResult = false;
        $newEvent->description = null;
        $newEvent->eventInfo = null;

        $controller->update($newEvent);
        $access = $controller->read($newEvent->eventId);

        $compare = '{"eventType":"resId hashAlgorithm TEST","timestamp":"2011-01-07 00:00:00","instanceName":"Name",'
        .'"orgRegNumber":"5","orgUnitRegNumber":"7","accountId":"bblier","objectClass":"resId hashAlgorithm",'
        .'"objectId":"None","operationResult":false,"description":null,"eventInfo":null}';

        $this->assertSame($compare, json_encode($access));
    }

    /**
     * Test of the delete method
     */
     
    public function testDelete()
    {
        $this->expectException(\MaarchRM\Errors\EntityNotFound::class);
        $dataAccess = new \MaarchRM\DataAccess\Events();
        $controller = new \MaarchRM\Business\Events\EventImpl();

        $controller->delete('EventID1');

        $dataAccess->read('EventID1');
    }
}
