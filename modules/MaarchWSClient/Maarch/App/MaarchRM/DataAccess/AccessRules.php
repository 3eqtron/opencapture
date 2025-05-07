<?php
/*
 * Copyright (C) 2016 Maarch
 *
 * This file is part of MaarchRM.
 *
 * MaarchRM is a free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * MaarchRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MaarchRM. If not, see <http://www.gnu.org/licenses/>.
 */
namespace MaarchRM\DataAccess;

/**
 * Access rule
 *
 * @author Cyril Vazquez <cyril.vazquez@maarch.org>
 */
class AccessRules
{
    /**
     * @var \PDO The PDO object
     */
    protected $pdo;

    /**
     * @var PDOStatement The pdo select statement
     */
    protected $statements;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $container;

        $this->pdo = $container->get('pdoRm');
    }

    /**
     * Count a collection
     *
     * @return int
     */
    public function count()
    {
        $countStmt = $this->pdo->prepare('SELECT COUNT(1) FROM "AccessRules"');

        $countStmt->execute();

        return $countStmt->fetchColumn();
    }

    /**
     * Read a collection
     * @param Maarch\Http\Headers\Range $range
     *
     * @return array
     */
    public function index($range = false)
    {
        $indexStmt = $this->pdo->prepare('SELECT "code", "name" FROM "AccessRules" OFFSET ? LIMIT ?');

        $offset = 0;
        $limit = 500;
        if ($range) {
            if ($range->start) {
                $offset = $range->start-1;
            }
            if ($range->end) {
                $limit = $range->end-$range->start+1;
            }
        }

        if ($indexStmt->execute([$offset, $limit])) {
            $index = [];
            $accessRulesData = $indexStmt->fetchAll(\PDO::FETCH_OBJ);
            foreach ($accessRulesData as $accessRuleData) {
                $index[$accessRuleData->code] = $accessRuleData->name;
            }

            return $index;
        }
    }

    /**
     * Search access rules by args
     * @param string $duration
     * @param string $orgId
     *
     * @return array
     */
    public function search($duration = false, $orgId = false)
    {
        $asserts = [];
        if (!empty($duration)) {
            $asserts[] = "data->>'duration'='$duration'";
        }
        if (!empty($orgId)) {
            $asserts[] = "data->'entries' @> '[{\"orgId\":\"$orgId\"}]'";
        }

        $sqlQueryString = 'SELECT * FROM "AccessRules" WHERE '.implode(' AND ', $asserts);
        $searchStmt = $this->pdo->prepare($sqlQueryString);

        if ($searchStmt->execute()) {
            $index = [];
            $accessRulesData = $searchStmt->fetchAll(\PDO::FETCH_OBJ);
            foreach ($accessRulesData as $accessRuleData) {
                $accessRule = new \MaarchRM\Business\ManagementRules\Entities\AccessRule($accessRuleData->code);
                $accessRule->duration = $accessRuleData->duration;
                $accessRule->description = $accessRuleData->description;
                $accessRule->name = $accessRuleData->name;

                $index[$accessRuleData->code] = $accessRule;
            }

            return $index;
        }
    }

    /**
     * Create an access rule
     * @param object $accessRule
     *
     * @return bool
     */
    public function insert($accessRule)
    {
        if (!isset($this->statements['insert'])) {
            $this->statements['insert'] = $this->pdo->prepare('INSERT INTO "AccessRules" ("code", "data") VALUES (?, ?)');
        }

        $code = $accessRule->code;

        try {
            $this->statements['insert']->execute([$code, json_encode($accessRule)]);
        } catch (\PDOException $PDOException) {
            list($code, $class) = \Maarch\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($code);
        }
    }

    /**
     * Read an access rule
     * @param string $code The access rule code
     *
     * @return MaarchRM\Business\ManagementRules\AccessRule
     */
    public function read($code)
    {
        if (!isset($this->statements['read'])) {
            $this->statements['read'] = $this->pdo->prepare('SELECT * FROM "AccessRules" WHERE code=?');
        }

        if ($this->statements['read']->execute([$code])) {
            $data = $this->statements['read']->fetchObject();

            if (!$data) {
                throw new \MaarchRM\Errors\EntityNotFound('Access rule', $code);
            }

            $accessRuleDecode = json_decode($data->data);


            $accessRule = new \MaarchRM\Business\ManagementRules\Entities\AccessRule($data->code);
            $accessRule->name = $accessRuleDecode->name;
            $accessRule->description = $accessRuleDecode->description;
            $accessRule->duration = $accessRuleDecode->duration;

            return $accessRule;
        }
    }

    /**
     * Data access method to update an access rule
     *
     * @param MaarchRM\Business\ManagementRules\AccessRule $accessRule The access rule object
     * @throws \PDOException
     */
    public function update($accessRule)
    {
        //  return $this->read($code);
        if (!isset($this->statements['update'])) {
            $this->statements['update'] = $this->pdo->prepare('UPDATE "AccessRules" SET data=?
             WHERE code=?');
        }

        $code = $accessRule->code;

        try {
            $this->statements['update']->execute([json_encode($accessRule), $code]);
        } catch (\PDOException $PDOException) {
            list($code, $class) = \Maarch\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($code);
        }
    }

    /**
     * Data access method to delete an access rule
     *
     * @param string $code The access rule code
     * @throws \PDOException
     */
    public function delete($code)
    {
        if (!isset($this->statements['delete'])) {
            $this->statements['delete'] = $this->pdo->prepare('DELETE FROM "AccessRules" WHERE code = ?');
        }

        try {
            $this->statements['delete']->execute([$code]);
        } catch (\PDOException $PDOException) {
            list($code, $class) = \Maarch\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($code);
        }
    }
}
