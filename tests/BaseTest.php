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

        // toString 大数字测试
        $this->assertSame('18446744073709551615', Base::toString('18446744073709551615'));
        $this->assertSame('-99999999999999999999999999999', Base::toString('-99999999999999999999999999999'));
        $this->assertSame(strval(PHP_INT_MAX), Base::toString(PHP_INT_MAX));

        $this->assertSame('-999999999999999999999999999999999', Base::toString('-999999999999999999999999999999999'));
    }

    public function testIsInteger()
    {
        // 正整数
        $this->assertTrue(Base::isInteger(0));
        $this->assertTrue(Base::isInteger(123));
        $this->assertTrue(Base::isInteger(PHP_INT_MAX));

        // 负整数
        $this->assertTrue(Base::isInteger(-1));
        $this->assertTrue(Base::isInteger(-123));
        $this->assertTrue(Base::isInteger(PHP_INT_MIN));

        // 正数字符串
        $this->assertTrue(Base::isInteger('0'));
        $this->assertTrue(Base::isInteger('123'));
        $this->assertTrue(Base::isInteger('9999999999999999999999999999'));

        // 负数字符串
        $this->assertTrue(Base::isInteger('-1'));
        $this->assertTrue(Base::isInteger('-123'));
        $this->assertTrue(Base::isInteger('-9999999999999999999999999999'));

        // 空字符串
        $this->assertFalse(Base::isInteger(''));

        // 只有负号
        $this->assertFalse(Base::isInteger('-'));

        // 包含非数字字符
        $this->assertFalse(Base::isInteger('12a34'));
        $this->assertFalse(Base::isInteger('abc'));
        $this->assertFalse(Base::isInteger('12.34'));
        $this->assertFalse(Base::isInteger('-12.34'));
        $this->assertFalse(Base::isInteger('12 34'));
        $this->assertFalse(Base::isInteger(' 123'));
        $this->assertFalse(Base::isInteger('123 '));
        $this->assertFalse(Base::isInteger('--123'));

        // null
        $this->assertFalse(Base::isInteger(null));

        // 布尔值
        $this->assertFalse(Base::isInteger(true));
        $this->assertFalse(Base::isInteger(false));

        // 数组
        $this->assertFalse(Base::isInteger([]));
        $this->assertFalse(Base::isInteger([1, 2, 3]));

        // 对象
        $this->assertFalse(Base::isInteger(new \stdClass()));

        // 浮点数
        $this->assertFalse(Base::isInteger(12.34));
        $this->assertFalse(Base::isInteger(-12.34));

        // 无小数点
        $this->assertTrue(Base::isInteger(0.0));

        // 2^53 范围内的 float 可以精确判断
        $this->assertTrue(Base::isInteger(9007199254740992.0));
        $this->assertTrue(Base::isInteger(-9007199254740992.0));

        // 超过 2^53 的 float 无法精确表示整数，返回 false
        // 建议使用字符串形式传入大整数
        $this->assertFalse(Base::isInteger(PHP_INT_MAX + 1));
        $this->assertFalse(Base::isInteger(PHP_INT_MIN - 1));
        $this->assertFalse(Base::isInteger(99999999999999999999999999999));
        $this->assertFalse(Base::isInteger(18446744073709551615));
        $this->assertFalse(Base::isInteger(18446744073709551615.1));

        $this->assertTrue(Base::isInteger(9223372036854775807));
        $this->assertFalse(Base::isInteger(9223372036854775807.1));

        // 大整数应该使用字符串形式
        $this->assertTrue(Base::isInteger('18446744073709551615'));
        $this->assertTrue(Base::isInteger('99999999999999999999999999999'));
        $this->assertTrue(Base::isInteger('-99999999999999999999999999999'));
    }
}
