<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Routing\Route\RouteFactory;
use WellRESTed\Routing\Route\RouteFactoryInterface;
use WellRESTed\Routing\Route\RouteInterface;

class RouteMap implements RouteMapInterface
{
    /** @var RouteFactoryInterface */
    private $factory;
    /** @var RouteInterface[] Array of Route objects */
    private $routes;
    /** @var RouteInterface[] Hash array mapping exact paths to routes */
    private $staticRoutes;
    /** @var RouteInterface[] Hash array mapping path prefixes to routes */
    private $prefixRoutes;
    /** @var RouteInterface[] Hash array mapping path prefixes to routes */
    private $patternRoutes;

    public function __construct()
    {
        $this->factory = $this->getRouteFactory();
        $this->routes = [];
        $this->staticRoutes = [];
        $this->prefixRoutes = [];
        $this->patternRoutes = [];
    }

    /**
     * Register middleware with the router for a given path and method.
     *
     * $method may be:
     * - A single verb ("GET"),
     * - A comma-separated list of verbs ("GET,PUT,DELETE")
     * - "*" to indicate any method.
     * @see MethodMapInterface::setMethod
     *
     * $target may be:
     * - An exact path (e.g., "/path/")
     * - An prefix path ending with "*"" ("/path/*"")
     * - A URI template with variables enclosed in "{}" ("/path/{id}")
     * - A regular expression ("~/cat/([0-9]+)~")
     *
     * $middleware may be:
     * - An instance implementing MiddlewareInterface
     * - A string containing the fully qualified class name of a class
     *     implementing MiddlewareInterface
     * - A callable that returns an instance implementing MiddleInterface
     * - A callable maching the signature of MiddlewareInteraface::dispatch
     * @see DispatchedInterface::dispatch
     *
     * @param string $target Request target or pattern to match
     * @param string $method HTTP method(s) to match
     * @param mixed $middleware Middleware to dispatch
     */
    public function add($method, $target, $middleware)
    {
        $route = $this->getRouteForTarget($target);
        $route->getMethodMap()->setMethod($method, $middleware);
    }

    public function dispatch(ServerRequestInterface $request, ResponseInterface &$response)
    {
        $requestTarget = $request->getRequestTarget();

        $route = $this->getStaticRoute($requestTarget);
        if ($route) {
            $route->dispatch($request, $response);
            return;
        }

        $route = $this->getPrefixRoute($requestTarget);
        if ($route) {
            $route->dispatch($request, $response);
            return;
        }

    }

    /**
     * @return RouteFactoryInterface
     */
    protected function getRouteFactory()
    {
        return new RouteFactory();
    }

    /**
     * Return the route for a given target.
     *
     * @param $target
     * @return RouteInterface
     */
    private function getRouteForTarget($target)
    {
        if (isset($this->routes[$target])) {
            $route = $this->routes[$target];
        } else {
            $route = $this->factory->create($target);
            $this->registerRouteForTarget($route, $target);
        }
        return $route;
    }

    private function registerRouteForTarget($route, $target)
    {
        // Store the route to the hash indexed by original target.
        $this->routes[$target] = $route;

        // Store the route to the array of routes for its type.
        switch ($route->getType()) {
            case RouteInterface::TYPE_STATIC:
                $this->staticRoutes[$route->getTarget()] = $route;
                break;
            case RouteInterface::TYPE_PREFIX:
                $this->prefixRoutes[rtrim($route->getTarget(), "*")] = $route;
                break;
            case RouteInterface::TYPE_PATTERN:
                $this->patternRoutes[] = $route;
                break;
        }
    }

    private function getStaticRoute($requestTarget)
    {
        if (isset($this->staticRoutes[$requestTarget])) {
            return $this->staticRoutes[$requestTarget];
        }
        return null;
    }

    private function getPrefixRoute($requestTarget)
    {
        // Find all prefixes that match the start of this path.
        $prefixes = array_keys($this->prefixRoutes);
        $matches = array_filter(
            $prefixes,
            function ($prefix) use ($requestTarget) {
                return (strrpos($requestTarget, $prefix, -strlen($requestTarget)) !== false);
            }
        );

        if ($matches) {
            if (count($matches) > 0) {
                // If there are multiple matches, sort them to find the one with the longest string length.
                $compareByLength = function ($a, $b) {
                    return strlen($b) - strlen($a);
                };
                usort($matches, $compareByLength);
            }
            $route = $this->prefixRoutes[$matches[0]];
            return $route;
        }
        return null;
    }
}