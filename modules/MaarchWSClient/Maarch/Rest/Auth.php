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
 * The REST auth manager
 */
class Auth
{
    /**
     * @var string
     */
    protected static $username;

    /**
     * Check the authentication credentials with the selected identityProvider
     * 
     * @return bool
     */
    public static function checkAuthentication()
    {
        if (App::hasIdentityProvider()) {
            $defaultIdentityProvider = App::getIdentityProvider();
        }

        $verifiedIdentities = [];

        foreach (Router::getSteps() as $path => $routingStep) {
            $resourceLinkDescription = $routingStep->getResourceLink();

            if ($resourceLinkDescription->isPublic()) {
                continue;
            }
            
            if ($identityProviderId = $resourceLinkDescription->getAuthentication()) {
                if (isset($verifiedIdentities[$identityProviderId])) {
                    continue;
                }

                if (!App::hasIdentityProvider($identityProviderId)) {
                    throw new \Maarch\Http\Errors\InternalServerError('Authentication method is not implemented :'.basename($identityProviderId));
                }

                $identityProvider = App::getIdentityProvider($identityProviderId);
            } elseif (isset($defaultIdentityProvider)) {
                $identityProvider = $defaultIdentityProvider;
                $identityProviderId = 'default';
            } else {
                throw new \Maarch\Http\Errors\InternalServerError('No authentication method found.');
            }

            static::$username = $identityProvider->validateCredentials();

            $verifiedIdentities[$identityProviderId] = static::$username;
        }
    }

    /**
     * Returns the authenticated user name
     * @return string
     */
    public static function getUsername()
    {
        return static::$username;
    }
}