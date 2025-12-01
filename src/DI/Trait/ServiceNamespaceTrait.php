<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI\Trait;

trait ServiceNamespaceTrait
{
    private const NAMESPACE_SEPARATOR = '\\';

    private function getNamespace(string $base, string $dir): string
    {
        return $base . self::NAMESPACE_SEPARATOR . $dir;
    }
}