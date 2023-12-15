<?php

namespace HughCube\Base\Tests;

use HughCube\Base\Base;
use PHPUnit\Framework\TestCase;

class BaseTest extends TestCase
{
    public function testBase()
    {
        $input = '01324523453243154324542341524315432113200203012';
        $from = '012345';
        $to = '0123456789ABCDEF';

        $value = Base::conv($input, $from, $to);
        $this->assertSame($value, '1F9881BAD10454A8C23A838EF00F50');

        $value = Base::conv($value, $to, $from);
        $this->assertSame($value, ltrim($input, '0'));

        $value = Base::toString(1000000);
        $this->assertSame($value, '1000000');

        $value = Base::toStringWithPad(1000000, 31);
        $this->assertSame($value, '0000000000000000000000001000000');
    }
}
