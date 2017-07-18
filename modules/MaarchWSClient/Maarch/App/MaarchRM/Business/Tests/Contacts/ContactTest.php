<?php

namespace MaarchRM\Business\Tests\Contacts;

use PHPUnit\Framework\TestCase;

/**
 * Contact test class
 */

class ContactTest extends TestCase
{
    /**
     * Test of the create method
     */
     /*
   
    }*/

    public function testCreate()
    {
        $controller = new \MaarchRM\Business\Contacts\ContactImpl();

        $contact = new \MaarchRM\Business\Contacts\Entities\Contact('Contact1ID');
        $contact->contactType = "Type1";
        $contact->orgName = "MAARCHRM";
        $contact->firstName = "Marche";
        $contact->lastName = "MAARCH";
        $contact->title = "title";
        $contact->function = "function1";
        $contact->service = "Support";
        $contact->displayName = "Supp";

        $controller->create($contact);

        $acces = $controller->read($contact->contactId);

        $compare = '{"contactType":"Type1","orgName":"MAARCHRM","firstName":"Marche",'
        . '"lastName":"MAARCH","title":"title","function":"function1","service":"Support",'
        . '"displayName":"Supp"}';

        $this->assertSame($compare, json_encode($acces));
    }
    /**
    * Test of the read method
    */
    
    public function testRead()
    {
        $controller = new \MaarchRM\Business\Contacts\ContactImpl();

        $acces = $controller->read('Contact1ID');

        $compare = '{"contactType":"Type1","orgName":"MAARCHRM","firstName":"Marche",'
        . '"lastName":"MAARCH","title":"title","function":"function1","service":"Support",'
        . '"displayName":"Supp"}';

        $this->assertSame($compare, json_encode($acces));
    }

    /**
     * Test of the update method
     */
     
    public function testUpdate()
    {
        $controller = new \MaarchRM\Business\Contacts\ContactImpl();

        $newContact = new \MaarchRM\Business\Contacts\Entities\Contact('Contact1ID');
        $newContact->contactType = "Type_TEST";
        $newContact->orgName = "MAARCHRM";
        $newContact->firstName = "Marche";
        $newContact->lastName = "MAARCH";
        $newContact->title = "title";
        $newContact->function = "function1";
        $newContact->service = "Support";
        $newContact->displayName = "Supp";
 
        $controller->update($newContact);
        $access = $controller->read($newContact->contactId);

        $compare = '{"contactType":"Type_TEST","orgName":"MAARCHRM","firstName":"Marche",'
        . '"lastName":"MAARCH","title":"title","function":"function1","service":"Support",'
        . '"displayName":"Supp"}';


        $this->assertSame($compare, json_encode($access));
    }

    /**
     * Test of the delete method
     */
     
    public function testDelete()
    {
        $this->expectException(\MaarchRM\Errors\EntityNotFound::class);
        $dataAccess = new \MaarchRM\DataAccess\Contacts();
        $controller = new \MaarchRM\Business\Contacts\ContactImpl();

        $controller->delete('Contact1ID');

        $dataAccess->read('Contact1ID');
    }
}
