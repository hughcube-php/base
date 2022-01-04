<?php

namespace HughCube\Base\Tests;

use HughCube\Base\Base;
use PHPUnit\Framework\TestCase;

class BaseTest extends TestCase
{
    public function testBaseConv()
    {
        $input = '1324523453243154324542341524315432113200203012';
        $from = '012345';
        $to = '0123456789ABCDEF';

        $value = Base::conv($input, $from, $to);
        $this->assertSame($value, '1F9881BAD10454A8C23A838EF00F50');

        $value = Base::conv($value, $to, $from);
        $this->assertSame($value, $input);
    }
}
