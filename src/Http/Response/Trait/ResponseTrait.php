<?php

declare(strict_types=1);

namespace CodersLairDev\ClFw\Http\Response\Trait;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

trait ResponseTrait
{
    private function createJsonResponse(
        Psr17Factory $psr17Factory,
        mixed $content,
        int $status
    ): ResponseInterface {
        $response = $this->createResponse($psr17Factory, json_encode($content), $status);

        return $response->withHeader('Content-Type', 'application/json');
    }

    private function createResponse(
        Psr17Factory $psr17Factory,
        string $content,
        int $status
    ): ResponseInterface {
        $responseBody = $psr17Factory->createStream($content);
        return $psr17Factory->createResponse($status)->withBody($responseBody);
    }
}