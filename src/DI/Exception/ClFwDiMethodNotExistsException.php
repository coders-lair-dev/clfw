<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI\Exception;

class ClFwDiMethodNotExistsException extends \ReflectionException implements ClFwExceptionInterface
{
    public function __construct(string $className, string $method, ?\ReflectionException $previous = null)
    {
        parent::__construct(
            message: sprintf(
                'Method %s is not exists for Class "%s"',
                $method,
                $className
            ),
            code: empty($previous?->getCode() ?? null) ? 500 : $previous->getCode(),
            previous: $previous
        );
    }
}