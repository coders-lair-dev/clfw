<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI;


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
        /**
         * Фабрики.
         * Регистрируем внешние сервисы (PDO, Monolog, и т.п.) и сервисы со скалярными
         * зависимостями в конструкторе, которые автоматический резолвер не умеет создавать.
         * Делаем это до сканирования src/, чтобы пользовательские сервисы могли инжектить
         * результаты фабрик через type hints.
         */
        foreach ($this->config['factories'] ?? [] as $id => $factory) {
            $this->services[$id] = $factory($this);
        }

        /**
         * Cканирование директорий из config['services'] и автоматическое
         * инстанцирование с инъекцией зависимостей по type hints.
         */
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

        /**
         * Bootstrapp'инг
         * Финальная настройка, когда все сервисы уже созданы.
         * Используется для конфигурации сервисов, которым нужны другие сервисы из контейнера
         * (например, наполнение MiddlewarePipeline экземплярами middleware).
         */
        foreach ($this->config['bootstrap'] ?? [] as $bootstrap) {
            $bootstrap($this);
        }

        $this->serviceInvoker = new ServiceInvoker($this->services);
    }

    /**
     * Регистрирует готовый объект как сервис под произвольным ключом.
     * Используется в основном из bootstrap-замыканий и тестов.
     *
     * @param string $id
     * @param object $service
     *
     * @return void
     */
    public function set(string $id, object $service): void
    {
        $this->services[$id] = $service;
    }

    /**
     * Регистрирует и сразу выполняет фабрику.
     * Используется в основном из bootstrap-замыканий.
     * Фабрики из config['factories'] выполняются автоматически в init().
     *
     * @param string $id
     * @param callable $factory
     *
     * @return void
     */
    public function factory(string $id, callable $factory): void
    {
        $this->services[$id] = $factory($this);
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