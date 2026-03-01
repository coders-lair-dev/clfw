<?php

namespace CodersLairDev\ClFw\Kernel;

use CodersLairDev\ClFw\DI\Exception\ClFwDiInsufficientOrWrongMethodArgumentsException;
use CodersLairDev\ClFw\DI\Exception\ClFwDiMethodNotExistsException;
use CodersLairDev\ClFw\DI\Exception\ClFwDiNotExistsException;
use CodersLairDev\ClFw\DI\Exception\ClFwDiNotImplementedServiceException;
use CodersLairDev\ClFw\DI\Exception\ClFwExceptionInterface;
use CodersLairDev\ClFw\DI\ServiceContainer;
use CodersLairDev\ClFw\Http\Response\Trait\ResponseTrait;
use CodersLairDev\ClFw\Routing\Route;
use CodersLairDev\ClFw\Routing\Router;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
        private readonly string $projectDir,
        private readonly array $config
    ) {
        $this->serviceContainer = new ServiceContainer($this->projectDir, $this->config);
        $this->serviceContainer->init();

        $this->router = new Router();
        $this->router->collectRoutes($this->serviceContainer->getServices());

        $this->psr17Factory = new Psr17Factory();

        $this->requestCreator = new ServerRequestCreator(
            serverRequestFactory: $this->psr17Factory,
            uriFactory: $this->psr17Factory,
            uploadedFileFactory: $this->psr17Factory,
            streamFactory: $this->psr17Factory
        );
    }

    /**
     * Prepare and send response - Front Controller
     * Формирует response и отправляет его — Front Controller
     */
    public function run(?ServerRequestInterface $preparedRequest = null): void
    {
        $response = $this->handle($preparedRequest);
        $this->sendResponse($response);
    }

    public function handle(?ServerRequestInterface $preparedRequest = null): ResponseInterface
    {
        $request = $preparedRequest ?? $this->requestCreator->fromGlobals();

        $route = $this->resolveRoute($request);

        if ($route === null) {
            return $this->createNotFoundResponse();
        }

        return $this->executeController($route, $request);
    }

    private function resolveRoute(ServerRequestInterface $request): ?Route
    {
        return $this->router->findRoute($request);
    }

    private function createNotFoundResponse(): ResponseInterface
    {
        return $this->createJsonResponse(
            psr17Factory: $this->psr17Factory,
            content: ['message' => 'Not Found'],
            status: 404
        );
    }

    private function executeController(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        try {
            $controller = $this->serviceContainer->getService($route->getController());

            $result = $this->serviceContainer->getServiceInvoker()->invoke(
                $controller,
                $route->getMethod(),
                [$request]
            );
        } catch (ClFwExceptionInterface $e) {
            return $this->createErrorResponse($e);
        }

        return $this->normalizeResponse($result);
    }

    private function createErrorResponse(ClFwExceptionInterface $e): ResponseInterface
    {
        return $this->createJsonResponse(
            psr17Factory: $this->psr17Factory,
            content: ['message' => $e->getMessage()],
            status: $e->getCode()
        );
    }

    private function normalizeResponse(mixed $result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        return $this->createJsonResponse(
            psr17Factory: $this->psr17Factory,
            content: ['data' => $result],
            status: 200
        );
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

    /**
     * @param ServerRequestInterface|null $preparedRequest
     * @param bool $shouldReturnResponse
     * @return ResponseInterface|null
     * @deprecated Deprecated FrontController
     *
     */
    public function _handle(
        ?ServerRequestInterface $preparedRequest,
        bool $shouldReturnResponse = false
    ): ?ResponseInterface {
        $request = $preparedRequest ?? $this->requestCreator->fromGlobals();

        $route = $this->resolveRoute($request);

        if (empty($route)) {
            $response = $this->createJsonResponse(
                psr17Factory: $this->psr17Factory,
                content: ['message' => 'Not Found'],
                status: 404
            );

            if ($shouldReturnResponse) {
                return $response;
            }

            $this->sendResponse($response);

            return null;
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

            if ($shouldReturnResponse) {
                return $response;
            }

            $this->sendResponse($response);

            return null;
        }

        if ($invokeResult instanceof ResponseInterface) {
            if ($shouldReturnResponse) {
                return $invokeResult;
            }

            $this->sendResponse($invokeResult);

            return null;
        }

        $response = $this->createJsonResponse(
            psr17Factory: $this->psr17Factory,
            content: ['data' => $invokeResult],
            status: 200
        );

        if ($shouldReturnResponse) {
            return $response;
        }

        $this->sendResponse($response);

        return null;
    }
}
