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
namespace Maarch\Reflection;
/**
 * Reflection property type
 * 
 * @package Maarch
 */
class ReflectionDataType
{
    const FACETS = "minLength maxLength pattern format minInclusive maxInclusive minExclusive maxExclusive precision scale minItems maxItems uniqueItems enum";
    const BUILTINS = "int integer float real double string bool boolean resource array object null";
    const SCALARS = "int integer float real double string bool boolean null";

    /**
     * @var string
     */
    protected $name;

    /**
     * @var DocComment
     */
    protected $docComment;

    /**
     * Constructor
     * @param string|Maarch\Reflection\ReflectionDocComment $docComment
     */
    public function __construct($docComment) 
    {
        if (is_string($docComment)) {
            $dummyComment = "/**
                              * @var ".$docComment;

            $docComment = new ReflectionDocComment($dummyComment);
        }

        $this->docComment = $docComment;
    }

    /**
     * Get the name of the component
     * @return string
     */
    public function getName() : string
    { 
        return $this->docComment->getTag('var');
    }

    /**
     * Get the string value
     * @return string
     */
    public function __toString() : string
    {
        return $this->docComment->getTag('var');
    }

    /**
     * Check if parameter type hint is a built-in type
     * @return boolean
     */
    public function isBuiltIn() : bool
    { 
        $base = $this->docComment->getTag('var');

        return in_array($base, explode(' ', static::BUILTINS));
    }

    /**
     * Check if parameter type hint is a built-in type
     * @return boolean
     */
    public function isScalar() : bool
    { 
        $base = $this->docComment->getTag('var');

        return in_array($base, explode(' ', static::SCALARS));
    }

    /**
     * Check if type type hint is a class name
     * @return boolean
     */
    public function isClass() : bool
    { 
        $name = $this->docComment->getTag('var');

        return $name && class_exists($name);
    }

    /**
     * Get the reflection class
     * @return ReflectionClass
     */
    public function getClass() : \Maarch\Reflection\ReflectionClass
    { 
        $name = $this->docComment->getTag('var');
        
        return new ReflectionClass($name);
    }

    /**
     * Check if parameter type hint is an array
     * @return boolean
     */
    public function isArray() : bool
    { 
        $type = $this->docComment->getTag('var');

        return substr($type, -2) == '[]' || $type == 'array';
    }

    /**
     * Checks if the item type is available
     * @return bool
     */
    public function hasItemType() : bool
    {
        $type = $this->docComment->getTag('var');

        return (substr($type, -2) == '[]');
    }

    /**
     * Returns the item type
     * @return ReflectionDataType
     */
    public function getItemType() : \Maarch\Reflection\ReflectionDataType
    { 
        if ($this->hasItemType()) {
            $type = $this->docComment->getTag('var');
            $typename = substr($type, 0, -2);

            return new \Maarch\Reflection\ReflectionDataType($typename);
        }
    }

    /**
     * Check if the type has a base type tag
     * @return bool
     */
    public function hasBaseType() : bool
    {
        return $this->docComment->hasTag('base');
    }

    /**
     * Get the ref type
     * @return Maarch\Reflection\ReflectionDataType
     */
    public function getBaseType()  : \Maarch\Reflection\ReflectionDataType
    {
        $base = $this->docComment->getTag('base');
        list($class, $name) = explode('::$', $base);

        $reflectionProperty = new ReflectionProperty($class, $name);

        return new ReflectionDataType($reflectionProperty->getDocComment());
    }

    /**
     * Get the list of restriction facets
     * @return array
     */
    public function getFacets() : array
    {
        $facets = [];

        $base = $this->docComment->getTag('base');

        foreach (explode(' ', static::FACETS) as $facet) {
            if ($this->docComment->hasTag($facet)) {
                $facets[$facet] = $this->docComment->getTag($facet);
            }
        }

        return $facets;
    }

    /**
     * Check the requested restriction facet
     * @param string $name
     * 
     * @return mixed
     */
    public function hasFacet($name) : bool
    {
        return $this->docComment->hasTag($name);
    }

    /**
     * Get the requested restriction facet
     * @param string $name
     * 
     * @return mixed
     */
    public function getFacet($name)
    {
        if ($this->docComment->hasTag($name)) {
            return $this->docComment->getTag($name);
        }
    }


    /**
     * Cast a value into type, accepting array of objects
     * @param mixed $source The source value, may be a scalar, object or array
     *
     * @return mixed The cast value
     */
    public function cast($source)
    {
        switch (true) {
            case $this->isArray() :
                return $this->castArray($source);

            case $this->isClass() : 
                $reflectionClass = $this->getClass();

                return $reflectionClass->setValues($source);

            case $this->isScalar() : 
                return $this->castScalar($source);
        }

        // Unknown type
        return $source;
    }

    protected function castScalar($source)
    {
        // Cast php types
        switch ($this->getName()) {
            case 'integer':
            case 'int':
                return (integer) $source;

            case 'boolean':
            case 'bool':
                return (boolean) $source;

            case 'float':
            case 'double':
            case 'real':
                return (float) $source;

            case 'string':
                return (string) $source;
        }
    }

    /**
     * Cast an array
     * @param array $items The collection of items
     *
     * @return array
     */
    protected function castArray($items)
    {
        $target = [];

        if (!is_array($items)) {
            $items = [$items];
        }

        $itemType = $this->getItemType();

        foreach ($items as $key => $item) {
            $target[$key] = $itemType->cast($item);
        }

        return $target;
    }

    /**
     * Validate data against schema
     * @param mixed $value The new value
     * 
     * @return void
     * @throws Maarch\Reflection\ReflectionException
     */
    public function validate($value)
    {
        if ($this->isArray()) {
            $this->validateArray($value);
        } else {
            $this->validateItem($value);
        }

        if ($this->hasBaseType()) {
            $reflectionDataType = $this->getBaseType();

            $reflectionDataType->validate($value);
        }
    }

    protected function validateArray($value)
    {
        if (!is_array($value)) {
            throw new ReflectionException(['Value is not a valid array']);
        }

        foreach ($value as $item) {
            $this->validateItem($item);
        }

        $this->validateMinItems($value);
        $this->validateMaxItems($value);
    }

    protected function validateItem($value)
    {
        if ($this->isClass()) {
            $this->validateObject($value);
        } else {
            $this->validateScalar($value);
        }        
    }

    protected function validateObject($value)
    {
        $classname = $this->docComment->getTag('var');
        if (substr($classname, -2) == '[]') {
            $classname = substr($classname, 0, -2);
        }
        if (get_class($value) != $classname) {
            throw new ReflectionException(['Value is not a valid %1$s', [$classname]]);
        }
    }

    protected function validateScalar($value)
    {
        $type = $this->docComment->getTag('var');
        if (substr($type, -2) == '[]') {
            $type = substr($type, 0, -2);
        }

        if (gettype($value) != $type) {
            throw new ReflectionException(['Value is not a valid %1$s', [$type]]);
        }

        switch ($type) {
            case 'string':
                $this->validateMinLength($value);
                $this->validateMaxLength($value);
                $this->validatePattern($value);
                $this->validateFormat($value);
                break;

            case 'int':
            case 'integer':
                $this->validateMinValue($value);
                $this->validateMaxValue($value);
                break;

            case 'float':
            case 'real':
            case 'double':
                $this->validateMinValue($value);
                $this->validateMaxValue($value);
                break;
        }

        $this->validateEnum($value);
    }

    protected function validateMinItems($value)
    {
        if ($this->hasFacet('minItems')) {
            $minItems = (int) $this->getFacet('minItems');
            if (count($value) < $minItems) {
                throw new ReflectionException(['Minimum number of items of %1$d not reached for array', [$minItems]]);
            }
        }
    }

    protected function validateMaxItems($value)
    {
        if ($this->hasFacet('maxItems')) {
            $maxItems = (int) $this->getFacet('maxItems');
            if (count($value) > $maxItems) {
                throw new ReflectionException(['Maximum number of items of %1$d exceeded for array', [$maxItems]]);
            }
        }
    }

    protected function validateMinLength($value)
    {
        if ($this->hasFacet('minLength')) {
            $minLength = (int) $this->getFacet('minLength');
            if (strlen($value) < $minLength) {
                throw new ReflectionException(['Minimum length of %1$d not reached for property %1$s::%2$s', [$minLength]]);
            }
        }
    }

    protected function validateMaxLength($value)
    {
        if ($this->hasFacet('maxLength')) {
            $maxLength = (int) $this->getFacet('maxLength');
            if (strlen($value) > $maxLength) {
                throw new ReflectionException(['Maximum length of %1$d exceeded for property %1$s::%2$s', [$maxLength]]);
            }
        }
    }

    protected function validatePattern($value)
    {
        if ($this->hasFacet('pattern')) {
            $pattern = $this->getFacet('pattern');

            if (!preg_match('#'.$pattern.'#', $value)) {
                throw new ReflectionException(['Value is not valid against pattern %1$s', [$pattern]]);
            }
        }
    }

    protected function validateFormat($value)
    {
        if ($this->hasFacet('format')) {
            $format = $this->getFacet('format');
            $patterns = [
                'date' => '\d{4}\-\d{2}\-\d{2}',
                'datetime' => '\d{4}\-\d{2}\-\d{2}T\d{2}\:\d{2}\:\d{2}',
                'duration' => '(\-\+)?P(\d+Y)?(\d+M)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?',
            ];

            if (!isset($patterns[$format])) {
                throw new ReflectionException(['Unknown format %1$s', [$format]]);
            }

            if (!preg_match('#^'.$patterns[$format].'$#', $value)) {
                throw new ReflectionException(['Value is not a valid %1$s', [$format]]);
            }
        }
    }

    protected function validateMinValue($value)
    {
        if ($this->hasFacet('minValue')) {
            $minValue = (float) $this->getFacet('minValue');
            if ($value < $minValue) {
                throw new ReflectionException(['Minimum value of %1$s not reached', [$minValue]]);
            }
        }
    }

    protected function validateMaxValue($value)
    {
        if ($this->hasFacet('maxValue')) {
            $maxValue = (float) $this->getFacet('maxValue');
            if ($value > $maxValue) {
                throw new ReflectionException(['Maximum value of %1$s exceeded', [$maxValue]]);
            }
        }
    }

    protected function validateEnum($value)
    {
        if ($this->hasFacet('enum')) {
            $enum = trim($this->getFacet('enum'));

            if (!in_array($value, str_getcsv($enum, ',', '"', '\\'))) {
                throw new ReflectionException(['Value not allowed. Possible values are %1$s', [$enum]]);
            }
        }
    }

}