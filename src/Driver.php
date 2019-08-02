<?php
/**
 * Created by PhpStorm.
 * User: Auoor
 * Date: 2019/2/4
 * Time: 15:58
 */
declare(strict_types=1);

namespace HZEX\Blade;

use think\App;
use think\contract\TemplateHandlerInterface;
use think\helper\Str;
use think\template\exception\TemplateNotFoundException;

/**
 * Class Driver
 * @package nhzex\Blade\Blade
 * @mixin BladeInstance
 */
class Driver implements TemplateHandlerInterface
{
    // 模板引擎实例
    /** @var BladeInstance */
    private $blade;
    /** @var App */
    private $app;

    // 模板引擎参数
    protected $config = [
        // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
        'auto_rule' => 1,
        // 模板基础路径
        'view_base' => '',
        // 模板路径
        'view_path' => '',
        // 模板后缀
        'view_suffix' => 'blade.php',
        // 模板文件名分隔符
        'view_depr' => DIRECTORY_SEPARATOR,
        // 缓存路径
        'cache_path' => '',
    ];

    public function __construct(App $app, array $config = [])
    {
        $this->app = $app;
        $this->config = array_merge($this->config, $config);

        if (empty($this->config['view_base'])) {
            $this->config['view_base'] = $app->getRootPath() . 'view' . DIRECTORY_SEPARATOR;
        }

        if (empty($this->config['cache_path'])) {
            $this->config['cache_path'] = $app->getRuntimePath() . 'temp' . DIRECTORY_SEPARATOR;
        }

        $this->config(array_merge($config, $this->config));
        $this->boot();
    }

    public function boot(): void
    {
        $cache_path = $this->config['cache_path'];

        $this->blade = new BladeInstance($this->config['view_base'], $cache_path);
    }

    /**
     * 检测是否存在模板文件
     * @param  string $template 模板文件或者模板规则
     * @return bool
     */
    public function exists(string $template): bool
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $template = $this->parseTemplate($template);
        }

        return is_file($template);
    }

    /**
     * 渲染模板文件
     * @param string $template 模板文件
     * @param array  $data     模板变量
     * @return void
     */
    public function fetch(string $template, array $data = []): void
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $template = $this->parseTemplate($template);
        }
        // 模板不存在 抛出异常
        if (!is_file($template)) {
            throw new TemplateNotFoundException('template not exists:' . $template, $template);
        }
        // 记录视图信息
        if ($this->app->isDebug()) {
            $debugInfo = var_export($this->dumpArrayData($data), true);
            $this->app->log->record("template: {$template}", 'view');
            $this->app->log->record("assign: [{$debugInfo}]", 'view');
        }

        echo $this->blade->file($template, $data)->render();
    }

    /**
     * 渲染模板内容
     * @param string $template 模板内容
     * @param array  $data     模板变量
     * @return void
     */
    public function display(string $template, array $data = []): void
    {
        echo $this->blade->make($template, $data)->render();
    }

    /**
     * 自动定位模板文件
     * @param  string $template 模板文件规则
     * @return string
     */
    private function parseTemplate($template)
    {
        if (empty($this->config['view_base'])) {
            $this->config['view_base'] = $this->app->getRootPath() . 'view' . DIRECTORY_SEPARATOR;
        }

        $request = $this->app->request;

        // 获取视图根目录
        if (strpos($template, '@')) {
            // 跨模块调用
            list($app, $template) = explode('@', $template);
        }

        if ($this->config['view_path'] && !isset($app)) {
            $path = $this->config['view_path'];
        } else {
            $app = isset($app) ? $app : $request->app();
            // 基础视图目录
            $path = $this->config['view_base'] . ($app ? $app . DIRECTORY_SEPARATOR : '');
        }

        $depr = $this->config['view_depr'];

        if (0 !== strpos($template, '/')) {
            $template   = str_replace(['/', ':'], $depr, $template);
            $controller = $request->controller();
            if (strpos($controller, '.')) {
                $pos        = strrpos($controller, '.');
                $controller = substr($controller, 0, $pos) . '.' . Str::snake(substr($controller, $pos + 1));
            } else {
                $controller = Str::snake($controller);
            }

            if ($controller) {
                if ('' == $template) {
                    // 如果模板文件名为空 按照默认模板渲染规则定位
                    if (2 == $this->config['auto_rule']) {
                        $template = $request->action(true);
                    } elseif (3 == $this->config['auto_rule']) {
                        $template = $request->action();
                    } else {
                        $template = App::parseName($request->action());
                    }
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
                } elseif (false === strpos($template, $depr)) {
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
                }
            }
        } else {
            $template = str_replace(['/', ':'], $depr, substr($template, 1));
        }

        return $path . ltrim($template, '/') . '.' . ltrim($this->config['view_suffix'], '.');
    }

    /**
     * 配置模板引擎
     * @param array $config
     * @return mixed
     */
    public function config(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 获取模板引擎配置
     * @param  string  $name 参数名
     * @return mixed
     */
    public function getConfig(string $name)
    {
        return $this->config[$name];
    }

    public function __call($method, $params)
    {
        return call_user_func_array([$this->blade, $method], $params);
    }

    public function __debugInfo()
    {
        return ['config' => $this->config];
    }

    /**
     * 获取数组调试信息
     * @param array $data
     * @return array
     */
    private function dumpArrayData(array $data)
    {
        $debugInfo = array_map(function ($value) {
            if (is_object($value)) {
                if (method_exists($value, '__toString')) {
                    $value = (string) $value;
                    if (mb_strlen($value) > 36) {
                        $value = mb_substr((string) $value, 0, 36) . '...';
                    }
                } else {
                    $value = get_class($value) . '#' . hash('crc32', spl_object_hash($value));
                }
            } else {
                $value = print_r($value, true);
                if (mb_strlen($value) > 36) {
                    $value = mb_substr($value, 0, 36) . '...';
                }
            }

            return str_replace(PHP_EOL, 'LF', $value);
        }, $data);

        return $debugInfo;
    }
}
