<?php

namespace MaarchRM\Business\Tests\ServicePrivilege;

use PHPUnit\Framework\TestCase;
use App\MaarchRM\DataAccess;
use App\MaarchRM\Business\Accounts;

/**
 * ServicePrivilege test class
 */

class ServicePrivilegeTest extends TestCase
{
    /**
     * Test of the create method
     */
     /*
   
    }*/

    public function testCreate()
    {
        // Create an account

        $controller2 = new \MaarchRM\Business\Users\AccountImpl();

        $account = new \MaarchRM\Business\Users\Entities\Account('ssystem');
        $account->accountId = "System";
        $account->displayName = "Sys";
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
        $account->firstName = "y";
        $account->lastName = "s";
        $account->title = "Machine";
        $account->salt = null;
        $account->tokenDate = null;
            
        $controller2->create($account);

        // Create a servicePrivilege

        $controller = new \MaarchRM\Business\Services\ServicePrivilegeImpl();

        $service = new \MaarchRM\Business\Services\Entities\ServicePrivilege('System');
        $service->serviceURI = "recordsManagement_archives_deleteDisposablearchive";

        $controller->create($service);

        $acces = $controller->read($service->accountId);

        $compare = '{"serviceURI":"recordsManagement_archives_deleteDisposablearchive"}';

        $this->assertSame($compare, json_encode($acces));
    }
    /**
    * Test of the read method
    */
    
    public function testRead()
    {
        $controller = new \MaarchRM\Business\Services\ServicePrivilegeImpl();

        $acces = $controller->read('System');

        $compare = '{"serviceURI":"recordsManagement_archives_deleteDisposablearchive"}';

        $this->assertSame($compare, json_encode($acces));
    }

    /**
     * Test of the delete method
     */
     
    public function testDelete()
    {
        $this->expectException(\MaarchRM\Errors\EntityNotFound::class);
        $dataAccess2 = new \MaarchRM\DataAccess\Accounts();
        $dataAccess = new \MaarchRM\DataAccess\ServicesPrivilege();

        // Delete an account and a servicePrivilege

        $controller = new \MaarchRM\Business\Services\ServicePrivilegeImpl();
        $controller2 = new \MaarchRM\Business\Users\AccountImpl();

        $controller->delete('System');
        $controller2->delete('ssystem');

        $dataAccess->read('System');
    }
}
