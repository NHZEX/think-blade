<?php
/**
 * Created by PhpStorm.
 * User: Auoor
 * Date: 2019/2/5
 * Time: 15:22
 */

namespace HZEX\BladeTest;

use Exception;
use Generator;
use HZEX\Blade\Driver;
use Illuminate\View\View;
use PHPUnit\Framework\TestCase;

class ViewBladeTest extends TestCase
{
    const CONFIG = [
        // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写
        'auto_rule' => 1,
        // 模板引擎类型 支持 php think 支持扩展
        'type' => Driver::class,
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

    /** @var \think\View */
    private $blade;
    /** @var Driver */
    private $engine;

    public static function setUpBeforeClass(): void
    {
        // 清理缓存
        array_map('unlink', glob(self::CONFIG['cache_path'] . '*'));
    }

    public function setUp(): void
    {
        $this->blade = new \think\View(self::CONFIG);
        $this->engine = $this->blade->engine;
    }

    /**
     * @throws Exception
     */
    public function testBasicFetch()
    {
        $result = $this->blade->fetch("view1");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view1.blade.php"), $result);
    }

    /**
     * @throws Exception
     */
    public function testParametersFetch()
    {
        $this->blade->assign(["title" => "Test Title"]);
        $result = $this->blade->fetch("view2");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view2.html"), $result);
    }

    public function testAltPath()
    {
        $this->engine->addPath(__DIR__ . "/views/alt");
        $result = $this->engine->render("view3");
        $this->assertSame(file_get_contents(__DIR__ . "/views/alt/view3.blade.php"), $result);
    }

    /**
     * @throws Exception
     */
    public function testUse()
    {
        $result = $this->blade->fetch("view5");
        $this->assertSame("stuff", trim($result));
    }

    /**
     * @throws Exception
     */
    public function testRawOutput()
    {
        $result = $this->blade->fetch("view6");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view6.html"), $result);
    }

    /**
     * @throws Exception
     */
    public function testEscapedOutput()
    {
        $result = $this->blade->fetch("view7");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view7.html"), $result);
    }

    /**
     * @throws Exception
     */
    public function testShare()
    {
        $this->blade->__set("shareData", "shared");
        $result = $this->blade->fetch("view8");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view8.html"), $result);
    }

    /**
     * @throws Exception
     */
    public function testComposer()
    {
        $this->engine->composer("*", function (View $view) {
            $view->with("items", ["One", "Two", "Three"]);
        });
        $result = $this->blade->fetch("view9");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view9.html"), $result);
    }

    /**
     * @throws Exception
     */
    public function testCreator()
    {
        $this->engine->creator("*", function (View $view) {
            $view->with("items", ["One", "Two", "Three"]);
        });
        $result = $this->blade->fetch("view9");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view9.html"), $result);
    }

    public function testExists1()
    {
        $this->assertTrue($this->blade->exists("view1"));
    }

    public function testDoesntExist()
    {
        $this->assertFalse($this->blade->exists("no-such-view"));
    }

    /**
     * @throws Exception
     */
    public function testInheritance()
    {
        $result = $this->blade->fetch("view10");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view10.html"), $result);
    }

    /**
     * @throws Exception
     */
    public function testInheritanceAltPath()
    {
        $this->engine->addPath(__DIR__ . "/views/alt");
        $result = $this->blade->fetch("view11");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view11.html"), $result);
    }

    /**
     * @throws Exception
     */
    public function testCustomCompiler()
    {
        $this->engine->extend(function ($value) {
            return str_replace("Original", "New", $value);
        });
        $result = $this->blade->fetch("view12");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view12.html"), $result);
    }

    /**
     * @throws Exception
     */
    public function testCustomDirective()
    {
        $this->engine->directive("normandie", function ($parameter) {
            $parameter = trim($parameter, "()");

            return "inguz({$parameter});";
        });
        $result = $this->blade->fetch("view13");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view13.html"), $result);
    }

    /**
     * @return Generator
     */
    public function customConditionProvider()
    {
        yield [false, "off"];
        yield [true, "on"];
    }

    /**
     * @dataProvider customConditionProvider
     * @param bool $global
     * @param string $expected
     * @throws Exception
     */
    public function testCustomConditions(bool $global, string $expected)
    {
        $this->markTestIncomplete('功能未实现');
        $this->engine->if("global", function () use ($global) {
            return $global;
        });
        $result = $this->blade->fetch("view14");
        $this->assertSame("{$expected}\n", $result);
    }

    /**
     * @dataProvider customConditionProvider
     * @param bool   $global
     * @param string $expected
     * @throws Exception
     */
    public function testCustomConditionArguments(bool $global, string $expected)
    {
        $this->markTestIncomplete('功能未实现');
        $this->engine->if("global", function (bool $global) {
            return $global;
        });
        $this->blade->assign([
            "global" => $global,
        ]);
        $result = $this->blade->fetch("view15");
        $this->assertSame("{$expected}\n", $result);
    }
}
