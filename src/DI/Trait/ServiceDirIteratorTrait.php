<?php

namespace CodersLairDev\ClFw\DI\Trait;

trait ServiceDirIteratorTrait
{
    use ServicePathTrait;
    use ServiceNamespaceTrait;
    use ServiceClassLoaderTrait;

    /**
     * @param string $namespace
     * @param string $path
     * @return \ReflectionClass[]
     * 
     * @throws \CodersLairDev\ClFw\DI\Exception\ClFwLoadClassException
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