<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\DI;

use CodersLairDev\ClFw\DI\Exception\ClFwDiInsufficientOrWrongMethodArgumentsException;
use CodersLairDev\ClFw\DI\Exception\ClFwDiMethodNotExistsException;
use CodersLairDev\ClFw\DI\Exception\ClFwDiNotExistsException;

final readonly class ServiceInvoker
{
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
        }

        $reflection = new \ReflectionClass($service);

        try {
            $method = $reflection->getMethod($method);
        } catch (\ReflectionException $e) {
            throw new ClFwDiMethodNotExistsException($method, $reflection->getName(), $e);
        }
        $parameters = $method->getParameters();

        $methodParameters = [];

        foreach ($parameters as $parameter) {
            $pType = $parameter->getType();
            if ($pType->isBuiltin()) {
                continue;
            }

            $pTypeName = $pType->getName();

            if (array_key_exists($pTypeName, $this->services)) {
                $methodParameters[] = $this->services[$pType->getName()];

                continue;
            }

            if (array_key_exists($pTypeName, $additionalObjectsByType)) {
                $methodParameters[] = $additionalObjectsByType[$pTypeName];
            }
        }

        if (count($parameters) == count($methodParameters)) {
            return $methodParameters;
        }

        throw new ClFwDiInsufficientOrWrongMethodArgumentsException($reflection->getName(), $method->getName());
    }
}