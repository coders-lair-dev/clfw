<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI;


use CodersLairDev\ClFw\DI\Trait\ServiceLoaderTrait;

class ServiceContainer
{
    use ServiceLoaderTrait;

    /**
     * Contains all instantiated services (e.g. services, controllers etc.)
     * Будет содержать все инстанциированные сервисы (например, сервисы, контроллеры и т.д.)
     * 
     * @var array 
     */
    private array $services = [];

    public function __construct(
        private readonly string $projectDir,
        private readonly array $config
    ) {
    }

    /**
     * @return void
     *
     * @throws \ReflectionException
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
    }

    public function getControllers(): array
    {
        return [];
    }
}