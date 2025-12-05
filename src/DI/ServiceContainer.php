<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI;


use CodersLairDev\ClFw\DI\Exception\ClFwDiInsufficientOrWrongMethodArgumentsException;
use CodersLairDev\ClFw\DI\Exception\ClFwDiNotImplementedServiceException;
use CodersLairDev\ClFw\DI\Exception\ClFwExceptionInterface;
use CodersLairDev\ClFw\DI\Trait\ServiceLoaderTrait;

class ServiceContainer
{
    use ServiceLoaderTrait;

    /**
     * Contains all instantiated objects (e.g. services, controllers etc.)
     *
     * Будет содержать все созданные объекты (например, сервисы, контроллеры и т.д.)
     *
     * @var array
     */
    private array $services = [];

    private ServiceInvoker $serviceInvoker;

    public function __construct(
        private readonly string $projectDir,
        private readonly array $config
    ) {
    }

    /**
     * @return void
     *
     * @throws ClFwExceptionInterface
     */
    public function init(): void
    {
        $servicesPaths = $this->config['services'] ?? [];

        foreach ($servicesPaths as $servicePathData) {
            $this->services = [
                ...$this->services,
                ...$this->loadServices(
                    projectDir: $this->projectDir,
                    pathData: $servicePathData,
                    allServices: $this->services
                )
            ];
        }

        $this->serviceInvoker = new ServiceInvoker($this->services);
    }

    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @param string $className
     * @return object
     *
     * @throws ClFwDiNotImplementedServiceException
     */
    public function getService(string $className): object
    {
        if (!isset($this->services[$className])) {
            throw new ClFwDiNotImplementedServiceException($className);
        }

        return $this->services[$className];
    }

    public function getServiceInvoker(): ServiceInvoker
    {
        return $this->serviceInvoker;
    }
}