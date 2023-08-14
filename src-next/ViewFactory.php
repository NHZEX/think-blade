<?php

declare(strict_types=1);

namespace Zxin\Think\Blade;

use Illuminate\Contracts\View\Engine;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory;

class ViewFactory extends Factory
{
    /**
     * Get the internal compiler in use.
     *
     * @return BladeCompiler
     */
    private function getCompiler(): BladeCompiler
    {
        return $this->container->get('blade.compiler');
    }

    /**
     * Register a custom Blade compiler.
     *
     * @param callable $compiler
     *
     * @return $this
     */
    public function extend(callable $compiler): ViewFactory
    {
        $this
            ->getCompiler()
            ->extend($compiler);

        return $this;
    }

    /**
     * Register a handler for custom directives.
     *
     * @param string $name
     * @param callable $handler
     *
     * @return $this
     */
    public function directive(string $name, callable $handler): ViewFactory
    {
        $this
            ->getCompiler()
            ->directive($name, $handler);

        return $this;
    }

    /**
     * Register an "if" statement directive.
     *
     * @param  string  $name
     * @param  callable  $handler
     * @return $this
     */
    public function if(string $name, callable $handler): ViewFactory
    {
        $this
            ->getCompiler()
            ->if($name, $handler);

        return $this;
    }

    public function render(string $view, array $params = []): string
    {
        return $this->make($view, $params)->render();
    }
}