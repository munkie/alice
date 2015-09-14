<?php

/*
 * This file is part of the Alice package.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\Alice\Instances\Populator\Methods;

use Symfony\Component\Form\Util\FormUtil;
use Symfony\Component\PropertyAccess\StringUtil;

use Nelmio\Alice\Fixtures\Fixture;
use Nelmio\Alice\Util\TypeHintChecker;

class ArrayAdd implements MethodInterface
{
    /**
     * @var TypeHintChecker
     */
    protected $typeHintChecker;

    public function __construct(TypeHintChecker $typeHintChecker)
    {
        $this->typeHintChecker = $typeHintChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function canSet(Fixture $fixture, $object, $property, $value)
    {
        return is_array($value) && null !== $this->findAdderMethod($object, $property);
    }

    /**
     * {@inheritDoc}
     */
    public function set(Fixture $fixture, $object, $property, $value)
    {
        $method = $this->findAdderMethod($object, $property);
        foreach ($value as $val) {
            $val = $this->typeHintChecker->check($object, $method, $val);
            $object->{$method}($val);
        }
    }

    /**
     * Finds the method used to append values to the named property.
     *
     * @param mixed  $object
     * @param string $property
     * @return string|null Adder method name or null if method not found
     */
    private function findAdderMethod($object, $property)
    {
        if (method_exists($object, $method = 'add'.$property)) {
            return $method;
        }

        foreach ($this->singularify($property) as $singularForm) {
            if (method_exists($object, $method = 'add'.$singularForm)) {
                return $method;
            }
        }

        if (method_exists($object, $method = 'add'.rtrim($property, 's'))) {
            return $method;
        }

        if (substr($property, -3) === 'ies' && method_exists($object, $method = 'add'.substr($property, 0, -3).'y')) {
            return $method;
        }

        return null;
    }

    /**
     * Returns singular forms of a property name.
     *
     * @param string $property
     * @return string[] array of possible singular forms
     */
    private function singularify($property)
    {
        if (class_exists('Symfony\Component\PropertyAccess\StringUtil')
            && method_exists('Symfony\Component\PropertyAccess\StringUtil', 'singularify')
        ) {
            return (array) StringUtil::singularify($property);
        } elseif (class_exists('Symfony\Component\Form\Util\FormUtil')
            && method_exists('Symfony\Component\Form\Util\FormUtil', 'singularify')
        ) {
            return (array) FormUtil::singularify($property);
        } else {
            return [];
        }
    }
}
