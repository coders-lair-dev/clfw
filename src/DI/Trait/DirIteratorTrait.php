<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI\Trait;

use CodersLairDev\ClFw\DI\Exception\ClFwDiLoadClassException;

trait DirIteratorTrait
{
    use PathTrait;
    use NamespaceTrait;
    use ClassLoaderTrait;

    /**
     * @param string $namespace
     * @param string $path
     * @return \ReflectionClass[]
     *
     * @throws ClFwDiLoadClassException
     */
    private function iterateDirRecursive(string $namespace, string $path): array
    {
        $reflections = [];

        $dirContent = scandir($path);

        if (!is_array($dirContent)) {
            return $reflections;
        }

        foreach ($dirContent as $file) {
            $fullPath = $this->getPath($path, $file);

            if ($this->isCurrentDir($file) || $this->isParentDir($file)) {
                continue;
            }

            if (is_dir($fullPath)) {
                $innerNamespace = $this->getNamespace($namespace, $file);
                $reflections = [
                    ...$reflections,
                    ...$this->iterateDirRecursive($innerNamespace, $fullPath)
                ];

                continue;
            }

            if (!is_file($fullPath) || !$this->isClass($fullPath)) {
                continue;
            }

            $reflections[] = $this->loadClass($namespace, $fullPath);
        }

        return $reflections;
    }
}