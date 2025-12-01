<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI\Exception;


class ClFwDiInitWithEmptyConstructorException extends \ReflectionException implements ClFwExceptionInterface
{
    public function __construct(string $reflectionName, ?\ReflectionException $previous = null)
    {
        parent::__construct(
            message: sprintf(
                'Unable to instantiate class with empty constructor with Reflection Name "%s"',
                $reflectionName
            ),
            code: empty($previous?->getCode() ?? null) ? 500 : $previous->getCode(),
            previous: $previous
        );
    }
}