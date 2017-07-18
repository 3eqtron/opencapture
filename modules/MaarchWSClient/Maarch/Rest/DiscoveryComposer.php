<?php
/*
 * Copyright (C) 2017 Maarch
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch\Rest;
/**
 * Provides information about the options on the requested resource
 */
class DiscoveryComposer
{
    
    /**
     * Get information about the resource
     * 
     * @return Psr\Http\Message\Response
     */
    public static function composeOptions()
    {
        $resourceDescription = Router::getTargetResource();

        $resourceDescription->path = "";
        $steps = Router::getSteps();
        foreach ($steps as $matched => $step) {
            $resourceDescription->path .= '/'.$step->getResourceLink()->getPath();
        }

        $allow = [];
        foreach ($resourceDescription->getMethods() as $methodDescription) {
            $allow[] = $methodDescription->getName();
        }

        $response = new \Maarch\Http\Message\Response();
        $response->withStatus(200)
                 ->withHeader('Content-Type', 'application/json')
                 ->withHeader('Allow', implode(', ', $allow))
                 ->withSerializedBody(\Maarch\Json::encode($resourceDescription, JSON_PRETTY_PRINT));

        return Server::setResponse($response);
    }

}