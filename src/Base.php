<?php

namespace HughCube\Base;

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

    public static function isDigit($string): bool
    {
        return ctype_digit(static::toString($string));
    }
}
