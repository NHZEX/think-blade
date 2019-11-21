<?php
declare(strict_types=1);

namespace HZEX\Blade;

use Illuminate\View\ViewFinderInterface;
use think\App;
use think\View;
use think\view\driver\Blade;

class TpViewFinder implements ViewFinderInterface
{

    /**
     * Get the fully qualified location of the view.
     *
     * @param string $view
     * @return string
     */
    public function find($view)
    {
        /** @var View|Blade $view */
        $driver = App::getInstance()->make(View::class);
        $view = str_replace('.', DIRECTORY_SEPARATOR, $view);
        return $driver->parseTemplate($view);
    }

    /**
     * Add a location to the finder.
     *
     * @param string $location
     * @return void
     */
    public function addLocation($location)
    {
        // TODO: Implement addLocation() method.
    }

    /**
     * Add a namespace hint to the finder.
     *
     * @param string       $namespace
     * @param string|array $hints
     * @return void
     */
    public function addNamespace($namespace, $hints)
    {
        // TODO: Implement addNamespace() method.
    }

    /**
     * Prepend a namespace hint to the finder.
     *
     * @param string       $namespace
     * @param string|array $hints
     * @return void
     */
    public function prependNamespace($namespace, $hints)
    {
        // TODO: Implement prependNamespace() method.
    }

    /**
     * Replace the namespace hints for the given namespace.
     *
     * @param string       $namespace
     * @param string|array $hints
     * @return void
     */
    public function replaceNamespace($namespace, $hints)
    {
        // TODO: Implement replaceNamespace() method.
    }

    /**
     * Add a valid view extension to the finder.
     *
     * @param string $extension
     * @return void
     */
    public function addExtension($extension)
    {
        // TODO: Implement addExtension() method.
    }

    /**
     * Flush the cache of located views.
     *
     * @return void
     */
    public function flush()
    {
        // TODO: Implement flush() method.
    }
}
