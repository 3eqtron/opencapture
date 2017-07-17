<?php

namespace MaarchRM\Business\Tests\Contacts;

use PHPUnit\Framework\TestCase;
use App\MaarchRM\DataAccess;
use App\Business\Contacts;

/**
 * Address test class
 */

class AddressTest extends TestCase
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

        $contact = new \MaarchRM\Business\Contacts\Entities\Contact('Contact3ID');
        $contact->contactType = "Type1";
        $contact->orgName = "MAARCHRM";
        $contact->firstName = "Marche";
        $contact->lastName = "MAARCH";
        $contact->title = "title";
        $contact->function = "function1";
        $contact->service = "Support";
        $contact->displayName = "Supp";

        $controller1->create($contact);

        $acces = $controller1->read($contact->contactId);

        // Create an address

        $controller = new \MaarchRM\Business\Contacts\AddressImpl();

        $address = new \MaarchRM\Business\Contacts\Entities\Address('Contact3ID');
        $address->addressId = "AddressID";
        $address->purpose = "purpose";
        $address->room = "2";
        $address->floor = "??";
        $address->building = "high";
        $address->number = "7";
        $address->street = "street";
        $address->postBox = "Before";
        $address->block = "West";
        $address->citySubDivision = "city";
        $address->postCode = "41814951";
        $address->city = "benfica";
        $address->country = "PORTUGAL";

        $controller->create($address);

        $acces = $controller->read($address->contactId);

        $compare = '{"addressId":"AddressID","purpose":"purpose","room":"2",'
        .'"floor":"??","building":"high","number":"7","street":"street",'
        .'"postBox":"Before","block":"West","citySubDivision":"city","postCode":"41814951","city":"benfica",'
        .'"country":"PORTUGAL"}';

        $this->assertSame($compare, json_encode($acces));
    }
    /**
    * Test of the read method
    */
    
    public function testRead()
    {
        $controller = new \MaarchRM\Business\Contacts\AddressImpl();

        $acces = $controller->read('Contact3ID');

        $compare = '{"addressId":"AddressID","purpose":"purpose","room":"2",'
        . '"floor":"??","building":"high","number":"7","street":"street",'
        . '"postBox":"Before","block":"West","citySubDivision":"city","postCode":"41814951","city":"benfica",'
        .'"country":"PORTUGAL"}';

        $this->assertSame($compare, json_encode($acces));
    }

    /**
     * Test of the update method
     */
     /*
    public function testUpdate()
    {
        $controller = new \MaarchRM\Business\Contacts\AddressImpl();

        $newAddress = new \MaarchRM\Business\Contacts\Entities\Address('Contact3ID');
        $newAddress->addressId = "AddressID_TEST";
        $newAddress->purpose = "purpose";
        $newAddress->room = "2";
        $newAddress->floor = "??";
        $newAddress->building = "high";
        $newAddress->number = "7";
        $newAddress->street = "street";
        $newAddress->postBox = "Before";
        $newAddress->block = "West";
        $newAddress->citySubDivision = "city";
        $newAddress->postCode = "41814951";
        $newAddress->city = "benfica";
        $newAddress->country = "PORTUGAL";

        $access = $controller->read($newAddress->contactId);

       $compare = '{"addressId":"AddressID_TEST","purpose":"purpose","room":"2",'
       . '"floor":"??","building":"high","number":"7","street":"street",'
       . '"postBox":"Before","block":"West","citySubDivision":"city","postCode":"41814951","city":"benfica",'
       .'"country":"PORTUGAL"}';

        $this->assertSame($compare, json_encode($access));
    }*/

    /**
     * Test of the delete method
     */
    
    public function testDelete()
    {
        $this->expectException(\MaarchRM\Errors\EntityNotFound::class);
        $dataAccess = new \MaarchRM\DataAccess\Contacts();

    // Delete an Address and a contact

        $controller = new \MaarchRM\Business\Contacts\AddressImpl();
        $controller1 = new \MaarchRM\Business\Contacts\ContactImpl();

        $controller->delete('Contact3ID');
        $controller1->delete('Contact3ID');

        $dataAccess->read('Contact3ID');
    }
}
