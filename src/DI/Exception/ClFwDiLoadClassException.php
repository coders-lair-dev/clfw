<?php

namespace CodersLairDev\ClFw\DI\Exception;

use CodersLairDev\ClFw\DI\Exception\ClFwExceptionInterface;

class ClFwDiLoadClassException extends \ReflectionException implements ClFwExceptionInterface
{
    public function __construct(string $className, ?\ReflectionException $previous = null)
    {
        parent::__construct(
            message: sprintf(
                'Unable to load class with Class name "%s"',
                $className
            ),
            code: empty($previous?->getCode() ?? null) ? 500 : $previous->getCode(),
            previous: $previous
        );
    }
}