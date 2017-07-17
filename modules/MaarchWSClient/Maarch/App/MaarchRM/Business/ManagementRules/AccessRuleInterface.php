<?php

/* 
 *  Copyright (C) 2017 Maarch
 * 
 *  This file is part of bundle MaarchRM.
 *  Bundle MaarchRM is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 * 
 *  Bundle MaarchRM is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 * 
 *  You should have received a copy of the GNU General Public License
 *  along with bundle MaarchRM.  If not, see <http://www.gnu.org/licenses/>.
 */

 namespace MaarchRM\Business\ManagementRules;

/**
 * Access rule interface
 * @author Alexis Ragot <alexis.ragot@maarch.org>
 */
interface AccessRuleInterface
{
    /**
     * Read an accessRule
     * @param string $code The access rule code
     *
     * @return MaarchRM\Business\ManagementRules\Entities\AccessRule
     */
    public function read($code);

    /**
     * Create an access rule
     * @param MaarchRM\Business\ManagementRules\Entities\AccessRule $accessRule The access rule object
     */
    public function create($accessRule);

    /**
     * Search access rule by args
     * @param string                    $duration
     * @param string                    $orgId
     * @param Maarch\Http\Headers\Range $range
     *
     * @return array
     */
    public function search($duration, $orgId, $range = false);

    /**
     * Update an access rule
     * @param object $accessRule The access rule object
     * @return type
     */
    public function update($accessRule);

    /**
     * Delete an access rule
     * @param type $code
     */
    public function delete($code);
}
