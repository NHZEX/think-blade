<?php

declare(strict_types=1);

namespace Zxin\Think\Blade;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use think\Container;
use think\Event;

class NullEventDispatcher implements DispatcherContract
{
    public function __construct(Container $container)
    {
    }

    public function listen($events, $listener = null)
    {
    }

    public function hasListeners($eventName)
    {
        // TODO: Implement hasListeners() method.
    }

    public function subscribe($subscriber)
    {
        // TODO: Implement subscribe() method.
    }

    public function until($event, $payload = [])
    {
        // TODO: Implement until() method.
    }

    public function dispatch($event, $payload = [], $halt = false)
    {
        // TODO: Implement dispatch() method.
    }

    public function push($event, $payload = [])
    {
        // TODO: Implement push() method.
    }

    public function flush($event)
    {
        // TODO: Implement flush() method.
    }

    public function forget($event)
    {
        // TODO: Implement forget() method.
    }

    public function forgetPushed()
    {
        // TODO: Implement forgetPushed() method.
    }
}