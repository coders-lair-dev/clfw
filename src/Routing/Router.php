<?php

namespace CodersLairDev\ClFw\Routing;

use CodersLairDev\ClFw\Http\Request\Request;
use CodersLairDev\ClFw\Routing\Attribute\AsController;
use CodersLairDev\ClFw\Routing\Attribute\AsRoute;
use CodersLairDev\ClFw\Routing\Trait\RequestUriTrait;
use CodersLairDev\ClFw\Routing\Trait\RouteTrait;

class Router
{
    use RouteTrait;
    use RequestUriTrait;

    private array $routes = [];

    public function collectRoutes(array $services): void
    {
        $this->routes = [];

        foreach ($services as $service) {
            $reflection = $this->getReflection($service);

            if (!$this->isController($reflection)) {
                continue;
            }

            $methods = $reflection->getMethods();
            foreach ($methods as $method) {
                $attributes = $method->getAttributes(AsRoute::class);

                foreach ($attributes as $attribute) {
                    $route = $this->createRouteOrNull($reflection, $method, $attribute);

                    if (empty($route)) {
                        continue;
                    }

                    $this->routes[$route->getPath()] = $route;
                }
            }
        }
    }

    private function getReflection(object $object): \ReflectionClass
    {
        return new \ReflectionClass($object);
    }

    private function isController(\ReflectionClass $reflection): bool
    {
        return !empty($reflection->getAttributes(AsController::class));
    }

    public function findRoute(Request $request): ?Route
    {
        $uri = $this->getRequestUri($request);

        return $this->routes[$uri] ?? null;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}