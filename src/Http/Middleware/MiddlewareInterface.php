<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Упрощённый middleware-контракт в духе PSR-15.
 *
 * Отличие от PSR-15: вместо RequestHandlerInterface принимаем callable $next.
 * Это убирает необходимость в дополнительной обёртке-handler'е и упрощает реализацию.
 * Концептуально и по сигнатуре полностью совместимо с PSR-15: при желании
 * перейти на стандарт достаточно обернуть $next в анонимный класс,
 * реализующий RequestHandlerInterface.
 */
interface MiddlewareInterface
{
//    TODO  переписать на использование полноценного PSR-15

    /**
     * @param callable(ServerRequestInterface): ResponseInterface $next
     */
    public function process(ServerRequestInterface $request, callable $next): ResponseInterface;
}