<?php

namespace Tests\Unit;

use herbie\Config;

class ConfigTest extends \Codeception\Test\Unit
{
    /**
     * @var Config
     */
    private $config;
    private $testValues = [
        'bool' => true,
        'int' => 2,
        'float' => 1.75,
        'array' => [1, 2, 3],
        'string' => 'yes'
    ];

    protected function _before()
    {
        $this->config = new Config($this->testValues);
    }

    public function testGetAsBool()
    {
        $this->assertIsBool($this->config->getAsBool('bool'));
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Value for "string" is not a bool');
        $this->assertIsNotBool($this->config->getAsBool('string'));
    }
    
    public function testGetAsInt()
    {
        $this->assertIsInt($this->config->getAsInt('int'));
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Value for "bool" is not an int');
        $this->assertIsNotInt($this->config->getAsInt('bool'));
    }

    public function testGetAsFloat()
    {
        $this->assertIsFloat($this->config->getAsFloat('float'));
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Value for "int" is not a float');
        $this->assertIsNotFloat($this->config->getAsFloat('int'));
    }
    
    public function testGetAsString()
    {
        $this->assertIsString($this->config->getAsString('string'));
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Value for "float" is not a string');
        $this->assertIsNotString($this->config->getAsString('float'));
    }

    public function testGetAsArray()
    {
        $this->assertIsArray($this->config->getAsArray('array'));
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Value for "string" is not an array');
        $this->assertIsNotArray($this->config->getAsArray('string'));
    }

    public function testGetAsConfig()
    {
        $this->assertIsObject($this->config->getAsConfig('array'));
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Config for "bool" is not an array');
        $this->assertIsNotObject($this->config->getAsConfig('bool'));
    }
    
    public function testGetAsConfigWithNull()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Config for "not-existing-key" not found');
        $this->assertIsNotObject($this->config->getAsConfig('not-existing-key'));
    }
    
    public function testCheck()
    {
        $this->assertNotNull($this->config->get('bool'));
        $this->assertNull($this->config->get('not-existing-key'));
    }

    public function testToArray()
    {
        $this->assertIsArray($this->config->toArray());
        $this->assertEquals($this->testValues, $this->config->toArray());
    }
}
