<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI\Trait;


trait ServiceLoaderTrait
{
    use ServicePathTrait;
    use ServiceDirIteratorTrait;

    /**
     * @param string $projectDir
     * @param array $pathData
     * @param array $_services
     * @return array
     */
    protected function loadServices(string $projectDir, array $pathData, array $_services): array
    {
        $path = $this->getPath(
            dir: $projectDir,
            path: $pathData['path']
        );

        $iterator = $this->iterateDir(
            namespace: $pathData['namespace'],
            path: $path
        );

        try {
            $servicesReflections = $this->iterateDirRecursive($pathData['namespace'], $pathData['path']);
        } catch (\ReflectionException $e) {
            $k = 0;
            throw $e;
        }

        $services = $this->initWithEmptyConstructors($servicesReflections);

        $reflectionsAmount = count($servicesReflections);
        $servicesAmount = count($services);

        while ($reflectionsAmount > $servicesAmount) {
            /** @var \ReflectionClass $reflection */
            foreach ($servicesReflections as $reflection) {
                $constructor = $reflection->getConstructor();

                if (is_null($constructor)) {
                    continue;
                }

                $pInjections = [];
                $parameters = $constructor->getParameters();
                foreach ($parameters as $parameter) {
                    $pType = $parameter->getType();
                    if ($pType->isBuiltin()) {
                        continue;
                    }

                    $pTypeName = $pType->getName();

                    if (array_key_exists($pTypeName, $services)) {
                        $pInjections[] = $services[$pTypeName];

                        continue;
                    }

                    if (array_key_exists($pTypeName, $_services)) {
                        $pInjections[] = $_services[$pTypeName];
                    }
                }

                if (count($parameters) == count($pInjections)) {
                    $services[$reflection->getName()] = $reflection->newInstanceArgs($pInjections);
                }
            }

            $servicesAmount = count($services);
        }

        return $services;
    }

    /**
     * @param ReflectionClass[] $reflections
     * @return array
     *
     * @throws ClFwInitWithEmptyConstructorException
     */
    private function initWithEmptyConstructors(array $reflections):array
    {
        $services = [];

        foreach ($reflections as $reflection) {
            $constructor = $reflection->getConstructor();

            if (is_null($constructor)) {
                try {
                    $services[$reflection->getName()] = $reflection->newInstanceWithoutConstructor();
                } catch (\ReflectionException $e) {
                    throw new ClFwInitWithEmptyConstructorException($reflection->getName(), $e);
                }
            }
        }

        return $services;
    }

    /**
     * @param string $namespace
     * @param string $path
     * @return \Generator
     *
     * @throws \ReflectionException
     */
    private function iterateDir(string $namespace, string $path): \Generator
    {
        $dirContent = scandir($path);

        if (!is_array($dirContent)) {
            return null;
        }

        foreach ($dirContent as $file) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $file;

            if (!is_file($fullPath) || !$this->isClass($fullPath)) {
                continue;
            }

            yield $this->loadClass($namespace, $fullPath);
        }
    }
}