<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI\Exception;

class ClFwDiNotImplementedServiceException extends \ReflectionException implements ClFwExceptionInterface
{
    public function __construct(string $className, ?\ReflectionException $previous = null)
    {
        parent::__construct(
            message: sprintf(
                'Not implemented service with Class name "%s"',
                $className
            ),
            code: empty($previous?->getCode() ?? null) ? 500 : $previous->getCode(),
            previous: $previous
        );
    }
}