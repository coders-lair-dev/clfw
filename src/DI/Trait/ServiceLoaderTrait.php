<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI\Trait;


use CodersLairDev\ClFw\DI\Exception\ClFwDiInitWithConstructorException;
use CodersLairDev\ClFw\DI\Exception\ClFwDiInitWithEmptyConstructorException;
use CodersLairDev\ClFw\DI\Exception\ClFwDiLoadClassException;

trait ServiceLoaderTrait
{
    use PathTrait;
    use DirIteratorTrait;
    use InjectablesTrait;

    /**
     * @param string $projectDir
     * @param array $pathData
     * @param array $allServices
     * @return array
     *
     * @throws ClFwDiInitWithConstructorException
     * @throws ClFwDiInitWithEmptyConstructorException
     * @throws ClFwDiLoadClassException
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

        /**
         * We create service instances with not declared constructors
         * or with constructors without any parameters (i.g. without dependencies).
         * For now, we can create only these objects (services).
         * Later, they will be used for dependency injection into other services
         * with declared constructors and dependencies.
         *
         * Создаём сервисы с пустыми/отсутствующими конструкторами.
         * Сейчас мы можем создать только их. В дальнейшем они будут использоваться
         * при внедрении зависимостей в другие сервисы с зависимостями в конструкторе.
         */
        $services = $this->instantiateWithEmptyConstructors($servicesReflections);

        /**
         * Total number of \ReflectionClass objects created
         * Общее количество созданных объектов \ReflectionClass
         */
        $reflectionsAmount = count($servicesReflections);

        /**
         * Number of services already instantiated at this point
         *
         * Количество инстанциированных сервисов к этому моменту
         */
        $servicesAmount = count($services);

        /**
         * If there are fewer instantiated services, it means that there are services with declared dependencies
         * in constructors and they also need to be instantiated with dependency injection
         * from existing previously created/instantified objects.
         *
         * Если созданных сервисов меньше, значит,
         * имеются сервисы с объявленными зависимостями в конструкторах
         * и их тоже нужно создать с внедрением зависимостей
         * из имеющихся созданных/инстанциированных объектов ранее.
         */
        while ($reflectionsAmount > $servicesAmount) {
            foreach ($servicesReflections as $reflection) {
                $this->instantiateWithConstructor($reflection, $services, $allServices);
            }

            $servicesAmount = count($services);
        }

        return $services;
    }

    /**
     * Creates a service with a constructor without parameters (or with an undeclared constructor)
     *
     * Создаёт сервис с конструктором без параметров (или с не объявленным конструктором)
     *
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

    /**
     * Creates a service with a constructor with parameters (with dependencies injection)
     *
     * Создаёт сервис с конструктором с зависимостями в конструкторе
     *
     * @param \ReflectionClass $reflection
     * @param array $currentlyInstantiated
     * @param array $allServices
     * @return void
     *
     * @throws ClFwDiInitWithConstructorException
     */
    private function instantiateWithConstructor(
        \ReflectionClass $reflection,
        array &$currentlyInstantiated,
        array $allServices
    ): void {
        $constructor = $reflection->getConstructor();

        if (is_null($constructor)) {
            return;
        }

        $parameters = $constructor->getParameters();

        $injectables = $this->getInjectables(
            parameters: $parameters,
            instantiatedServices: [
                ...$allServices,
                ...$currentlyInstantiated
            ]
        );

        if (count($parameters) == count($injectables)) {
            try {
                $currentlyInstantiated[$reflection->getName()] = $reflection->newInstanceArgs($injectables);
            } catch (\ReflectionException $e) {
                throw new ClFwDiInitWithConstructorException($reflection->getName(), $e);
            }
        }
    }
}