<?php
declare(strict_types=1);

namespace HZEX\Blade;

class Register
{
    protected $directives = [];

    protected $ifs = [];

    /**
     * @return array
     */
    public function getDirectives(): array
    {
        return $this->directives;
    }

    /**
     * @return array
     */
    public function getIfs(): array
    {
        return $this->ifs;
    }

    /**
     * @param BladeInstance $bladeInstance
     */
    public function exec(BladeInstance $bladeInstance)
    {
        foreach ($this->directives as $name => $handler) {
            $bladeInstance->directive($name, $handler);
        }
        foreach ($this->ifs as $name => $handler) {
            $bladeInstance->if($name, $handler);
        }
    }

    /**
     * Register a handler for custom directives.
     *
     * @param string   $name
     * @param callable $handler
     *
     * @return $this
     */
    public function directive(string $name, callable $handler): self
    {
        $this->directives[$name] = $handler;

        return $this;
    }

    /**
     * Register an "if" statement directive.
     *
     * @param string   $name
     * @param callable $handler
     * @return $this
     */
    public function if(string $name, callable $handler): self
    {
        $this->ifs[$name] = $handler;

        return $this;
    }
}
