<?php

namespace CodersLairDev\ClFw\Kernel;

use CodersLairDev\ClFw\DI\Exception\ClFwDiInsufficientOrWrongMethodArgumentsException;
use CodersLairDev\ClFw\DI\Exception\ClFwDiMethodNotExistsException;
use CodersLairDev\ClFw\DI\Exception\ClFwDiNotExistsException;
use CodersLairDev\ClFw\DI\Exception\ClFwDiNotImplementedServiceException;
use CodersLairDev\ClFw\DI\Exception\ClFwExceptionInterface;
use CodersLairDev\ClFw\DI\ServiceContainer;
use CodersLairDev\ClFw\Http\Response\Trait\ResponseTrait;
use CodersLairDev\ClFw\Routing\Router;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;

final class Kernel
{
    use ResponseTrait;

    private ServerRequestCreator $requestCreator;
    private ServiceContainer $serviceContainer;
    private Router $router;
    private Psr17Factory $psr17Factory;

    /**
     * @param string $projectDir
     * @param array $config
     *
     * @throws ClFwExceptionInterface
     */
    public function __construct(
        private string $projectDir,
        private array $config
    ) {
        $this->serviceContainer = new ServiceContainer($this->projectDir, $this->config);
        $this->serviceContainer->init();

        $this->router = new Router();
        $this->router->collectRoutes($this->serviceContainer->getServices());

        $this->psr17Factory = new Psr17Factory();

        $this->requestCreator = new ServerRequestCreator(
            serverRequestFactory: $this->psr17Factory, // ServerRequestFactory
            uriFactory: $this->psr17Factory, // UriFactory
            uploadedFileFactory: $this->psr17Factory, // UploadedFileFactory
            streamFactory: $this->psr17Factory  // StreamFactory
        );
    }

    public function handle(): void
    {
        $request = $this->requestCreator->fromGlobals();

        $route = $this->router->findRoute($request);

        if (empty($route)) {
            $response = $this->createJsonResponse(
                psr17Factory: $this->psr17Factory,
                content: ['message' => 'Not Found'],
                status: 404
            );

            $this->sendResponse($response);

            return;
        }

        try {
            $controller = $this->serviceContainer->getService($route->getController());

            $invokeResult = $this->serviceContainer->getServiceInvoker()->invoke(
                $controller,
                $route->getMethod(),
                [$request]
            );
        } catch (ClFwDiNotImplementedServiceException|ClFwDiInsufficientOrWrongMethodArgumentsException|ClFwDiMethodNotExistsException|ClFwDiNotExistsException $e) {
            $response = $this->createJsonResponse(
                psr17Factory: $this->psr17Factory,
                content: ['message' => $e->getMessage()],
                status: $e->getCode()
            );

            $this->sendResponse($response);

            return;
        }

        if ($invokeResult instanceof ResponseInterface) {
            $this->sendResponse($invokeResult);

            return;
        }

        $response = $this->createJsonResponse(
            psr17Factory: $this->psr17Factory,
            content: ['data' => $invokeResult],
            status: 200
        );

        $this->sendResponse($response);
    }

    private function sendResponse(ResponseInterface $response): void
    {
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false, $response->getStatusCode());
            }
        }

        echo $response->getBody()->getContents();
    }
}