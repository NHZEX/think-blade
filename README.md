# Think-Blade
基于 laravel 5.8 封装用于 ThinkPHP 的视图渲染驱动

## Installation
```
composer require nhzex/think-blade
```

#### view.php
```php
[
    // 模板引擎类型
    'type'         => 'blade',
    // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
    'auto_rule'    => 1,
    // 模板目录名
    'view_dir_name'=> 'view',
    // 模板后缀
    'view_suffix'  => 'blade.php',
    // 模板文件名分隔符
    'view_depr'    => DIRECTORY_SEPARATOR,
]
```

### 鉴权支持 (需自行实现并绑定到以下命名)
- app->bind('auth')
  - auth->check(): bool
  - auth->guest(): bool
- app->bind('auth.gate')
  - auth->check($abilities, $arguments): bool
  - auth->denies($abilities, $arguments): bool
  - auth->any($abilities, $arguments): bool

## 代码引用
- [duncan3dc/blade](https://github.com/duncan3dc/blade)
- [illuminate/view](https://github.com/illuminate/view)