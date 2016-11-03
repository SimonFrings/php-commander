<?php

use Clue\Commander\Router;

class RouterTest extends PHPUnit_Framework_TestCase
{
    public function testEmptyRouterHasNoRoutes()
    {
        $router = new Router();

        $this->assertEquals(array(), $router->getRoutes());
    }

    public function testAddRouteReturnRoute()
    {
        $router = new Router();

        $route = $router->add('hello', function () { });

        $this->assertInstanceOf('Clue\Commander\Route', $route);
        $this->assertEquals(array($route), $router->getRoutes());
    }

    public static function provideMatchingRoutes()
    {
        return array(
            'single word' => array(
                'hello',
                array('hello'),
                array()
            ),
            'empty route' => array(
                '',
                array(),
                array()
            ),
            'word with argument' => array(
                'hello <name>',
                array('hello', 'clue'),
                array('name' => 'clue')
            ),
            'word with optional argument' => array(
                'hello [<name>]',
                array('hello', 'clue'),
                array('name' => 'clue')
            ),
            'word without optional argument' => array(
                'hello [<name>]',
                array('hello'),
                array()
            ),
            'word with ellipse arguments' => array(
                'hello <names>...',
                array('hello', 'first', 'second'),
                array('names' => array('first', 'second'))
            ),
            'word without optional ellipse arguments' => array(
                'hello [<names>...]',
                array('hello'),
                array()
            ),
            'word with optional long option' => array(
                'hello [--test]',
                array('hello', '--test'),
                array('test' => false)
            ),
            'word without optional long option' => array(
                'hello [--test]',
                array('hello'),
                array()
            ),
            'word without optional long option first' => array(
                '[--test] hello',
                array('hello'),
                array()
            ),
            'word with optional long option first' => array(
                '[--test] hello',
                array('hello', '--test'),
                array('test' => false)
            ),
            'word with optional short option' => array(
                'hello [-i]',
                array('hello', '-i'),
                array('i' => false)
            ),
            'word without optional short option' => array(
                'hello [-i]',
                array('hello'),
                array()
            ),
            'word with optional long and short option before words' => array(
                'hello [--test] [-i]',
                array('-i', '--test', 'hello'),
                array('test' => false, 'i' => false)
            ),
            'word with argument ignores double dash' => array(
                'hello <name>',
                array('hello', '--', 'clue'),
                array('name' => 'clue')
            ),
            'word with argument starting with dash' => array(
                'hello <name>',
                array('hello', '--', '-nobody-'),
                array('name' => '-nobody-')
            ),
            'word without optional argument ignores double dash' => array(
                'hello [<name>]',
                array('hello', '--'),
                array()
            ),

            'word with required long option with required value' => array(
                'hello --name=<yes>',
                array('hello', '--name=demo'),
                array('name' => 'demo'),
            ),
            'word with required long option with optional value' => array(
                'hello --name[=<yes>]',
                array('hello', '--name=demo'),
                array('name' => 'demo'),
            ),
            'word with required long option witout optional value' => array(
                'hello --name[=<yes>]',
                array('hello', '--name'),
                array('name' => false),
            ),

            'word with required short option with required value' => array(
                'hello -i=<n>',
                array('hello', '-i=demo'),
                array('i' => 'demo'),
            ),
            'word with required short option with required value concatenated' => array(
                'hello -i=<n>',
                array('hello', '-idemo'),
                array('i' => 'demo'),
            ),
            'word with required short option with optional value' => array(
                'hello -i[=<n>]',
                array('hello', '-i=demo'),
                array('i' => 'demo'),
            ),
            'word with required short option without optional value' => array(
                'hello -i[=<n>]',
                array('hello', '-i'),
                array('i' => false),
            ),
        );
    }

    /**
     * @dataProvider provideMatchingRoutes
     * @param string $route
     * @param array $args
     * @param array $expected
     */
    public function testHandleRouteMatches($route, $args, $expected)
    {
        $router = new Router();

        $invoked = null;
        $router->add($route, function ($args) use (&$invoked) {
            $invoked = $args;
        });

        $this->assertNull($invoked);

        $router->handleArgs($args);

        $this->assertEquals($expected, $invoked);
    }

    public function provideNonMatchingRoutes()
    {
        return array(
            'other word' => array(
                'hello',
                array('test')
            ),
            'excesive arguments' => array(
                'hello',
                array('hello', 'world')
            ),
            'word in sentence missing' => array(
                'hello world',
                array('hello')
            ),
            'argument missing' => array(
                'hello <name>',
                array('hello')
            ),
            'without ellipse arguments' => array(
                'hello <names>...',
                array('hello')
            ),
            'with keyword after ellipses never matches' => array(
                'hello <names>... test',
                array('hello', 'a', 'b', 'test')
            ),
            'without keyword after optional keyword' => array(
                'hello [user] user',
                array('hello', 'user')
            ),
            'without long option' => array(
                'hello [--test]',
                array('hello', 'test')
            ),
            'without short option' => array(
                'hello [-i]',
                array('hello', 'test')
            ),
            'with option instead of argument' => array(
                'hello <name>',
                array('hello', '--test')
            ),
            'with explicit dash argument instead of option' => array(
                'hello [--test]',
                array('hello', '--', '--test')
            ),
            'invalid word in sentence' => array(
                'hello word [<any>]',
                array('hello', 'not', 'word')
            ),

            'invalid long option shares same prefix' => array(
                'test --option',
                array('test', '--options')
            ),
            'value passed to long option but does not accept value' => array(
                'test --option',
                array('test', '--option=value')
            ),
            'without required long option value' => array(
                'test --option=<value>',
                array('test', '--option')
            ),

            'short option shares same prefix but does not accept value' => array(
                'test -i',
                array('test', '-ix')
            ),
            'without required short option value' => array(
                'test -i=<value>',
                array('test', '-i')
            ),
        );
    }

    /**
     * @dataProvider provideNonMatchingRoutes
     * @expectedException Clue\Commander\NoRouteFoundException
     * @param string $route
     * @param array  $args
     */
    public function testHandleRouteDoesNotMatch($route, $args)
    {
        $router = new Router();
        $router->add($route, 'var_dump');

        $router->handleArgs($args);
    }

    public function testAddRouteCanBeRemoved()
    {
        $router = new Router();

        $route = $router->add('hello', function () { });

        $router->remove($route);

        $this->assertEquals(array(), $router->getRoutes());
    }

    /**
     * @expectedException UnderflowException
     */
    public function testCanNotRemoveRouteWhichHasNotBeenAdded()
    {
        $router = new Router();
        $route = $router->add('hello', function () { });

        $router2 = new Router();
        $router2->remove($route);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddRouteThrowsForInvalidHandler()
    {
        $router = new Router();

        $router->add('hello', 'invalid');
    }

    public function testHandleArgvAddedRouteEmpty()
    {
        $router = new Router();

        $invoked = null;
        $router->add('hello', function ($args) use (&$invoked) {
            $invoked = $args;
        });

        $this->assertNull($invoked);

        $router->handleArgv(array('ignored', 'hello'));

        $this->assertEquals(array(), $invoked);
    }

    public function testHandleArgvFromGlobalsAddedRouteEmpty()
    {
        $router = new Router();

        $invoked = null;
        $router->add('hello', function ($args) use (&$invoked) {
            $invoked = $args;
        });

        $this->assertNull($invoked);

        $orig = $_SERVER['argv'];
        $_SERVER['argv'] = array('ignored', 'hello');
        $router->handleArgv();
        $_SERVER['argv'] = $orig;

        $this->assertEquals(array(), $invoked);
    }

    public function testExecArgvAddedRouteEmpty()
    {
        $router = new Router();

        $invoked = null;
        $router->add('hello', function ($args) use (&$invoked) {
            $invoked = $args;
            return 2;
        });

        $this->assertNull($invoked);

        $ret = $router->execArgv(array('ignored', 'hello'));

        $this->assertEquals(array(), $invoked);

        $this->assertNull($ret);
    }

    /**
     * @expectedException Clue\Commander\NoRouteFoundException
     */
    public function testHandleEmptyRouterThrowsUnderflowException()
    {
        $router = new Router();

        $router->handleArgs(array());
    }
}
