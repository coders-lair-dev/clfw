<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\Routing\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
readonly class AsRoute implements ClFwRouteInterface
{
    public function __construct(
        public string $path
    ) {
    }
}