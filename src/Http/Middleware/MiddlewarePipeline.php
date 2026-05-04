<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Собирает middleware в цепочку и оборачивает финальный обработчик.
 * Сервис создаётся контейнером автоматически и наполняется в bootstrap-фазе.
 */
final class MiddlewarePipeline
{
    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

    public function add(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @param ServerRequestInterface $request
     * @param callable(ServerRequestInterface): ResponseInterface $finalHandler
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request, callable $finalHandler): ResponseInterface
    {
        $next = $finalHandler;

        foreach (array_reverse($this->middlewares) as $middleware) {
            $current = $next;
            $next = static fn(ServerRequestInterface $req): ResponseInterface => $middleware->process($req, $current);
        }

        return $next($request);
    }
}