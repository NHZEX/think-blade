<?php

namespace Illuminate\Support;

use Closure;

class Benchmark
{
    /**
     * Measure a callable or array of callables over the given number of iterations.
     */
    public static function measure(Closure|array $benchmarkables, int $iterations = 1): array|float
    {
        return \__Illuminate\collect(\Illuminate\Support\Arr::wrap($benchmarkables))->map(function ($callback) use ($iterations) {
            return \__Illuminate\collect(range(1, $iterations))->map(function () use ($callback) {
                gc_collect_cycles();
                $start = hrtime(true);
                $callback();

                return (hrtime(true) - $start) / 1000000;
            })->average();
        })->when($benchmarkables instanceof Closure, fn ($c) => $c->first(), fn ($c) => $c->all());
    }

    /**
     * Measure a callable or array of callables over the given number of iterations, then dump and die.
     *
     * @return never
     */
    public static function dd(Closure|array $benchmarkables, int $iterations = 1): void
    {
        $result = \__Illuminate\collect(static::measure(\Illuminate\Support\Arr::wrap($benchmarkables), $iterations))->map(fn ($average) => number_format($average, 3).'ms')->when($benchmarkables instanceof Closure, fn ($c) => $c->first(), fn ($c) => $c->all());
        dd($result);
    }
}
