<?php
/**
 * Created by PhpStorm.
 * User: Auoor
 * Date: 2019/2/5
 * Time: 15:22
 */

namespace HZEX\BladeTest;

use Exception;
use Illuminate\View\View;
use PHPUnit\Framework\TestCase;
use think\App;
use think\view\driver\Blade;
use Generator;

class ViewBladeTest extends TestCase
{
    public const CONFIG = [
        // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写
        'auto_rule' => 1,
        // 模板引擎类型 支持 php think 支持扩展
        'type' => Blade::class,
        // 视图目录名
        'view_dir_name' => 'views',
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
    private $view;
    /** @var Blade */
    private $bladeEngine;

    public static function setUpBeforeClass(): void
    {
        // 清理缓存
        array_map('unlink', glob(self::CONFIG['cache_path'] . '*'));
    }

    public function setUp(): void
    {
        $app = new App(__DIR__);
        $app->config->set(self::CONFIG, 'view');
        $this->view        = $app->make(\think\View::class);
        $this->bladeEngine = $this->view->engine();
    }

    public function tearDown(): void
    {
    }

    /**
     * @throws Exception
     */
    public function testBasicFetch()
    {
        $result = $this->view->fetch("view1");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view1.blade.php"), $result);
    }

    /**
     * @throws Exception
     */
    public function testParametersFetch()
    {
        $this->view->assign(["title" => "Test Title"]);
        $result = $this->view->fetch("view2");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view2.html"), $result);
    }

    public function testAltPath()
    {
        $result = $this->bladeEngine->render("alt/view3");
        $this->assertSame(file_get_contents(__DIR__ . "/views/alt/view3.blade.php"), $result);
    }

    /**
     * @throws Exception
     */
    public function testUse()
    {
        $result = $this->view->fetch("view5");
        $this->assertSame("stuff", trim($result));
    }

    /**
     * @throws Exception
     */
    public function testRawOutput()
    {
        $result = $this->view->fetch("view6");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view6.html"), $result);
    }

    /**
     * @throws Exception
     */
    public function testEscapedOutput()
    {
        $result = $this->view->fetch("view7");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view7.html"), $result);
    }

    /**
     * @throws Exception
     */
    public function testShare()
    {
        $this->view->__set("shareData", "shared");
        $result = $this->view->fetch("view8");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view8.html"), $result);
    }

    /**
     * @throws Exception
     */
    public function testComposer()
    {
        $this->markTestSkipped('事件模块未移植，功能不可用');
        $this->bladeEngine->composer("*", function (View $view) {
            $view->with("items", ["One", "Two", "Three"]);
        });
        $result = $this->view->fetch("view9");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view9.html"), $result);
    }

    /**
     * @throws Exception
     */
    public function testCreator()
    {
        $this->markTestSkipped('事件模块未移植，功能不可用');
        $this->bladeEngine->creator("*", function (View $view) {
            $view->with("items", ["One", "Two", "Three"]);
        });
        $result = $this->view->fetch("view9");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view9.html"), $result);
    }

    public function testExists1()
    {
        $this->assertTrue($this->view->exists("view1"));
    }

    public function testDoesntExist()
    {
        $this->assertFalse($this->view->exists("no-such-view"));
    }

    /**
     * @throws Exception
     */
    public function testInheritance()
    {
        $result = $this->view->fetch("view10");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view10.html"), $result);
    }

    /**
     * @throws Exception
     */
    public function testInheritanceAltPath()
    {
        // $this->engine->setViewDirName("views/alt");
        $result = $this->view->fetch("view11");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view11.html"), $result);
    }

    /**
     * @throws Exception
     */
    public function testCustomCompiler()
    {
        $this->bladeEngine->extend(function ($value) {
            return str_replace("Original", "New", $value);
        });
        $result = $this->view->fetch("view12");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view12.html"), $result);
    }

    /**
     * @throws Exception
     */
    public function testCustomDirective()
    {
        $this->bladeEngine->directive("normandie", function ($parameter) {
            $parameter = trim($parameter, "()");

            return "inguz({$parameter});";
        });
        $result = $this->view->fetch("view13");
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
    public function testCustomConditions(bool $constantVal, string $expected)
    {
        $this->bladeEngine->if("ConstantVal", function () use ($constantVal) {
            return $constantVal;
        });
        $result = $this->view->fetch("view14");
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
        // $this->markTestIncomplete('功能未实现');
        $this->bladeEngine->if("IfBool", function (bool $value) {
            return $value;
        });
        $this->view->assign([
            "global" => $global,
        ]);
        $result = $this->view->fetch("view15");
        $this->assertSame("{$expected}\n", $result);
    }

    /**
     * Ensure we support the basic PHP engine.
     */
    public function testRender1(): void
    {
        $result = $this->view->fetch("view17", ["title" => "Test Title"]);
        $this->assertSame(file_get_contents(__DIR__ . "/views/view17.html"), $result);
    }
}
