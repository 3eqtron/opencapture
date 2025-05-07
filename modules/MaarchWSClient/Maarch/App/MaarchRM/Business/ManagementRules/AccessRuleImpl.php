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
namespace MaarchRM\Business\ManagementRules;

/**
 * Access rule
 *
 * @author Cyril Vazquez <cyril.vazquez@maarch.org>
 */
class AccessRuleImpl implements AccessRuleInterface
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dataAccess = new \MaarchRM\DataAccess\AccessRules();
    }

    /**
     * Read an accessRule
     * @param string $code The access rule code
     *
     * @return MaarchRM\Business\ManagementRules\Entities\AccessRule
     */
    public function read($code) : \MaarchRM\Business\ManagementRules\Entities\AccessRule
    {
        return $this->dataAccess->read($code);
    }

    /**
     * Create an access rule
     * @param MaarchRM\Business\ManagementRules\Entities\AccessRule $accessRule The access rule object
     */
    public function create($accessRule)
    {
        $this->dataAccess->insert($accessRule);
    }

    /**
     * Search access rule by args
     * @param string                    $duration
     * @param string                    $orgId
     * @param Maarch\Http\Headers\Range $range
     *
     * @return array
     */
    public function search($duration, $orgId, $range = false) : array
    {
        return $this->dataAccess->search($duration, $orgId, $range);
    }

    /**
     * Update an access rule
     * @param MaarchRM\Business\ManagementRules\Entities\AccessRule $accessRule The access rule object
     */
    public function update($accessRule)
    {
        $this->dataAccess->update($accessRule);
    }

    /**
     * Delete an access rule
     * @param string $code The access rule code
     */
    public function delete($code)
    {
        $this->dataAccess->delete($code);
    }
}
