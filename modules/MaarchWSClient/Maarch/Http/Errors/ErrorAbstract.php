<?php
/*
 * Copyright (C) 2015 Maarch
 *
 * This file is part of Maarch.
 *
 * Maarch is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Maarch is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Maarch. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch\Http\Errors;
/**
 * Exception for unknown error
 */
abstract class ErrorAbstract
    extends \Maarch\Exception
    implements \JsonSerializable, \Throwable
{
    /**
     * Constructor
     * @param string    $message
     * @param int       $code
     * @param Throwable $previous
     */
    public function __construct(string $message, int $code=400, \Throwable $previous=null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the html version
     * @return string
     * @representation 
     * @mediaType text/html
     */
    public function toHtml()
    {
        return $this->getMessage();
    }

    /**
     * Return the serializable data
     * @return mixed
     */
    public function JsonSerialize()
    {
        return $this->getMessage();

        if ($trace = $this->getTrace()) {
            return [$this-getMessage(), $trace];
            /*$traceHeader = new \Maarch\Http\Headers\ExtendedList();
            foreach ($trace as $step) {
                $info = new \Maarch\Http\Headers\ExtendedValue();
                if (isset($step['file'])) {
                    $info->params['file'] = $step['file'];
                    $info->params['line'] = $step['line'];
                }
                if (isset($step['class'])) {
                    $info->value = $step['class'].$step['type'].$step['function'];                    
                } else {
                    $info->value = $step['function'];
                }
                $args = [];
                foreach ($step['args'] as $arg) {
                    $args[] = gettype($arg);
                }
                $info->params['args'] = implode(', ', $args);

                $traceHeader->append($info);
            }

            $httpResponse->withAddedHeader('X-Trace', $traceHeader);*/

        }
    }
}