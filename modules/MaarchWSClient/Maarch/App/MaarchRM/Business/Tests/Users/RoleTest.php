<?php

namespace MaarchRM\Business\Tests\Users;

use PHPUnit\Framework\TestCase;

/**
 * Role test class
 */

class RoleTest extends TestCase
{
    /**
     * Test of the create method
     */
     /*
   
    }*/

    public function testCreate()
    {
        $controller = new \MaarchRM\Business\Users\RoleImpl();

        $role = new \MaarchRM\Business\Users\Entities\Role('CORRESPONDANT_ARCHIVES');
        $role->roleName = "Archiviste";
        $role->description = "Correspondant d_archives";
        $role->enabled = true;

        $controller->create($role);

        $acces = $controller->read($role->roleId);

        $compare = '{"roleName":"Archiviste","description":"Correspondant d_archives","enabled":true}';

        $this->assertSame($compare, json_encode($acces));
    }
    /**
    * Test of the read method
    */
    
    public function testRead()
    {
        $controller = new \MaarchRM\Business\Users\RoleImpl();

        $acces = $controller->read('CORRESPONDANT_ARCHIVES');

        $compare = '{"roleName":"Archiviste","description":"Correspondant d_archives","enabled":true}';

        $this->assertSame($compare, json_encode($acces));
    }

    /**
     * Test of the update method
     */
     
    public function testUpdate()
    {
        $controller = new \MaarchRM\Business\Users\RoleImpl();

        $newRole = new \MaarchRM\Business\Users\Entities\Role('CORRESPONDANT_ARCHIVES');
        $newRole->roleName = "Archiviste";
        $newRole->description = "Correspondant d_archives TEST";
        $newRole->enabled = true;
 
        $controller->update($newRole);
        $access = $controller->read($newRole->roleId);

        $compare = '{"roleName":"Archiviste","description":"Correspondant d_archives TEST","enabled":true}';

        $this->assertSame($compare, json_encode($access));
    }

    /**
     * Test of the delete method
     */
     
    public function testDelete()
    {
        $this->expectException(\MaarchRM\Errors\EntityNotFound::class);
        $dataAccess = new \MaarchRM\DataAccess\Roles();
        $controller = new \MaarchRM\Business\Users\RoleImpl();

        $controller->delete('CORRESPONDANT_ARCHIVES');

        $dataAccess->read('CORRESPONDANT_ARCHIVES');
    }
}
