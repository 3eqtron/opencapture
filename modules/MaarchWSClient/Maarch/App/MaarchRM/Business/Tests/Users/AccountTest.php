<?php

namespace MaarchRM\Business\Tests\Users;

use PHPUnit\Framework\TestCase;

/**
 * Account test class
 */
class AccountTest extends TestCase
{
    /*
    /**
     * Test of the create method
     */
     
    public function testCreate()
    {
        $controller = new \MaarchRM\Business\Users\AccountImpl();

        $account = new \MaarchRM\Business\Users\Entities\Account('bblier');
        $account->accountId = "bblier";
        $account->displayName = "Bernard BLIER";
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
        $account->firstName = "Bernard";
        $account->lastName = "BLIER";
        $account->title = "Mr.";
        $account->salt = null;
        $account->tokenDate = null;
            
        $controller->create($account);

        $acces = $controller->read($account->accountName);

        $compare = '{"accountId":"bblier","displayName":"Bernard BLIER",'
            . '"accountType":"user","emailAddress":"info@maarch.org","enabled":true,'
            . '"password":"fffd2272074225feae229658e248b81529639e6199051abdeb49b6ed60adf13d",'
            . '"passwordChangeRequired":false,"passwordLastChange":null,"locked":false,"lockDate":null,'
            . '"badPasswordCount":0,"lastLogin":null,"lastIp":null,"replacingUserAccountId":null,'
            . '"firstName":"Bernard","lastName":"BLIER","title":"Mr.","salt":null,"tokenDate":null}';

        $this->assertSame($compare, json_encode($acces));
    }

    /**
    * Test of the read method
    */
    
    public function testRead()
    {
        $controller = new \MaarchRM\Business\Users\AccountImpl();

        $acces = $controller->read("bblier");

        $compare = '{"accountId":"bblier","displayName":"Bernard BLIER",'
        . '"accountType":"user","emailAddress":"info@maarch.org","enabled":true,'
        . '"password":"fffd2272074225feae229658e248b81529639e6199051abdeb49b6ed60adf13d",'
        . '"passwordChangeRequired":false,"passwordLastChange":null,"locked":false,"lockDate":null,'
        . '"badPasswordCount":0,"lastLogin":null,"lastIp":null,"replacingUserAccountId":null,'
        . '"firstName":"Bernard","lastName":"BLIER","title":"Mr.","salt":null,"tokenDate":null}';

        $this->assertSame($compare, json_encode($acces));
    }
    /**
     * Test of the update method
     */
     
    public function testUpdate()
    {
        $controller = new \MaarchRM\Business\Users\AccountImpl();

        $newAccount = new \MaarchRM\Business\Users\Entities\Account('bblier');
        $newAccount->accountId = "bblier";
        $newAccount->displayName = "Bernard BLIER2";
        $newAccount->accountType = "user";
        $newAccount->emailAddress = "info@maarch.org";
        $newAccount->enabled = true;
        $newAccount->password = "fffd2272074225feae229658e248b81529639e6199051abdeb49b6ed60adf13d";
        $newAccount->passwordChangeRequired = false;
        $newAccount->passwordLastChange = null;
        $newAccount->locked = false;
        $newAccount->lockDate = null;
        $newAccount->badPasswordCount = 0;
        $newAccount->lastLogin = null;
        $newAccount->lastIp = null;
        $newAccount->replacingUsernewAccountId = null;
        $newAccount->firstName = "Bernard";
        $newAccount->lastName = "BLIER";
        $newAccount->title = "Mr.";
        $newAccount->salt = null;
        $newAccount->tokenDate = null;

        $controller->update($newAccount);
        $access = $controller->read($newAccount->accountName);

        $compare = '{"accountId":"bblier","displayName":"Bernard BLIER2",'
        . '"accountType":"user","emailAddress":"info@maarch.org","enabled":true,'
        . '"password":"fffd2272074225feae229658e248b81529639e6199051abdeb49b6ed60adf13d",'
        . '"passwordChangeRequired":false,"passwordLastChange":null,"locked":false,"lockDate":null,'
        . '"badPasswordCount":0,"lastLogin":null,"lastIp":null,"replacingUserAccountId":null,'
        . '"firstName":"Bernard","lastName":"BLIER","title":"Mr.","salt":null,"tokenDate":null}';

        $this->assertSame($compare, json_encode($access));
    }

    /**
     * Test of the delete method
     */

    public function testDelete()
    {
         $this->expectException(\MaarchRM\Errors\EntityNotFound::class);
         $dataAccess = new \MaarchRM\DataAccess\Accounts();
         $controller = new \MaarchRM\Business\Users\AccountImpl();

         $controller->delete('bblier');

         $dataAccess->read('bblier');
    }
}
