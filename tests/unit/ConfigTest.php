<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Config;
use UnexpectedValueException;

final class ConfigTest extends Unit
{
    private ?\herbie\Config $config = null;

    private array $testValues = [
        'bool' => true,
        'int' => 2,
        'float' => 1.75,
        'array' => [1, 2, 3],
        'string' => 'yes',
    ];
    private array $nestedTestValues = [
        'one' => [
            'bool' => false,
            'two' => [
                'int' => 3,
                'three' => [
                    'float' => 2.25,
                    'four' => [
                        'array' => [true, 4, 3.5, ['a', 'b', 'c'], 'true'],
                        'five' => [
                            'string' => 'no',
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function testGetAsBool(): void
    {
        $this->assertIsBool($this->config->getAsBool('bool'));
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Value for "string" is not a bool');
        $this->assertIsNotBool($this->config->getAsBool('string'));
    }

    public function testGetAsInt(): void
    {
        $this->assertIsInt($this->config->getAsInt('int'));
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Value for "bool" is not an int');
        $this->assertIsNotInt($this->config->getAsInt('bool'));
    }

    public function testGetAsFloat(): void
    {
        $this->assertIsFloat($this->config->getAsFloat('float'));
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Value for "int" is not a float');
        $this->assertIsNotFloat($this->config->getAsFloat('int'));
    }

    public function testGetAsString(): void
    {
        $this->assertIsString($this->config->getAsString('string'));
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Value for "float" is not a string');
        $this->assertIsNotString($this->config->getAsString('float'));
    }

    public function testGetAsArray(): void
    {
        $this->assertIsArray($this->config->getAsArray('array'));
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Value for "string" is not an array');
        $this->assertIsNotArray($this->config->getAsArray('string'));
    }

    public function testGetAsConfig(): void
    {
        $this->assertIsObject($this->config->getAsConfig('array'));
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Config for "bool" is not an array');
        $this->assertIsNotObject($this->config->getAsConfig('bool'));
    }

    public function testGetAsConfigWithNull(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Config for "not-existing-key" not found');
        $this->assertIsNotObject($this->config->getAsConfig('not-existing-key'));
    }

    public function testCheck(): void
    {
        $this->assertTrue($this->config->check('string'));
        $this->assertFalse($this->config->check('not-existing-key'));
    }

    public function testGetWithEmptyName(): void
    {
        $values = $this->config->get('');
        $this->assertIsArray($values);
        $this->assertEquals($this->testValues, $values);
    }

    public function testNestedValues(): void
    {
        $config = new Config($this->nestedTestValues);
        $this->assertIsBool($config->get('one.bool'));
        $this->assertIsInt($config->get('one.two.int'));
        $this->assertIsFloat($config->get('one.two.three.float'));
        $this->assertIsArray($config->get('one.two.three.four.array'));
        $this->assertIsString($config->get('one.two.three.four.five.string'));
    }

    public function testFlatten(): void
    {
        $flatten = $this->config->flatten();
        $this->assertIsArray($flatten);
        $expected = [
            'array.0' => 1,
            'array.1' => 2,
            'array.2' => 3,
            'bool' => true,
            'float' => 1.75,
            'int' => 2,
            'string' => 'yes',
        ];
        $this->assertEquals($expected, $flatten);
    }

    public function testFlattenWithNested(): void
    {
        $config = new Config($this->nestedTestValues);
        $flatten = $config->flatten();
        $this->assertIsArray($flatten);
        $expected = [
            'one.bool' => false,
            'one.two.int' => 3,
            'one.two.three.float' => 2.25,
            'one.two.three.four.array.0' => true,
            'one.two.three.four.array.1' => 4,
            'one.two.three.four.array.2' => 3.5,
            'one.two.three.four.array.3.0' => 'a',
            'one.two.three.four.array.3.1' => 'b',
            'one.two.three.four.array.3.2' => 'c',
            'one.two.three.four.array.4' => 'true',
            'one.two.three.four.five.string' => 'no',
        ];
        $this->assertEquals($expected, $flatten);
    }

    protected function _before(): void
    {
        $this->config = new Config($this->testValues);
    }
}
