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
 * The archive management appraisal rule
 * 
 * @author Cyril Vazquez <cyril.vazquez@maarch.org>
 */
class AppraisalRule extends ManagementRuleWithDurationAbstract
{
    /**
     * @const string
     */
    const DATE = 'date';


    /**
     * The final action on archive units
     * 
     * @var string
     * @enum "preservation", "destruction"
     */
    protected $finalDisposition;

    /**
     * @var string
     * @ref simpleTypes::date
     */
    protected $myDate;
}
