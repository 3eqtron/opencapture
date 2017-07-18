<?php

namespace MaarchRM\Business\Tests\Users;

use PHPUnit\Framework\TestCase;
use App\MaarchRM\DataAccess;
use App\MaarchRM\Business\Users;
use App\MaarchRM\Business\Accounts;

/**
 * RoleMember test class
 */
class RoleMemberTest extends TestCase
{
    /*
    /**
     * Test of the create method
     */
     
    public function testCreate()
    {
        // Create a Role :

        $controller1 = new \MaarchRM\Business\Users\RoleImpl();

        $role = new \MaarchRM\Business\Users\Entities\Role('CORRESPONDANT_COMPTA');
        $role->roleName = "Compta";
        $role->description = "Correspondant comptable";
        $role->enabled = true;

        $controller1->create($role);
         
        // Create a User :

        $controller2 = new \MaarchRM\Business\Users\AccountImpl();

        $account = new \MaarchRM\Business\Users\Entities\Account('aadams');
        $account->accountId = "aadams";
        $account->displayName = "Amy ADAMS";
        $account->accountType = "user";
        $account->emailAddress = "info@maarch.org";
        $account->enabled = true;
        $account->password = "fffd2272074225feae229658e248b81529639e6199051abdeb49b6ed60adf13d";
        $account->passwordChangeRequired = false;
        $account->passwordLastChange = null;
        $account->locked = false;
        $account->lockDate = null;
        $account->badPasswordCount = 0;
        $account->lastLogin = null;
        $account->lastIp = null;
        $account->replacingUserAccountId = null;
        $account->firstName = "Amy";
        $account->lastName = "ADAMS";
        $account->title = "Mme.";
        $account->salt = null;
        $account->tokenDate = null;
        
        $controller2->create($account);

        // Create a RoleMember

        $controller = new \MaarchRM\Business\Users\RoleMemberImpl();

        $roleMember = new \MaarchRM\Business\Users\Entities\RoleMember('CORRESPONDANT_COMPTA');
        $roleMember->userAccountId = "aadams";
       
        $controller->create($roleMember);

        $acces = $controller->read($roleMember->roleId);

        $compare = '{"userAccountId":"aadams"}';

        $this->assertSame($compare, json_encode($acces));
    }/*

    /**
    * Test of the read method
    */

    public function testRead()
    {
        $controller = new \MaarchRM\Business\Users\RoleMemberImpl();

        $acces = $controller->read('CORRESPONDANT_COMPTA');

        $compare = '{"userAccountId":"aadams"}';

        $this->assertSame($compare, json_encode($acces));
    }
    /**
     * Test of the update method
     */
     /*
     /*
    public function testUpdate()
    {
        $controller = new \MaarchRM\Business\Users\RoleMemberImpl();

        $newRoleMember = new \MaarchRM\Business\Users\Entities\RoleMember('CORRESPONDANT_ARCHIVES');
        $newRoleMember->userAccountId= "bblier_TEST";
      
        
        $controller->update($newRoleMember);
        $access = $controller->read($newRoleMember->roleId);

        $compare = '{"userAccountId":"bblier_TEST"}';

        $this->assertSame($compare, json_encode($access));
    }*/

    /**
     * Test of the delete method
     */
   
    public function testDelete()
    {

        // Delete a Role, an account and a RoleMember

        $this->expectException(\MaarchRM\Errors\EntityNotFound::class);

        $dataAccess = new \MaarchRM\DataAccess\Roles();
        $dataAccess2 = new \MaarchRM\DataAccess\Accounts();
        $dataAccess3 = new \MaarchRM\DataAccess\RolesMembers();

        $controller1 = new \MaarchRM\Business\Users\RoleImpl();
        $controller2 = new \MaarchRM\Business\Users\AccountImpl();
        $controller = new \MaarchRM\Business\Users\RoleMemberImpl();

        $controller->delete('CORRESPONDANT_COMPTA');
        $controller1->delete('CORRESPONDANT_COMPTA');
        $controller2->delete('aadams');

        $dataAccess->read('CORRESPONDANT_COMPTA');
        $dataAccess2->read('aadams');
    }
}
