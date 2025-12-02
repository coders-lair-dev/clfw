<?php

namespace CodersLairDev\ClFw\Routing\Trait;

use CodersLairDev\ClFw\Routing\Route;

trait RouteTrait
{
    private function createRouteOrNull(
        \ReflectionClass $reflection,
        \ReflectionMethod $method,
        \ReflectionAttribute $attribute
    ): ?Route {
        $path = $this->extractRoutePath($attribute);
        if (empty($path)) {
            return null;
        }

        return Route::create(
            path: $path,
            controller: $reflection->getName(),
            method: $method->getName(),
        );
    }

    private function extractRoutePath(\ReflectionAttribute $attribute): ?string
    {
        $arguments = $attribute->getArguments();
        return $arguments['path'] ?? null;
    }
}