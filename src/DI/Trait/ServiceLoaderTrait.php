<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI\Trait;


use CodersLairDev\ClFw\DI\Exception\ClFwDiInitWithEmptyConstructorException;

trait ServiceLoaderTrait
{
    use ServicePathTrait;
    use ServiceDirIteratorTrait;

    /**
     * @param string $projectDir
     * @param array $pathData
     * @param array $allServices
     * @return array
     */
    protected function loadServices(string $projectDir, array $pathData, array $allServices): array
    {
        $path = $this->getPath(
            dir: $projectDir,
            path: $pathData['path']
        );

        /**
         * We recursively go through all the directories specified in the configuration
         * with services, controllers, etc. and create \ReflectionClass objects based on the classes found.
         *
         * Рекурсивно проходим по всем указанным в конфигурации каталогам с сервисами, контроллерами и т.д.
         * и создаём на основе найденных классов объекты \ReflectionClass
         */
        $servicesReflections = $this->iterateDirRecursive(
            namespace: $pathData['namespace'],
            path: $path
        );

        $services = $this->initWithEmptyConstructors($servicesReflections);

        $reflectionsAmount = count($servicesReflections);
        $servicesAmount = count($services);

        while ($reflectionsAmount > $servicesAmount) {
            /** @var \ReflectionClass $reflection */
            foreach ($servicesReflections as $reflection) {
                $this->initWithConstructor($reflection, $services, $allServices);
            }

            $servicesAmount = count($services);
        }

        return $services;
    }

    /**
     * @param \ReflectionClass[] $reflections
     * @return array
     *
     * @throws ClFwDiInitWithEmptyConstructorException
     */
    private function instantiateWithEmptyConstructors(array $reflections): array
    {
        $services = [];

        foreach ($reflections as $reflection) {
            $constructor = $reflection->getConstructor();

            if (is_null($constructor)) {
                try {
                    $services[$reflection->getName()] = $reflection->newInstanceWithoutConstructor();
                } catch (\ReflectionException $e) {
                    throw new ClFwDiInitWithEmptyConstructorException($reflection->getName(), $e);
                }
            }
        }

        return $services;
    }

    private function instantiateWithConstructor(
        \ReflectionClass $reflection,
        array &$currentlyInstantiated,
        array $allServices
    ): void {
        $constructor = $reflection->getConstructor();

        if (is_null($constructor)) {
            return;
        }

        $pInjections = [];
        $parameters = $constructor->getParameters();

        foreach ($parameters as $parameter) {
            $pType = $parameter->getType();
            if ($pType->isBuiltin()) {
                continue;
            }

            $pTypeName = $pType->getName();

            if (array_key_exists($pTypeName, $currentlyInstantiated)) {
                $pInjections[] = $currentlyInstantiated[$pTypeName];

                continue;
            }

            if (array_key_exists($pTypeName, $allServices)) {
                $pInjections[] = $allServices[$pTypeName];
            }
        }

        if (count($parameters) == count($pInjections)) {
            $currentlyInstantiated[$reflection->getName()] = $reflection->newInstanceArgs($pInjections);
        }
    }
}