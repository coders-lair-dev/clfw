<?php

namespace CodersLairDev\ClFw\Routing;

class Route
{
    public function __construct(
        private string $path,
        private string $controller,
        private string $method
    ) {
    }

    public static function create(string $path, string $controller, string $method): Route
    {
        return new Route($path, $controller, $method);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}