<?php

declare(strict_types=1);

namespace Zxin\Think\Blade;

use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\View\Factory as FactoryIlluminate;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Component;
use Illuminate\View\DynamicComponent;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\FileEngine;
use Illuminate\View\Engines\PhpEngine;
use think\App;
use think\Container;
use think\event\HttpEnd;
use think\Service;
use think\view\driver\Blade;

class BladeViewService extends Service
{
    private ContainerContract $container;

    public function register(): void
    {
        $this->app->bind('container-bridge', ContainerBridge::class);
        $this->container = $this->resolveContainer($this->app);

        $this->setBridgeContainer($this->container);

        $this->registerNativeFilesystem();
        $this->registerEventDispatcher();
        $this->registerFactory();
        $this->registerViewFinder();
        $this->registerBladeCompiler();
        $this->registerEngineResolver();
    }

    private static function setBridgeContainer(ContainerContract $container): void
    {
        \Illuminate\Container\Container::setInstance($container);
        Facade::setFacadeApplication($container);
        Facade::clearResolvedInstances();
    }

    public static function resolveContainer(Container $container): ContainerContract
    {
        return $container->make('container-bridge');
    }

    public static function resolveDriver(Container $container): Blade
    {
        return $container->get('view.blade.driver');
    }

    public function registerTerminatingCallback(App $app, callable $callback): void
    {
        $app->event->listen(HttpEnd::class, $callback);
    }

    public function registerNativeFilesystem()
    {
        $this->container->singleton('view.blade.fs', function () {
            return new Filesystem();
        });
    }

    public function registerEventDispatcher()
    {
        $this->container->singleton('view.blade.events', NullEventDispatcher::class);
    }

    /**
     * Register the view environment.
     *
     * @return void
     */
    public function registerFactory()
    {
        $this->container->alias('view.blade', FactoryIlluminate::class);
        $this->container->singleton('view.blade', function (App $app) {
            // Next we need to grab the engine resolver instance that will be used by the
            // environment. The resolver will be used by an environment to get each of
            // the various engine implementations such as plain PHP or Blade engine.
            $resolver = $app['view.engine.resolver'];

            /** @var \Illuminate\View\ViewFinderInterface $finder */
            $finder = $app['view.finder'];

            $factory = $this->createFactory($resolver, $finder, $app['view.blade.events']);

            // We will also set the container instance on this view environment since the
            // view composers may be classes registered in the container, which allows
            // for great testable, flexible composers for the application developer.
            $factory->setContainer($this->resolveContainer($app));

            $factory->share('app', $app);

            $this->registerTerminatingCallback($app, static function () {
                Component::forgetFactory();
            });

            return $factory;
        });
    }

    /**
     * Create a new Factory Instance.
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @param  \Illuminate\View\ViewFinderInterface  $finder
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return \Illuminate\View\Factory
     */
    protected function createFactory($resolver, $finder, $events)
    {
        return new ViewFactory($resolver, $finder, $events);
    }

    /**
     * Register the view finder implementation.
     *
     * @return void
     */
    public function registerViewFinder()
    {
        $this->container->singleton('view.finder', TpFileViewFinder::class);
    }

    /**
     * Register the Blade compiler implementation.
     *
     * @return void
     */
    public function registerBladeCompiler()
    {
        $this->container->singleton('blade.compiler', function (App $app) {

            $drive = $this->resolveDriver($app);

            $viewCompiled = $drive->getConfig('cache_path');
            $viewBasePath = $drive->getConfig('view_path');
            $viewCache = $drive->getConfig('tpl_cache');

            $blade = new BladeCompiler(
                $app['view.blade.fs'],
                $viewCompiled,
                $viewBasePath,
                $viewCache,
                'php',
            );
            $blade->component('dynamic-component', DynamicComponent::class);

            return $blade;
        });
    }

    /**
     * Register the engine resolver instance.
     *
     * @return void
     */
    public function registerEngineResolver()
    {
        $this->container->singleton('view.engine.resolver', function () {
            $resolver = new EngineResolver;

            // Next, we will register the various view engines with the resolver so that the
            // environment will resolve the engines needed for various views based on the
            // extension of view file. We call a method for each of the view's engines.
            $this->registerFileEngine($resolver);
            $this->registerPhpEngine($resolver);
            $this->registerBladeEngine($resolver);

            return $resolver;
        });
    }

    /**
     * Register the file engine implementation.
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerFileEngine($resolver)
    {
        $resolver->register('file', function () {
            return new FileEngine($this->app['view.blade.fs']);
        });
    }

    /**
     * Register the PHP engine implementation.
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerPhpEngine($resolver)
    {
        $resolver->register('php', function () {
            return new PhpEngine($this->app['view.blade.fs']);
        });
    }

    /**
     * Register the Blade engine implementation.
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerBladeEngine($resolver)
    {
        $resolver->register('blade', function () {
            $compiler = new CompilerEngine($this->app['blade.compiler'], $this->app['view.blade.fs']);

            $this->registerTerminatingCallback($this->app, static function () use ($compiler) {
                $compiler->forgetCompiledOrNotExpired();
            });

            return $compiler;
        });
    }
}
