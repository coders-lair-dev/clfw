<?php

namespace CodersLairDev\ClFw\DI\Trait;

trait ServicePathTrait
{
    private function getPath(string $dir, string $path): string
    {
        return $dir . DIRECTORY_SEPARATOR . $path;
    }

    private function isCurrentDir(string $path): bool
    {
        return $path == '.';
    }

    private function isParentDir(string $path): bool
    {
        return $path == '..';
    }
}