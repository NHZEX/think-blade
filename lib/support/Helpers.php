<?php
declare(strict_types=1);

namespace Illuminate\Support;

use Closure;
use Illuminate\Contracts\Support\Htmlable;

class Helpers
{
    /**
     * Determine whether the current environment is Windows based.
     *
     * @return bool
     */
    public static function windows_os()
    {
        return strtolower(substr(PHP_OS, 0, 3)) === 'win';
    }

    /**
     * Call the given Closure with the given value then return the value.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    public static function tap($value, $callback = null)
    {
        if (is_null($callback)) {
            return new HigherOrderTapProxy($value);
        }

        $callback($value);

        return $value;
    }

    /**
     * Get the last element from an array.
     *
     * @param  array  $array
     * @return mixed
     */
    public static function last($array)
    {
        return end($array);
    }

    /**
     * Encode HTML special characters in a string.
     *
     * @param Htmlable|string $value
     * @param  bool           $doubleEncode
     * @return string
     */
    public static function e($value, $doubleEncode = true)
    {
        if ($value instanceof Htmlable) {
            return $value->toHtml();
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }

    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public static function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }

    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed   $target
     * @param  string|array|int  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (! is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (! is_array($target)) {
                    return self::value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = self::data_get($item, $key);
                }

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return self::value($default);
            }
        }

        return $target;
    }

    /**
     * Create a collection from the given value.
     *
     * @param  mixed  $value
     * @return Collection
     */
    public static function collect($value = null)
    {
        return new Collection($value);
    }
}
