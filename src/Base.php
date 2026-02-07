<?php

namespace HughCube\Base;

use Throwable;

/**
 * @see https://www.php.net/manual/zh/function.base-convert.php
 */
class Base
{
    /**
     * @param string $string
     *
     * @return string[]
     */
    protected static function splitChars(string $string): array
    {
        $result = @preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
        if (false !== $result) {
            return $result;
        }

        return str_split($string, 1);
    }

    /**
     * @return bool
     */
    protected static function hasGmp(): bool
    {
        static $result = null;
        if (null === $result) {
            $result = function_exists('gmp_init');
        }
        return $result;
    }

    /**
     * 获取 GMP 原生字符表 (用于 gmp_init/gmp_strval 的快速路径)
     *
     * @param int $baseLen
     *
     * @return string
     */
    protected static function gmpAlphabet(int $baseLen): string
    {
        static $cache = [];
        if (!isset($cache[$baseLen])) {
            $full = $baseLen <= 36
                ? '0123456789abcdefghijklmnopqrstuvwxyz'
                : '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            $cache[$baseLen] = substr($full, 0, $baseLen);
        }
        return $cache[$baseLen];
    }

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

        if ($fromBaseInput != '0123456789') {
            $base10 = static::baseToDecimal($numberInput, $fromBaseInput);
        } else {
            $base10 = $numberInput;
        }

        if ($toBaseInput == '0123456789') {
            return $base10;
        }

        return static::decimalToBase($base10, $toBaseInput);
    }

    /**
     * @param string $number
     * @param string $fromBase
     *
     * @return string
     */
    protected static function baseToDecimal(string $number, string $fromBase): string
    {
        $fromChars = static::splitChars($fromBase);
        $baseLen = count($fromChars);
        $isByteLevel = (strlen($fromBase) === $baseLen);

        if (static::hasGmp()) {
            // 快速路径: 进制 2-62 且为单字节字符集时, 用 gmp_init 在 C 层一次性完成解析
            if ($baseLen >= 2 && $baseLen <= 62 && $isByteLevel) {
                $gmpAlphabet = static::gmpAlphabet($baseLen);
                $normalized = ($fromBase === $gmpAlphabet) ? $number : strtr($number, $fromBase, $gmpAlphabet);
                return gmp_strval(gmp_init($normalized, $baseLen));
            }

            // 回退路径: 进制 > 62 或多字节字符集, 用 Horner 循环
            $charMap = array_flip($fromChars);
            $digits = $isByteLevel ? str_split($number, 1) : static::splitChars($number);
            $result = gmp_init(0);
            $gmpBase = gmp_init($baseLen);
            foreach ($digits as $digit) {
                $result = gmp_add(gmp_mul($result, $gmpBase), gmp_init($charMap[$digit]));
            }
            return gmp_strval($result);
        }

        // bcmath 回退
        $charMap = array_flip($fromChars);
        $digits = $isByteLevel ? str_split($number, 1) : static::splitChars($number);
        $result = '0';
        $strBaseLen = (string)$baseLen;
        foreach ($digits as $digit) {
            $result = bcadd(bcmul($result, $strBaseLen, 0), (string)$charMap[$digit], 0);
        }
        return $result;
    }

    /**
     * @param string $base10
     * @param string $toBase
     *
     * @return string
     */
    protected static function decimalToBase(string $base10, string $toBase): string
    {
        $chars = static::splitChars($toBase);
        $baseLen = count($chars);

        if (static::hasGmp()) {
            $gmpVal = gmp_init($base10, 10);
            if (gmp_sign($gmpVal) === 0) {
                return $chars[0];
            }

            // 快速路径: 进制 2-62 且为单字节字符集时, 用 gmp_strval 在 C 层一次性完成转换
            if ($baseLen >= 2 && $baseLen <= 62 && strlen($toBase) === $baseLen) {
                $gmpAlphabet = static::gmpAlphabet($baseLen);
                $gmpResult = gmp_strval($gmpVal, $baseLen);
                return ($toBase === $gmpAlphabet) ? $gmpResult : strtr($gmpResult, $gmpAlphabet, $toBase);
            }

            // 回退路径: 进制 > 62 或多字节字符集, 用除法循环
            $gmpBase = gmp_init($baseLen);
            $result = '';
            while (gmp_sign($gmpVal) > 0) {
                list($gmpVal, $rem) = gmp_div_qr($gmpVal, $gmpBase);
                $result = $chars[gmp_intval($rem)] . $result;
            }
            return $result;
        }

        // bcmath 回退
        if (bccomp($base10, '0', 0) == 0) {
            return $chars[0];
        }
        $result = '';
        $strBaseLen = (string)$baseLen;
        while (bccomp($base10, '0', 0) > 0) {
            $result = $chars[intval(bcmod($base10, $strBaseLen, 0))] . $result;
            $base10 = bcdiv($base10, $strBaseLen, 0);
        }
        return $result;
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

        if (is_int($digital)) {
            return strval($digital);
        }

        if (static::hasGmp() && is_numeric($digital)) {
            return gmp_strval(gmp_init($digital));
        }

        return strval($digital);
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
            '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'
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
            return 1 === preg_match('/^-?[0-9]+$/D', static::toString($value));
        } catch (Throwable $e) {
            return false;
        }
    }
}
