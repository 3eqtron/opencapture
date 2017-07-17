<?php
/*
 * Copyright (C) 2016 Maarch
 *
 * This file is part of Dice.
 *
 * Dice is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Dice is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Dice.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace MaarchRM;

/**
 * MAARS stands for Maarch Archival And Retrieval System
 *
 * @package MaarchRM
 * @author  Cyril VAZQUEZ (Maarch) <cyril.vazquez@maarch.org>
 *
 */
class ServiceContainer
{
    /**
     * Get PDO service for RM
     * @param string $dsn conf The dsn
     *
     * @return PDO
     */
    public static function pdoRm($dsn) : \PDO
    {
        $pdo = new \PDO($dsn, null, null, [\PDO::ATTR_PERSISTENT => true]);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
