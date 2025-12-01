<?php

namespace CodersLairDev\ClFw\DI\Trait;

trait ServiceNamespaceTrait
{
    private const NAMESPACE_SEPARATOR = '\\';

    private function getNamespace(string $base, string $dir): string
    {
        return $base . self::NAMESPACE_SEPARATOR . $dir;
    }
}