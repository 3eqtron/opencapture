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
namespace MaarchRM\Rest\Controllers;

/**
 * Access rule
 *
 * @author Cyril Vazquez <cyril.vazquez@maarch.org>
 * @additionalProperties
 */
class AccessRules
{
    /**
     * @var MaarchRM\Rest\Controllers\AccessRule
     * @path {code}
     */
    public $accessRule;

    /**
     * Read
     * @param Maarch\Http\Headers\Range $range    @header
     * @param string                    $duration @query
     * @param string                    $orgId    @query
     *
     * @return MaarchRM\Rest\Responses\ReadAccessRules
     * @api
     *
     * @request MaarchRM\Rest\Requests\ReadAccessRules
     * @response MaarchRM\Rest\Responses\ReadAccessRules
     */
    public function get(\Maarch\Http\Headers\Range $range = null, string $duration = null, string $orgId = null)
    {
        $accessRulesModel = new \MaarchRM\Business\ManagementRules\AccessRulesImpl();
        if ($duration || $orgId) {
            $accessRules = $accessRulesModel->search($duration, $orgId, $range);
        } else {
            $accessRules = $accessRulesModel->index($range);
        }

        $httpResponse = new \Maarch\Http\Message\Response();
        $httpResponse->withSerializedBody(\Maarch\Json::encode($accessRules, JSON_PRETTY_PRINT));
        $httpResponse->withHeader('Content-Type', 'application/json');

        if ($range) {
            $totalCount = $accessRulesModel->totalCount();

            $start = (int) $range->start;
            $end = ($range->start + $accessRulesModel->count() - 1);

            $httpResponse->withHeader('Content-Range', 'items '.$start.'-'.$end.'/'.$totalCount);
        }

        return $httpResponse;
    }

    /**
     * Read
     * @param Maarch\Http\Headers\Range $range    @header
     * @param string                    $duration @query
     *
     * @return MaarchRM\Rest\Responses\ReadAccessRules
     * @api
     *
     * @request MaarchRM\Rest\Requests\ReadAccessRules
     * @response MaarchRM\Rest\Responses\ReadAccessRules
     */
    public function view(\Maarch\Http\Headers\Range $range = null, string $duration = null) : \MaarchRM\Rest\Responses\ReadAccessRules
    {
        $accessRulesModel = new \MaarchRM\Business\ManagementRules\AccessRulesImpl();

        if ($duration) {
            $accessRules = $accessRulesModel->search($duration, $range);
        } else {
            $accessRules = $accessRulesModel->index($range);
        }

        $readAccessRulesResponse = new \MaarchRM\Rest\Responses\ReadAccessRules($accessRules);
        if ($range) {
            $totalCount = $accessRulesModel->totalCount();

            $start = (int) $range->start;
            $end = ($range->start + $accessRulesModel->count() - 1);

            $readAccessRulesResponse->setContentRange($start, $end, $totalCount);
        }

        return $readAccessRulesResponse;
    }

    /**
     * Create an access rule object
     * @param object $accessRule @entity
     *
     * @return MaarchRM\Rest\Responses\CreateAccessRule
     * @api
     *
     */
    public function post($accessRule) : \MaarchRM\Rest\Responses\CreateAccessRule
    {
        $accessRulesModel = new \MaarchRM\Business\ManagementRules\AccessRules();

        $accessRulesModel->create($accessRule);

        $createAccessRule = new \MaarchRM\Rest\Responses\CreateAccessRule();

        return $createAccessRule;
    }
}
