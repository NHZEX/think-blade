<?php

namespace nhzex\BladeTest;

use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: Auoor
 * Date: 2019/2/5
 * Time: 15:23
 */

abstract class TestBase extends TestCase
{

    const config = [
        // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写
        'auto_rule' => 1,
        // 模板引擎类型 支持 php think 支持扩展
        'type' => \nhzex\Blade\Blade\Driver::class,
        // 视图基础目录，配置目录为所有模块的视图起始目录
        'view_base' => __DIR__ . '/views/',
        // 当前模板的视图目录 留空为自动获取
        'view_path' => '',
        // 模板后缀
        'view_suffix' => 'blade.php',
        // 模板文件名分隔符
        'view_depr' => DIRECTORY_SEPARATOR,
        // 是否开启模板编译缓存,设为false则每次都会重新编译
        'tpl_cache' => false,
        // 缓存生成位置
        'cache_path' => __DIR__ . '/../runtime/temp/',
    ];

}