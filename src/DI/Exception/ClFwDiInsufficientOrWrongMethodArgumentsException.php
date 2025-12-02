<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI\Exception;

class ClFwDiInsufficientOrWrongMethodArgumentsException extends \ReflectionException implements ClFwExceptionInterface
{
    public function __construct(string $className, string $method, ?\ReflectionException $previous = null)
    {
        parent::__construct(
            message: sprintf(
                'Insufficient or wrong arguments for method %s for Class "%s"',
                $method,
                $className
            ),
            code: empty($previous?->getCode() ?? null) ? 500 : $previous->getCode(),
            previous: $previous
        );
    }
}