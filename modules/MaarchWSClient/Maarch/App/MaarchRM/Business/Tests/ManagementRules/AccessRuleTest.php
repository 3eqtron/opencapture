<?php

namespace MaarchRM\Business\Tests\ManagementRules;

use PHPUnit\Framework\TestCase;

/**
 * Access Rule test class
 */
class AccessRuleTest extends TestCase
{

    /**
     * Test of the create method
     */
    public function testCreate()
    {
        $controller = new \MaarchRM\Business\ManagementRules\AccessRuleImpl();

        $accessRule = new \MaarchRM\Business\ManagementRules\Entities\AccessRule('code');
        $accessRule->name = "test1";
        $accessRule->description = "blabla";
        $accessRule->duration = "P999Y";

        $controller->create($accessRule);

        $acces = $controller->read($accessRule->code);

        $compare = '{"duration":"P999Y","name":"test1","description":"blabla"}';

        $this->assertSame($compare, json_encode($acces));
    }

    /**
    * Test of the read method
    */
    public function testRead()
    {
        $controller = new \MaarchRM\Business\ManagementRules\AccessRuleImpl();

        $acces = $controller->read("code");

        $compare = '{"duration":"P999Y","name":"test1","description":"blabla"}';

        $this->assertSame($compare, json_encode($acces));
    }

    /**
     * Test of the update method
     */
    public function testUpdate()
    {
        $controller = new \MaarchRM\Business\ManagementRules\AccessRuleImpl();

        $newAccessRule = new \MaarchRM\Business\ManagementRules\Entities\AccessRule('code');
        $newAccessRule->duration = "P9M";
        $newAccessRule->name = "test1";
        $newAccessRule->description = "blabla";

        $controller->update($newAccessRule);
        $access = $controller->read($newAccessRule->code);

        $compare = '{"duration":"P9M","name":"test1","description":"blabla"}';

        $this->assertSame($compare, json_encode($access));
    }

    /**
     * Test of the delete method
     */
    public function testDelete()
    {
        $this->expectException(\MaarchRM\Errors\EntityNotFound::class);
        $dataAccess = new \MaarchRM\DataAccess\AccessRules();
        $controller = new \MaarchRM\Business\ManagementRules\AccessRuleImpl();

        $controller->delete('code');

        $dataAccess->read('code');
    }
}
