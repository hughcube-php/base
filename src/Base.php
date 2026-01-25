<?php

namespace HughCube\Base;

use Throwable;

/**
 * @see https://www.php.net/manual/zh/function.base-convert.php
 */
class Base
{
    /**
     * @param int|string $numberInput
     * @param string $fromBaseInput
     * @param string $toBaseInput
     *
     * @return null|string
     */
    public static function conv($numberInput, string $fromBaseInput, string $toBaseInput)
    {
        if (null === $numberInput) {
            return null;
        }

        if ($fromBaseInput == $toBaseInput) {
            return static::toString($numberInput);
        }

        $numberInput = static::toString($numberInput);

        $fromBase = str_split($fromBaseInput, 1);
        $toBase = str_split($toBaseInput, 1);
        $number = str_split($numberInput, 1);
        $fromLen = strlen($fromBaseInput);
        $toLen = strlen($toBaseInput);
        $numberLen = strlen($numberInput);
        $retval = '';
        if ($toBaseInput == '0123456789') {
            $retval = 0;
            for ($i = 1; $i <= $numberLen; $i++) {
                $retval = bcadd(
                    $retval,
                    bcmul(
                        array_search($number[$i - 1], $fromBase),
                        bcpow($fromLen, $numberLen - $i, 0),
                        0
                    ),
                    0
                );
            }

            return $retval;
        }
        if ($fromBaseInput != '0123456789') {
            $base10 = static::conv($numberInput, $fromBaseInput, '0123456789');
        } else {
            $base10 = $numberInput;
        }
        if ($base10 < strlen($toBaseInput)) {
            return $toBase[$base10];
        }
        while ($base10 != '0') {
            $retval = $toBase[bcmod($base10, $toLen, 0)] . $retval;
            $base10 = bcdiv($base10, $toLen, 0);
        }

        return $retval;
    }

    /**
     * @param int|string $digital
     *
     * @return string
     */
    public static function toString($digital): string
    {
        if (is_string($digital)) {
            return $digital;
        }

        if (!function_exists('gmp_strval') || !function_exists('gmp_init')) {
            return strval($digital);
        }

        return is_numeric($digital) ? gmp_strval(gmp_init($digital)) : $digital;
    }

    /**
     * @param int|string $digital
     *
     * @return string
     *
     * @deprecated 名字是在太长了
     * @see Base::toString()
     */
    public static function digitalToString($digital): string
    {
        return static::toString($digital);
    }

    /**
     * @param int|string $digital
     * @param int $length
     *
     * @return string
     */
    public static function toStringWithPad($digital, int $length = 30): string
    {
        $value = static::toString($digital);
        $value = str_pad($value, $length, '0', STR_PAD_LEFT);

        return substr($value, 0, $length);
    }

    /**
     * @param int|string $digital
     * @return null|string
     */
    public static function to36($digital)
    {
        return static::conv($digital,
            '0123456789',
            '0123456789abcdefghijklmnopqrstuvwxyz'
        );
    }

    /**
     * @param int|string $digital
     * @return null|string
     */
    public static function to62($digital)
    {
        return static::conv($digital,
            '0123456789',
            '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
        );
    }

    /**
     * 判断是否为整数（支持正数、负数、大整数字符串）
     *
     * @param mixed $value
     * @return bool
     */
    public static function isInteger($value): bool
    {
        if (is_int($value)) {
            return true;
        }

        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        // float 类型处理
        if (is_float($value)) {
            // 超过 2^53 的 float 无法精确表示整数，返回 false
            if (abs($value) > 9007199254740992) {
                return false;
            }

            // 检查是否有小数部分
            if (0 != fmod($value, 1.0)) {
                return false;
            }

            // 使用 sprintf 避免科学计数法
            $value = sprintf('%.0f', $value);
        }

        try {
            return 1 === preg_match('/^-?[0-9]+$/', static::toString($value));
        } catch (Throwable $e) {
            return false;
        }
    }
}
