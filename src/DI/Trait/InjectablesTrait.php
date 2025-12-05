<?php

namespace CodersLairDev\ClFw\DI\Trait;

trait InjectablesTrait
{
    /**
     * @param \ReflectionParameter[] $parameters
     * @param array $instantiatedServices
     * @return object[]
     */
    private function getInjectables(array $parameters, array $instantiatedServices): array
    {
        $injectables = [];

        foreach ($parameters as $parameter) {
            $pType = $parameter->getType();
            if ($pType->isBuiltin()) {
                continue;
            }

            $pTypeName = $pType->getName();

            if (array_key_exists($pTypeName, $instantiatedServices)) {
                $injectables[] = $instantiatedServices[$pTypeName];
            }
        }

        return $injectables;
    }
}