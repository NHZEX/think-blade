<?php
declare(strict_types=1);

namespace HZEX\Blade\Overlay;

use Illuminate\Support\Collection;
use Illuminate\View\Compilers\BladeCompiler as BladeCompilerIlluminate;

class BladeCompiler extends BladeCompilerIlluminate
{
    /**
     * Get the open and closing PHP tag tokens from the given string.
     *
     * @param  string  $contents
     * @return Collection
     */
    protected function getOpenAndClosingPhpTokens($contents)
    {
        return (new Collection(token_get_all($contents)))
            ->pluck($tokenNumber = 0)
            ->filter(function ($token) {
                return in_array($token, [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG]);
            });
    }
}
