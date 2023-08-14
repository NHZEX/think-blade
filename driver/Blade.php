<?php

declare(strict_types=1);

namespace think\view\driver;

use think\App;
use think\contract\TemplateHandlerInterface;
use think\helper\Str;
use think\template\exception\TemplateNotFoundException;
use Zxin\Think\Blade\BladeViewService;
use Zxin\Think\Blade\ViewFactory;
use RuntimeException;

use function call_user_func_array;
use function is_object;
use function get_class;

class Blade implements TemplateHandlerInterface
{
    private ViewFactory $view;

    private App $app;

    /**
     * @var array<string, mixed>
     */
    protected array $config = [
        // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
        'auto_rule' => 1,
        // 视图目录名
        'view_dir_name' => 'view',
        // 模板起始路径
        'view_path' => '',
        // 模板文件后缀
        'view_suffix' => 'blade.php',
        // 模板文件名分隔符
        'view_depr' => DIRECTORY_SEPARATOR,
        // 缓存路径
        'cache_path' => '',
        // 编译缓存
        'tpl_cache' => true,
        // 启用模板调试
        'view_debug' => null,
    ];

    public function __construct(App $app, array $config = [])
    {
        $this->app = $app;
        $this->config = array_merge($this->config, $config);

        if (empty($this->config['cache_path'])) {
            $this->config['cache_path'] = $app->getRuntimePath().'temp'.DIRECTORY_SEPARATOR;
        }

        $this->config(array_merge($config, $this->config));
        $this->boot();
    }

    public function boot(): void
    {
        $this->app->instance('view.blade.driver', $this);
        $this->app->register(BladeViewService::class);

        /** @var ViewFactory $viewFactory */
        $viewFactory = $this->app->get('view.blade');

        $this->view = $viewFactory;
    }

    /**
     * 检测是否存在模板文件
     *
     * @param  string  $template 模板文件或者模板规则
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
     *
     * @param  string  $template 模板文件
     * @param  array  $data     模板变量
     */
    public function fetch(string $template, array $data = []): void
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $template = $this->parseTemplate($template);
        }
        // 模板不存在 抛出异常
        if (!is_file($template)) {
            if (class_exists(TemplateNotFoundException::class)) {
                throw new TemplateNotFoundException('template not exists:'.$template, $template);
            } else {
                throw new RuntimeException('template not exists:'.$template, 0);
            }
        }
        $this->debugViewRender($template, $data);

        echo $this->view->file($template, $data)->render();
    }

    /**
     * 渲染模板内容
     */
    public function display(string $content, array $data = []): void
    {
        $this->debugViewRender('__content', $data);

        echo $this->view->make($content, $data)->render();
    }

    protected function debugViewRender(string $template, array $data): void
    {
        // 记录视图信息
        if (($this->config['view_debug'] ?? false) !== false && $this->app->isDebug()) {
            $debugInfo = var_export($this->dumpArrayData($data), true);
            $this->app->log->record("template: {$template}", 'view');
            $this->app->log->record("assign: [{$debugInfo}]", 'view');
        }
    }

    /**
     * 自动定位模板文件
     *
     * @param  string  $template 模板文件规则
     * @return string
     */
    public function parseTemplate(string $template): string
    {
        $request = $this->app->request;

        // 获取视图根目录
        if (strpos($template, '@')) {
            // 跨模块调用
            [$app, $template] = explode('@', $template);
        }

        if ($this->config['view_path'] && !isset($app)) {
            $path = $this->config['view_path'];
        } else {
            $appName = $app ?? $this->app->http->getName();
            $view = $this->config['view_dir_name'];

            if (is_dir($this->app->getAppPath().$view)) {
                $path = isset($app) ?
                    $this->app->getBasePath().(
                        $appName ? $appName.DIRECTORY_SEPARATOR : ''
                    ).$view.DIRECTORY_SEPARATOR :
                    $this->app->getAppPath().$view.DIRECTORY_SEPARATOR;
            } else {
                $path = $this->app->getRootPath().$view.DIRECTORY_SEPARATOR.(
                    $appName ? $appName.DIRECTORY_SEPARATOR : ''
                );
            }
        }

        $depr = $this->config['view_depr'];

        if (0 !== strpos($template, '/')) {
            $template = str_replace(['/', ':'], $depr, $template);
            $controller = $request->controller();

            if (strpos($controller, '.')) {
                $pos = strrpos($controller, '.');
                $controller = substr($controller, 0, $pos).'.'.Str::snake(substr($controller, $pos + 1));
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
                        $template = Str::snake($request->action());
                    }
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller).$depr.$template;
                } elseif (false === strpos($template, $depr)) {
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller).$depr.$template;
                }
            }
        } else {
            $template = str_replace(['/', ':'], $depr, substr($template, 1));
        }

        return $path.ltrim($template, '/').'.'.ltrim($this->config['view_suffix'], '.');
    }

    /**
     * 配置模板引擎
     */
    public function config(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 获取模板引擎配置
     */
    public function getConfig(string $name): mixed
    {
        return $this->config[$name];
    }

    public function __call(string $method, $params): mixed
    {
        return call_user_func_array([$this->view, $method], $params);
    }

    public function __debugInfo()
    {
        return ['config' => $this->config];
    }

    /**
     * 获取数组调试信息
     */
    private function dumpArrayData(array $data): array
    {
        $debugInfo = array_map(function ($value) {
            if (is_object($value)) {
                if (method_exists($value, '__toString')) {
                    $value = (string) $value;
                    if (mb_strlen($value) > 36) {
                        $value = mb_substr((string) $value, 0, 36).'...';
                    }
                } else {
                    $value = get_class($value).'#'.\spl_object_id($value);
                }
            } else {
                $value = print_r($value, true);
                if (mb_strlen($value) > 36) {
                    $value = mb_substr($value, 0, 36).'...';
                }
            }

            return str_replace(PHP_EOL, 'LF', $value);
        }, $data);

        return $debugInfo;
    }
}
