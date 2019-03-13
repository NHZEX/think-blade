<?php
/**
 * Created by PhpStorm.
 * User: Auoor
 * Date: 2019/2/4
 * Time: 15:58
 */
declare(strict_types=1);

namespace nhzex\Blade\Blade;

use duncan3dc\Laravel\BladeInstance;
use Illuminate\Filesystem\Filesystem;
use think\App;
use think\Exception;

/**
 * Class Driver
 * @package nhzex\Blade\Blade
 * @mixin BladeInstance
 */
class Driver
{
    // 模板引擎实例
    /** @var BladeInstance */
    private $template;
    /** @var App */
    private $app;

    // 模板引擎参数
    protected $config = [
        // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写
        'auto_rule' => 1,
        // 视图基础目录（集中式）
        'view_base' => '',
        // 模板起始路径
        'view_path' => '',
        // 模板文件后缀
        'view_suffix' => 'blade.php',
        // 模板文件名分隔符
        'view_depr' => DIRECTORY_SEPARATOR,
        // 是否开启模板编译缓存,设为false则每次都会重新编译
        'tpl_cache' => true,
        // 缓存池路径
        'cache_path' => '',
        // 缓存池后缀
        'cache_prefix' => '',
    ];

    public function __construct(App $app, array $config = [])
    {
        $this->app = $app;
        $this->config['cache_path'] = $app->getRuntimePath() . 'temp/';
        $this->config = array_merge($this->config, $config);

        if (empty($this->config['view_path'])) {
            $this->config['view_path'] = $app->getAppPath() . 'view' . DIRECTORY_SEPARATOR;
        }

        if (empty($this->config['cache_path'])) {
            $this->config['cache_path'] = $app->getRuntimePath() . 'temp' . DIRECTORY_SEPARATOR;
        }

        $this->config(array_merge($config, $this->config));
        $this->boot();
    }

    public function boot(): void
    {
        $cache_path = $this->config['cache_path'] . $this->config['cache_prefix'];

        if (!$this->config['tpl_cache']) {
            (new Filesystem)->cleanDirectory($cache_path);
        }

        $this->template = new BladeInstance($this->config['view_base'], $cache_path);
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
     * @param array $data 模板变量
     * @return void
     * @throws Exception
     */
    public function fetch(string $template, array $data = []): void
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $template = $this->parseTemplate($template);
        }

        // 模板不存在 抛出异常
        if (!is_file($template)) {
            if (class_exists('\think\template\exception\TemplateNotFoundException')) {
                /** @noinspection PhpUndefinedNamespaceInspection, PhpUndefinedClassInspection */
                throw new \think\template\exception\TemplateNotFoundException('template not exists:' . $template, $template);
            } else {
                throw new Exception('template not exists:' . $template, 0);
            }
        }

        // 记录视图信息
        $this->app->log->alert('[ VIEW ] ' . $template . ' [ ' . var_export(array_keys($data), true) . ' ]');

        echo $this->template->file($template, $data)->render();
    }

    /**
     * 渲染模板内容
     * @param string $template 模板内容
     * @param array $data 模板变量
     * @return void
     */
    public function display(string $template, array $data = []): void
    {
        echo $this->template->make($template, $data)->render();
    }

    /**
     * 自动定位模板文件
     * @param  string $template 模板文件规则
     * @return string
     */
    private function parseTemplate($template): string
    {
        // 分析模板文件规则
        $request = $this->app['request'];

        // 获取视图根目录
        if (strpos($template, '@')) {
            // 跨模块调用
            list($app, $template) = explode('@', $template);
        }

        if ($this->config['view_base']) {
            // 基础视图目录
            $app = isset($app) ? $app : $request->app();
            $path = $this->config['view_base'] . ($app ? $app . DIRECTORY_SEPARATOR : '');
        } else {
            $path = isset($app)
                ? $this->app->getBasePath() . $app . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR
                : $this->config['view_path'];
        }

        $depr = $this->config['view_depr'];

        if (0 !== strpos($template, '/')) {
            $template = str_replace(['/', ':'], $depr, $template);
            $controller = App::parseName($request->controller());
            if ($controller) {
                if ('' == $template) {
                    // 如果模板文件名为空 按照默认规则定位
                    $template = (1 == $this->config['auto_rule']
                        ? App::parseName($request->action(true))
                        : $request->action());
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
     * @access public
     * @param  string $name 参数名
     * @return mixed
     */
    public function getConfig(string $name)
    {
        return $this->config[$name];
    }

    public function __call($method, $params)
    {
        return call_user_func_array([$this->template, $method], $params);
    }

    public function __debugInfo()
    {
        return ['config' => $this->config];
    }
}
