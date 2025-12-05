<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI;

use CodersLairDev\ClFw\DI\Exception\ClFwDiInsufficientOrWrongMethodArgumentsException;
use CodersLairDev\ClFw\DI\Exception\ClFwDiMethodNotExistsException;
use CodersLairDev\ClFw\DI\Exception\ClFwDiNotExistsException;
use CodersLairDev\ClFw\DI\Trait\InjectablesTrait;

final readonly class ServiceInvoker
{
    use InjectablesTrait;

    public function __construct(private array $services)
    {
    }

    /**
     * @param object $service
     * @param string $method
     * @param array $additionalParams
     * @return mixed
     *
     * @throws ClFwDiInsufficientOrWrongMethodArgumentsException
     * @throws ClFwDiMethodNotExistsException
     * @throws ClFwDiNotExistsException
     */
    public function invoke(object $service, string $method, array $additionalParams = []): mixed
    {
        $methodParameters = $this->getParametersForServiceMethod($service, $method, $additionalParams);

        return call_user_func_array([$service, $method], $methodParameters);
    }

    /**
     * @param object $service
     * @param string $method
     * @param array $additionalObjects
     * @return array
     *
     * @throws ClFwDiInsufficientOrWrongMethodArgumentsException
     * @throws ClFwDiMethodNotExistsException
     * @throws ClFwDiNotExistsException
     */
    public function getParametersForServiceMethod(object $service, string $method, array $additionalObjects = []): array
    {
        $additionalObjectsByType = [];

        foreach ($additionalObjects as $additionalObject) {
            try {
                $reflection = new \ReflectionClass($additionalObject);
            } catch (\ReflectionException $e) {
                throw new ClFwDiNotExistsException($e);
            }

            $additionalObjectsByType[$reflection->getName()] = $additionalObject;

            foreach ($reflection->getInterfaceNames() as $interfaceName) {
                /**
                 * If an additional object implements some interfaces,
                 * then we include the object in the list of objects for dependency injection
                 * in the form of all implemented interfaces
                 *
                 * Если дополнительным объектом реализуются какие-то интерфейсы,
                 * то включаем объект в список объектов для инъекций зависимости
                 * в виде всех реализуемых интерфейсов
                 */
                $additionalObjectsByType[$interfaceName] = $additionalObject;
            }
        }

        $reflection = new \ReflectionClass($service);

        try {
            $method = $reflection->getMethod($method);
        } catch (\ReflectionException $e) {
            throw new ClFwDiMethodNotExistsException($method, $reflection->getName(), $e);
        }

        $parameters = $method->getParameters();

        $methodParameters = $this->getInjectables(
            parameters: $parameters,
            instantiatedServices: [
                ...$this->services,
                ...$additionalObjectsByType
            ]
        );

        if (count($parameters) == count($methodParameters)) {
            return $methodParameters;
        }

        throw new ClFwDiInsufficientOrWrongMethodArgumentsException($reflection->getName(), $method->getName());
    }
}