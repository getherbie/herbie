<?php

namespace herbie\tests\unit;

use function herbie\is_digit;
use function herbie\is_natural;

class FunctionsTest extends \Codeception\Test\Unit
{
    public function testIsDigit()
    {
        $tests = [
            [42, true],
            [-42, true],
            ['42', true],
            ['-42', true],
            [42.12, false],
            ['42.12', false],
            [-42.12, false],
            ['-42.12', false],
            ['0155', false],
            [0xFF, true], // same as 255
            ['0xFF', false],
            ['a', false],
            [[], false],
            [null, false],
            [false, false],
            ['', false],
        ];
        foreach ($tests as $test) {
            $message = sprintf('testing "%s"', is_array($test[0]) ? 'array' : $test[0]);
            $this->assertEquals($test[1], is_digit($test[0]), $message);
        }
    }

    public function testIsNatural()
    {
        // without zero
        $tests = [
            [-42, false],
            [0, false],
            [1, true],
            [42, true],
            [PHP_INT_MAX, true]
        ];
        foreach ($tests as $test) {
            $message = sprintf('testing "%s"', $test[0]);
            $this->assertEquals($test[1], is_natural($test[0]), $message);
        }
        // including zero
        $this->assertFalse(is_natural(-1, true));
        $this->assertTrue(is_natural(0, true));
        $this->assertTrue(is_natural(1, true));
    }
}
