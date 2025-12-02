<?php

namespace CodersLairDev\ClFw\Routing\Trait;

use CodersLairDev\ClFw\Http\Request\Request;

trait RequestUriTrait
{
    private function getRequestUri(Request $request): string
    {
        return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $request->getRequestUriBag());
    }
}