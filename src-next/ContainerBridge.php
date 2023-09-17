<?php

declare(strict_types=1);

namespace Zxin\Think\Blade;

use Closure;
use Illuminate\Contracts\Container\Container as ContainerContract;
use think\Container as ThinkContainer;
use ArrayAccess;
use RuntimeException;

class ContainerBridge implements ArrayAccess, ContainerContract
{
    private ThinkContainer $container;

    public function __construct(ThinkContainer $container)
    {
        $this->container = $container;
    }

    public function bound($abstract)
    {
        return $this->container->bound($abstract);
    }

    public function alias($abstract, $alias)
    {
        $this->container->bind($alias, $abstract);
    }

    public function tag($abstracts, $tags)
    {
        throw new RuntimeException('not support');
    }

    public function tagged($tag)
    {
        throw new RuntimeException('not support');
    }

    public function bind($abstract, $concrete = null, $shared = false)
    {
        // tp 容器没有 shared === false 的条件，都为单实例

        $this->container->bind($abstract, $concrete);
    }

    public function bindIf($abstract, $concrete = null, $shared = false)
    {
        if (!$this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    public function singletonIf($abstract, $concrete = null)
    {
        if (!$this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
        }
    }

    public function scoped($abstract, $concrete = null)
    {
        throw new RuntimeException('not support');
    }

    public function scopedIf($abstract, $concrete = null)
    {
        throw new RuntimeException('not support');
    }

    public function extend($abstract, Closure $closure)
    {
        throw new RuntimeException('not support');
    }

    public function instance($abstract, $instance)
    {
        $this->container->instance($abstract, $instance);
    }

    public function addContextualBinding($concrete, $abstract, $implementation)
    {
        throw new RuntimeException('not support');
    }

    public function when($concrete)
    {
        throw new RuntimeException('not support');
    }

    public function factory($abstract)
    {
        return fn () => $this->make($abstract);
    }

    public function flush()
    {
        // 可以考虑静默忽略
        throw new RuntimeException('not support');
    }

    public function make($abstract, array $parameters = [])
    {
        $this->container->make($abstract, $parameters);
    }

    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        // $defaultMethod 无法实现，tp 仅支持`__make`

        if (null !== $defaultMethod && '__make' !== $defaultMethod) {
            throw new RuntimeException('not support $defaultMethod');
        }

        $this->container->invoke($callback, $parameters);
    }

    public function resolved($abstract)
    {
        return $this->container->exists($abstract);
    }

    public function beforeResolving($abstract, Closure $callback = null)
    {
        throw new RuntimeException('not support');
    }

    public function resolving($abstract, Closure $callback = null)
    {
        $this->container->resolving($abstract, $callback);
    }

    public function afterResolving($abstract, Closure $callback = null)
    {
        throw new RuntimeException('not support');
    }

    public function get(string $id)
    {
        return $this->container->get($id);
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->container[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->container[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->container[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->container[$offset]);
    }
}
