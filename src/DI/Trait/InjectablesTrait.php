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

            if (!$pType instanceof \ReflectionNamedType || $pType->isBuiltin()) {
                continue;
            }

            $injectables[] = $instantiatedServices[$pType->getName()] ?? null;
        }

        return array_filter($injectables, static fn($s) => $s !== null);
    }
}