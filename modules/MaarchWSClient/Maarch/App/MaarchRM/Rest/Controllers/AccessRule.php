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
 * 
 * 
 * @doc fr Règle d'accès
 * Les règles d'accès définissent les droits sur les archives
 * en fonction de l'appartenance des utilisateurs aux services
 * acteurs du système et ceux en relation avec l'archive.
 * 
 */
class AccessRule
{
    /**
     * The access rule code
     * 
     * This is a description
     * on multiple lines
     * 
     * @var string
     * 
     * @pattern [A-Za-z_][A-Za-z0-9_]*
     */
    public $code;

    /**
     * Constructor
     * @param string $code @template
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * Read
     * @param Psr\Http\Message\ServerRequestInterface $httpRequest  @request
     * @param Psr\Http\Message\ResponseInterface      $httpResponse @response
     * 
     * @return MaarchRM\Rest\Responses\ReadAccessRule The access rule object
     * 
     * @throws MaarchRM\Errors\EntityNotFound
     * @api
     * 
     * @response MaarchRM\Rest\Responses\ReadAccessRule
     * @error MaarchRM\Errors\EntityNotFound
     */
    public function get(\Psr\Http\Message\ServerRequestInterface $httpRequest, \Psr\Http\Message\ResponseInterface $httpResponse) //: \MaarchRM\Rest\Responses\ReadAccessRule
    {
        $accessRuleModel = new \MaarchRM\Business\ManagementRules\AccessRuleImpl();
        $accessRule = $accessRuleModel->read($this->code);

        //$httpResponse = new \MaarchRM\Rest\Responses\ReadAccessRule($accessRule);
        // Negociate accepted content type ?
        $httpResponse->withHeader('Content-Type', 'application/json');
        $httpResponse->withSerializedBody(json_encode($accessRule, JSON_PRETTY_PRINT));

        return $httpResponse;
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

        $accessRulesModel->add($this->code, $accessRule);

        $createAccessRule = new \MaarchRM\Rest\Responses\CreateAccessRule();

        return $createAccessRule;
    }
}
