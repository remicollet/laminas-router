<?php

/**
 * @see       https://github.com/laminas/laminas-router for the canonical source repository
 * @copyright https://github.com/laminas/laminas-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-router/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Router\Http;

use ArrayObject;
use Laminas\Http\Request as Request;
use Laminas\Mvc\Router\Http\Part;
use Laminas\Mvc\Router\RoutePluginManager;
use Laminas\Stdlib\Parameters;
use Laminas\Stdlib\Request as BaseRequest;
use LaminasTest\Mvc\Router\FactoryTester;
use PHPUnit_Framework_TestCase as TestCase;

class PartTest extends TestCase
{
    public static function getRoute()
    {
        $routePlugins = new RoutePluginManager();
        $routePlugins->setInvokableClass('part', 'Laminas\Mvc\Router\Http\Part');

        return new Part(
            [
                'type'    => 'Laminas\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/foo',
                    'defaults' => [
                        'controller' => 'foo'
                    ]
                ]
            ],
            true,
            $routePlugins,
            [
                'bar' => [
                    'type'    => 'Laminas\Mvc\Router\Http\Literal',
                    'options' => [
                        'route'    => '/bar',
                        'defaults' => [
                            'controller' => 'bar'
                        ]
                    ]
                ],
                'baz' => [
                    'type'    => 'Laminas\Mvc\Router\Http\Literal',
                    'options' => [
                        'route' => '/baz'
                    ],
                    'child_routes' => [
                        'bat' => [
                            'type'    => 'Laminas\Mvc\Router\Http\Segment',
                            'options' => [
                                'route' => '/:controller'
                            ],
                            'may_terminate' => true,
                            'child_routes'  => [
                                'wildcard' => [
                                    'type' => 'Laminas\Mvc\Router\Http\Wildcard'
                                ]
                            ]
                        ]
                    ]
                ],
                'bat' => [
                    'type'    => 'Laminas\Mvc\Router\Http\Segment',
                    'options' => [
                        'route'    => '/bat[/:foo]',
                        'defaults' => [
                            'foo' => 'bar'
                        ]
                    ],
                    'may_terminate' => true,
                    'child_routes'  => [
                        'literal' => [
                            'type'   => 'Laminas\Mvc\Router\Http\Literal',
                            'options' => [
                                'route' => '/bar'
                            ]
                        ],
                        'optional' => [
                            'type'   => 'Laminas\Mvc\Router\Http\Segment',
                            'options' => [
                                'route' => '/bat[/:bar]'
                            ]
                        ],
                    ]
                ]
            ]
        );
    }

    public static function getRouteAlternative()
    {
        $routePlugins = new RoutePluginManager();
        $routePlugins->setInvokableClass('part', 'Laminas\Mvc\Router\Http\Part');

        return new Part(
            [
                'type' => 'Laminas\Mvc\Router\Http\Segment',
                'options' => [
                    'route' => '/[:controller[/:action]]',
                    'defaults' => [
                        'controller' => 'fo-fo',
                        'action' => 'index'
                    ]
                ]
            ],
            true,
            $routePlugins,
            [
                'wildcard' => [
                    'type' => 'Laminas\Mvc\Router\Http\Wildcard',
                    'options' => [
                        'key_value_delimiter' => '/',
                        'param_delimiter' => '/'
                    ]
                ],
                /*
                'query' => array(
                    'type' => 'Laminas\Mvc\Router\Http\Query',
                    'options' => array(
                        'key_value_delimiter' => '=',
                        'param_delimiter' => '&'
                    )
                )
                */
            ]
        );
    }

    public static function routeProvider()
    {
        return [
            'simple-match' => [
                self::getRoute(),
                '/foo',
                null,
                null,
                ['controller' => 'foo']
            ],
            'offset-skips-beginning' => [
                self::getRoute(),
                '/bar/foo',
                4,
                null,
                ['controller' => 'foo']
            ],
            'simple-child-match' => [
                self::getRoute(),
                '/foo/bar',
                null,
                'bar',
                ['controller' => 'bar']
            ],
            'offset-does-not-enable-partial-matching' => [
                self::getRoute(),
                '/foo/foo',
                null,
                null,
                null
            ],
            'offset-does-not-enable-partial-matching-in-child' => [
                self::getRoute(),
                '/foo/bar/baz',
                null,
                null,
                null
            ],
            'non-terminating-part-does-not-match' => [
                self::getRoute(),
                '/foo/baz',
                null,
                null,
                null
            ],
            'child-of-non-terminating-part-does-match' => [
                self::getRoute(),
                '/foo/baz/bat',
                null,
                'baz/bat',
                ['controller' => 'bat']
            ],
            'parameters-are-used-only-once' => [
                self::getRoute(),
                '/foo/baz/wildcard/foo/bar',
                null,
                'baz/bat/wildcard',
                ['controller' => 'wildcard', 'foo' => 'bar']
            ],
            'optional-parameters-are-dropped-without-child' => [
                self::getRoute(),
                '/foo/bat',
                null,
                'bat',
                ['foo' => 'bar']
            ],
            'optional-parameters-are-not-dropped-with-child' => [
                self::getRoute(),
                '/foo/bat/bar/bar',
                null,
                'bat/literal',
                ['foo' => 'bar']
            ],
            'optional-parameters-not-required-in-last-part' => [
                self::getRoute(),
                '/foo/bat/bar/bat',
                null,
                'bat/optional',
                ['foo' => 'bar']
            ],
            'simple-match' => [
                self::getRouteAlternative(),
                '/',
                null,
                null,
                [
                    'controller' => 'fo-fo',
                    'action' => 'index'
                ]
            ],
            'match-wildcard' => [
                self::getRouteAlternative(),
                '/fo-fo/index/param1/value1',
                null,
                'wildcard',
                [
                        'controller' => 'fo-fo',
                        'action' => 'index',
                        'param1' => 'value1'
                ]
            ],
            /*
            'match-query' => array(
                self::getRouteAlternative(),
                '/fo-fo/index?param1=value1',
                0,
                'query',
                array(
                    'controller' => 'fo-fo',
                    'action' => 'index'
                )
            )
            */
        ];
    }

    /**
     * @dataProvider routeProvider
     * @param        Part    $route
     * @param        string  $path
     * @param        integer $offset
     * @param        string  $routeName
     * @param        array   $params
     */
    public function testMatching(Part $route, $path, $offset, $routeName, array $params = null)
    {
        $request = new Request();
        $request->setUri('http://example.com' . $path);
        $match = $route->match($request, $offset);

        if ($params === null) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf('Laminas\Mvc\Router\Http\RouteMatch', $match);

            if ($offset === null) {
                $this->assertEquals(strlen($path), $match->getLength());
            }

            $this->assertEquals($routeName, $match->getMatchedRouteName());

            foreach ($params as $key => $value) {
                $this->assertEquals($value, $match->getParam($key));
            }
        }
    }

    /**
     * @dataProvider routeProvider
     * @param        Part    $route
     * @param        string  $path
     * @param        integer $offset
     * @param        string  $routeName
     * @param        array   $params
     */
    public function testAssembling(Part $route, $path, $offset, $routeName, array $params = null)
    {
        if ($params === null) {
            // Data which will not match are not tested for assembling.
            return;
        }

        $result = $route->assemble($params, ['name' => $routeName]);

        if ($offset !== null) {
            $this->assertEquals($offset, strpos($path, $result, $offset));
        } else {
            $this->assertEquals($path, $result);
        }
    }

    public function testAssembleNonTerminatedRoute()
    {
        $this->setExpectedException('Laminas\Mvc\Router\Exception\RuntimeException', 'Part route may not terminate');
        self::getRoute()->assemble([], ['name' => 'baz']);
    }

    public function testBaseRouteMayNotBePartRoute()
    {
        $this->setExpectedException('Laminas\Mvc\Router\Exception\InvalidArgumentException', 'Base route may not be a part route');

        $route = new Part(self::getRoute(), true, new RoutePluginManager());
    }

    public function testNoMatchWithoutUriMethod()
    {
        $route   = self::getRoute();
        $request = new BaseRequest();

        $this->assertNull($route->match($request));
    }

    public function testGetAssembledParams()
    {
        $route = self::getRoute();
        $route->assemble(['controller' => 'foo'], ['name' => 'baz/bat']);

        $this->assertEquals([], $route->getAssembledParams());
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            'Laminas\Mvc\Router\Http\Part',
            [
                'route'         => 'Missing "route" in options array',
                'route_plugins' => 'Missing "route_plugins" in options array'
            ],
            [
                'route'         => new \Laminas\Mvc\Router\Http\Literal('/foo'),
                'route_plugins' => new RoutePluginManager()
            ]
        );
    }

    /**
     * @group Laminas-105
     */
    public function testFactoryShouldAcceptTraversableChildRoutes()
    {
        $children = new ArrayObject([
            'create' => [
                'type'    => 'Literal',
                'options' => [
                    'route' => 'create',
                    'defaults' => [
                        'controller' => 'user-admin',
                        'action'     => 'edit',
                    ],
                ],
            ],
        ]);
        $options = [
            'route'        => [
                'type' => 'Laminas\Mvc\Router\Http\Literal',
                'options' => [
                    'route' => '/admin/users',
                    'defaults' => [
                        'controller' => 'Admin\UserController',
                        'action'     => 'index',
                    ],
                ],
            ],
            'route_plugins' => new RoutePluginManager(),
            'may_terminate' => true,
            'child_routes'  => $children,
        ];

        $route = Part::factory($options);
        $this->assertInstanceOf('Laminas\Mvc\Router\Http\Part', $route);
    }

    /**
     * @group 3711
     */
    public function testPartRouteMarkedAsMayTerminateCanMatchWhenQueryStringPresent()
    {
        $options = [
            'route' => [
                'type' => 'Laminas\Mvc\Router\Http\Literal',
                'options' => [
                    'route' => '/resource',
                    'defaults' => [
                        'controller' => 'ResourceController',
                        'action'     => 'resource',
                    ],
                ],
            ],
            'route_plugins' => new RoutePluginManager(),
            'may_terminate' => true,
            'child_routes'  => [
                'child' => [
                    'type' => 'Laminas\Mvc\Router\Http\Literal',
                    'options' => [
                        'route' => '/child',
                        'defaults' => [
                            'action' => 'child',
                        ],
                    ],
                ],
            ],
        ];

        $route = Part::factory($options);
        $request = new Request();
        $request->setUri('http://example.com/resource?foo=bar');
        $query = new Parameters(['foo' => 'bar']);
        $request->setQuery($query);
        $query = $request->getQuery();

        $match = $route->match($request);
        $this->assertInstanceOf('Laminas\Mvc\Router\RouteMatch', $match);
        $this->assertEquals('resource', $match->getParam('action'));
    }

    /**
     * @group 3711
     */
    public function testPartRouteMarkedAsMayTerminateButWithQueryRouteChildWillMatchChildRoute()
    {
        $options = [
            'route' => [
                'type' => 'Laminas\Mvc\Router\Http\Literal',
                'options' => [
                    'route' => '/resource',
                    'defaults' => [
                        'controller' => 'ResourceController',
                        'action'     => 'resource',
                    ],
                ],
            ],
            'route_plugins' => new RoutePluginManager(),
            'may_terminate' => true,
            /*
            'child_routes'  => array(
                'query' => array(
                    'type' => 'Laminas\Mvc\Router\Http\Query',
                    'options' => array(
                        'defaults' => array(
                            'query' => 'string',
                        ),
                    ),
                ),
            ),
            */
        ];

        $route = Part::factory($options);
        $request = new Request();
        $request->setUri('http://example.com/resource?foo=bar');
        $query = new Parameters(['foo' => 'bar']);
        $request->setQuery($query);
        $query = $request->getQuery();

        /*
        $match = $route->match($request);
        $this->assertInstanceOf('Laminas\Mvc\Router\RouteMatch', $match);
        $this->assertEquals('string', $match->getParam('query'));
        */
    }
}
