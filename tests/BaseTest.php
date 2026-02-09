<?php

namespace HughCube\Base\Tests;

use Carbon\Carbon;
use HughCube\Base\Base;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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

    // ======================== conv() null / same base ========================

    public function testConvNull()
    {
        $this->assertNull(Base::conv(null, '0123456789', '01'));
        $this->assertNull(Base::conv(null, '01', '0123456789'));
        $this->assertNull(Base::conv(null, 'abc', 'xyz'));
    }

    public function testConvSameBase()
    {
        $base = '0123456789abcdef';
        $this->assertSame('ff', Base::conv('ff', $base, $base));
        $this->assertSame('0', Base::conv('0', '0123456789', '0123456789'));
        $this->assertSame('999', Base::conv('999', '0123456789', '0123456789'));
    }

    // ======================== conv() 零值 ========================

    public function testConvZero()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $bin = '01';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // 十进制 0 → 各种进制
        $this->assertSame('0', Base::conv('0', $dec, $hex));
        $this->assertSame('0', Base::conv('0', $dec, $bin));
        $this->assertSame('0', Base::conv('0', $dec, $b36));
        $this->assertSame('0', Base::conv('0', $dec, $b62));

        // 各种进制 0 → 十进制
        $this->assertSame('0', Base::conv('0', $hex, $dec));
        $this->assertSame('0', Base::conv('0', $bin, $dec));
        $this->assertSame('0', Base::conv('0', $b36, $dec));

        // 非十进制之间的 0
        $this->assertSame('0', Base::conv('0', $bin, $hex));
        $this->assertSame('0', Base::conv('0', $hex, $bin));
    }

    // ======================== conv() 单字符 ========================

    public function testConvSingleDigit()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $bin = '01';

        $this->assertSame('1', Base::conv('1', $dec, $hex));
        $this->assertSame('1', Base::conv('1', $dec, $bin));
        $this->assertSame('1', Base::conv('1', $hex, $dec));
        $this->assertSame('1', Base::conv('1', $bin, $dec));

        $this->assertSame('15', Base::conv('f', $hex, $dec));
        $this->assertSame('f', Base::conv('15', $dec, $hex));
    }

    // ======================== conv() 二进制 ========================

    public function testConvBinary()
    {
        $dec = '0123456789';
        $bin = '01';

        $this->assertSame('11111111', Base::conv('255', $dec, $bin));
        $this->assertSame('255', Base::conv('11111111', $bin, $dec));
        $this->assertSame('10000000', Base::conv('128', $dec, $bin));
        $this->assertSame('128', Base::conv('10000000', $bin, $dec));
        $this->assertSame('1', Base::conv('1', $dec, $bin));
        $this->assertSame('10', Base::conv('2', $dec, $bin));
        $this->assertSame('100', Base::conv('4', $dec, $bin));
        $this->assertSame('10000000000000000', Base::conv('65536', $dec, $bin));
    }

    // ======================== conv() 十六进制 ========================

    public function testConvHex()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';

        $this->assertSame('ff', Base::conv('255', $dec, $hex));
        $this->assertSame('255', Base::conv('ff', $hex, $dec));
        $this->assertSame('100', Base::conv('256', $dec, $hex));
        $this->assertSame('256', Base::conv('100', $hex, $dec));
        $this->assertSame('a', Base::conv('10', $dec, $hex));
        $this->assertSame('10', Base::conv('a', $hex, $dec));
        $this->assertSame('7b', Base::conv('123', $dec, $hex));
        $this->assertSame('123', Base::conv('7b', $hex, $dec));
    }

    // ======================== conv() 自定义字符集 ========================

    public function testConvCustomCharset()
    {
        $base3 = 'XYZ';
        $dec = '0123456789';

        $this->assertSame('X', Base::conv('0', $dec, $base3));
        $this->assertSame('Y', Base::conv('1', $dec, $base3));
        $this->assertSame('Z', Base::conv('2', $dec, $base3));
        $this->assertSame('YX', Base::conv('3', $dec, $base3));
        $this->assertSame('YY', Base::conv('4', $dec, $base3));
        $this->assertSame('YZ', Base::conv('5', $dec, $base3));
        $this->assertSame('ZX', Base::conv('6', $dec, $base3));
        $this->assertSame('YXX', Base::conv('9', $dec, $base3));

        $this->assertSame('0', Base::conv('X', $base3, $dec));
        $this->assertSame('1', Base::conv('Y', $base3, $dec));
        $this->assertSame('2', Base::conv('Z', $base3, $dec));
        $this->assertSame('9', Base::conv('YXX', $base3, $dec));
    }

    // ======================== conv() 前导零 ========================

    public function testConvLeadingZeros()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $bin = '01';

        $this->assertSame('ff', Base::conv('00255', $dec, $hex));
        $this->assertSame('11111111', Base::conv('000255', $dec, $bin));

        $this->assertSame('255', Base::conv('00ff', $hex, $dec));
        $this->assertSame('255', Base::conv('0011111111', $bin, $dec));

        $this->assertSame('ff', Base::conv('0011111111', $bin, $hex));

        // 全零
        $this->assertSame('0', Base::conv('000', $dec, $hex));
        $this->assertSame('0', Base::conv('000', $hex, $dec));
    }

    // ======================== conv() 大数 ========================

    public function testConvLargeNumbers()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $bin = '01';

        // 2^64
        $big = '18446744073709551616';
        $bigHex = Base::conv($big, $dec, $hex);
        $this->assertSame('10000000000000000', $bigHex);
        $this->assertSame($big, Base::conv($bigHex, $hex, $dec));

        // 2^64 - 1
        $big2 = '18446744073709551615';
        $this->assertSame('ffffffffffffffff', Base::conv($big2, $dec, $hex));
        $this->assertSame($big2, Base::conv('ffffffffffffffff', $hex, $dec));

        // 120 位超大数往返
        $huge = '123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890';
        $hugeHex = Base::conv($huge, $dec, $hex);
        $this->assertSame($huge, Base::conv($hugeHex, $hex, $dec));

        $hugeBin = Base::conv($huge, $dec, $bin);
        $this->assertSame($huge, Base::conv($hugeBin, $bin, $dec));
    }

    // ======================== conv() 进制边界值 ========================

    public function testConvBaseBoundary()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';

        // hex: 15→f, 16→10, 17→11
        $this->assertSame('f', Base::conv('15', $dec, $hex));
        $this->assertSame('10', Base::conv('16', $dec, $hex));
        $this->assertSame('11', Base::conv('17', $dec, $hex));

        // b36: 35→z, 36→10, 37→11
        $this->assertSame('z', Base::conv('35', $dec, $b36));
        $this->assertSame('10', Base::conv('36', $dec, $b36));
        $this->assertSame('11', Base::conv('37', $dec, $b36));

        // base^2 - 1（最大两位数）
        $this->assertSame('ff', Base::conv('255', $dec, $hex));
        $this->assertSame('zz', Base::conv('1295', $dec, $b36));

        // base^2（进三位）
        $this->assertSame('100', Base::conv('256', $dec, $hex));
        $this->assertSame('100', Base::conv('1296', $dec, $b36));
    }

    // ======================== conv() 非十进制互转 ========================

    public function testConvNonDecimalToNonDecimal()
    {
        $bin = '01';
        $hex = '0123456789abcdef';
        $oct = '01234567';

        $this->assertSame('ff', Base::conv('11111111', $bin, $hex));
        $this->assertSame('1a', Base::conv('11010', $bin, $hex));
        $this->assertSame('11111111', Base::conv('ff', $hex, $bin));
        $this->assertSame('11010', Base::conv('1a', $hex, $bin));

        $this->assertSame('ff', Base::conv('377', $oct, $hex));
        $this->assertSame('377', Base::conv('ff', $hex, $oct));

        $this->assertSame('377', Base::conv('11111111', $bin, $oct));
        $this->assertSame('11111111', Base::conv('377', $oct, $bin));
    }

    // ======================== conv() 多进制多数值全量往返 ========================

    public function testConvRoundTrip()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $bin = '01';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $testValues = [
            '0', '1', '2', '9', '10', '15', '16', '35', '36', '61', '62',
            '100', '255', '256', '1000', '1295', '1296', '3843', '3844',
            '65535', '65536', '999999', '1000000',
            '4294967295',
            '4294967296',
            '9007199254740992',
            strval(PHP_INT_MAX),
            '18446744073709551615',
            '18446744073709551616',
            '999999999999999999999999999999999999999',
        ];

        $bases = [$dec, $hex, $bin, $b36, $b62];

        foreach ($testValues as $val) {
            foreach ($bases as $baseA) {
                foreach ($bases as $baseB) {
                    $converted = Base::conv($val, $dec, $baseA);
                    $result = Base::conv($converted, $baseA, $baseB);
                    $back = Base::conv($result, $baseB, $dec);
                    $this->assertSame(
                        ltrim($val, '0') ?: '0',
                        $back,
                        "往返失败: dec({$val}) → baseA → baseB → dec = {$back}"
                    );
                }
            }
        }
    }

    // ======================== conv() 整数输入 ========================

    public function testConvIntInput()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';

        $this->assertSame('ff', Base::conv(255, $dec, $hex));
        $this->assertSame('0', Base::conv(0, $dec, $hex));
        $this->assertSame('1', Base::conv(1, $dec, $hex));
    }

    // ======================== to36() ========================

    public function testTo36Null()
    {
        $this->assertNull(Base::to36(null));
    }

    public function testTo36Zero()
    {
        $this->assertSame('0', Base::to36('0'));
        $this->assertSame('0', Base::to36(0));
    }

    public function testTo36KnownValues()
    {
        for ($i = 0; $i <= 9; $i++) {
            $this->assertSame((string)$i, Base::to36((string)$i), "to36({$i})");
        }
        $this->assertSame('a', Base::to36('10'));
        $this->assertSame('z', Base::to36('35'));
        $this->assertSame('10', Base::to36('36'));
        $this->assertSame('11', Base::to36('37'));
        $this->assertSame('zz', Base::to36('1295'));
        $this->assertSame('100', Base::to36('1296'));
    }

    public function testTo36LargeNumber()
    {
        $dec = '0123456789';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';
        $big = '99999999999999999999999999';
        $result = Base::to36($big);
        $back = Base::conv($result, $b36, $dec);
        $this->assertSame($big, $back);
    }

    // ======================== to62() ========================

    public function testTo62Null()
    {
        $this->assertNull(Base::to62(null));
    }

    public function testTo62Zero()
    {
        $this->assertSame('0', Base::to62('0'));
        $this->assertSame('0', Base::to62(0));
    }

    public function testTo62KnownValues()
    {
        // 0-9 → '0'-'9'
        for ($i = 0; $i <= 9; $i++) {
            $this->assertSame((string)$i, Base::to62((string)$i), "to62({$i})");
        }

        // 10-35 → 'A'-'Z'
        $this->assertSame('A', Base::to62('10'));
        $this->assertSame('Z', Base::to62('35'));

        // 36-61 → 'a'-'z'
        $this->assertSame('a', Base::to62('36'));
        $this->assertSame('z', Base::to62('61'));

        // 进位
        $this->assertSame('10', Base::to62('62'));
        $this->assertSame('11', Base::to62('63'));

        // zz = 61*62+61 = 3843
        $this->assertSame('zz', Base::to62('3843'));
        $this->assertSame('100', Base::to62('3844'));
    }

    public function testTo62LargeNumber()
    {
        $dec = '0123456789';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $big = '99999999999999999999999999';
        $result = Base::to62($big);
        $back = Base::conv($result, $b62, $dec);
        $this->assertSame($big, $back);
    }

    // ======================== to36/to62 与 conv 一致性 ========================

    public function testTo36To62ConsistencyWithConv()
    {
        $dec = '0123456789';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $values = [
            '0', '1', '9', '10', '35', '36', '61', '62', '100',
            '1295', '1296', '3843', '3844', '65535',
            '999999999', '18446744073709551615',
        ];

        foreach ($values as $v) {
            $this->assertSame(
                Base::conv($v, $dec, $b36),
                Base::to36($v),
                "to36 与 conv 不一致: {$v}"
            );
            $this->assertSame(
                Base::conv($v, $dec, $b62),
                Base::to62($v),
                "to62 与 conv 不一致: {$v}"
            );
        }
    }

    // ======================== toString() 边界 ========================

    public function testToStringPassthrough()
    {
        $this->assertSame('hello', Base::toString('hello'));
        $this->assertSame('', Base::toString(''));
        $this->assertSame('0', Base::toString('0'));
        $this->assertSame('00123', Base::toString('00123'));
        $this->assertSame('-99999999999999999999', Base::toString('-99999999999999999999'));
    }

    public function testToStringInt()
    {
        $this->assertSame('0', Base::toString(0));
        $this->assertSame('1', Base::toString(1));
        $this->assertSame('-1', Base::toString(-1));
        $this->assertSame('1000000', Base::toString(1000000));
        $this->assertSame(strval(PHP_INT_MAX), Base::toString(PHP_INT_MAX));
        $this->assertSame(strval(PHP_INT_MIN), Base::toString(PHP_INT_MIN));
    }

    // ======================== toStringWithPad() 边界 ========================

    public function testToStringWithPadBoundary()
    {
        $this->assertSame('000123', Base::toStringWithPad('123', 6));
        $this->assertSame('000000', Base::toStringWithPad('0', 6));
        $this->assertSame('123', Base::toStringWithPad('123', 3));
        $this->assertSame('12345', Base::toStringWithPad('123456', 5));
        $this->assertSame('5', Base::toStringWithPad('5', 1));
        $this->assertSame('1', Base::toStringWithPad('12345', 1));
    }

    // ======================== 0-1000 连续往返 ========================

    public function testConvSequential()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        for ($i = 0; $i <= 1000; $i++) {
            $s = (string)$i;

            $hexVal = Base::conv($s, $dec, $hex);
            $this->assertSame($s, Base::conv($hexVal, $hex, $dec), "hex 往返: {$i}");

            $b36Val = Base::conv($s, $dec, $b36);
            $this->assertSame($s, Base::conv($b36Val, $b36, $dec), "b36 往返: {$i}");

            $b62Val = Base::conv($s, $dec, $b62);
            $this->assertSame($s, Base::conv($b62Val, $b62, $dec), "b62 往返: {$i}");
        }
    }

    // ======================== 编码后单调递增 ========================

    public function testConvMonotonicity()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';

        // hex 字符集符合 ASCII 序，可以直接做字符串单调性验证
        $prev = '';
        for ($i = 0; $i <= 500; $i++) {
            $cur = Base::conv((string)$i, $dec, $hex);

            if ($i > 0) {
                $this->assertTrue(
                    strlen($cur) > strlen($prev)
                    || (strlen($cur) === strlen($prev) && $cur > $prev),
                    "单调性失败: hex({$i})={$cur} <= hex(" . ($i - 1) . ")={$prev}"
                );
            }

            $prev = $cur;
        }

        // b62 字符集不符合 ASCII 序，改为转回十进制做数值递增验证
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        for ($i = 0; $i <= 500; $i++) {
            $encoded = Base::conv((string)$i, $dec, $b62);
            $back = Base::conv($encoded, $b62, $dec);
            $this->assertSame((string)$i, $back, "b62 编码→解码不一致: {$i}");

            // 编码长度递增验证：值越大，位数不减少
            if ($i > 0) {
                $prevEncoded = Base::conv((string)($i - 1), $dec, $b62);
                $this->assertTrue(
                    strlen($encoded) >= strlen($prevEncoded),
                    "b62 位数减少: to62({$i})={$encoded} 比 to62(" . ($i - 1) . ")={$prevEncoded} 短"
                );
            }
        }
    }

    // ======================== 对照 PHP base_convert ========================

    public function testConvAgainstBuiltinBaseConvert()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';

        $values = ['0', '1', '255', '256', '65535', '1000000', '2147483647'];
        foreach ($values as $v) {
            $expected = base_convert($v, 10, 16);
            $this->assertSame($expected, Base::conv($v, $dec, $hex), "base_convert 对照: {$v}");
        }
    }

    // ======================== 2 的幂次验证 ========================

    public function testConvPowersOfTwo()
    {
        $dec = '0123456789';
        $bin = '01';

        $powers = [0, 1, 2, 4, 8, 16, 32, 53, 64, 128, 256];
        foreach ($powers as $n) {
            $decVal = bcpow('2', (string)$n, 0);
            $binVal = Base::conv($decVal, $dec, $bin);
            $this->assertSame('1' . str_repeat('0', $n), $binVal, "2^{$n} 二进制");
            $this->assertSame($decVal, Base::conv($binVal, $bin, $dec), "2^{$n} 往返");
        }
    }

    // ======================== 自定义二进制字符 ========================

    public function testConvBase2CustomChars()
    {
        $dec = '0123456789';
        $ab = 'ab'; // a=0, b=1

        $this->assertSame('a', Base::conv('0', $dec, $ab));
        $this->assertSame('b', Base::conv('1', $dec, $ab));
        $this->assertSame('ba', Base::conv('2', $dec, $ab));
        $this->assertSame('bb', Base::conv('3', $dec, $ab));
        $this->assertSame('baa', Base::conv('4', $dec, $ab));

        $this->assertSame('0', Base::conv('a', $ab, $dec));
        $this->assertSame('3', Base::conv('bb', $ab, $dec));
        $this->assertSame('4', Base::conv('baa', $ab, $dec));
        $this->assertSame('255', Base::conv('bbbbbbbb', $ab, $dec));
    }

    // ======================== 自定义字符集互转 ========================

    public function testConvCustomToCustom()
    {
        $base3 = 'XYZ';
        $base5 = 'ABCDE';

        // 通过十进制做中间桥梁验证
        $dec = '0123456789';
        for ($i = 0; $i <= 200; $i++) {
            $s = (string)$i;
            $in3 = Base::conv($s, $dec, $base3);
            $in5 = Base::conv($in3, $base3, $base5);
            $back = Base::conv($in5, $base5, $dec);
            $this->assertSame($s, $back, "custom→custom 往返: {$i}");
        }
    }

    // ======================== to36 连续 0-100 每个值精确验证 ========================

    public function testTo36SequentialCharMapping()
    {
        $charset = '0123456789abcdefghijklmnopqrstuvwxyz';

        // 0-35 应该是单字符
        for ($i = 0; $i <= 35; $i++) {
            $this->assertSame($charset[$i], Base::to36((string)$i), "to36({$i}) 单字符");
        }

        // 36 = '10', 71 = '1z', 72 = '20'
        $this->assertSame('10', Base::to36('36'));
        $this->assertSame('1z', Base::to36('71'));
        $this->assertSame('20', Base::to36('72'));
    }

    // ======================== to62 连续 0-61 每个值精确验证 ========================

    public function testTo62SequentialCharMapping()
    {
        $charset = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // 0-61 应该是单字符
        for ($i = 0; $i <= 61; $i++) {
            $this->assertSame($charset[$i], Base::to62((string)$i), "to62({$i}) 单字符");
        }

        // 62 = '10', 123 = '1z', 124 = '20'
        $this->assertSame('10', Base::to62('62'));
        $this->assertSame('1z', Base::to62('123'));
        $this->assertSame('20', Base::to62('124'));
    }

    // ======================== digitalToString() deprecated 方法 ========================

    public function testDigitalToString()
    {
        // 确保 deprecated 方法和 toString 行为一致
        $values = [0, 1, -1, 1000000, PHP_INT_MAX, PHP_INT_MIN, '0', '123', '', 'hello', '00123'];
        foreach ($values as $v) {
            $this->assertSame(Base::toString($v), Base::digitalToString($v), "digitalToString 不一致: " . var_export($v, true));
        }
    }

    // ======================== 已知十六进制常量验证 ========================

    public function testConvWellKnownHexValues()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';

        // 0xDEADBEEF = 3735928559
        $this->assertSame('deadbeef', Base::conv('3735928559', $dec, $hex));
        $this->assertSame('3735928559', Base::conv('deadbeef', $hex, $dec));

        // 0xCAFEBABE = 3405691582
        $this->assertSame('cafebabe', Base::conv('3405691582', $dec, $hex));
        $this->assertSame('3405691582', Base::conv('cafebabe', $hex, $dec));

        // 0x7FFFFFFF = 2147483647 (INT32_MAX)
        $this->assertSame('7fffffff', Base::conv('2147483647', $dec, $hex));

        // 0xFFFFFFFF = 4294967295 (UINT32_MAX)
        $this->assertSame('ffffffff', Base::conv('4294967295', $dec, $hex));
    }

    // ======================== conv() 输出无前导零 ========================

    public function testConvOutputNoLeadingZeros()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $bin = '01';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $values = ['1', '10', '100', '255', '1000', '65536', '999999'];
        foreach ($values as $v) {
            $hexResult = Base::conv($v, $dec, $hex);
            $this->assertNotSame('0', $hexResult[0], "hex 输出有前导零: {$v} → {$hexResult}");

            $binResult = Base::conv($v, $dec, $bin);
            $this->assertNotSame('0', $binResult[0], "bin 输出有前导零: {$v} → {$binResult}");

            $b62Result = Base::conv($v, $dec, $b62);
            $this->assertNotSame('0', $b62Result[0], "b62 输出有前导零: {$v} → {$b62Result}");
        }
    }

    // ======================== to36/to62 整数类型输入 ========================

    public function testTo36IntInput()
    {
        $this->assertSame('0', Base::to36(0));
        $this->assertSame('1', Base::to36(1));
        $this->assertSame('73', Base::to36(255));
        $this->assertSame('zzzzz', Base::to36((string)(36 * 36 * 36 * 36 * 36 - 1)));  // 36^5-1
    }

    public function testTo62IntInput()
    {
        $this->assertSame('0', Base::to62(0));
        $this->assertSame('1', Base::to62(1));
        $this->assertSame('47', Base::to62(255));
        $this->assertSame('AzL8n0Y58m7', Base::to62(strval(PHP_INT_MAX)));
    }

    // ======================== 中间进制无关性 ========================

    public function testConvIntermediateBaseIndependence()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $bin = '01';
        $oct = '01234567';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // 无论经过什么中间进制，最终结果都一样
        $values = ['0', '1', '255', '65535', '18446744073709551615'];
        $intermediates = [$dec, $hex, $bin, $oct, $b36, $b62];

        foreach ($values as $v) {
            $directHex = Base::conv($v, $dec, $hex);
            foreach ($intermediates as $mid) {
                $midVal = Base::conv($v, $dec, $mid);
                $viaHex = Base::conv($midVal, $mid, $hex);
                $this->assertSame(
                    $directHex,
                    $viaHex,
                    "中间进制导致结果不同: {$v} → mid → hex = {$viaHex}, 直接 = {$directHex}"
                );
            }
        }
    }

    // ======================== 重复字符模式 ========================

    public function testConvRepeatedDigitPatterns()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $bin = '01';

        // 111...1 (二进制全1) = 2^n - 1
        $this->assertSame('1', Base::conv('1', $bin, $dec));          // 2^1-1
        $this->assertSame('3', Base::conv('11', $bin, $dec));         // 2^2-1
        $this->assertSame('7', Base::conv('111', $bin, $dec));        // 2^3-1
        $this->assertSame('15', Base::conv('1111', $bin, $dec));      // 2^4-1
        $this->assertSame('31', Base::conv('11111', $bin, $dec));     // 2^5-1
        $this->assertSame('63', Base::conv('111111', $bin, $dec));    // 2^6-1
        $this->assertSame('127', Base::conv('1111111', $bin, $dec));  // 2^7-1
        $this->assertSame('255', Base::conv('11111111', $bin, $dec)); // 2^8-1

        // fff... (十六进制全f) = 16^n - 1
        $this->assertSame('15', Base::conv('f', $hex, $dec));
        $this->assertSame('255', Base::conv('ff', $hex, $dec));
        $this->assertSame('4095', Base::conv('fff', $hex, $dec));
        $this->assertSame('65535', Base::conv('ffff', $hex, $dec));
        $this->assertSame('1048575', Base::conv('fffff', $hex, $dec));

        // 十进制 111111 → hex/bin 往返
        $this->assertSame('111111', Base::conv(Base::conv('111111', $dec, $hex), $hex, $dec));
        $this->assertSame('111111', Base::conv(Base::conv('111111', $dec, $bin), $bin, $dec));
    }

    // ======================== 反转字符集的十进制 ========================

    public function testConvReversedDecimalCharset()
    {
        $dec = '0123456789';
        $revDec = '9876543210';

        // 反转字符集: '9'=0, '8'=1, ..., '0'=9
        $this->assertSame('9', Base::conv('0', $dec, $revDec));
        $this->assertSame('8', Base::conv('1', $dec, $revDec));
        $this->assertSame('0', Base::conv('9', $dec, $revDec));
        $this->assertSame('89', Base::conv('10', $dec, $revDec));  // 1→8, 0→9

        // 往返
        for ($i = 0; $i <= 100; $i++) {
            $s = (string)$i;
            $rev = Base::conv($s, $dec, $revDec);
            $back = Base::conv($rev, $revDec, $dec);
            $this->assertSame($s, $back, "反转十进制往返: {$i}");
        }
    }

    // ======================== 超长二进制串 ========================

    public function testConvVeryLongBinaryString()
    {
        $dec = '0123456789';
        $bin = '01';
        $hex = '0123456789abcdef';

        // 1024 位二进制 = 1 后面跟 1023 个 0 → 2^1023
        $bigBin = '1' . str_repeat('0', 1023);
        $bigDec = Base::conv($bigBin, $bin, $dec);
        $this->assertSame($bigBin, Base::conv($bigDec, $dec, $bin), "2^1023 往返");

        // 1024 位全 1 → 2^1024 - 1
        $allOnes = str_repeat('1', 1024);
        $allOnesDec = Base::conv($allOnes, $bin, $dec);
        // 2^1024 - 1 的十六进制应该是 256 个 f
        $allOnesHex = Base::conv($allOnesDec, $dec, $hex);
        $this->assertSame(str_repeat('f', 256), $allOnesHex, "2^1024-1 hex");
        $this->assertSame($allOnesDec, Base::conv($allOnesHex, $hex, $dec));
    }

    // ======================== toStringWithPad 补充 ========================

    public function testToStringWithPadIntInput()
    {
        $this->assertSame('000000000100', Base::toStringWithPad(100, 12));
        $this->assertSame('000000100', Base::toStringWithPad(100, 9));
        $this->assertSame(str_pad(strval(PHP_INT_MAX), 30, '0', STR_PAD_LEFT), Base::toStringWithPad(PHP_INT_MAX));
    }

    public function testToStringWithPadDefaultLength()
    {
        // 默认长度 30
        $result = Base::toStringWithPad('123');
        $this->assertSame(30, strlen($result));
        $this->assertSame('000000000000000000000000000123', $result);
    }

    public function testToStringWithPadLargeNumber()
    {
        // 数字位数大于 pad 长度，从左截断
        $big = '12345678901234567890';
        $result = Base::toStringWithPad($big, 10);
        $this->assertSame(10, strlen($result));
        $this->assertSame('1234567890', $result);
    }

    // ======================== conv() 相邻进制互转 ========================

    public function testConvAdjacentBases()
    {
        $dec = '0123456789';

        // base-7 和 base-9 之间互转
        $base7 = '0123456';
        $base9 = '012345678';

        for ($i = 0; $i <= 300; $i++) {
            $s = (string)$i;
            $in7 = Base::conv($s, $dec, $base7);
            $in9 = Base::conv($in7, $base7, $base9);
            $back = Base::conv($in9, $base9, $dec);
            $this->assertSame($s, $back, "base7→base9 往返: {$i}");
        }
    }

    // ======================== conv() 500 位大数 → base62 往返 ========================

    public function testConvHugeDecimalToBase62()
    {
        $dec = '0123456789';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // 500 位十进制数
        $huge = str_repeat('1234567890', 50);
        $encoded = Base::conv($huge, $dec, $b62);
        $back = Base::conv($encoded, $b62, $dec);
        $this->assertSame(ltrim($huge, '0'), $back);

        // 编码后长度应该缩短（62 > 10，信息密度更高）
        $this->assertLessThan(strlen($huge), strlen($encoded));
    }

    // ======================== conv() 转换结果长度验证 ========================

    public function testConvOutputLengthBounds()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // 对于 n 位十进制数，hex 最多 ceil(n * log10/log16) ≈ ceil(n * 0.8305) 位
        // 对于 n 位十进制数，b62 最多 ceil(n * log10/log62) ≈ ceil(n * 0.5587) 位
        $values = ['9', '99', '9999', '99999999', '9999999999999999'];
        foreach ($values as $v) {
            $hexResult = Base::conv($v, $dec, $hex);
            $b62Result = Base::conv($v, $dec, $b62);

            // hex 位数不超过十进制位数
            $this->assertLessThanOrEqual(strlen($v), strlen($hexResult), "hex 位数异常: {$v}");
            // b62 位数不超过十进制位数
            $this->assertLessThanOrEqual(strlen($v), strlen($b62Result), "b62 位数异常: {$v}");
        }

        // 对足够大的数，b62 位数应严格小于十进制位数
        $bigValues = ['9999999999', '999999999999999999999999999999'];
        foreach ($bigValues as $v) {
            $b62Result = Base::conv($v, $dec, $b62);
            $this->assertLessThan(strlen($v), strlen($b62Result), "b62 大数压缩比异常: {$v}");
        }
    }

    // ======================== to36/to62 与 PHP_INT_MAX ========================

    public function testTo36To62PhpIntMax()
    {
        $dec = '0123456789';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $max = strval(PHP_INT_MAX);

        $r36 = Base::to36($max);
        $this->assertSame($max, Base::conv($r36, $b36, $dec));

        $r62 = Base::to62($max);
        $this->assertSame($max, Base::conv($r62, $b62, $dec));

        // 也测试 int 类型输入
        $r36int = Base::to36(PHP_INT_MAX);
        $this->assertSame($r36, $r36int);

        $r62int = Base::to62(PHP_INT_MAX);
        $this->assertSame($r62, $r62int);
    }

    // ======================== conv() 幂等性 ========================

    public function testConvIdempotency()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // 同一个转换执行两次，结果应该完全一致
        $values = ['0', '1', '255', '65535', '18446744073709551615'];
        foreach ($values as $v) {
            $first = Base::conv($v, $dec, $hex);
            $second = Base::conv($v, $dec, $hex);
            $this->assertSame($first, $second, "幂等性: hex {$v}");

            $first62 = Base::conv($v, $dec, $b62);
            $second62 = Base::conv($v, $dec, $b62);
            $this->assertSame($first62, $second62, "幂等性: b62 {$v}");
        }
    }

    // ======================== conv() 零值在自定义字符集中输出第一个字符 ========================

    public function testConvZeroOutputsFirstCharOfTarget()
    {
        $dec = '0123456789';

        $charsets = [
            '01',
            '0123456789abcdef',
            'XYZ',
            'ABCDE',
            '!@#$%',
            '0123456789abcdefghijklmnopqrstuvwxyz',
            '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
        ];

        foreach ($charsets as $cs) {
            $result = Base::conv('0', $dec, $cs);
            $this->assertSame($cs[0], $result, "零值应输出目标字符集第一个字符: " . substr($cs, 0, 10) . "...");
        }
    }

    // ======================== conv() 特殊字符作为字符集 ========================

    public function testConvSpecialCharCharset()
    {
        $dec = '0123456789';
        $special = '!@#$';

        // base-4 用特殊字符: !=0, @=1, #=2, $=3
        $this->assertSame('!', Base::conv('0', $dec, $special));
        $this->assertSame('@', Base::conv('1', $dec, $special));
        $this->assertSame('#', Base::conv('2', $dec, $special));
        $this->assertSame('$', Base::conv('3', $dec, $special));
        $this->assertSame('@!', Base::conv('4', $dec, $special));

        // 往返
        for ($i = 0; $i <= 100; $i++) {
            $s = (string)$i;
            $encoded = Base::conv($s, $dec, $special);
            $decoded = Base::conv($encoded, $special, $dec);
            $this->assertSame($s, $decoded, "特殊字符往返: {$i}");
        }
    }

    // ======================== 连续 base^n 幂次验证 ========================

    public function testConvConsecutiveBasePowers()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';

        // 16^n 在 hex 中始终是 "1" + n 个 "0"
        for ($n = 0; $n <= 10; $n++) {
            $decVal = bcpow('16', (string)$n, 0);
            $hexVal = Base::conv($decVal, $dec, $hex);
            $this->assertSame('1' . str_repeat('0', $n), $hexVal, "16^{$n} hex");
        }

        // 36^n 在 b36 中始终是 "1" + n 个 "0"
        for ($n = 0; $n <= 8; $n++) {
            $decVal = bcpow('36', (string)$n, 0);
            $b36Val = Base::conv($decVal, $dec, $b36);
            $this->assertSame('1' . str_repeat('0', $n), $b36Val, "36^{$n} b36");
        }
    }

    // ======================== conv() base^n - 1 全是最大字符 ========================

    public function testConvBaseMaxDigits()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';

        // 16^n - 1 在 hex 中始终是 n 个 "f"
        for ($n = 1; $n <= 10; $n++) {
            $decVal = bcsub(bcpow('16', (string)$n, 0), '1', 0);
            $hexVal = Base::conv($decVal, $dec, $hex);
            $this->assertSame(str_repeat('f', $n), $hexVal, "16^{$n}-1 hex");
        }

        // 36^n - 1 在 b36 中始终是 n 个 "z"
        for ($n = 1; $n <= 8; $n++) {
            $decVal = bcsub(bcpow('36', (string)$n, 0), '1', 0);
            $b36Val = Base::conv($decVal, $dec, $b36);
            $this->assertSame(str_repeat('z', $n), $b36Val, "36^{$n}-1 b36");
        }
    }

    // ======================== 中文字符集（多字节 UTF-8） ========================

    public function testConvChineseCharset()
    {
        $dec = '0123456789';
        $cn3 = '零壹贰';

        // 零=0, 壹=1, 贰=2
        $this->assertSame('零', Base::conv('0', $dec, $cn3));
        $this->assertSame('壹', Base::conv('1', $dec, $cn3));
        $this->assertSame('贰', Base::conv('2', $dec, $cn3));
        $this->assertSame('壹零', Base::conv('3', $dec, $cn3));
        $this->assertSame('壹壹', Base::conv('4', $dec, $cn3));
        $this->assertSame('壹贰', Base::conv('5', $dec, $cn3));
        $this->assertSame('贰零', Base::conv('6', $dec, $cn3));
        $this->assertSame('壹零零', Base::conv('9', $dec, $cn3));

        // 反向: 中文 → 十进制
        $this->assertSame('0', Base::conv('零', $cn3, $dec));
        $this->assertSame('1', Base::conv('壹', $cn3, $dec));
        $this->assertSame('2', Base::conv('贰', $cn3, $dec));
        $this->assertSame('9', Base::conv('壹零零', $cn3, $dec));

        // 往返 0-200
        for ($i = 0; $i <= 200; $i++) {
            $s = (string)$i;
            $encoded = Base::conv($s, $dec, $cn3);
            $decoded = Base::conv($encoded, $cn3, $dec);
            $this->assertSame($s, $decoded, "中文 base3 往返: {$i}");
        }
    }

    // ======================== 中文字符集互转 ========================

    public function testConvChineseToChineseCharset()
    {
        $cn3 = '零壹贰';
        $cn5 = '甲乙丙丁戊';
        $dec = '0123456789';

        for ($i = 0; $i <= 100; $i++) {
            $s = (string)$i;
            $in3 = Base::conv($s, $dec, $cn3);
            $in5 = Base::conv($in3, $cn3, $cn5);
            $back = Base::conv($in5, $cn5, $dec);
            $this->assertSame($s, $back, "中文互转往返: {$i}");
        }
    }

    // ======================== 中文 ↔ ASCII 字符集互转 ========================

    public function testConvChineseToAsciiCharset()
    {
        $cn3 = '零壹贰';
        $hex = '0123456789abcdef';
        $dec = '0123456789';

        $values = ['0', '1', '100', '255', '65535', '999999'];
        foreach ($values as $v) {
            $cnVal = Base::conv($v, $dec, $cn3);
            $hexVal = Base::conv($cnVal, $cn3, $hex);
            $back = Base::conv($hexVal, $hex, $dec);
            $this->assertSame($v, $back, "中文→hex 往返: {$v}");
        }
    }

    // ======================== 混合多字节字符集（中英混合） ========================

    public function testConvMixedMultibyteCharset()
    {
        $dec = '0123456789';
        // 混合字符集: ASCII + 中文 + 日文
        $mixed = '0aB零壹あ';  // base 6

        for ($i = 0; $i <= 100; $i++) {
            $s = (string)$i;
            $encoded = Base::conv($s, $dec, $mixed);
            $decoded = Base::conv($encoded, $mixed, $dec);
            $this->assertSame($s, $decoded, "混合字符集往返: {$i}");
        }
    }

    // ======================== base 128 进制 ========================

    public function testConvBase128()
    {
        $dec = '0123456789';

        // 构建 128 个不同的 ASCII 字符 (0x00-0x7F)
        $chars = '';
        for ($i = 0; $i < 128; $i++) {
            $chars .= chr($i);
        }
        $this->assertSame(128, strlen($chars));

        // 基本转换和往返
        $this->assertSame(chr(0), Base::conv('0', $dec, $chars));
        $this->assertSame(chr(1), Base::conv('1', $dec, $chars));
        $this->assertSame(chr(127), Base::conv('127', $dec, $chars));
        $this->assertSame(chr(1) . chr(0), Base::conv('128', $dec, $chars));

        // 往返 0-500
        for ($i = 0; $i <= 500; $i++) {
            $s = (string)$i;
            $encoded = Base::conv($s, $dec, $chars);
            $decoded = Base::conv($encoded, $chars, $dec);
            $this->assertSame($s, $decoded, "base128 往返: {$i}");
        }

        // 大数往返
        $big = '18446744073709551615';
        $encoded = Base::conv($big, $dec, $chars);
        $decoded = Base::conv($encoded, $chars, $dec);
        $this->assertSame($big, $decoded, "base128 大数往返");
    }

    // ======================== base 256 进制 ========================

    public function testConvBase256()
    {
        $dec = '0123456789';

        // 构建 256 个单字节字符
        $chars = '';
        for ($i = 0; $i < 256; $i++) {
            $chars .= chr($i);
        }
        $this->assertSame(256, strlen($chars));

        $this->assertSame(chr(0), Base::conv('0', $dec, $chars));
        $this->assertSame(chr(255), Base::conv('255', $dec, $chars));
        $this->assertSame(chr(1) . chr(0), Base::conv('256', $dec, $chars));

        // 往返 0-500
        for ($i = 0; $i <= 500; $i++) {
            $s = (string)$i;
            $encoded = Base::conv($s, $dec, $chars);
            $decoded = Base::conv($encoded, $chars, $dec);
            $this->assertSame($s, $decoded, "base256 往返: {$i}");
        }

        // 大数往返
        $big = '99999999999999999999999999999999';
        $encoded = Base::conv($big, $dec, $chars);
        $decoded = Base::conv($encoded, $chars, $dec);
        $this->assertSame($big, $decoded, "base256 大数往返");

        // 回归测试: 编码结果恰好是合法 UTF-8 的值
        // 50089 = 195*256+169, 编码为 \xC3\xA9 (UTF-8 字符 'é')
        // 确保不会被 preg_split 错误地按 UTF-8 字符分割
        $this->assertSame('50089', Base::conv(Base::conv('50089', $dec, $chars), $chars, $dec));

        // 覆盖全部两字节合法 UTF-8 碰撞区间: 首字节 0xC2-0xDF, 续字节 0x80-0xBF
        for ($b1 = 0xC2; $b1 <= 0xDF; $b1++) {
            for ($b2 = 0x80; $b2 <= 0xBF; $b2++) {
                $val = (string)($b1 * 256 + $b2);
                $encoded = Base::conv($val, $dec, $chars);
                $decoded = Base::conv($encoded, $chars, $dec);
                $this->assertSame($val, $decoded, "base256 UTF-8碰撞往返: {$val}");
            }
        }
    }

    // ======================== 大进制（100+ 多字节字符） ========================

    public function testConvLargeMultibyteBase()
    {
        $dec = '0123456789';

        // 用 CJK 统一汉字构建 base-200 字符集（U+4E00 起始）
        $chars = '';
        for ($i = 0; $i < 200; $i++) {
            // mb_chr 在 PHP 7.2+，用 json_decode 兼容 7.0
            $chars .= json_decode(sprintf('"\\u%04X"', 0x4E00 + $i));
        }

        // 基本验证
        $char0 = json_decode('"\\u4E00"'); // '一'
        $char1 = json_decode('"\\u4E01"'); // '丁'
        $this->assertSame($char0, Base::conv('0', $dec, $chars));
        $this->assertSame($char1, Base::conv('1', $dec, $chars));

        // 往返 0-500
        for ($i = 0; $i <= 500; $i++) {
            $s = (string)$i;
            $encoded = Base::conv($s, $dec, $chars);
            $decoded = Base::conv($encoded, $chars, $dec);
            $this->assertSame($s, $decoded, "base200 CJK 往返: {$i}");
        }

        // 大数往返
        $big = '18446744073709551615';
        $encoded = Base::conv($big, $dec, $chars);
        $decoded = Base::conv($encoded, $chars, $dec);
        $this->assertSame($big, $decoded, "base200 CJK 大数往返");

        // base200 编码应比 base62 更短
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $huge = '99999999999999999999999999999999';
        $encCjk = Base::conv($huge, $dec, $chars);
        $enc62 = Base::conv($huge, $dec, $b62);
        // 比较字符数而非字节数
        $cjkCharCount = preg_match_all('/./u', $encCjk);
        $b62CharCount = strlen($enc62);
        $this->assertLessThan($b62CharCount, $cjkCharCount, "base200 字符数应少于 base62");
    }

    // ======================== Emoji 字符集 ========================

    public function testConvEmojiCharset()
    {
        $dec = '0123456789';
        // 每个 emoji 是 4 字节 UTF-8
        $emoji4 = '😀😁😂🤣';

        $this->assertSame('😀', Base::conv('0', $dec, $emoji4));
        $this->assertSame('😁', Base::conv('1', $dec, $emoji4));
        $this->assertSame('😂', Base::conv('2', $dec, $emoji4));
        $this->assertSame('🤣', Base::conv('3', $dec, $emoji4));
        $this->assertSame('😁😀', Base::conv('4', $dec, $emoji4));

        // 往返 0-100
        for ($i = 0; $i <= 100; $i++) {
            $s = (string)$i;
            $encoded = Base::conv($s, $dec, $emoji4);
            $decoded = Base::conv($encoded, $emoji4, $dec);
            $this->assertSame($s, $decoded, "emoji base4 往返: {$i}");
        }
    }

    // ======================== isInteger 补充: 特殊浮点值 ========================

    public function testIsIntegerSpecialFloats()
    {
        $this->assertFalse(Base::isInteger(INF));
        $this->assertFalse(Base::isInteger(-INF));
        $this->assertFalse(Base::isInteger(NAN));
        $this->assertFalse(Base::isInteger(1e100));   // 超大 float
        $this->assertFalse(Base::isInteger(-1e100));
        $this->assertFalse(Base::isInteger(0.1));
        $this->assertFalse(Base::isInteger(-0.1));
        $this->assertFalse(Base::isInteger(0.9999999999));
    }

    // ======================== isInteger 补充: 字符串格式边界 ========================

    public function testIsIntegerStringEdgeCases()
    {
        // 前导零的字符串（仍然匹配 /^-?[0-9]+$/）
        $this->assertTrue(Base::isInteger('00123'));
        $this->assertTrue(Base::isInteger('000'));
        $this->assertTrue(Base::isInteger('-0'));
        $this->assertTrue(Base::isInteger('-00123'));

        // 正号
        $this->assertFalse(Base::isInteger('+123'));
        $this->assertFalse(Base::isInteger('+0'));

        // 科学计数法字符串
        $this->assertFalse(Base::isInteger('1e5'));
        $this->assertFalse(Base::isInteger('1E5'));
        $this->assertFalse(Base::isInteger('1.0e5'));

        // 十六进制字符串
        $this->assertFalse(Base::isInteger('0x1F'));
        $this->assertFalse(Base::isInteger('0xFF'));

        // 八进制字符串
        $this->assertFalse(Base::isInteger('0o77'));
        $this->assertFalse(Base::isInteger('0b1010'));

        // 空白字符
        $this->assertFalse(Base::isInteger("\t123"));
        $this->assertFalse(Base::isInteger("123\n"));
        $this->assertFalse(Base::isInteger(" 0 "));
        $this->assertFalse(Base::isInteger("\r\n"));
    }

    // ======================== isInteger 补充: 更多类型 ========================

    public function testIsIntegerMoreTypes()
    {
        // Closure
        $this->assertFalse(Base::isInteger(function () {}));

        // 嵌套数组
        $this->assertFalse(Base::isInteger([[1]]));

        // 带 __toString 的对象
        $obj = new class {
            public function __toString() { return '123'; }
        };
        $this->assertFalse(Base::isInteger($obj));

        // 单字符 "0"
        $this->assertTrue(Base::isInteger('0'));

        // 非常长的数字字符串（1000 位）
        $longNum = str_repeat('9', 1000);
        $this->assertTrue(Base::isInteger($longNum));

        $longNeg = '-' . str_repeat('1', 1000);
        $this->assertTrue(Base::isInteger($longNeg));
    }

    // ======================== isInteger 补充: 浮点精度边界 ========================

    public function testIsIntegerFloatPrecisionBoundary()
    {
        // 2^53 边界精确测试
        $this->assertTrue(Base::isInteger(4503599627370496.0));   // 2^52, 有小数 0
        $this->assertTrue(Base::isInteger(9007199254740991.0));   // 2^53-1
        $this->assertTrue(Base::isInteger(9007199254740992.0));   // 2^53 (恰好等于上限)

        // 负方向
        $this->assertTrue(Base::isInteger(-9007199254740992.0));
        $this->assertTrue(Base::isInteger(-9007199254740991.0));

        // 小 float 整数
        $this->assertTrue(Base::isInteger(1.0));
        $this->assertTrue(Base::isInteger(-1.0));
        $this->assertTrue(Base::isInteger(100.0));
    }

    // ======================== toString 补充: float 输入 ========================

    public function testToStringFloat()
    {
        // float 走 is_numeric 分支
        $this->assertSame('0', Base::toString(0.0));
        $this->assertSame('1', Base::toString(1.0));
        $this->assertSame('-1', Base::toString(-1.0));
    }

    // ======================== conv() 单射性（不同输入 → 不同输出） ========================

    public function testConvInjectivity()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // hex: 0-9999 中不能有两个不同的十进制数映射到同一个 hex
        $hexSeen = [];
        for ($i = 0; $i <= 9999; $i++) {
            $v = Base::conv((string)$i, $dec, $hex);
            $prev = isset($hexSeen[$v]) ? $hexSeen[$v] : '?';
            $this->assertArrayNotHasKey($v, $hexSeen, "hex 单射性失败: {$i} 和 {$prev} 都映射到 {$v}");
            $hexSeen[$v] = $i;
        }

        // b62: 0-9999
        $b62Seen = [];
        for ($i = 0; $i <= 9999; $i++) {
            $v = Base::conv((string)$i, $dec, $b62);
            $prev = isset($b62Seen[$v]) ? $b62Seen[$v] : '?';
            $this->assertArrayNotHasKey($v, $b62Seen, "b62 单射性失败: {$i} 和 {$prev} 都映射到 {$v}");
            $b62Seen[$v] = $i;
        }
    }

    // ======================== conv() 字符集顺序影响编码结果 ========================

    public function testConvCharsetOrderMatters()
    {
        $dec = '0123456789';
        $abc = 'abc';
        $bac = 'bac';

        // 同一个数在不同顺序的字符集中编码结果不同
        // abc: a=0, b=1, c=2  → 5 = 1*3+2 = 'bc'
        // bac: b=0, a=1, c=2  → 5 = 1*3+2 = 'ac'
        $this->assertSame('bc', Base::conv('5', $dec, $abc));
        $this->assertSame('ac', Base::conv('5', $dec, $bac));

        // 但各自往返都正确
        for ($i = 0; $i <= 50; $i++) {
            $s = (string)$i;
            $this->assertSame($s, Base::conv(Base::conv($s, $dec, $abc), $abc, $dec), "abc 往返: {$i}");
            $this->assertSame($s, Base::conv(Base::conv($s, $dec, $bac), $bac, $dec), "bac 往返: {$i}");
        }
    }

    // ======================== conv() 大进制压缩比验证 ========================

    public function testConvCompressionRatio()
    {
        $dec = '0123456789';
        $bin = '01';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // 同一个大数, bin 最长, dec 中间, b62 最短
        $values = ['999999999999999999', '123456789012345678901234567890'];
        foreach ($values as $v) {
            $binLen = strlen(Base::conv($v, $dec, $bin));
            $decLen = strlen($v);
            $b62Len = strlen(Base::conv($v, $dec, $b62));

            $this->assertGreaterThan($decLen, $binLen, "bin 应比 dec 长: {$v}");
            $this->assertLessThan($decLen, $b62Len, "b62 应比 dec 短: {$v}");
        }
    }

    // ======================== conv() 连续 10001-20000 大范围往返 ========================

    public function testConvLargeRangeSequential()
    {
        $dec = '0123456789';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        for ($i = 10001; $i <= 20000; $i++) {
            $s = (string)$i;
            $encoded = Base::conv($s, $dec, $b62);
            $decoded = Base::conv($encoded, $b62, $dec);
            $this->assertSame($s, $decoded, "b62 大范围往返: {$i}");
        }
    }

    // ======================== conv() 10 的幂次 ========================

    public function testConvPowersOfTen()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';

        for ($n = 0; $n <= 30; $n++) {
            $val = bcpow('10', (string)$n, 0);
            $hexVal = Base::conv($val, $dec, $hex);
            $back = Base::conv($hexVal, $hex, $dec);
            $this->assertSame($val, $back, "10^{$n} hex 往返");

            $b36Val = Base::conv($val, $dec, $b36);
            $back36 = Base::conv($b36Val, $b36, $dec);
            $this->assertSame($val, $back36, "10^{$n} b36 往返");
        }
    }

    // ======================== conv() 阶乘值验证 ========================

    public function testConvFactorials()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // 计算 1! 到 50!
        $factorial = '1';
        for ($n = 1; $n <= 50; $n++) {
            $factorial = bcmul($factorial, (string)$n, 0);
            $hexVal = Base::conv($factorial, $dec, $hex);
            $this->assertSame($factorial, Base::conv($hexVal, $hex, $dec), "{$n}! hex 往返");

            $b62Val = Base::conv($factorial, $dec, $b62);
            $this->assertSame($factorial, Base::conv($b62Val, $b62, $dec), "{$n}! b62 往返");
        }
    }

    // ======================== conv() 斐波那契数列验证 ========================

    public function testConvFibonacci()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';

        $a = '0';
        $b = '1';
        for ($n = 0; $n < 100; $n++) {
            $hexVal = Base::conv($a, $dec, $hex);
            $back = Base::conv($hexVal, $hex, $dec);
            $this->assertSame($a, $back, "fib({$n}) hex 往返");

            $next = bcadd($a, $b, 0);
            $a = $b;
            $b = $next;
        }
    }

    // ======================== base128/256 互转 ========================

    public function testConvBase128ToBase256()
    {
        $dec = '0123456789';
        $b128 = '';
        for ($i = 0; $i < 128; $i++) {
            $b128 .= chr($i);
        }
        $b256 = '';
        for ($i = 0; $i < 256; $i++) {
            $b256 .= chr($i);
        }

        $values = ['0', '1', '127', '128', '255', '256', '65535', '18446744073709551615'];
        foreach ($values as $v) {
            $in128 = Base::conv($v, $dec, $b128);
            $in256 = Base::conv($in128, $b128, $b256);
            $back = Base::conv($in256, $b256, $dec);
            $this->assertSame($v, $back, "base128→base256 往返: {$v}");
        }
    }

    // ======================== toStringWithPad 补充: 边界长度 ========================

    public function testToStringWithPadEdgeLengths()
    {
        // 长度等于数字长度
        $this->assertSame('12345', Base::toStringWithPad('12345', 5));

        // 长度为 0: str_pad 不填充, substr(x, 0, 0) 返回空
        // 注意: 这是一个边界行为
        $result = Base::toStringWithPad('123', 0);
        $this->assertSame('', $result);

        // 超长 pad
        $result = Base::toStringWithPad('1', 100);
        $this->assertSame(100, strlen($result));
        $this->assertSame(str_repeat('0', 99) . '1', $result);
    }

    // ======================== conv() 同一值不同类型输入一致 ========================

    public function testConvInputTypeConsistency()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // int 和 string 输入结果应一致
        $intValues = [0, 1, 10, 100, 255, 65535, 1000000];
        foreach ($intValues as $v) {
            $fromInt = Base::conv($v, $dec, $hex);
            $fromStr = Base::conv((string)$v, $dec, $hex);
            $this->assertSame($fromStr, $fromInt, "int/string hex 不一致: {$v}");

            $fromInt62 = Base::conv($v, $dec, $b62);
            $fromStr62 = Base::conv((string)$v, $dec, $b62);
            $this->assertSame($fromStr62, $fromInt62, "int/string b62 不一致: {$v}");
        }
    }

    // ======================== to36/to62 编码长度增长验证 ========================

    public function testTo36To62LengthGrowth()
    {
        // to36: 单字符 0-35, 两字符 36-1295, 三字符 1296-46655
        $this->assertSame(1, strlen(Base::to36('0')));
        $this->assertSame(1, strlen(Base::to36('35')));
        $this->assertSame(2, strlen(Base::to36('36')));
        $this->assertSame(2, strlen(Base::to36('1295')));
        $this->assertSame(3, strlen(Base::to36('1296')));
        $this->assertSame(3, strlen(Base::to36('46655')));
        $this->assertSame(4, strlen(Base::to36('46656')));

        // to62: 单字符 0-61, 两字符 62-3843, 三字符 3844-238327
        $this->assertSame(1, strlen(Base::to62('0')));
        $this->assertSame(1, strlen(Base::to62('61')));
        $this->assertSame(2, strlen(Base::to62('62')));
        $this->assertSame(2, strlen(Base::to62('3843')));
        $this->assertSame(3, strlen(Base::to62('3844')));
        $this->assertSame(3, strlen(Base::to62('238327')));
        $this->assertSame(4, strlen(Base::to62('238328')));
    }

    // ======================== 任意进制互转: base 2-20 全组合 ========================

    /**
     * 对 base 2 到 base 20 的每一对 (A, B) 做交叉往返测试
     * 覆盖 19×19=361 种进制组合
     */
    public function testConvAllPairsBase2To20()
    {
        $dec = '0123456789';
        $fullChars = 'abcdefghijklmnopqrstuvwxyz';

        $bases = [];
        for ($size = 2; $size <= 20; $size++) {
            $bases[$size] = substr($fullChars, 0, $size);
        }

        $testValues = ['0', '1', '100', '9999', '123456789'];

        foreach ($testValues as $decVal) {
            foreach ($bases as $sizeA => $baseA) {
                $inA = Base::conv($decVal, $dec, $baseA);

                foreach ($bases as $sizeB => $baseB) {
                    $inB = Base::conv($inA, $baseA, $baseB);
                    $back = Base::conv($inB, $baseB, $dec);
                    $this->assertSame(
                        $decVal,
                        $back,
                        "base{$sizeA}→base{$sizeB} 失败: dec={$decVal}"
                    );
                }
            }
        }
    }

    // ======================== 任意进制互转: base 2-62 全扫描 ========================

    /**
     * 每个进制 (2..62) 都做十进制往返, 确保 GMP 快速路径对所有支持的进制正确
     */
    public function testConvEveryBaseSize2To62RoundTrip()
    {
        $dec = '0123456789';
        $fullChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $testValues = [
            '0', '1', '2', '10', '61', '62', '255', '1000',
            '65535', '999999', strval(PHP_INT_MAX),
            '18446744073709551615',
            '99999999999999999999999999999',
        ];

        for ($size = 2; $size <= 62; $size++) {
            $base = substr($fullChars, 0, $size);

            foreach ($testValues as $val) {
                $encoded = Base::conv($val, $dec, $base);
                $decoded = Base::conv($encoded, $base, $dec);
                $this->assertSame(
                    $val,
                    $decoded,
                    "base{$size} 往返失败: val={$val}"
                );
            }
        }
    }

    // ======================== 任意进制互转: base 37-61 GMP 大写字母表 ========================

    /**
     * 专门测试 base 37-61 区间, 这些进制使用 GMP 的大写优先字母表 (0-9A-Za-z)
     * 而我们的字符集是 0-9a-zA-Z, 需要 strtr 映射
     */
    public function testConvBases37To61GmpAlphabetMapping()
    {
        $dec = '0123456789';
        $fullChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // 重点测试 37, 40, 50, 58, 61
        $sizes = [37, 38, 40, 45, 50, 55, 58, 60, 61];
        $values = ['0', '1', '36', '37', '100', '999', '65535', '18446744073709551615'];

        foreach ($sizes as $size) {
            $base = substr($fullChars, 0, $size);
            foreach ($values as $val) {
                $encoded = Base::conv($val, $dec, $base);
                $decoded = Base::conv($encoded, $base, $dec);
                $this->assertSame(
                    $val,
                    $decoded,
                    "base{$size} 往返失败: val={$val}"
                );

                // 编码结果中每个字符都必须在字符集内
                for ($c = 0; $c < strlen($encoded); $c++) {
                    $this->assertNotFalse(
                        strpos($base, $encoded[$c]),
                        "base{$size} 输出包含非法字符 '{$encoded[$c]}': val={$val}, encoded={$encoded}"
                    );
                }
            }
        }
    }

    // ======================== 任意进制互转: base 37-61 N×N 交叉 ========================

    public function testConvCrossPairsBases37To61()
    {
        $dec = '0123456789';
        $fullChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $sizes = [37, 42, 50, 58, 62];
        $values = ['0', '1', '1000', '999999', '18446744073709551615'];

        foreach ($values as $decVal) {
            foreach ($sizes as $sizeA) {
                $baseA = substr($fullChars, 0, $sizeA);
                $inA = Base::conv($decVal, $dec, $baseA);

                foreach ($sizes as $sizeB) {
                    $baseB = substr($fullChars, 0, $sizeB);
                    $inB = Base::conv($inA, $baseA, $baseB);
                    $back = Base::conv($inB, $baseB, $dec);
                    $this->assertSame(
                        $decVal,
                        $back,
                        "base{$sizeA}→base{$sizeB} 失败: dec={$decVal}"
                    );
                }
            }
        }
    }

    // ======================== 任意进制互转: 边界值 (0, 1, base-1, base, base^2-1, base^2) ========================

    public function testConvAnyBaseBoundaryValues()
    {
        $dec = '0123456789';
        $fullChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // 选取典型进制
        $sizes = [2, 3, 7, 10, 16, 36, 50, 62];

        foreach ($sizes as $size) {
            $base = substr($fullChars, 0, $size);

            // 边界值
            $boundaries = [
                '0',                                           // 零
                '1',                                           // 最小正数
                (string)($size - 1),                           // 最大单位数
                (string)$size,                                 // 最小两位数
                (string)($size * $size - 1),                   // 最大两位数
                (string)($size * $size),                       // 最小三位数
                bcpow((string)$size, '3', 0),                  // base^3
                bcsub(bcpow((string)$size, '3', 0), '1', 0),  // base^3 - 1
                bcpow((string)$size, '10', 0),                 // base^10
                bcsub(bcpow((string)$size, '10', 0), '1', 0), // base^10 - 1
            ];

            foreach ($boundaries as $val) {
                $encoded = Base::conv($val, $dec, $base);
                $decoded = Base::conv($encoded, $base, $dec);
                $this->assertSame(
                    $val,
                    $decoded,
                    "base{$size} 边界值往返失败: val={$val}"
                );
            }

            // base^n 的编码必须是 "10...0" (第二个字符 + n个第一个字符)
            $zeroChar = $base[0];
            $oneChar = $base[1];
            for ($n = 1; $n <= 5; $n++) {
                $val = bcpow((string)$size, (string)$n, 0);
                $encoded = Base::conv($val, $dec, $base);
                $expected = $oneChar . str_repeat($zeroChar, $n);
                $this->assertSame(
                    $expected,
                    $encoded,
                    "base{$size}^{$n} 编码应为 '{$expected}', 实际 '{$encoded}'"
                );
            }

            // base^n - 1 的编码必须是 n 个最后一个字符
            $lastChar = substr($base, $size - 1, 1);
            for ($n = 1; $n <= 5; $n++) {
                $val = bcsub(bcpow((string)$size, (string)$n, 0), '1', 0);
                $encoded = Base::conv($val, $dec, $base);
                $expected = str_repeat($lastChar, $n);
                $this->assertSame(
                    $expected,
                    $encoded,
                    "base{$size}^{$n}-1 编码应为 '{$expected}', 实际 '{$encoded}'"
                );
            }
        }
    }

    // ======================== 任意进制互转: 非十进制→非十进制 大数 ========================

    public function testConvNonDecToNonDecLargeNumbers()
    {
        $dec = '0123456789';
        $bin = '01';
        $oct = '01234567';
        $hex = '0123456789abcdef';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $allBases = [
            'bin' => $bin, 'oct' => $oct, 'hex' => $hex,
            'b36' => $b36, 'b62' => $b62,
        ];

        // 大数: 2^128, 阶乘 30!, 随机样式
        $bigDecimals = [
            bcpow('2', '128', 0),
            bcpow('2', '256', 0),
            '265252859812191058636308480000000', // 30!
            str_repeat('1234567890', 10),
        ];

        foreach ($bigDecimals as $decVal) {
            $decVal = ltrim($decVal, '0') ?: '0';

            // 先全部编码
            $encoded = [];
            foreach ($allBases as $name => $base) {
                $encoded[$name] = Base::conv($decVal, $dec, $base);
            }

            // 任意两个之间互转
            foreach ($allBases as $nameA => $baseA) {
                foreach ($allBases as $nameB => $baseB) {
                    $result = Base::conv($encoded[$nameA], $baseA, $baseB);
                    $this->assertSame(
                        $encoded[$nameB],
                        $result,
                        "{$nameA}→{$nameB} 不一致: decVal=" . substr($decVal, 0, 30) . '...'
                    );
                }
            }
        }
    }

    // ======================== 任意进制互转: 自定义字符集与GMP字母表重叠 ========================

    /**
     * 测试用户自定义字符集恰好与 GMP 字母表部分重叠但顺序不同的场景
     * 验证 strtr 映射的正确性
     */
    public function testConvCustomCharsetOverlapsGmpAlphabet()
    {
        $dec = '0123456789';

        // case 1: 大写 hex (GMP 用小写)
        $upperHex = '0123456789ABCDEF';
        $lowerHex = '0123456789abcdef';
        $values = ['0', '1', '255', '65535', '18446744073709551615'];
        foreach ($values as $v) {
            $upper = Base::conv($v, $dec, $upperHex);
            $lower = Base::conv($v, $dec, $lowerHex);
            $this->assertSame(strtoupper($lower), $upper, "大写hex: {$v}");
            $this->assertSame($v, Base::conv($upper, $upperHex, $dec), "大写hex往返: {$v}");
        }

        // case 2: base36 用大写字母 (GMP base36 用小写)
        $upperB36 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowerB36 = '0123456789abcdefghijklmnopqrstuvwxyz';
        foreach ($values as $v) {
            $upper = Base::conv($v, $dec, $upperB36);
            $lower = Base::conv($v, $dec, $lowerB36);
            $this->assertSame(strtoupper($lower), $upper, "大写b36: {$v}");
            $this->assertSame($v, Base::conv($upper, $upperB36, $dec), "大写b36往返: {$v}");
        }

        // case 3: 完全反转的 hex 字符集
        $revHex = 'fedcba9876543210';
        foreach ($values as $v) {
            $encoded = Base::conv($v, $dec, $revHex);
            $decoded = Base::conv($encoded, $revHex, $dec);
            $this->assertSame($v, $decoded, "反转hex往返: {$v}");
        }

        // case 4: 反转的 base62
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $revB62 = strrev($b62);
        foreach ($values as $v) {
            $normal = Base::conv($v, $dec, $b62);
            $reversed = Base::conv($v, $dec, $revB62);
            // 反转字符集的编码与正常不同
            if ($v !== '0') {
                $this->assertNotSame($normal, $reversed, "反转b62应产生不同编码: {$v}");
            }
            $this->assertSame($v, Base::conv($reversed, $revB62, $dec), "反转b62往返: {$v}");
        }

        // case 5: base62 小写优先顺序 (a-zA-Z)
        $altOrder62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        foreach ($values as $v) {
            $encoded = Base::conv($v, $dec, $altOrder62);
            $decoded = Base::conv($encoded, $altOrder62, $dec);
            $this->assertSame($v, $decoded, "小写优先b62往返: {$v}");
        }

        // case 6: 两个不同的自定义字符集直接互转
        foreach ($values as $v) {
            $inUpper = Base::conv($v, $dec, $upperHex);
            $inRev = Base::conv($inUpper, $upperHex, $revHex);
            $back = Base::conv($inRev, $revHex, $dec);
            $this->assertSame($v, $back, "大写hex→反转hex: {$v}");
        }
    }

    // ======================== 任意进制互转: 素数进制 ========================

    public function testConvPrimeBaseSizes()
    {
        $dec = '0123456789';
        $fullChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // 素数进制: 2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47, 53, 59, 61
        $primes = [2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47, 53, 59, 61];
        $testValues = ['0', '1', '999', '65535', '18446744073709551615'];

        // 每对素数进制之间互转
        for ($i = 0; $i < count($primes); $i++) {
            $baseA = substr($fullChars, 0, $primes[$i]);
            for ($j = $i + 1; $j < count($primes); $j++) {
                $baseB = substr($fullChars, 0, $primes[$j]);

                foreach ($testValues as $decVal) {
                    $inA = Base::conv($decVal, $dec, $baseA);
                    $inB = Base::conv($inA, $baseA, $baseB);
                    $back = Base::conv($inB, $baseB, $dec);
                    $this->assertSame(
                        $decVal,
                        $back,
                        "base{$primes[$i]}→base{$primes[$j]} 失败: val={$decVal}"
                    );
                }
            }
        }
    }

    // ======================== 任意进制互转: 多字节字符集全组合 ========================

    public function testConvMultibyteAllPairs()
    {
        $dec = '0123456789';

        // 构造不同大小的多字节字符集
        $cn3 = '零壹贰';
        $cn5 = '甲乙丙丁戊';
        $emoji4 = '😀😁😂🤣';
        $jp6 = 'あいうえおか';
        $mixed8 = '零a壹bあ1い2';  // 中文+ASCII+日文+数字

        $multibyteBases = [
            'cn3' => $cn3,
            'cn5' => $cn5,
            'emoji4' => $emoji4,
            'jp6' => $jp6,
            'mixed8' => $mixed8,
        ];

        $testValues = ['0', '1', '100', '9999', '999999'];

        // 多字节字符集之间的全组合互转
        foreach ($testValues as $decVal) {
            foreach ($multibyteBases as $nameA => $baseA) {
                $inA = Base::conv($decVal, $dec, $baseA);

                foreach ($multibyteBases as $nameB => $baseB) {
                    $inB = Base::conv($inA, $baseA, $baseB);
                    $back = Base::conv($inB, $baseB, $dec);
                    $this->assertSame(
                        $decVal,
                        $back,
                        "{$nameA}→{$nameB} 失败: val={$decVal}"
                    );
                }
            }
        }
    }

    // ======================== 任意进制互转: 多字节 ↔ ASCII 全组合 ========================

    public function testConvMultibyteToAsciiAllPairs()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $bin = '01';

        $asciiBases = ['bin' => $bin, 'hex' => $hex, 'b36' => $b36, 'b62' => $b62];
        $multibyteBases = [
            'cn3' => '零壹贰',
            'emoji4' => '😀😁😂🤣',
            'cn10' => '零壹贰叁肆伍陆柒捌玖',
        ];

        $testValues = ['0', '1', '255', '65535', '999999', '18446744073709551615'];

        foreach ($testValues as $decVal) {
            // ASCII → 多字节
            foreach ($asciiBases as $aName => $aBase) {
                $inA = Base::conv($decVal, $dec, $aBase);

                foreach ($multibyteBases as $mName => $mBase) {
                    $inM = Base::conv($inA, $aBase, $mBase);
                    $back = Base::conv($inM, $mBase, $dec);
                    $this->assertSame(
                        $decVal,
                        $back,
                        "{$aName}→{$mName} 失败: val={$decVal}"
                    );
                }
            }

            // 多字节 → ASCII
            foreach ($multibyteBases as $mName => $mBase) {
                $inM = Base::conv($decVal, $dec, $mBase);

                foreach ($asciiBases as $aName => $aBase) {
                    $inA = Base::conv($inM, $mBase, $aBase);
                    $back = Base::conv($inA, $aBase, $dec);
                    $this->assertSame(
                        $decVal,
                        $back,
                        "{$mName}→{$aName} 失败: val={$decVal}"
                    );
                }
            }
        }
    }

    // ======================== 任意进制互转: base > 62 (Horner路径) ↔ base ≤ 62 (快速路径) ========================

    public function testConvHornerPathVsFastPath()
    {
        $dec = '0123456789';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // 构建 base-100 CJK 字符集 (走 Horner 路径)
        $cjk100 = '';
        for ($i = 0; $i < 100; $i++) {
            $cjk100 .= json_decode(sprintf('"\\u%04X"', 0x4E00 + $i));
        }

        // 构建 base-128 字节字符集 (走 Horner 路径, 因为 >62)
        $b128 = '';
        for ($i = 0; $i < 128; $i++) {
            $b128 .= chr($i);
        }

        $testValues = ['0', '1', '100', '65535', '999999', '18446744073709551615'];

        foreach ($testValues as $decVal) {
            // b62 (快速路径) ↔ cjk100 (Horner 路径)
            $inB62 = Base::conv($decVal, $dec, $b62);
            $inCjk = Base::conv($inB62, $b62, $cjk100);
            $back = Base::conv($inCjk, $cjk100, $dec);
            $this->assertSame($decVal, $back, "b62→cjk100 失败: val={$decVal}");

            $inCjk2 = Base::conv($decVal, $dec, $cjk100);
            $inB62_2 = Base::conv($inCjk2, $cjk100, $b62);
            $back2 = Base::conv($inB62_2, $b62, $dec);
            $this->assertSame($decVal, $back2, "cjk100→b62 失败: val={$decVal}");

            // b62 (快速路径) ↔ b128 (Horner 路径)
            $inB62_3 = Base::conv($decVal, $dec, $b62);
            $inB128 = Base::conv($inB62_3, $b62, $b128);
            $back3 = Base::conv($inB128, $b128, $dec);
            $this->assertSame($decVal, $back3, "b62→b128 失败: val={$decVal}");

            // cjk100 (Horner) ↔ b128 (Horner)
            $inCjk3 = Base::conv($decVal, $dec, $cjk100);
            $inB128_2 = Base::conv($inCjk3, $cjk100, $b128);
            $back4 = Base::conv($inB128_2, $b128, $dec);
            $this->assertSame($decVal, $back4, "cjk100→b128 失败: val={$decVal}");
        }
    }

    // ======================== 任意进制互转: 连续值 N×N 高密度测试 ========================

    /**
     * 对 0-500 每个值, 在 6 种不同进制之间做全组合互转
     * 共计 501 × 6 × 6 = 18036 次转换
     */
    public function testConvDenseSequentialAllPairs()
    {
        $dec = '0123456789';
        $bases = [
            '01',                                                             // base 2
            '01234567',                                                       // base 8
            '0123456789abcdef',                                               // base 16
            '0123456789abcdefghijklmnopqrstuvwxyz',                           // base 36
            '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', // base 62
            'XYZ',                                                            // base 3 自定义
        ];

        for ($i = 0; $i <= 500; $i++) {
            $decVal = (string)$i;

            // 预先编码到所有进制
            $encoded = [];
            foreach ($bases as $idx => $base) {
                $encoded[$idx] = Base::conv($decVal, $dec, $base);
            }

            // 任意两种进制之间互转, 结果应一致
            foreach ($bases as $idxA => $baseA) {
                foreach ($bases as $idxB => $baseB) {
                    $result = Base::conv($encoded[$idxA], $baseA, $baseB);
                    $this->assertSame(
                        $encoded[$idxB],
                        $result,
                        "i={$i}: base[{$idxA}]→base[{$idxB}] 转换不一致"
                    );
                }
            }
        }
    }

    // ======================== 任意进制互转: 链式转换 ========================

    /**
     * 一个值经过 A→B→C→D→...→Z→dec 的链式转换后, 结果应和直接 A→dec 一致
     */
    public function testConvChainedConversion()
    {
        $dec = '0123456789';

        $chain = [
            '01',                                                             // base 2
            'XYZ',                                                            // base 3
            '01234567',                                                       // base 8
            '0123456789abcdef',                                               // base 16
            '0123456789abcdefghijklmnopqrstuvwxyz',                           // base 36
            '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', // base 62
        ];

        $values = ['0', '1', '42', '255', '65535', '999999', '18446744073709551615'];

        foreach ($values as $decVal) {
            // 正向链: dec → chain[0] → chain[1] → ... → chain[n-1]
            $current = $decVal;
            $currentBase = $dec;
            foreach ($chain as $nextBase) {
                $current = Base::conv($current, $currentBase, $nextBase);
                $currentBase = $nextBase;
            }
            // 最后转回十进制
            $result = Base::conv($current, $currentBase, $dec);
            $this->assertSame($decVal, $result, "正向链失败: val={$decVal}");

            // 反向链: dec → chain[n-1] → ... → chain[0]
            $current = $decVal;
            $currentBase = $dec;
            foreach (array_reverse($chain) as $nextBase) {
                $current = Base::conv($current, $currentBase, $nextBase);
                $currentBase = $nextBase;
            }
            $result = Base::conv($current, $currentBase, $dec);
            $this->assertSame($decVal, $result, "反向链失败: val={$decVal}");
        }
    }

    // ======================== 任意进制互转: 相同进制大小但不同字符集 ========================

    /**
     * 两个 base-16 字符集 (小写/大写) 之间直接转换
     * 本质是字符替换, 数值逻辑应完全一致
     */
    public function testConvSameBaseSizeDifferentCharsets()
    {
        $dec = '0123456789';
        $lowerHex = '0123456789abcdef';
        $upperHex = '0123456789ABCDEF';
        $symbolHex = '!@#$%^&*()abcdef'; // 怪异的 base-16

        $hexVariants = [$lowerHex, $upperHex, $symbolHex];
        $values = ['0', '1', '255', '65535', '18446744073709551615'];

        foreach ($values as $decVal) {
            foreach ($hexVariants as $fromHex) {
                $inFrom = Base::conv($decVal, $dec, $fromHex);

                foreach ($hexVariants as $toHex) {
                    $converted = Base::conv($inFrom, $fromHex, $toHex);
                    $back = Base::conv($converted, $toHex, $dec);
                    $this->assertSame($decVal, $back, "hex变体互转失败: val={$decVal}");
                }
            }
        }

        // 同理测试两个不同的 base-62
        $b62a = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $b62b = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; // 小写优先顺序
        $b62c = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'; // 字母优先

        $b62Variants = [$b62a, $b62b, $b62c];
        foreach ($values as $decVal) {
            foreach ($b62Variants as $from62) {
                $inFrom = Base::conv($decVal, $dec, $from62);
                foreach ($b62Variants as $to62) {
                    $converted = Base::conv($inFrom, $from62, $to62);
                    $back = Base::conv($converted, $to62, $dec);
                    $this->assertSame($decVal, $back, "b62变体互转失败: val={$decVal}");
                }
            }
        }
    }

    // ======================== 任意进制互转: 单射性在任意进制对中成立 ========================

    public function testConvInjectivityAcrossBases()
    {
        $dec = '0123456789';
        $b7 = 'abcdefg';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';
        $b50 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMN';

        $bases = ['b7' => $b7, 'b36' => $b36, 'b50' => $b50];

        // 对每个进制, 验证 0-2000 编码结果无重复
        foreach ($bases as $name => $base) {
            $seen = [];
            for ($i = 0; $i <= 2000; $i++) {
                $v = Base::conv((string)$i, $dec, $base);
                $prev = isset($seen[$v]) ? $seen[$v] : '?';
                $this->assertArrayNotHasKey(
                    $v,
                    $seen,
                    "{$name} 单射性失败: {$i} 和 {$prev} 都映射到 '{$v}'"
                );
                $seen[$v] = $i;
            }
        }

        // 对 b7→b36 直接转换, 验证不同输入产生不同输出
        $seen = [];
        for ($i = 0; $i <= 2000; $i++) {
            $inB7 = Base::conv((string)$i, $dec, $b7);
            $inB36 = Base::conv($inB7, $b7, $b36);
            $prev = isset($seen[$inB36]) ? $seen[$inB36] : '?';
            $this->assertArrayNotHasKey(
                $inB36,
                $seen,
                "b7→b36 单射性失败: {$i} 和 {$prev} 都映射到 '{$inB36}'"
            );
            $seen[$inB36] = $i;
        }
    }

    // ======================== 任意进制互转: 随机大数矩阵 ========================

    /**
     * 用数学方式构造一批大数 (非随机, 可重现), 在多种进制间做全矩阵往返
     */
    public function testConvLargeNumberMatrix()
    {
        $dec = '0123456789';

        $bases = [
            '01',
            '01234567',
            '0123456789abcdef',
            '0123456789abcdefghijklmnopqrstuvwxyz',
            '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
        ];

        // 构造大数: 2^n, 10^n, n!, 以及拼接数
        $bigValues = [];
        for ($n = 50; $n <= 500; $n += 50) {
            $bigValues[] = bcpow('2', (string)$n, 0);
        }
        for ($n = 20; $n <= 100; $n += 10) {
            $bigValues[] = bcpow('10', (string)$n, 0);
        }
        $fact = '1';
        for ($n = 1; $n <= 80; $n++) {
            $fact = bcmul($fact, (string)$n, 0);
            if ($n % 10 === 0) {
                $bigValues[] = $fact;
            }
        }

        foreach ($bigValues as $decVal) {
            $decVal = ltrim($decVal, '0') ?: '0';

            // 在所有进制之间做全矩阵转换
            $encodedList = [];
            foreach ($bases as $base) {
                $encodedList[] = Base::conv($decVal, $dec, $base);
            }

            for ($a = 0; $a < count($bases); $a++) {
                for ($b = 0; $b < count($bases); $b++) {
                    $result = Base::conv($encodedList[$a], $bases[$a], $bases[$b]);
                    $this->assertSame(
                        $encodedList[$b],
                        $result,
                        "大数矩阵[{$a}]→[{$b}] 失败: decVal=" . substr($decVal, 0, 30) . '...'
                    );
                }
            }
        }
    }

    // ======================== 任意进制互转: 十进制字符集不在标准位置 ========================

    /**
     * conv() 通过比较 fromBase/toBase 是否等于 '0123456789' 来判断是否十进制
     * 测试其他碰巧有 10 个字符但不是 '0123456789' 的字符集不会被误判
     */
    public function testConvNonStandardDecimal()
    {
        $dec = '0123456789';

        // 这些都是 base-10 但不等于 '0123456789', 不会走 "fromBase == dec" 的快速路径
        $alt10 = 'ABCDEFGHIJ';       // A=0, B=1, ..., J=9
        $alt10b = '9876543210';       // 反转
        $alt10c = 'abcdefghij';

        $variants = [$alt10, $alt10b, $alt10c];
        $values = ['0', '1', '42', '255', '9999', '65535', '18446744073709551615'];

        foreach ($variants as $base10) {
            foreach ($values as $decVal) {
                // dec → alt10 → dec
                $encoded = Base::conv($decVal, $dec, $base10);
                $decoded = Base::conv($encoded, $base10, $dec);
                $this->assertSame($decVal, $decoded, "非标准十进制往返失败: charset=" . substr($base10, 0, 5) . " val={$decVal}");

                // alt10 → hex → alt10 → dec (经过第三方进制)
                $hex = '0123456789abcdef';
                $inHex = Base::conv($encoded, $base10, $hex);
                $backAlt = Base::conv($inHex, $hex, $base10);
                $backDec = Base::conv($backAlt, $base10, $dec);
                $this->assertSame($decVal, $backDec, "非标准十进制三方往返失败: val={$decVal}");
            }
        }
    }

    // ================================================================
    //  全量 base_convert 兼容性测试
    //  PHP base_convert 支持 base 2-36, 字符集 0123456789abcdefghijklmnopqrstuvwxyz
    //  以下测试验证 Base::conv() 对同一输入产生完全一致的输出
    // ================================================================

    /**
     * 返回 base 2-36 的标准字符集数组 (与 base_convert 使用相同字符集)
     */
    private static function baseConvertCharsets(): array
    {
        static $charsets = null;
        if ($charsets === null) {
            $full = '0123456789abcdefghijklmnopqrstuvwxyz';
            $charsets = [];
            for ($b = 2; $b <= 36; $b++) {
                $charsets[$b] = substr($full, 0, $b);
            }
        }
        return $charsets;
    }

    /**
     * 十进制 → base 2-36 全量对照
     * 覆盖: 35 种目标进制 × 10001 个值 = 350,035 次对照
     */
    public function testBaseConvertCompatDecToAllBases()
    {
        $charsets = self::baseConvertCharsets();
        $dec = $charsets[10];

        for ($val = 0; $val <= 10000; $val++) {
            $decStr = (string)$val;
            for ($to = 2; $to <= 36; $to++) {
                $expected = base_convert($decStr, 10, $to);
                $actual = Base::conv($decStr, $dec, $charsets[$to]);
                $this->assertSame(
                    $expected,
                    $actual,
                    "dec→base{$to} 不兼容: val={$val}"
                );
            }
        }
    }

    /**
     * base 2-36 → 十进制 全量对照
     * 覆盖: 35 种源进制 × 10001 个值 = 350,035 次对照
     */
    public function testBaseConvertCompatAllBasesToDec()
    {
        $charsets = self::baseConvertCharsets();
        $dec = $charsets[10];

        for ($val = 0; $val <= 10000; $val++) {
            $decStr = (string)$val;
            for ($from = 2; $from <= 36; $from++) {
                // 先用 base_convert 把十进制转成源进制表示
                $inFrom = base_convert($decStr, 10, $from);

                // 然后两种方式把源进制转回十进制, 结果应一致
                $expected = base_convert($inFrom, $from, 10);
                $actual = Base::conv($inFrom, $charsets[$from], $dec);
                $this->assertSame(
                    $expected,
                    $actual,
                    "base{$from}→dec 不兼容: val={$val}, repr='{$inFrom}'"
                );
            }
        }
    }

    /**
     * base X → base Y 全组合对照 (1225 种进制对 × 101 个值)
     * 覆盖: 35×35=1225 种进制对 × 101 个值 = 123,725 次对照
     */
    public function testBaseConvertCompatAllPairsCrossConvert()
    {
        $charsets = self::baseConvertCharsets();

        for ($val = 0; $val <= 100; $val++) {
            for ($from = 2; $from <= 36; $from++) {
                // 获取 val 在 from 进制中的表示
                $inFrom = base_convert((string)$val, 10, $from);

                for ($to = 2; $to <= 36; $to++) {
                    $expected = base_convert($inFrom, $from, $to);
                    $actual = Base::conv($inFrom, $charsets[$from], $charsets[$to]);
                    $this->assertSame(
                        $expected,
                        $actual,
                        "base{$from}→base{$to} 不兼容: val={$val}, input='{$inFrom}'"
                    );
                }
            }
        }
    }

    /**
     * 大数值对照: 在 base_convert 精度范围内 (< 2^53) 测试更大的数
     * 覆盖: 代表性进制对 × 特定大值
     */
    public function testBaseConvertCompatLargeValues()
    {
        $charsets = self::baseConvertCharsets();

        // base_convert 使用 float, 精度约 15-16 位十进制
        // 以下值都在精度范围内
        $largeValues = [
            '10000', '65535', '65536', '100000', '999999',
            '1000000', '16777215', '16777216',
            '100000000', '2147483647', '2147483648',
            '4294967295', '4294967296',
            '999999999999', '1000000000000',
            '9007199254740992', // 2^53, float 精确上限
        ];

        // 测试所有进制到十进制、十进制到所有进制
        $dec = $charsets[10];
        foreach ($largeValues as $decVal) {
            for ($base = 2; $base <= 36; $base++) {
                // dec → base
                $expected = base_convert($decVal, 10, $base);
                $actual = Base::conv($decVal, $dec, $charsets[$base]);
                $this->assertSame(
                    $expected,
                    $actual,
                    "大数 dec→base{$base} 不兼容: val={$decVal}"
                );

                // base → dec
                $expectedBack = base_convert($expected, $base, 10);
                $actualBack = Base::conv($actual, $charsets[$base], $dec);
                $this->assertSame(
                    $expectedBack,
                    $actualBack,
                    "大数 base{$base}→dec 不兼容: val={$decVal}"
                );
            }
        }

        // 代表性进制对之间的大数互转
        $pairs = [
            [2, 8], [2, 16], [2, 36], [8, 16], [8, 36],
            [10, 2], [10, 8], [10, 16], [10, 36],
            [16, 2], [16, 8], [16, 36], [36, 2], [36, 16],
        ];

        foreach ($largeValues as $decVal) {
            foreach ($pairs as list($from, $to)) {
                $inFrom = base_convert($decVal, 10, $from);
                $expected = base_convert($inFrom, $from, $to);
                $actual = Base::conv($inFrom, $charsets[$from], $charsets[$to]);
                $this->assertSame(
                    $expected,
                    $actual,
                    "大数 base{$from}→base{$to} 不兼容: val={$decVal}"
                );
            }
        }
    }

    /**
     * 前导零兼容性: base_convert 和 Base::conv 对前导零的处理应一致
     */
    public function testBaseConvertCompatLeadingZeros()
    {
        $charsets = self::baseConvertCharsets();

        $cases = [
            ['00ff', 16, 10],
            ['000', 10, 16],
            ['00000000', 2, 10],
            ['00000000', 2, 16],
            ['007', 8, 10],
            ['0000', 10, 2],
            ['0100', 2, 10],
            ['00zz', 36, 10],
            ['0010', 16, 36],
        ];

        foreach ($cases as list($input, $from, $to)) {
            $expected = base_convert($input, $from, $to);
            $actual = Base::conv($input, $charsets[$from], $charsets[$to]);
            $this->assertSame(
                $expected,
                $actual,
                "前导零不兼容: base_convert('{$input}', {$from}, {$to}) = '{$expected}', Base::conv = '{$actual}'"
            );
        }
    }

    /**
     * 同进制转换兼容性: base_convert(x, n, n) 应保持值不变 (去掉前导零)
     */
    public function testBaseConvertCompatSameBase()
    {
        $charsets = self::baseConvertCharsets();

        for ($base = 2; $base <= 36; $base++) {
            for ($val = 0; $val <= 100; $val++) {
                $repr = base_convert((string)$val, 10, $base);

                // base_convert(repr, n, n) 应返回原值
                $expected = base_convert($repr, $base, $base);
                $actual = Base::conv($repr, $charsets[$base], $charsets[$base]);
                $this->assertSame(
                    $expected,
                    $actual,
                    "同进制转换不兼容: base{$base}, val={$val}, repr='{$repr}'"
                );
            }
        }
    }

    /**
     * 连续区间 base X → base Y 一致性 (验证所有非十进制对在连续值上的行为)
     * 选取有代表性的非十进制对, 密集测试 0-5000
     */
    public function testBaseConvertCompatDenseNonDecPairs()
    {
        $charsets = self::baseConvertCharsets();

        $pairs = [
            [2, 8], [2, 16], [2, 36],
            [8, 2], [8, 16], [8, 36],
            [16, 2], [16, 8], [16, 36],
            [36, 2], [36, 8], [36, 16],
            [3, 7], [5, 11], [7, 13],
            [11, 23], [13, 29], [17, 31],
        ];

        foreach ($pairs as list($from, $to)) {
            for ($val = 0; $val <= 5000; $val++) {
                $inFrom = base_convert((string)$val, 10, $from);
                $expected = base_convert($inFrom, $from, $to);
                $actual = Base::conv($inFrom, $charsets[$from], $charsets[$to]);
                $this->assertSame(
                    $expected,
                    $actual,
                    "base{$from}→base{$to} 不兼容: val={$val}"
                );
            }
        }
    }

    // ================================================================
    //  base_convert 兼容性: 高值区间 [MAX-10000, MAX]
    //  MAX = PHP_INT_MAX, base_convert 在此范围内精确无损
    // ================================================================

    /**
     * 十进制 → base 2-36, 值 [MAX-10000, MAX]
     * 覆盖: 35 种目标进制 × 10001 个值 = 350,035 次对照
     */
    public function testBaseConvertCompatHighRangeDecToAllBases()
    {
        $charsets = self::baseConvertCharsets();
        $dec = $charsets[10];
        $max = PHP_INT_MAX;

        for ($offset = 10000; $offset >= 0; $offset--) {
            $val = (string)($max - $offset);
            for ($to = 2; $to <= 36; $to++) {
                $expected = base_convert($val, 10, $to);
                $actual = Base::conv($val, $dec, $charsets[$to]);
                $this->assertSame(
                    $expected,
                    $actual,
                    "高值 dec→base{$to}: val={$val}"
                );
            }
        }
    }

    /**
     * base 2-36 → 十进制, 值 [MAX-10000, MAX]
     * 覆盖: 35 种源进制 × 10001 个值 = 350,035 次对照
     */
    public function testBaseConvertCompatHighRangeAllBasesToDec()
    {
        $charsets = self::baseConvertCharsets();
        $dec = $charsets[10];
        $max = PHP_INT_MAX;

        for ($offset = 10000; $offset >= 0; $offset--) {
            $val = (string)($max - $offset);
            for ($from = 2; $from <= 36; $from++) {
                $inFrom = base_convert($val, 10, $from);
                $expected = base_convert($inFrom, $from, 10);
                $actual = Base::conv($inFrom, $charsets[$from], $dec);
                $this->assertSame(
                    $expected,
                    $actual,
                    "高值 base{$from}→dec: val={$val}"
                );
            }
        }
    }

    /**
     * base X → base Y 全组合, 值 [MAX-100, MAX]
     * 覆盖: 1225 种进制对 × 101 个值 = 123,725 次对照
     */
    public function testBaseConvertCompatHighRangeAllPairs()
    {
        $charsets = self::baseConvertCharsets();
        $max = PHP_INT_MAX;

        for ($offset = 100; $offset >= 0; $offset--) {
            $decVal = (string)($max - $offset);
            for ($from = 2; $from <= 36; $from++) {
                $inFrom = base_convert($decVal, 10, $from);
                for ($to = 2; $to <= 36; $to++) {
                    $expected = base_convert($inFrom, $from, $to);
                    $actual = Base::conv($inFrom, $charsets[$from], $charsets[$to]);
                    $this->assertSame(
                        $expected,
                        $actual,
                        "高值 base{$from}→base{$to}: val={$decVal}"
                    );
                }
            }
        }
    }

    // ================================================================
    //  高进制 (64+) 全面测试
    //  base_convert 只支持 2-36, 以下通过十进制互转自洽验证 + 交叉验证
    //  确保 64、85、128、200、256 等高进制同样可靠
    // ================================================================

    /**
     * 构建高进制字符集
     */
    private static function highBaseCharsets(): array
    {
        static $result = null;
        if ($result !== null) {
            return $result;
        }

        $dec = '0123456789';

        // base62 标准字符集
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // base64: 62 + 两个可打印字符
        $b64 = $b62 . '+/';

        // base85: 可打印 ASCII (0x21-0x75)
        $b85 = '';
        for ($i = 0x21; $i < 0x21 + 85; $i++) {
            $b85 .= chr($i);
        }

        // base128: 0x00-0x7F
        $b128 = '';
        for ($i = 0; $i < 128; $i++) {
            $b128 .= chr($i);
        }

        // base200: CJK 汉字 (U+4E00 起始)
        $b200 = '';
        for ($i = 0; $i < 200; $i++) {
            $b200 .= json_decode(sprintf('"\\u%04X"', 0x4E00 + $i));
        }

        // base256: 0x00-0xFF
        $b256 = '';
        for ($i = 0; $i < 256; $i++) {
            $b256 .= chr($i);
        }

        $result = [
            10  => $dec,
            62  => $b62,
            64  => $b64,
            85  => $b85,
            128 => $b128,
            200 => $b200,
            256 => $b256,
        ];
        return $result;
    }

    // ======================== 高进制: dec ↔ 各高进制 [0, 10000] ========================

    /**
     * 十进制 → 各高进制, 值 0-10000 往返
     */
    public function testHighBaseDecToAllHighBases()
    {
        $bases = self::highBaseCharsets();
        $dec = $bases[10];

        foreach ($bases as $size => $base) {
            if ($size === 10) {
                continue;
            }
            for ($val = 0; $val <= 10000; $val++) {
                $decStr = (string)$val;
                $encoded = Base::conv($decStr, $dec, $base);
                $decoded = Base::conv($encoded, $base, $dec);
                $this->assertSame(
                    $decStr,
                    $decoded,
                    "dec→base{$size}→dec 往返失败: val={$val}"
                );
            }
        }
    }

    // ======================== 高进制: dec ↔ 各高进制 [MAX-10000, MAX] ========================

    /**
     * 十进制 → 各高进制, 值 [PHP_INT_MAX-10000, PHP_INT_MAX] 往返
     */
    public function testHighBaseDecToAllHighBasesHighRange()
    {
        $bases = self::highBaseCharsets();
        $dec = $bases[10];
        $max = PHP_INT_MAX;

        foreach ($bases as $size => $base) {
            if ($size === 10) {
                continue;
            }
            for ($offset = 10000; $offset >= 0; $offset--) {
                $val = (string)($max - $offset);
                $encoded = Base::conv($val, $dec, $base);
                $decoded = Base::conv($encoded, $base, $dec);
                $this->assertSame(
                    $val,
                    $decoded,
                    "高值 dec→base{$size}→dec 往返失败: val={$val}"
                );
            }
        }
    }

    // ======================== 高进制: 全组合交叉互转 [0, 1000] ========================

    /**
     * 所有高进制对之间互转, 值 0-1000
     * 覆盖: 6×6=36 种进制对 × 1001 个值
     */
    public function testHighBaseCrossConversion()
    {
        $bases = self::highBaseCharsets();
        $dec = $bases[10];

        for ($val = 0; $val <= 1000; $val++) {
            $decStr = (string)$val;

            // 预编码到所有进制
            $encoded = [];
            foreach ($bases as $size => $base) {
                $encoded[$size] = Base::conv($decStr, $dec, $base);
            }

            // 任意两进制之间互转, 结果回到十进制应一致
            foreach ($bases as $sizeA => $baseA) {
                foreach ($bases as $sizeB => $baseB) {
                    $result = Base::conv($encoded[$sizeA], $baseA, $baseB);
                    $back = Base::conv($result, $baseB, $dec);
                    $this->assertSame(
                        $decStr,
                        $back,
                        "base{$sizeA}→base{$sizeB}→dec 交叉失败: val={$val}"
                    );
                }
            }
        }
    }

    // ======================== 高进制: 全组合交叉互转 [MAX-100, MAX] ========================

    public function testHighBaseCrossConversionHighRange()
    {
        $bases = self::highBaseCharsets();
        $dec = $bases[10];
        $max = PHP_INT_MAX;

        for ($offset = 100; $offset >= 0; $offset--) {
            $decStr = (string)($max - $offset);

            $encoded = [];
            foreach ($bases as $size => $base) {
                $encoded[$size] = Base::conv($decStr, $dec, $base);
            }

            foreach ($bases as $sizeA => $baseA) {
                foreach ($bases as $sizeB => $baseB) {
                    $result = Base::conv($encoded[$sizeA], $baseA, $baseB);
                    $back = Base::conv($result, $baseB, $dec);
                    $this->assertSame(
                        $decStr,
                        $back,
                        "高值 base{$sizeA}→base{$sizeB}→dec 交叉失败: val={$decStr}"
                    );
                }
            }
        }
    }

    // ======================== 高进制: 与低进制交叉 [0, 5000] ========================

    /**
     * 高进制 (64/128/256) 与低进制 (2/8/16/36/62) 之间互转
     */
    public function testHighBaseVsLowBaseCrossConversion()
    {
        $dec = '0123456789';
        $lowBases = [
            2  => '01',
            8  => '01234567',
            16 => '0123456789abcdef',
            36 => '0123456789abcdefghijklmnopqrstuvwxyz',
            62 => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
        ];

        $allHighBases = self::highBaseCharsets();
        $highBases = [];
        foreach ($allHighBases as $size => $base) {
            if ($size > 62) {
                $highBases[$size] = $base;
            }
        }

        for ($val = 0; $val <= 5000; $val++) {
            $decStr = (string)$val;

            foreach ($highBases as $hSize => $hBase) {
                $inHigh = Base::conv($decStr, $dec, $hBase);

                foreach ($lowBases as $lSize => $lBase) {
                    // 高进制 → 低进制
                    $inLow = Base::conv($inHigh, $hBase, $lBase);
                    $back = Base::conv($inLow, $lBase, $dec);
                    $this->assertSame(
                        $decStr,
                        $back,
                        "base{$hSize}→base{$lSize}→dec 失败: val={$val}"
                    );

                    // 低进制 → 高进制
                    $inLow2 = Base::conv($decStr, $dec, $lBase);
                    $inHigh2 = Base::conv($inLow2, $lBase, $hBase);
                    $back2 = Base::conv($inHigh2, $hBase, $dec);
                    $this->assertSame(
                        $decStr,
                        $back2,
                        "base{$lSize}→base{$hSize}→dec 失败: val={$val}"
                    );
                }
            }
        }
    }

    // ======================== 高进制: 边界值数学验证 ========================

    /**
     * 对每种高进制验证:
     * - base^n 编码为 "第二个字符" + n 个"第一个字符"
     * - base^n - 1 编码为 n 个"最后一个字符"
     * - 0 编码为"第一个字符"
     * - base-1 编码为"最后一个字符"
     */
    public function testHighBaseBoundaryValuesMath()
    {
        $bases = self::highBaseCharsets();
        $dec = $bases[10];

        foreach ($bases as $size => $base) {
            if ($size === 10) {
                continue;
            }

            if (strlen($base) === $size) {
                // 字节级字符集: 用 str_split
                $chars = str_split($base, 1);
            } else {
                // 多字节字符集: 用 preg_split
                $chars = preg_split('//u', $base, -1, PREG_SPLIT_NO_EMPTY);
            }

            $zeroChar = $chars[0];
            $oneChar = $chars[1];
            $lastChar = $chars[$size - 1];

            // 0 → 第一个字符
            $this->assertSame(
                $zeroChar,
                Base::conv('0', $dec, $base),
                "base{$size}: 0 应编码为第一个字符"
            );

            // 1 → 第二个字符
            $this->assertSame(
                $oneChar,
                Base::conv('1', $dec, $base),
                "base{$size}: 1 应编码为第二个字符"
            );

            // base-1 → 最后一个字符
            $this->assertSame(
                $lastChar,
                Base::conv((string)($size - 1), $dec, $base),
                "base{$size}: {$size}-1 应编码为最后一个字符"
            );

            // base^n = "1" + n个"0", base^n - 1 = n个"最后字符"
            for ($n = 1; $n <= 5; $n++) {
                $power = bcpow((string)$size, (string)$n, 0);

                $encoded = Base::conv($power, $dec, $base);
                $expected = $oneChar . str_repeat($zeroChar, $n);
                $this->assertSame(
                    $expected,
                    $encoded,
                    "base{$size}^{$n} 编码不正确"
                );

                $powerMinus1 = bcsub($power, '1', 0);
                $encoded2 = Base::conv($powerMinus1, $dec, $base);
                $expected2 = str_repeat($lastChar, $n);
                $this->assertSame(
                    $expected2,
                    $encoded2,
                    "base{$size}^{$n}-1 编码不正确"
                );

                // 往返验证
                $this->assertSame(
                    $power,
                    Base::conv($encoded, $base, $dec),
                    "base{$size}^{$n} 往返失败"
                );
                $this->assertSame(
                    $powerMinus1,
                    Base::conv($encoded2, $base, $dec),
                    "base{$size}^{$n}-1 往返失败"
                );
            }
        }
    }

    // ======================== 高进制: 单射性 0-10000 ========================

    /**
     * 每种高进制: 0-10000 编码结果互不相同
     */
    public function testHighBaseInjectivity()
    {
        $bases = self::highBaseCharsets();
        $dec = $bases[10];

        foreach ($bases as $size => $base) {
            if ($size === 10) {
                continue;
            }

            $seen = [];
            for ($i = 0; $i <= 10000; $i++) {
                $encoded = Base::conv((string)$i, $dec, $base);
                $key = bin2hex($encoded); // 用 hex 作 key, 避免二进制字符问题
                $prev = isset($seen[$key]) ? $seen[$key] : '?';
                $this->assertArrayNotHasKey(
                    $key,
                    $seen,
                    "base{$size} 单射性失败: {$i} 和 {$prev} 编码相同"
                );
                $seen[$key] = $i;
            }
        }
    }

    // ======================== 高进制: 超大数往返 ========================

    /**
     * 100-500 位十进制大数在各高进制间的往返和交叉验证
     */
    public function testHighBaseLargeNumbers()
    {
        $bases = self::highBaseCharsets();
        $dec = $bases[10];

        // 构造大数: 2^n, 10^n, n!
        $bigValues = [];
        for ($n = 100; $n <= 500; $n += 100) {
            $bigValues[] = bcpow('2', (string)$n, 0);
            $bigValues[] = bcpow('10', (string)$n, 0);
        }
        $fact = '1';
        for ($n = 1; $n <= 100; $n++) {
            $fact = bcmul($fact, (string)$n, 0);
        }
        $bigValues[] = $fact; // 100!

        foreach ($bigValues as $decVal) {
            // 各进制往返
            foreach ($bases as $size => $base) {
                if ($size === 10) {
                    continue;
                }
                $encoded = Base::conv($decVal, $dec, $base);
                $decoded = Base::conv($encoded, $base, $dec);
                $this->assertSame(
                    $decVal,
                    $decoded,
                    "base{$size} 大数往返失败: " . substr($decVal, 0, 20) . '...'
                );
            }

            // 任意两高进制之间交叉
            $encoded = [];
            foreach ($bases as $size => $base) {
                $encoded[$size] = Base::conv($decVal, $dec, $base);
            }
            foreach ($bases as $sizeA => $baseA) {
                foreach ($bases as $sizeB => $baseB) {
                    if ($sizeA === $sizeB) {
                        continue;
                    }
                    $cross = Base::conv($encoded[$sizeA], $baseA, $baseB);
                    $this->assertSame(
                        $encoded[$sizeB],
                        $cross,
                        "大数 base{$sizeA}→base{$sizeB} 交叉不一致"
                    );
                }
            }
        }
    }

    // ======================== 高进制: 链式转换 ========================

    /**
     * dec → b64 → b85 → b128 → b200 → b256 → b62 → dec
     */
    public function testHighBaseChainConversion()
    {
        $bases = self::highBaseCharsets();
        $dec = $bases[10];

        // 链式路径: 经过所有高进制
        $chain = [64, 85, 128, 200, 256, 62];

        $values = ['0', '1', '42', '255', '65535', '999999', strval(PHP_INT_MAX),
                   '18446744073709551615', bcpow('2', '128', 0)];

        foreach ($values as $decVal) {
            // 正向链
            $current = $decVal;
            $currentBase = $dec;
            foreach ($chain as $nextSize) {
                $nextBase = $bases[$nextSize];
                $current = Base::conv($current, $currentBase, $nextBase);
                $currentBase = $nextBase;
            }
            $result = Base::conv($current, $currentBase, $dec);
            $this->assertSame($decVal, $result, "正向链失败: val={$decVal}");

            // 反向链
            $current = $decVal;
            $currentBase = $dec;
            foreach (array_reverse($chain) as $nextSize) {
                $nextBase = $bases[$nextSize];
                $current = Base::conv($current, $currentBase, $nextBase);
                $currentBase = $nextBase;
            }
            $result = Base::conv($current, $currentBase, $dec);
            $this->assertSame($decVal, $result, "反向链失败: val={$decVal}");
        }
    }

    // ======================== 高进制: base256 UTF-8 碰撞扩展 (三字节+四字节) ========================

    /**
     * base256 编码结果恰好构成合法三字节/四字节 UTF-8 的区间
     * 三字节 UTF-8: E0-EF + 80-BF + 80-BF
     * 确保这些值也能正确往返
     */
    public function testHighBaseBase256ThreeByteUtf8Collision()
    {
        $dec = '0123456789';
        $b256 = '';
        for ($i = 0; $i < 256; $i++) {
            $b256 .= chr($i);
        }

        // 三字节 UTF-8 起始区间:
        // E0 80 80 = 224*65536 + 128*256 + 128 = 14713856 (作为三字节 base256)
        // 但实际上不是所有 E0 xx xx 都是合法 UTF-8 (E0 要求第二字节 >= A0)
        // 合法三字节: E0 A0-BF, E1-EC 80-BF, ED 80-9F, EE-EF 80-BF
        // 我们选取一些确定的三字节合法 UTF-8 值来测试

        // E4 B8 80 = "一" (U+4E00), 十进制 = 0xE4*65536 + 0xB8*256 + 0x80
        $val = (string)(0xE4 * 65536 + 0xB8 * 256 + 0x80);
        $encoded = Base::conv($val, $dec, $b256);
        $decoded = Base::conv($encoded, $b256, $dec);
        $this->assertSame($val, $decoded, "base256 三字节UTF-8(一)往返");

        // E2 9C 93 = "✓" (U+2713)
        $val2 = (string)(0xE2 * 65536 + 0x9C * 256 + 0x93);
        $encoded2 = Base::conv($val2, $dec, $b256);
        $decoded2 = Base::conv($encoded2, $b256, $dec);
        $this->assertSame($val2, $decoded2, "base256 三字节UTF-8(✓)往返");

        // 扫描一段三字节合法 UTF-8 区间: E1 80 80 到 E1 80 BF (64个值)
        for ($b3 = 0x80; $b3 <= 0xBF; $b3++) {
            $val = (string)(0xE1 * 65536 + 0x80 * 256 + $b3);
            $encoded = Base::conv($val, $dec, $b256);
            $decoded = Base::conv($encoded, $b256, $dec);
            $this->assertSame($val, $decoded, "base256 三字节UTF-8碰撞: val={$val}");
        }

        // 四字节 UTF-8: F0 90 80 80 = "𐀀" (U+10000)
        // 十进制 = 0xF0*16777216 + 0x90*65536 + 0x80*256 + 0x80
        $val4 = (string)(0xF0 * 16777216 + 0x90 * 65536 + 0x80 * 256 + 0x80);
        $encoded4 = Base::conv($val4, $dec, $b256);
        $decoded4 = Base::conv($encoded4, $b256, $dec);
        $this->assertSame($val4, $decoded4, "base256 四字节UTF-8(𐀀)往返");

        // F0 9F 98 80 = "😀" (U+1F600)
        $val5 = (string)(0xF0 * 16777216 + 0x9F * 65536 + 0x98 * 256 + 0x80);
        $encoded5 = Base::conv($val5, $dec, $b256);
        $decoded5 = Base::conv($encoded5, $b256, $dec);
        $this->assertSame($val5, $decoded5, "base256 四字节UTF-8(😀)往返");

        // 扫描四字节 UTF-8 区间: F0 90 80 80 到 F0 90 80 BF (64个值)
        for ($b4 = 0x80; $b4 <= 0xBF; $b4++) {
            $val = (string)(0xF0 * 16777216 + 0x90 * 65536 + 0x80 * 256 + $b4);
            $encoded = Base::conv($val, $dec, $b256);
            $decoded = Base::conv($encoded, $b256, $dec);
            $this->assertSame($val, $decoded, "base256 四字节UTF-8碰撞: val={$val}");
        }
    }

    // ======================== 高进制: 编码长度压缩比验证 ========================

    /**
     * 进制越高, 编码字符数越少 (信息密度越高)
     */
    public function testHighBaseCompressionRatio()
    {
        $bases = self::highBaseCharsets();
        $dec = $bases[10];

        $bigValues = [
            str_repeat('9', 50),
            str_repeat('9', 100),
            bcpow('2', '256', 0),
        ];

        // 需要一个统一的"字符长度"度量
        // 对单字节字符集用 strlen, 对多字节用 preg_match_all
        $charLen = function ($str, $size, $base) {
            if (strlen($base) === $size) {
                return strlen($str);
            }
            return preg_match_all('/./u', $str);
        };

        foreach ($bigValues as $decVal) {
            $lengths = [];
            foreach ($bases as $size => $base) {
                if ($size === 10) {
                    continue;
                }
                $encoded = Base::conv($decVal, $dec, $base);
                $lengths[$size] = $charLen($encoded, $size, $base);
            }

            // 进制越高 → 字符数越少 (或相等)
            $sizes = array_keys($lengths);
            for ($i = 0; $i < count($sizes) - 1; $i++) {
                for ($j = $i + 1; $j < count($sizes); $j++) {
                    if ($sizes[$i] < $sizes[$j]) {
                        $this->assertGreaterThanOrEqual(
                            $lengths[$sizes[$j]],
                            $lengths[$sizes[$i]],
                            "base{$sizes[$i]} 编码应 ≥ base{$sizes[$j]} 长度: val=" . substr($decVal, 0, 20) . '...'
                        );
                    }
                }
            }
        }
    }

    // ======================== 高进制: 同进制转换 ========================

    public function testHighBaseSameBaseConversion()
    {
        $bases = self::highBaseCharsets();
        $dec = $bases[10];

        foreach ($bases as $size => $base) {
            if ($size === 10) {
                continue;
            }
            for ($val = 0; $val <= 100; $val++) {
                $encoded = Base::conv((string)$val, $dec, $base);
                // 同进制转换应该返回原值
                $same = Base::conv($encoded, $base, $base);
                $this->assertSame(
                    $encoded,
                    $same,
                    "base{$size} 同进制转换不一致: val={$val}"
                );
            }
        }
    }

    // ======================== 高进制: 与 base_convert 桥接验证 ========================

    /**
     * 通过十进制作为桥梁, 验证高进制与 base_convert 可达进制(2-36)的一致性:
     * base_convert(val, 10, 16) 应等于 Base::conv(val, dec, hex)
     * 然后 hex → 高进制 → hex 往返, 与 base_convert 输出一致
     */
    public function testHighBaseBridgeWithBaseConvert()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';
        $allBases = self::highBaseCharsets();
        $highBases = [];
        foreach ($allBases as $size => $base) {
            if ($size > 62) {
                $highBases[$size] = $base;
            }
        }

        $values = [];
        // 0-1000
        for ($i = 0; $i <= 1000; $i++) {
            $values[] = (string)$i;
        }
        // 高值区间
        $max = PHP_INT_MAX;
        for ($offset = 100; $offset >= 0; $offset--) {
            $values[] = (string)($max - $offset);
        }

        foreach ($values as $decVal) {
            $expectedHex = base_convert($decVal, 10, 16);
            $expectedB36 = base_convert($decVal, 10, 36);

            foreach ($highBases as $hSize => $hBase) {
                // dec → 高进制 → hex, 应等于 base_convert 结果
                $inHigh = Base::conv($decVal, $dec, $hBase);
                $backHex = Base::conv($inHigh, $hBase, $hex);
                $this->assertSame(
                    $expectedHex,
                    $backHex,
                    "base{$hSize}桥接hex不一致: val={$decVal}"
                );

                // dec → 高进制 → b36, 应等于 base_convert 结果
                $backB36 = Base::conv($inHigh, $hBase, $b36);
                $this->assertSame(
                    $expectedB36,
                    $backB36,
                    "base{$hSize}桥接b36不一致: val={$decVal}"
                );
            }
        }
    }

    // ======================== 高进制: 前导零处理 ========================

    public function testHighBaseLeadingZeros()
    {
        $bases = self::highBaseCharsets();
        $dec = $bases[10];

        // 前导零的十进制输入, 转到各高进制再转回, 应去掉前导零
        $cases = ['00', '000', '00123', '00001', '0000000100'];

        foreach ($bases as $size => $base) {
            if ($size === 10) {
                continue;
            }
            foreach ($cases as $input) {
                $encoded = Base::conv($input, $dec, $base);
                $decoded = Base::conv($encoded, $base, $dec);
                $expected = ltrim($input, '0') ?: '0';
                $this->assertSame(
                    $expected,
                    $decoded,
                    "base{$size} 前导零处理: input='{$input}'"
                );
            }
        }
    }

    public function testConvNegativeNumbers()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $bin = '01';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $this->assertSame('-ff', Base::conv('-255', $dec, $hex));
        $this->assertSame('-255', Base::conv('-ff', $hex, $dec));
        $this->assertSame('-a', Base::conv('-1010', $bin, $hex));
        $this->assertSame('-1010', Base::conv('-a', $hex, $bin));
        $this->assertSame('-47', Base::conv('-255', $dec, $b62));
        $this->assertSame('0', Base::conv('-0', $dec, $hex));
    }

    public function testConvBaseContainingSignCharacter()
    {
        $dec = '0123456789';
        $base3 = '-01';

        // 回归: 字符集包含 '-' 时, '-' 应按普通数字字符处理而不是负号
        $this->assertSame('0', Base::conv('-', $base3, $dec));
        $this->assertSame('1', Base::conv('0', $base3, $dec));
        $this->assertSame('-', Base::conv('0', $dec, $base3));
    }

    public function testConvInvalidSourceDigitThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        Base::conv('102', '01', '0123456789');
    }

    public function testConvInvalidSourceBaseLengthThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        Base::conv('000', '0', '0123456789');
    }

    public function testConvInvalidTargetBaseLengthThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        Base::conv('10', '0123456789', '0');
    }

    public function testConvDuplicateBaseCharsetThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        Base::conv('10', '0123456789', '0012');
    }

    public function testConvEmptyAndSignOnlyInputThrowsException()
    {
        try {
            Base::conv('', '0123456789', '01');
            $this->fail('Expected InvalidArgumentException for empty input.');
        } catch (InvalidArgumentException $e) {
            $this->assertNotFalse(strpos($e->getMessage(), 'empty'));
        }

        $this->expectException(InvalidArgumentException::class);
        Base::conv('-', '0123456789', '01');
    }

    public function testConvPositiveSignNumbers()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $bin = '01';

        $this->assertSame('ff', Base::conv('+255', $dec, $hex));
        $this->assertSame('255', Base::conv('+ff', $hex, $dec));
        $this->assertSame('10', Base::conv('+1010', $bin, $dec));
        $this->assertSame('1010', Base::conv('+10', $dec, $bin));
    }

    public function testConvSignedRoundTrip()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $values = ['-1', '-2', '-10', '-255', '-65535', '-18446744073709551615'];
        foreach ($values as $value) {
            $toHex = Base::conv($value, $dec, $hex);
            $this->assertSame($value, Base::conv($toHex, $hex, $dec), "signed hex round-trip: {$value}");

            $to62 = Base::conv($value, $dec, $b62);
            $this->assertSame($value, Base::conv($to62, $b62, $dec), "signed b62 round-trip: {$value}");
        }
    }

    public function testConvZeroSignNormalization()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';

        $this->assertSame('0', Base::conv('+0', $dec, $hex));
        $this->assertSame('0', Base::conv('-0', $dec, $hex));
        $this->assertSame('0', Base::conv('+0000', $dec, $hex));
        $this->assertSame('0', Base::conv('-0000', $dec, $hex));
        $this->assertSame('+0000', Base::conv('+0000', $dec, $dec));
    }

    public function testConvNonDecimalNegativeZeroNormalization()
    {
        $hex = '0123456789abcdef';
        $dec = '0123456789';
        $bin = '01';

        $this->assertSame('0', Base::conv('-0000', $hex, $dec));
        $this->assertSame('0', Base::conv('-0000', $hex, $bin));
    }

    public function testConvBaseContainingPlusCharacter()
    {
        $dec = '0123456789';
        $base3 = '+01';

        // 回归: 字符集包含 '+' 时, '+' 应按普通数字字符处理而不是正号
        $this->assertSame('0', Base::conv('+', $base3, $dec));
        $this->assertSame('1', Base::conv('+0', $base3, $dec));
        $this->assertSame('+', Base::conv('0', $dec, $base3));
    }

    public function testConvMultibyteNegativeNumberRoundTrip()
    {
        $bases = self::highBaseCharsets();
        $dec = $bases[10];
        $b200 = $bases[200];

        $encoded = Base::conv('-12345678901234567890', $dec, $b200);
        $this->assertSame('-', substr($encoded, 0, 1));
        $this->assertSame('-12345678901234567890', Base::conv($encoded, $b200, $dec));
    }

    public function testConvInvalidMultibyteSourceDigitThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        Base::conv('甲丁', '甲乙丙', '0123456789');
    }

    public function testConvDuplicateMultibyteBaseThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        Base::conv('10', '0123456789', '甲乙甲');
    }

    public function testConvBooleanInputBoundary()
    {
        $dec = '0123456789';
        $hex = '0123456789abcdef';

        // true 会转为字符串 "1"
        $this->assertSame('1', Base::conv(true, $dec, $hex));

        // false 会转为空字符串, 应抛异常
        $this->expectException(InvalidArgumentException::class);
        Base::conv(false, $dec, $hex);
    }

    public function testConvSignedInvalidDigitThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        Base::conv('-g', '0123456789abcdef', '0123456789');
    }

    public function testTo36AndTo62WithSignedInput()
    {
        $this->assertSame('-z', Base::to36('-35'));
        $this->assertSame('-10', Base::to36('-36'));
        $this->assertSame('10', Base::to36('+36'));

        $this->assertSame('-z', Base::to62('-61'));
        $this->assertSame('-10', Base::to62('-62'));
        $this->assertSame('10', Base::to62('+62'));
    }

    public function testTo36InvalidInputThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        Base::to36('1e3');
    }

    public function testTo62InvalidInputThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        Base::to62('12.34');
    }

    public function testConvInvalidDecimalFormatsThrowException()
    {
        foreach (self::invalidDecimalNumberProvider() as $case) {
            $input = $case[0];
            try {
                Base::conv($input, '0123456789', '0123456789abcdef');
                $this->fail("Expected InvalidArgumentException for invalid decimal input: '{$input}'");
            } catch (InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function testConvFallsBackToBcmathWhenGmpIsDisabled()
    {
        if (!function_exists('bcadd')) {
            $this->markTestSkipped('ext-bcmath not available.');
        }

        $class = get_class(new class extends Base {
            protected static function hasGmp(): bool
            {
                return false;
            }
        });

        $dec = '0123456789';
        $hex = '0123456789abcdef';

        $this->assertSame('ff', $class::conv('255', $dec, $hex));
        $this->assertSame('255', $class::conv('ff', $hex, $dec));
    }

    public function testConvThrowsRuntimeExceptionWhenNoMathExtensionIsAvailable()
    {
        $class = get_class(new class extends Base {
            protected static function hasGmp(): bool
            {
                return false;
            }

            protected static function hasBcMath(): bool
            {
                return false;
            }
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ext-gmp or ext-bcmath');
        $class::conv('255', '0123456789', '0123456789abcdef');
    }

    public function testConvThrowsRuntimeExceptionWhenNoMathExtensionIsAvailableForSourceParsing()
    {
        $class = get_class(new class extends Base {
            protected static function hasGmp(): bool
            {
                return false;
            }

            protected static function hasBcMath(): bool
            {
                return false;
            }
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ext-gmp or ext-bcmath');
        $class::conv('ff', '0123456789abcdef', '0123456789');
    }

    public function testConvSameBaseDoesNotRequireMathExtension()
    {
        $class = get_class(new class extends Base {
            protected static function hasGmp(): bool
            {
                return false;
            }

            protected static function hasBcMath(): bool
            {
                return false;
            }
        });

        $dec = '0123456789';
        $this->assertSame('+000123', $class::conv('+000123', $dec, $dec));
    }

    public function testIsIntegerStringEdgeFormats()
    {
        $this->assertFalse(Base::isInteger('1e3'));
        $this->assertFalse(Base::isInteger('+123'));
        $this->assertTrue(Base::isInteger('000123'));
    }

    public function testDigitalToStringAlias()
    {
        $this->assertSame('123', Base::digitalToString(123));
        $this->assertSame('-456', Base::digitalToString('-456'));
    }

    public function testToStringWithPadTruncateBehaviorForLongInput()
    {
        $this->assertSame('123', Base::toStringWithPad('123456', 3));
    }

    public function testToStringCarbonGetPreciseTimestamp()
    {
        // 用同一个 Carbon 实例的 format('U').format('u') 拼出字符串基准值
        // 完全绕开 float, 作为无精度丢失的 ground truth
        $carbon = Carbon::now();
        $expected = $carbon->format('U') . $carbon->format('u');
        $actual = Base::toString($carbon->getPreciseTimestamp(6));
        $this->assertSame($expected, $actual, 'toString应与Carbon format(U).format(u)完全一致');

        // 多次采样验证一致性
        for ($i = 0; $i < 10; $i++) {
            $c = Carbon::now();
            $expected = $c->format('U') . $c->format('u');
            $actual = Base::toString($c->getPreciseTimestamp(6));
            $this->assertSame($expected, $actual);
            usleep(1000);
        }

        // 不含科学计数法
        $str = Base::toString(Carbon::now()->getPreciseTimestamp(6));
        $this->assertSame(16, strlen($str));
        $this->assertFalse(strpos($str, 'E') !== false);
        $this->assertFalse(strpos($str, 'e') !== false);

        // toString 结果可以正确往返 conv
        $b62 = Base::to62($str);
        $back = Base::conv($b62, '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', '0123456789');
        $this->assertSame($str, $back);

        // 小值 float 不丢失
        $this->assertSame('1', Base::toString(1.0));
        $this->assertSame('0', Base::toString(0.0));
        $this->assertSame('1000000', Base::toString(1000000.0));
    }

    public function testToStringCarbonFixedTimestamps()
    {
        // 用固定时间点构造 Carbon, 验证 toString 与 format 基准值完全一致
        // 仅选择 2001 年之后的时间, 确保秒部分始终为 10 位, 与 getPreciseTimestamp 的数字值对齐
        $timestamps = [
            '2001-09-09 01:46:40.000000',
            '2001-09-09 01:46:40.000001',
            '2001-09-09 01:46:40.100000',
            '2001-09-09 01:46:40.999999',
            '2010-03-15 08:20:30.654321',
            '2020-01-01 00:00:00.000000',
            '2025-06-15 12:30:45.123456',
            '2030-12-31 23:59:59.999999',
            '2038-01-19 03:14:07.999999',
            '2050-01-01 00:00:00.500000',
        ];

        foreach ($timestamps as $ts) {
            $c = Carbon::parse($ts, 'UTC');
            $expected = $c->format('U') . $c->format('u');
            $actual = Base::toString($c->getPreciseTimestamp(6));
            $this->assertSame($expected, $actual, "固定时间 {$ts} toString与format基准值不一致");
        }
    }

    public function testToStringCarbonPreciseTimestampMs()
    {
        // 毫秒精度: Carbon 内部使用 round(), format 截取前3位微秒
        // 用同一个 Carbon 实例, 手动 round 模拟 Carbon 行为来验证
        for ($i = 0; $i < 10; $i++) {
            $c = Carbon::now();
            $float = $c->getPreciseTimestamp(3);
            $str = Base::toString($float);

            // toString 结果必须是纯数字
            $this->assertSame(1, preg_match('/^\d+$/', $str), '毫秒时间戳应为纯数字');
            $this->assertSame(13, strlen($str), '毫秒时间戳应为13位');

            // float 值与 toString 结果互转一致
            $this->assertSame(sprintf('%.0f', $float), $str);

            usleep(1000);
        }
    }

    public function testToStringCarbonPreciseTimestampSeconds()
    {
        // 秒级精度: 用固定 Carbon 实例避免跨秒边界
        $timestamps = [
            '2025-01-01 00:00:00.000000',
            '2025-06-15 12:30:45.000000',
            '2030-12-31 23:59:59.000000',
            '2038-01-19 03:14:07.000000',
        ];

        foreach ($timestamps as $ts) {
            $c = Carbon::parse($ts, 'UTC');
            $expected = $c->format('U');
            $actual = Base::toString($c->getPreciseTimestamp(0));
            $this->assertSame($expected, $actual, "秒级时间戳 {$ts} 应与format(U)一致");
            $this->assertSame(10, strlen($actual));
        }
    }

    public function testToStringCarbonConvRoundTrip()
    {
        // Carbon 微秒时间戳 -> toString -> to62 -> 还原, 验证全链路无损
        $dec = '0123456789';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        for ($i = 0; $i < 10; $i++) {
            $c = Carbon::now();
            $expected = $c->format('U') . $c->format('u');
            $str = Base::toString($c->getPreciseTimestamp(6));
            $this->assertSame($expected, $str);

            // to36 往返
            $encoded36 = Base::conv($str, $dec, $b36);
            $decoded36 = Base::conv($encoded36, $b36, $dec);
            $this->assertSame($str, $decoded36, 'to36往返应无损');

            // to62 往返
            $encoded62 = Base::to62($str);
            $decoded62 = Base::conv($encoded62, $b62, $dec);
            $this->assertSame($str, $decoded62, 'to62往返应无损');

            usleep(1000);
        }
    }

    public function testToStringFloatNoScientificNotation()
    {
        // 所有整数 float 经 toString 后不应包含科学计数法
        $cases = [
            1e6,
            1e10,
            1e12,
            1e15,
            123456789012345.0,
            999999999999999.0,
            1000000000000000.0,
            9007199254740992.0,  // 2^53, float能精确表示的最大整数
        ];

        foreach ($cases as $float) {
            $str = Base::toString($float);
            $this->assertFalse(strpos($str, 'E') !== false, "toString({$float})不应包含E");
            $this->assertFalse(strpos($str, 'e') !== false, "toString({$float})不应包含e");
            $this->assertSame(1, preg_match('/^-?\d+$/', $str), "toString({$float})应为纯数字");
            // 转回 float 值不变
            $this->assertTrue($float == (float)$str, "toString({$float})转回float应一致");
        }
    }

    public function testToStringFloatIntegerBoundary()
    {
        // 2^53 是 float 能精确表示整数的边界
        $pow253 = 9007199254740992.0;
        $this->assertSame('9007199254740992', Base::toString($pow253));
        $this->assertSame('-9007199254740992', Base::toString(-$pow253));

        // 2^53 - 1
        $this->assertSame('9007199254740991', Base::toString(9007199254740991.0));

        // 常见整数 float
        $this->assertSame('0', Base::toString(0.0));
        $this->assertSame('1', Base::toString(1.0));
        $this->assertSame('-1', Base::toString(-1.0));
        $this->assertSame('100', Base::toString(100.0));
        $this->assertSame('999999', Base::toString(999999.0));
        $this->assertSame('1000000000', Base::toString(1e9));
    }

    public function testToStringFloatNonInteger()
    {
        // 非整数 float 走 strval 分支, 保留小数
        $this->assertSame('1.5', Base::toString(1.5));
        $this->assertSame('0.1', Base::toString(0.1));
        $this->assertSame('-3.14', Base::toString(-3.14));
        $this->assertSame('INF', Base::toString(INF));
        $this->assertSame('-INF', Base::toString(-INF));
        $this->assertSame('NAN', @Base::toString(NAN));
    }

    public function testToStringCarbonCreateFromTimestampMs()
    {
        // 用 createFromTimestampMs 构造特定毫秒时间戳, 验证 toString
        $knownMs = [
            1000000000000,  // 2001-09-09
            1234567890123,
            1700000000000,
            1750000000000,
            1800000000000,
        ];

        foreach ($knownMs as $ms) {
            $c = Carbon::createFromTimestampMs($ms, 'UTC');
            $float = $c->getPreciseTimestamp(3);
            $str = Base::toString($float);

            $this->assertSame(13, strlen($str), "毫秒时间戳 {$ms} 应为13位");
            $this->assertSame(1, preg_match('/^\d{13}$/', $str), "毫秒时间戳 {$ms} 应为纯数字");
            // 回转验证
            $this->assertSame(sprintf('%.0f', $float), $str);
        }
    }

    public function testToStringCarbonEpochBoundary()
    {
        // Unix epoch: getPreciseTimestamp(6) = 0.0, toString = '0'
        $c = Carbon::createFromTimestamp(0, 'UTC');
        $this->assertSame('0', Base::toString($c->getPreciseTimestamp(0)));
        $this->assertSame('0', Base::toString($c->getPreciseTimestamp(6)));

        // timestamp = 1: getPreciseTimestamp(6) = 1000000.0
        $c1 = Carbon::createFromTimestamp(1, 'UTC');
        $this->assertSame('1', Base::toString($c1->getPreciseTimestamp(0)));
        $this->assertSame('1000', Base::toString($c1->getPreciseTimestamp(3)));
        $this->assertSame('1000000', Base::toString($c1->getPreciseTimestamp(6)));

        // 2001-09-09 01:46:40 UTC = timestamp 1000000000, 10位秒级时间戳的起点
        // 从此刻开始 format('U').format('u') 与 toString(getPreciseTimestamp(6)) 字面一致
        $c2 = Carbon::parse('2001-09-09 01:46:40.123456', 'UTC');
        $expected = $c2->format('U') . $c2->format('u');
        $actual = Base::toString($c2->getPreciseTimestamp(6));
        $this->assertSame($expected, $actual);
        $this->assertSame(16, strlen($actual));
    }

    public function testToStringFloatScientificNotationBoundary()
    {
        // strval 会在15位以上有效数字时输出科学计数法
        // toString 必须在所有 case 下输出纯数字
        $cases = [
            // [float输入, 期望的纯数字字符串]
            [1e14, '100000000000000'],
            [1e15, '1000000000000000'],
            [1e16, '10000000000000000'],
            [1e17, '100000000000000000'],
            [1e18, '1000000000000000000'],
            [1770644663751071.0, '1770644663751071'],
            [1234567890123456.0, '1234567890123456'],
            [9999999999999998.0, '9999999999999998'],
            [1000000000000001.0, '1000000000000001'],
        ];

        foreach ($cases as $pair) {
            $float = $pair[0];
            $expected = $pair[1];
            $str = Base::toString($float);

            // 不能包含科学计数法
            $this->assertFalse(strpos($str, 'E') !== false, "toString({$expected})不应包含E, got: {$str}");
            $this->assertFalse(strpos($str, 'e') !== false, "toString({$expected})不应包含e, got: {$str}");
            // 值必须正确
            $this->assertSame($expected, $str, "toString float {$expected}");
        }
    }

    public function testToStringCarbonTimestampArithmetic()
    {
        // 业务中常见: 对 getPreciseTimestamp 结果做加减运算后再 toString
        $c = Carbon::parse('2025-06-15 12:30:45.123456', 'UTC');
        $ts = $c->getPreciseTimestamp(6);

        // +1 微秒
        $plus1 = Base::toString($ts + 1);
        $this->assertSame(1, preg_match('/^\d{16}$/', $plus1), '+1微秒应为16位纯数字');

        // -1 微秒
        $minus1 = Base::toString($ts - 1);
        $this->assertSame(1, preg_match('/^\d{16}$/', $minus1), '-1微秒应为16位纯数字');

        // 单调性: -1 < 原值 < +1
        $this->assertTrue($minus1 < Base::toString($ts));
        $this->assertTrue(Base::toString($ts) < $plus1);

        // 两个时间戳的差值
        $c2 = Carbon::parse('2025-06-15 12:30:46.123456', 'UTC');
        $diff = $c2->getPreciseTimestamp(6) - $ts;
        $diffStr = Base::toString($diff);
        $this->assertSame('1000000', $diffStr, '相差1秒=1000000微秒');

        // +1000 毫秒
        $plus1000ms = Base::toString($ts + 1000000);
        $expected1s = $c2->format('U') . $c2->format('u');
        $this->assertSame($expected1s, $plus1000ms);
    }

    public function testToStringCarbonJsonRoundTrip()
    {
        // 业务场景: 时间戳经过 JSON 序列化/反序列化后再 toString
        $c = Carbon::parse('2025-06-15 12:30:45.123456', 'UTC');
        $expected = $c->format('U') . $c->format('u');
        $ts = $c->getPreciseTimestamp(6);

        // json_encode float -> json_decode 可能返回 int 或 float
        $decoded = json_decode(json_encode($ts));
        $str = Base::toString($decoded);
        $this->assertSame($expected, $str, 'JSON round-trip后toString应与format基准一致');

        // json_encode 包在对象里
        $json = json_encode(['ts' => $ts]);
        $obj = json_decode($json, true);
        $str2 = Base::toString($obj['ts']);
        $this->assertSame($expected, $str2, 'JSON对象round-trip后toString应与format基准一致');
    }

    public function testToStringCarbonTypeCasting()
    {
        // 业务场景: 时间戳被意外类型转换后再 toString
        $c = Carbon::parse('2025-06-15 12:30:45.000000', 'UTC');
        $expected = $c->format('U') . $c->format('u');
        $ts = $c->getPreciseTimestamp(6);

        // (int) 强转: 当前时间戳在 PHP_INT_MAX 范围内, 不会溢出
        $this->assertTrue($ts < PHP_INT_MAX);
        $intTs = (int)$ts;
        $this->assertSame($expected, Base::toString($intTs), '(int)强转后toString应一致');

        // (string) 强转后再传入
        $strTs = (string)(int)$ts;
        $this->assertSame($expected, Base::toString($strTs), '(string)(int)强转后toString应一致');

        // 从数据库取出的字符串形式
        $dbValue = $expected;
        $this->assertSame($expected, Base::toString($dbValue), '字符串形式直接传入应原样返回');
    }

    public function testToStringCarbonStringComparison()
    {
        // 关键业务场景: toString 结果用于字符串比较排序
        // 同一秒内的微秒时间戳, 字符串比较顺序必须等于数值顺序
        $c1 = Carbon::parse('2025-06-15 12:30:45.000001', 'UTC');
        $c2 = Carbon::parse('2025-06-15 12:30:45.000002', 'UTC');
        $c3 = Carbon::parse('2025-06-15 12:30:45.999999', 'UTC');
        $c4 = Carbon::parse('2025-06-15 12:30:46.000000', 'UTC');

        $s1 = Base::toString($c1->getPreciseTimestamp(6));
        $s2 = Base::toString($c2->getPreciseTimestamp(6));
        $s3 = Base::toString($c3->getPreciseTimestamp(6));
        $s4 = Base::toString($c4->getPreciseTimestamp(6));

        // 字符串比较顺序 = 时间顺序
        $this->assertTrue($s1 < $s2, 's1 < s2');
        $this->assertTrue($s2 < $s3, 's2 < s3');
        $this->assertTrue($s3 < $s4, 's3 < s4');

        // 长度一致, 可以安全做字符串排序
        $this->assertSame(strlen($s1), strlen($s2));
        $this->assertSame(strlen($s2), strlen($s3));
        $this->assertSame(strlen($s3), strlen($s4));
    }

    public function testToStringCarbonMicrosecondBoundaries()
    {
        // 微秒的每个边界值: .000000, .000001, .500000, .999998, .999999
        $boundaries = [
            ['2025-01-01 00:00:00.000000', '000000'],
            ['2025-01-01 00:00:00.000001', '000001'],
            ['2025-01-01 00:00:00.000010', '000010'],
            ['2025-01-01 00:00:00.000100', '000100'],
            ['2025-01-01 00:00:00.001000', '001000'],
            ['2025-01-01 00:00:00.010000', '010000'],
            ['2025-01-01 00:00:00.100000', '100000'],
            ['2025-01-01 00:00:00.500000', '500000'],
            ['2025-01-01 00:00:00.999998', '999998'],
            ['2025-01-01 00:00:00.999999', '999999'],
        ];

        foreach ($boundaries as $pair) {
            $ts = $pair[0];
            $expectUs = $pair[1];
            $c = Carbon::parse($ts, 'UTC');
            $expected = $c->format('U') . $expectUs;
            $actual = Base::toString($c->getPreciseTimestamp(6));
            $this->assertSame($expected, $actual, "微秒边界 {$ts} 不一致");
        }
    }

    public function testToStringCarbonYear2038()
    {
        // 2038年问题: 32位系统 Unix 时间戳溢出边界
        $cases = [
            '2038-01-19 03:14:06.999999',  // 溢出前1秒
            '2038-01-19 03:14:07.000000',  // 32位最大值 2147483647
            '2038-01-19 03:14:07.999999',  // 32位最大值的最后一微秒
            '2038-01-19 03:14:08.000000',  // 溢出后
            '2050-01-01 00:00:00.123456',  // 远未来
            '2100-12-31 23:59:59.999999',  // 更远未来
        ];

        foreach ($cases as $ts) {
            $c = Carbon::parse($ts, 'UTC');
            $expected = $c->format('U') . $c->format('u');
            $actual = Base::toString($c->getPreciseTimestamp(6));
            $this->assertSame($expected, $actual, "2038边界 {$ts} 不一致");
            $this->assertSame(16, strlen($actual), "2038边界 {$ts} 应为16位");
        }
    }

    public function testToStringCarbonDayBoundary()
    {
        // 日期跨天边界: 23:59:59.999999 -> 00:00:00.000000
        $c1 = Carbon::parse('2025-06-15 23:59:59.999999', 'UTC');
        $c2 = Carbon::parse('2025-06-16 00:00:00.000000', 'UTC');

        $s1 = Base::toString($c1->getPreciseTimestamp(6));
        $s2 = Base::toString($c2->getPreciseTimestamp(6));

        $expected1 = $c1->format('U') . $c1->format('u');
        $expected2 = $c2->format('U') . $c2->format('u');
        $this->assertSame($expected1, $s1);
        $this->assertSame($expected2, $s2);

        // 差值恰好为1微秒
        $diff = bcsub($s2, $s1, 0);
        $this->assertSame('1', $diff, '跨天边界差值应为1微秒');
    }

    public function testToStringCarbonLeapSecond()
    {
        // 闰年2月29日
        $c = Carbon::parse('2024-02-29 12:00:00.123456', 'UTC');
        $expected = $c->format('U') . $c->format('u');
        $actual = Base::toString($c->getPreciseTimestamp(6));
        $this->assertSame($expected, $actual);

        // 年末
        $c2 = Carbon::parse('2024-12-31 23:59:59.999999', 'UTC');
        $expected2 = $c2->format('U') . $c2->format('u');
        $actual2 = Base::toString($c2->getPreciseTimestamp(6));
        $this->assertSame($expected2, $actual2);
    }

    public function testToStringCarbonConvMultiBaseRoundTrip()
    {
        // 微秒时间戳在多种进制间互转, 全部无损
        $c = Carbon::parse('2025-06-15 12:30:45.123456', 'UTC');
        $expected = $c->format('U') . $c->format('u');
        $str = Base::toString($c->getPreciseTimestamp(6));
        $this->assertSame($expected, $str);

        $dec = '0123456789';
        $bin = '01';
        $oct = '01234567';
        $hex = '0123456789abcdef';
        $b36 = '0123456789abcdefghijklmnopqrstuvwxyz';
        $b62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $bases = [$bin, $oct, $hex, $b36, $b62];
        foreach ($bases as $base) {
            $encoded = Base::conv($str, $dec, $base);
            $decoded = Base::conv($encoded, $base, $dec);
            $this->assertSame($str, $decoded, '进制转换往返应无损, base长度=' . strlen($base));
        }
    }

    public static function invalidDecimalNumberProvider(): array
    {
        return [
            [''],
            [' '],
            ['1e3'],
            ['12.0'],
            ['12.34'],
            [' 12'],
            ['12 '],
            ['+'],
            ['++1'],
            ['--1'],
            ['+-1'],
            ['0x10'],
            ['1_000'],
            ['1,000'],
            ['NaN'],
            ['INF'],
        ];
    }

}
