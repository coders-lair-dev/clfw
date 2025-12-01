<?php

namespace CodersLairDev\ClFw\DI\Trait;

trait ServiceDirIteratorTrait
{
    use ServicePathTrait;

    /**
     * @param string $namespace
     * @param string $path
     * @return array
     *
     * @throws \ReflectionException
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

            if ($this->isCurrentDir($fullPath) || $this->isParentDir($fullPath)) {
                continue;
            }

            if (is_dir($fullPath)) {
                $reflections[] = $this->iterateDirRecursive($namespace, pathinfo($fullPath, PATHINFO_DIRNAME));
            }

            if (!is_file($fullPath) || !$this->isClass($fullPath)) {
                continue;
            }

            $reflections[] = $this->loadClass($namespace, $fullPath);
        }

        return $reflections;
    }

    private function isClass(string $fullPath): bool
    {
        $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
        return $ext === 'php';
    }

    /**
     * @param string $namespace
     * @param string $fullPath
     * @return \ReflectionClass
     *
     * @throws \ReflectionException
     */
    private function loadClass(string $namespace, string $fullPath): \ReflectionClass
    {
        $fileName = pathinfo($fullPath, PATHINFO_FILENAME);
        $className = $namespace . '\\' . $fileName;
        return new \ReflectionClass($className);
    }
}