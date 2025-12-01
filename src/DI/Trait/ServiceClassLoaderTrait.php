<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI\Trait;


use CodersLairDev\ClFw\DI\Exception\ClFwDiLoadClassException;

trait ServiceClassLoaderTrait
{
    private const PHP_CLASS_EXTENSION = 'php';

    private function isClass(string $fullPath): bool
    {
        return pathinfo($fullPath, PATHINFO_EXTENSION) === self::PHP_CLASS_EXTENSION;
    }

    /**
     * @param string $namespace
     * @param string $fullPath
     * @return \ReflectionClass
     *
     * @throws ClFwDiLoadClassException
     */
    private function loadClass(string $namespace, string $fullPath): \ReflectionClass
    {
        $fileName = pathinfo($fullPath, PATHINFO_FILENAME);
        $className = $namespace . '\\' . $fileName;

        try {
            return new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new ClFwDiLoadClassException($className, $e);
        }
    }
}