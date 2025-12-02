<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI\Exception;

class ClFwDiNotExistsException extends \ReflectionException implements ClFwExceptionInterface
{
    public function __construct(?\ReflectionException $previous = null)
    {
        parent::__construct(
            message: 'Service is not exists',
            code: empty($previous?->getCode() ?? null) ? 500 : $previous->getCode(),
            previous: $previous
        );
    }
}