<?php

namespace HughCube\Base;

use InvalidArgumentException;
use RuntimeException;
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
        if (false !== $result && PREG_NO_ERROR === preg_last_error()) {
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
     * @return bool
     */
    protected static function hasBcMath(): bool
    {
        static $result = null;
        if (null === $result) {
            $result = function_exists('bcadd')
                && function_exists('bcmul')
                && function_exists('bccomp')
                && function_exists('bcdiv')
                && function_exists('bcsub');
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
     * @param string $base
     *
     * @return array{chars: string[], charMap: array<string, int>, baseLen: int, isByteLevel: bool}
     */
    protected static function baseMeta(string $base): array
    {
        static $cache = [];
        if (!array_key_exists($base, $cache)) {
            $chars = static::splitChars($base);
            $baseLen = count($chars);
            if ($baseLen < 2) {
                throw new InvalidArgumentException('Base alphabet must contain at least two unique characters.');
            }

            $charMap = [];
            foreach ($chars as $index => $char) {
                if (isset($charMap[$char])) {
                    throw new InvalidArgumentException('Base alphabet characters must be unique.');
                }
                $charMap[$char] = $index;
            }

            $cache[$base] = [
                'chars' => $chars,
                'charMap' => $charMap,
                'baseLen' => $baseLen,
                'isByteLevel' => (strlen($base) === $baseLen),
            ];
        }

        return $cache[$base];
    }

    /**
     * @param string $number
     * @param string $base
     *
     * @return array{0: string, 1: bool}
     */
    protected static function splitSignedNumber(string $number, string $base = ''): array
    {
        if ('' === $number) {
            throw new InvalidArgumentException('Number input cannot be empty.');
        }

        $first = substr($number, 0, 1);
        if (
            ('-' === $first || '+' === $first)
            && ('' === $base || false === strpos($base, $first))
        ) {
            $number = substr($number, 1);
            if ('' === $number) {
                throw new InvalidArgumentException('Number input must contain digits.');
            }
            return [$number, '-' === $first];
        }

        return [$number, false];
    }

    /**
     * @param string $number
     *
     * @return string
     */
    protected static function normalizeDecimalNumber(string $number): string
    {
        if (1 !== preg_match('/^[0-9]+$/D', $number)) {
            throw new InvalidArgumentException('Decimal number must contain only digits.');
        }

        $normalized = ltrim($number, '0');
        return '' === $normalized ? '0' : $normalized;
    }

    /**
     * @param mixed $numberInput
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
        list($numberInput, $isNegative) = static::splitSignedNumber($numberInput, $fromBaseInput);

        if ($fromBaseInput != '0123456789') {
            $base10 = static::baseToDecimal($numberInput, $fromBaseInput);
        } else {
            $base10 = static::normalizeDecimalNumber($numberInput);
        }

        if ($isNegative && '0' !== $base10) {
            $base10 = '-' . $base10;
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
        $meta = static::baseMeta($fromBase);
        $digits = $meta['isByteLevel'] ? str_split($number, 1) : static::splitChars($number);
        if ([] === $digits) {
            throw new InvalidArgumentException('Number input must contain digits.');
        }

        $digitValues = [];
        foreach ($digits as $digit) {
            if (!isset($meta['charMap'][$digit])) {
                throw new InvalidArgumentException('Number contains a digit that is not defined in source base.');
            }
            $digitValues[] = $meta['charMap'][$digit];
        }

        if (static::hasGmp()) {
            // 快速路径: 进制 2-62 且为单字节字符集时, 用 gmp_init 在 C 层一次性完成解析
            if ($meta['baseLen'] >= 2 && $meta['baseLen'] <= 62 && $meta['isByteLevel']) {
                $gmpAlphabet = static::gmpAlphabet($meta['baseLen']);
                $normalized = ($fromBase === $gmpAlphabet) ? $number : strtr($number, $fromBase, $gmpAlphabet);
                return gmp_strval(gmp_init($normalized, $meta['baseLen']));
            }

            // 回退路径: 进制 > 62 或多字节字符集, 用 Horner 循环
            $result = gmp_init(0);
            $gmpBase = gmp_init($meta['baseLen']);
            foreach ($digitValues as $digitValue) {
                $result = gmp_add(gmp_mul($result, $gmpBase), $digitValue);
            }
            return gmp_strval($result);
        }

        if (!static::hasBcMath()) {
            throw new RuntimeException('Conversion requires either ext-gmp or ext-bcmath.');
        }

        // bcmath 回退
        $result = '0';
        $strBaseLen = (string)$meta['baseLen'];
        foreach ($digitValues as $digitValue) {
            $result = bcadd(bcmul($result, $strBaseLen, 0), (string)$digitValue, 0);
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
        $meta = static::baseMeta($toBase);
        list($base10, $isNegative) = static::splitSignedNumber($base10);
        $base10 = static::normalizeDecimalNumber($base10);

        if ('0' === $base10) {
            return $meta['chars'][0];
        }

        if (static::hasGmp()) {
            $gmpVal = gmp_init($base10, 10);

            // 快速路径: 进制 2-62 且为单字节字符集时, 用 gmp_strval 在 C 层一次性完成转换
            if ($meta['baseLen'] >= 2 && $meta['baseLen'] <= 62 && $meta['isByteLevel']) {
                $gmpAlphabet = static::gmpAlphabet($meta['baseLen']);
                $gmpResult = gmp_strval($gmpVal, $meta['baseLen']);
                $result = ($toBase === $gmpAlphabet) ? $gmpResult : strtr($gmpResult, $gmpAlphabet, $toBase);
                return $isNegative ? ('-' . $result) : $result;
            }

            // 回退路径: 进制 > 62 或多字节字符集, 用除法循环
            $gmpBase = gmp_init($meta['baseLen']);
            $result = '';
            while (gmp_sign($gmpVal) > 0) {
                list($gmpVal, $rem) = gmp_div_qr($gmpVal, $gmpBase);
                $result = $meta['chars'][gmp_intval($rem)] . $result;
            }
            return $isNegative ? ('-' . $result) : $result;
        }

        if (!static::hasBcMath()) {
            throw new RuntimeException('Conversion requires either ext-gmp or ext-bcmath.');
        }

        // bcmath 回退
        $result = '';
        $strBaseLen = (string)$meta['baseLen'];
        while (bccomp($base10, '0', 0) > 0) {
            $quotient = bcdiv($base10, $strBaseLen, 0);
            $mod = bcsub($base10, bcmul($quotient, $strBaseLen, 0), 0);
            $result = $meta['chars'][intval($mod)] . $result;
            $base10 = $quotient;
        }
        return $isNegative ? ('-' . $result) : $result;
    }

    /**
     * @param mixed $digital
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

        return strval($digital);
    }

    /**
     * @param mixed $digital
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
     * @param mixed $digital
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
     * @param mixed $digital
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
     * @param mixed $digital
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
