<?php

namespace __Illuminate;

use BackedEnum;
use Closure;
use Countable;
use Exception;
use Illuminate\Contracts\Support\DeferringDisplayableValue;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\Env;
use Illuminate\Support\HigherOrderTapProxy;
use Illuminate\Support\Optional;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

if (! function_exists('__Illuminate\\\__Illuminate\append_config')) {
    /**
     * Assign high numeric IDs to a config item to force appending.
     *
     * @return array
     */
    function append_config(array $array)
    {
        $start = 9999;

        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                $start++;
                $array[$start] = Arr::pull($array, $key);
            }
        }

        return $array;
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\blank')) {
    /**
     * Determine if the given value is "blank".
     *
     * @param  mixed  $value
     * @return bool
     */
    function blank($value)
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\class_basename')) {
    /**
     * Get the class "basename" of the given object / class.
     *
     * @param  string|object  $class
     * @return string
     */
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\class_uses_recursive')) {
    /**
     * Returns all traits used by a class, its parent classes and trait of their traits.
     *
     * @param  object|string  $class
     * @return array
     */
    function class_uses_recursive($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        $results = [];

        foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
            $results += \__Illuminate\trait_uses_recursive($class);
        }

        return array_unique($results);
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\e')) {
    /**
     * Encode HTML special characters in a string.
     *
     * @param  \Illuminate\Contracts\Support\DeferringDisplayableValue|\Illuminate\Contracts\Support\Htmlable|BackedEnum|string|null  $value
     * @param  bool  $doubleEncode
     * @return string
     */
    function e($value, $doubleEncode = true)
    {
        if ($value instanceof DeferringDisplayableValue) {
            $value = $value->resolveDisplayableValue();
        }

        if ($value instanceof Htmlable) {
            return $value->toHtml();
        }

        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        return htmlspecialchars($value ?? '', \ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        return Env::get($key, $default);
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\filled')) {
    /**
     * Determine if a value is "filled".
     *
     * @param  mixed  $value
     * @return bool
     */
    function filled($value)
    {
        return ! \__Illuminate\blank($value);
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\object_get')) {
    /**
     * Get an item from an object using "dot" notation.
     *
     * @param  object  $object
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function object_get($object, $key, $default = null)
    {
        if (is_null($key) || trim($key) === '') {
            return $object;
        }

        foreach (explode('.', $key) as $segment) {
            if (! is_object($object) || ! isset($object->{$segment})) {
                return \__Illuminate\value($default);
            }
            $object = $object->{$segment};
        }

        return $object;
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\optional')) {
    /**
     * Provide access to optional objects.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function optional($value = null, callable $callback = null)
    {
        if (is_null($callback)) {
            return new Optional($value);
        } elseif (! is_null($value)) {
            return $callback($value);
        }
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\preg_replace_array')) {
    /**
     * Replace a given pattern with each value in the array in sequentially.
     *
     * @param  string  $pattern
     * @param  string  $subject
     * @return string
     */
    function preg_replace_array($pattern, array $replacements, $subject)
    {
        return preg_replace_callback($pattern, function () use (&$replacements) {
            foreach ($replacements as $value) {
                return array_shift($replacements);
            }
        }, $subject);
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\retry')) {
    /**
     * Retry an operation a given number of times.
     *
     * @param  int|array  $times
     * @param  int|Closure  $sleepMilliseconds
     * @param  callable|null  $when
     * @return mixed
     *
     * @throws Exception
     */
    function retry($times, callable $callback, $sleepMilliseconds = 0, $when = null)
    {
        $attempts = 0;
        $backoff = [];

        if (is_array($times)) {
            $backoff = $times;
            $times = count($times) + 1;
        }
        beginning:
        $attempts++;
        $times--;

        try {
            return $callback($attempts);
        } catch (Exception $e) {
            if ($times < 1 || $when && ! $when($e)) {
                throw $e;
            }
            $sleepMilliseconds = $backoff[$attempts - 1] ?? $sleepMilliseconds;

            if ($sleepMilliseconds) {
                usleep(\__Illuminate\value($sleepMilliseconds, $attempts, $e) * 1000);
            }

            goto beginning;
        }
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\str')) {
    /**
     * Get a new stringable object from the given string.
     *
     * @param  string|null  $string
     * @return \Illuminate\Support\Stringable|mixed
     */
    function str($string = null)
    {
        if (func_num_args() === 0) {
            return new class
            {
                public function __call($method, $parameters)
                {
                    return Str::$method(...$parameters);
                }

                public function __toString()
                {
                    return '';
                }
            };
        }

        return Str::of($string);
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    function tap($value, $callback = null)
    {
        if (is_null($callback)) {
            return new HigherOrderTapProxy($value);
        }
        $callback($value);

        return $value;
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\throw_if')) {
    /**
     * Throw the given exception if the given condition is true.
     *
     * @param  mixed  $condition
     * @param  Throwable|string  $exception
     * @param  mixed  ...$parameters
     * @return mixed
     *
     * @throws Throwable
     */
    function throw_if($condition, $exception = 'RuntimeException', ...$parameters)
    {
        if ($condition) {
            if (is_string($exception) && class_exists($exception)) {
                $exception = new $exception(...$parameters);
            }

            throw is_string($exception) ? new RuntimeException($exception) : $exception;
        }

        return $condition;
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\throw_unless')) {
    /**
     * Throw the given exception unless the given condition is true.
     *
     * @param  mixed  $condition
     * @param  Throwable|string  $exception
     * @param  mixed  ...$parameters
     * @return mixed
     *
     * @throws Throwable
     */
    function throw_unless($condition, $exception = 'RuntimeException', ...$parameters)
    {
        \__Illuminate\throw_if(! $condition, $exception, ...$parameters);

        return $condition;
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\trait_uses_recursive')) {
    /**
     * Returns all traits used by a trait and its traits.
     *
     * @param  string  $trait
     * @return array
     */
    function trait_uses_recursive($trait)
    {
        $traits = class_uses($trait) ?: [];

        foreach ($traits as $trait) {
            $traits += \__Illuminate\trait_uses_recursive($trait);
        }

        return $traits;
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\transform')) {
    /**
     * Transform the given value if it is present.
     *
     * @param  mixed  $value
     * @param  mixed  $default
     * @return mixed|null
     */
    function transform($value, callable $callback, $default = null)
    {
        if (\__Illuminate\filled($value)) {
            return $callback($value);
        }

        if (is_callable($default)) {
            return $default($value);
        }

        return $default;
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\windows_os')) {
    /**
     * Determine whether the current environment is Windows based.
     *
     * @return bool
     */
    function windows_os()
    {
        return \PHP_OS_FAMILY === 'Windows';
    }
}

if (! function_exists('__Illuminate\\\__Illuminate\with')) {
    /**
     * Return the given value, optionally passed through the given callback.
     *
     * @template TValue
     * @template TReturn
     *
     * @param  TValue  $value
     * @param  (callable(TValue): (TReturn))|null  $callback
     * @return ($callback is null ? TValue : TReturn)
     */
    function with($value, callable $callback = null)
    {
        return is_null($callback) ? $value : $callback($value);
    }
}
