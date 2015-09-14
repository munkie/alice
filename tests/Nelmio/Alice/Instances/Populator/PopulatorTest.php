<?php

/*
 * This file is part of the Alice package.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\Alice\Instances\Populator;

use Nelmio\Alice\Fixtures\ParameterBag;
use Nelmio\Alice\Instances\Collection;
use Nelmio\Alice\Fixtures\Fixture;
use Nelmio\Alice\Instances\Populator\Methods\ArrayAdd;
use Nelmio\Alice\Instances\Processor\Processor;
use Nelmio\Alice\support\extensions\CustomPopulator;
use Nelmio\Alice\support\models\SingularProperties;
use Nelmio\Alice\Util\TypeHintChecker;

class PopulatorTest extends \PHPUnit_Framework_TestCase
{
    const CONTACT = 'Nelmio\Alice\support\models\Contact';
    const PLURAL = 'Nelmio\Alice\support\models\PluralProperties';
    const SINGULAR = 'Nelmio\Alice\support\models\SingularProperties';

    /**
    * @var Collection
    */
    protected $objects;

    /**
     * @var Populator
     */
    protected $populator;

    protected function createPopulator(array $options = [])
    {
        $objects = isset($options['objects']) ? $options['objects'] : new Collection;
        $defaults = [
            'objects' => $objects,
            'processor' => new Processor($objects, [], new ParameterBag()),
            'methods' => []
        ];
        $options = array_merge($defaults, $options);

        return $this->populator = new Populator($options['objects'], $options['processor'], $options['methods']);
    }

    public function testAddPopulator()
    {
        $class = self::CONTACT;
        $fixture = new Fixture($class, 'test', [ 'magicProp' => 'magicValue' ], null);
        $object = new $class(new \Nelmio\Alice\support\models\User);

        $this->createPopulator([ 'objects' => new Collection([ 'test' => $object ]) ]);
        $this->populator->addPopulator(new CustomPopulator);
        $this->populator->populate($fixture);
        $this->assertEquals('magicValue set by magic setter', $object->magicProp);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage All setters passed into Populator must implement MethodInterface.
     */
    public function testOnlyMethodInterfacesCanBeUsedToInstantiateThePopulator()
    {
        $this->createPopulator(['methods' => ['CustomPopulator']]);
    }

    /**
     * @TODO https://github.com/nelmio/alice/pull/220#issuecomment-113524513
     *
     * @dataProvider arrayAddPropertyProvider
     *
     * @param string $fieldName 'field' property name
     * @param string $propertyName 'property' property name
     */
    public function testArrayAddSingularAdder($fieldName, $propertyName)
    {
        $class = self::PLURAL;
        $fixture = new Fixture(
            $class,
            'test',
            [
                $fieldName => ['a', 'b', 'c'],
                $propertyName => ['q', 'w', 'e']
            ],
            null
        );
        $object = new $class();

        $this->createPopulator([ 'objects' => new Collection([ 'test' => $object ]) ]);
        $this->populator->addPopulator(new ArrayAdd(new TypeHintChecker()));
        $this->populator->populate($fixture);

        $this->assertEquals(['a', 'b', 'c'], $object->getFields());
        $this->assertEquals(['q', 'w', 'e'], $object->getProperties());
    }

    public static function arrayAddPropertyProvider()
    {
        return [
            'plural property names' => [
                'fields',
                'properties',
            ],
            'singular property name' => [
                'field',
                'property'
            ]
        ];
    }
}
