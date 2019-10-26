<?php
declare (strict_types = 1);

namespace Illuminate\Support\Facades;

use think\Facade;

/**
 * Blade facade
 */
class Blade extends Facade
{
    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'blade.compiler';
    }
}
