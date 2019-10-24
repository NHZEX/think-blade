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
    'type'         => '\HZEX\Blade\Driver',
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