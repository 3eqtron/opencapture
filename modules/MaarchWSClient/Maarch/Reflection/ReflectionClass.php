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
 * Reflection class
 * 
 * @package Maarch
 * @author  Cyril Vazquez Maarch <cyril.vazquez@maarch.org>
 */
class ReflectionClass
    extends Reflection
{
    /**
     * Constructor
     * @param mixed $class The class name or the reflectionClass
     */
    public function __construct($class)
    {
        if ($class instanceof \Reflector) {
            parent::__construct($class);
        } else {
            parent::__construct(new \ReflectionClass($class));
        }
    }

    /**
     * Returns the class identifier.
     * @return string
     */
    public function getId() : string
    {
        return $this->reflection->name;
    }

    /**
     * Checks if class has a __toString method
     * 
     * @return bool
     */
    public function isStringifyable() : bool
    {
        return $this->hasMethod('__toString');
    }

    /**
     * Check if class has a __construct method
     * 
     * @return bool
     */
    public function hasConstructor() : bool
    {
        return ($this->hasMethod('__construct'));
    }

    /**
     * Check if class has a __invoke method
     * 
     * @return bool
     */
    public function isCallable() : bool
    {
        return $this->hasMethod('__invoke');
    }

    /**
     * Get the class has __invoke method
     * 
     * @return Maarch\Reflection\ReflectionMethod
     */
    public function getCallable() : \Maarch\Reflection\ReflectionMethod
    {
        return new ReflectionMethod($this->reflection->name, '__invoke');
    }


    /**
     * Get the class methods
     * @param int $filter
     * 
     * @return Maarch\Reflection\ReflectionMethod[]
     */
    public function getMethods($filter = 0) : array
    {
        $methods = [];

        foreach ($this->reflection->getMethods($filter) as $reflectionMethod) {
            $methods[] = new ReflectionMethod($reflectionMethod);
        }

        return $methods;
    }

    /**
     * Get the class method
     * @param string $name The name of the method
     * 
     * @return Maarch\Reflection\ReflectionMethod
     */
    public function getMethod($name) : \Maarch\Reflection\ReflectionMethod
    {
        return new ReflectionMethod($this->reflection->name, $name);
    }

    /**
     * Get the constructor
     * 
     * @return Maarch\Reflection\ReflectionMethod
     */
    public function getConstructor() : \Maarch\Reflection\ReflectionMethod
    {
        if (method_exists($this->reflection->name, '__construct')) {
            return new ReflectionMethod($this->reflection->name, '__construct');
        }
    }

    /**
     * Checks if the class allows additional properties, not declared in the class
     * 
     * @return bool
     */
    public function allowsAdditionalProperties() : bool
    {
        return (bool) $this->docComment->hasTag('additionalProperties')
            && empty($this->docComment->getTag('additionalProperties')); 
    }

    /**
     * Get the class properties
     * 
     * @return Maarch\Reflection\ReflectionProperty[]
     */
    public function getProperties() : array
    {
        $properties = [];

        foreach ($this->reflection->getProperties() as $reflectionProperty) {
            $properties[] = new ReflectionProperty($reflectionProperty);
        }

        return $properties;
    }

    /**
     * Get the class property
     * @param string $name The name of the property
     * 
     * @return Maarch\Reflection\ReflectionProperty
     */
    public function getProperty($name) : \Maarch\Reflection\ReflectionProperty
    {
        return new ReflectionProperty($this->reflection->name, $name);
    }

    /**
     * Get the parent class
     * @return Maarch\Reflection\ReflectionClass
     */
    public function getParentClass() : \Maarch\Reflection\ReflectionClass
    {
        if ($parentClass = get_parent_class($this->reflection->name)) {
            return new ReflectionClass($parentClass);
        }
    }

    /**
     * Get the traits
     * @return Maarch\Reflection\ReflectionClass[]
     */
    public function getTraits() : array
    {
        $reflectionTraits = [];

        foreach ($this->getTraitNames() as $trait) {
            $reflectionTraits[] = new ReflectionClass($trait);
        }

        return $reflectionTraits;
    }

    /**
     * Get the class constants
     * 
     * @return Maarch\Reflection\reflectionConstant[]
     */
    public function getConstants() : array
    {
        $constants = [];

        if ($this->isUserDefined()) {
            $content = file_get_contents($this->reflection->getFileName());
            $tokens = token_get_all($content);

            $docComment = null;
            $isConst = false;

            foreach ($tokens as $token) {
                if (!is_array($token)) {
                    continue;
                }
                list($tokenType, $tokenValue) = $token;

                switch ($tokenType) {
                    // ignored tokens
                    case T_WHITESPACE:
                    case T_COMMENT:
                        break;

                    case T_DOC_COMMENT:
                        $docComment = $tokenValue;
                        break;

                    case T_CONST:
                        $isConst = true;
                        break;

                    case T_STRING:
                        if ($isConst) {
                            $docComment = preg_replace("/\n\s+\*/", "\n *", $docComment);

                            $constants[$tokenValue] = new ReflectionConstant($tokenValue, parent::getConstant($tokenValue), $docComment);
                        }
                        $docComment = null;
                        $isConst = false;
                        break;

                    // all other tokens reset the parser
                    default:
                        $docComment = null;
                        $isConst = false;
                        break;
                }
            }
        } else {
            foreach ($this->reflection->getConstants() as $name => $value) {
                $constants[$name] = new ReflectionConstant($name, $value);
            }
        }

        // recursively parse parent doc blocs and parent traits
        if ($parentClass = $this->reflection->getParentClass()) {
            $parentConstants = $parentClass->getConstants();
            if (!empty($parentConstants)) {
                $constants = array_merge($constants, $parentConstants);
            }
        }

        return $constants;
    }

    /**
     * Get the class constant
     * @param string $name The name of the constant
     * 
     * @return Maarch\Reflection\ReflectionConstant
     */
    public function getConstant($name) : \Maarch\Reflection\ReflectionConstant
    {
        if ($this->reflection->isUserDefined()) {

            $content = file_get_contents($this->reflection->getFileName());
            $tokens = token_get_all($content);

            $docComment = null;
            $isConst = false;

            foreach ($tokens as $token) {
                if (!is_array($token)) {
                    continue;
                }
                list($tokenType, $tokenValue) = $token;

                switch ($tokenType) {
                    // ignored tokens
                    case T_WHITESPACE:
                    case T_COMMENT:
                        break;

                    case T_DOC_COMMENT:
                        $docComment = $tokenValue;
                        break;

                    case T_CONST:
                        $isConst = true;
                        break;

                    case T_STRING:
                        if ($isConst && $name == $tokenValue) {
                            $docComment = preg_replace("/\n\s+\*/", "\n *", $docComment);

                            return new ReflectionConstant($name, $this->reflection->getConstant($name), $docComment);
                        }
                        $docComment = null;
                        $isConst = false;
                        break;

                    // all other tokens reset the parser
                    default:
                        $docComment = null;
                        $isConst = false;
                        break;
                }
            }
        } else {
            if ($this->reflection->hasConstant($name)) {
                return new ReflectionConstant($name, parent::getConstant($name));
            }
        }

        // recursively parse parent doc blocs and parent traits
        if ($parentClass = $this->reflection->getParentClass()) {
            if ($parentClass->hasConstant($name)) {
                return $parentClass->getConstant($name);
            }
        }
    }

    /**
     * Cast an object into class
     * @param object|array $source The source object
     * @param object       $target The target object
     *
     * @return object
     */
    public function setValues($source, $target=null)
    {
        if (is_null($target)) {
            $target = $this->newInstanceWithoutConstructor();
        }

        if (is_object($source)) {
            $source = get_object_vars($source);
        }

        foreach ($source as $name => $sourceProperty) {
            if (!$this->hasProperty($name)) {
                $target->{$name} = $value;
            } else {
                $reflectionProperty = $this->getProperty($name);

                if (!$reflectionProperty->isPublic()) {
                    $reflectionProperty->setAccessible(true);
                }
            
                if (!$reflectionProperty->hasType()) {
                    $targetProperty = $sourceProperty;
                } else {
                    $propertyType = $reflectionProperty->getType();
                    $targetProperty = $propertyType->cast($sourceProperty);
                }

                $reflectionProperty->setValue($target, $targetProperty);
            }

        }

        return $target;
    }

    /**
     * Validate the object against the schema
     * @param object $object
     * 
     * @return void
     * @throws Maarch\Reflection\ReflectionException
     */
    public function validate($object)
    {
        foreach ($this->getProperties() as $reflectionProperty) {
            $reflectionProperty->setAccessible(true);

            $value = $reflectionProperty->getValue($object);

            $reflectionProperty->validate($value);
        }

        foreach ($object as $name => $value) {
            if (!$this->hasProperty($name) && !$this->allowsAdditionalProperties()) {
                throw new \Maarch\Reflection\ReflectionException(['Undefined property %1$s::%2$s', [get_class($object), $name]]);
            }
        }
    }
}
