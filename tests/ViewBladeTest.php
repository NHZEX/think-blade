<?php
/**
 * Created by PhpStorm.
 * User: Auoor
 * Date: 2019/2/5
 * Time: 15:22
 */

namespace nhzex\BladeTest;

use Illuminate\View\View;

class ViewBladeTest extends TestBase
{
    /** @var \think\View */
    private $blade;
    /** @var \nhzex\Blade\Blade\Driver */
    private $engine;

    public function setUp()
    {
        $config = new \think\Config();
        $config->set(self::config, 'template');
        $this->blade = \think\Container::get('view', [$config], true);
        $this->engine = $this->blade->engine;
    }

    /**
     * @throws \Exception
     */
    public function testBasicFetch()
    {
        $result = $this->blade->fetch("view1");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view1.blade.php"), $result);
    }

    /**
     * @throws \Exception
     */
    public function testParametersFetch()
    {
        $result = $this->blade->fetch("view2", ["title" => "Test Title"]);
        $this->assertSame(file_get_contents(__DIR__ . "/views/view2.html"), $result);
    }

    /**
     *
     */
    public function testAltPath()
    {
        $this->engine->addPath(__DIR__ . "/views/alt");
        $result = $this->engine->render("view3");
        $this->assertSame(file_get_contents(__DIR__ . "/views/alt/view3.blade.php"), $result);
    }

    /**
     * @throws \Exception
     */
    public function testUse()
    {
        $result = $this->blade->fetch("view5");
        $this->assertSame("stuff", trim($result));
    }

    /**
     * @throws \Exception
     */
    public function testRawOutput()
    {
        $result = $this->blade->fetch("view6");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view6.html"), $result);
    }

    /**
     * @throws \Exception
     */
    public function testEscapedOutput()
    {
        $result = $this->blade->fetch("view7");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view7.html"), $result);
    }

    /**
     * @throws \Exception
     */
    public function testShare()
    {
        $this->blade->share("shareData", "shared");
        $result = $this->blade->fetch("view8");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view8.html"), $result);
    }

    /**
     * @throws \Exception
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
     * @throws \Exception
     */
    public function testCreator()
    {
        $this->engine->creator("*", function (View $view) {
            $view->with("items", ["One", "Two", "Three"]);
        });
        $result = $this->blade->fetch("view9");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view9.html"), $result);
    }

    /**
     *
     */
    public function testExists1()
    {
        $this->assertTrue($this->blade->exists("view1"));
    }

    /**
     *
     */
    public function testDoesntExist()
    {
        $this->assertFalse($this->blade->exists("no-such-view"));
    }

    /**
     * @throws \Exception
     */
    public function testInheritance()
    {
        $result = $this->blade->fetch("view10");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view10.html"), $result);
    }

    /**
     * @throws \Exception
     */
    public function testInheritanceAltPath()
    {
        $this->engine->addPath(__DIR__ . "/views/alt");
        $result = $this->blade->fetch("view11");
        $this->assertSame(file_get_contents(__DIR__ . "/views/view11.html"), $result);
    }

    /**
     * @throws \Exception
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
     * @throws \Exception
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
     * @return \Generator
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
     * @throws \Exception
     */
    public function testCustomConditions(bool $global, string $expected)
    {
        $this->engine->if("global", function () use ($global) {
            return $global;
        });
        $result = $this->blade->fetch("view14");
        $this->assertSame("{$expected}\n", $result);
    }

    /**
     * @dataProvider customConditionProvider
     */
    public function testCustomConditionArguments(bool $global, string $expected)
    {
        $this->engine->if("global", function (bool $global) {
            return $global;
        });
        $result = $this->blade->fetch("view15", [
            "global" => $global,
        ]);
        $this->assertSame("{$expected}\n", $result);
    }
}