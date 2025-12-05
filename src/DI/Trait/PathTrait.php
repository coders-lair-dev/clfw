<?php

namespace CodersLairDev\ClFw\DI\Trait;

trait PathTrait
{
    private function getPath(string $dir, string $path): string
    {
        return $dir . DIRECTORY_SEPARATOR . $path;
    }

    private function isCurrentDir(string $file): bool
    {
        return $file == '.';
    }

    private function isParentDir(string $file): bool
    {
        return $file == '..';
    }
}