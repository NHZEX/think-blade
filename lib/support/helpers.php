<?php

namespace Illuminate\Support;

use Illuminate\Contracts\Support\DeferringDisplayableValue;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Encode HTML special characters in a string.
 *
 * @param  \Illuminate\Contracts\Support\DeferringDisplayableValue|\Illuminate\Contracts\Support\Htmlable|string|null  $value
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

    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8', $doubleEncode);
}

/**
 * Provide access to optional objects.
 *
 * @param  mixed  $value
 * @param  callable|null  $callback
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
