<?php

declare(strict_types=1);

namespace Sokil\Psr\Http\Server;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as SinglePassMiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sokil\Psr\Http\Server\Middleware\DoublePassMiddlewareInterface;
use Sokil\Psr\Http\Server\Middleware\DoublePassToSinglePassMiddlewareDecorator;

class RequestDispatcher implements RequestHandlerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var string
     */
    private $handlers;

    /**
     * @var string[]
     */
    private $middlewares = [];

    /**
     * @param ContainerInterface $container
     * @param ResponseFactoryInterface $responseFactory
     * @param string $handlers
     * @param string[] $middlewares
     */
    public function __construct(
        ContainerInterface $container,
        ResponseFactoryInterface $responseFactory,
        string $handlers,
        array $middlewares
    ) {
        $this->container = $container;
        $this->responseFactory = $responseFactory;
        $this->handlers = $handlers;
        $this->middlewares = $middlewares;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (count($this->middlewares) === 0) {
            return $this
                ->resolveHandler($this->handlers)
                ->handle($request);
        }

        $middlewares = $this->middlewares;

        $middleware = array_shift($middlewares);

        return $this
            ->resolveMiddleware($middleware)
            ->process(
                $request,
                new static(
                    $this->container,
                    $this->responseFactory,
                    $this->handlers,
                    $middlewares
                )
            );
    }

    /**
     * @param string|callable|RequestHandlerInterface $handler
     *
     * @return RequestHandlerInterface
     */
    private function resolveHandler($handler): RequestHandlerInterface
    {
        if (is_string($handler)) {
            return $this->container->get($handler);
        } elseif (is_callable($handler)) {
            return call_user_func($handler, $this->container);
        } elseif ($handler instanceof RequestHandlerInterface) {
            return $handler;
        } else {
            throw new \InvalidArgumentException('Handler definition is invalid');
        }
    }

    /**
     * @param string|callable|SinglePassMiddlewareInterface|DoublePassMiddlewareInterface $middleware
     *
     * @return SinglePassMiddlewareInterface
     */
    private function resolveMiddleware($middleware): SinglePassMiddlewareInterface
    {
        if (is_string($middleware)) {
            return $this->container->get($middleware);
        } elseif (is_callable($middleware)) {
            return call_user_func($middleware, $this->container);
        } elseif ($middleware instanceof SinglePassMiddlewareInterface) {
            return $middleware;
        } elseif ($middleware instanceof DoublePassMiddlewareInterface) {
            return new DoublePassToSinglePassMiddlewareDecorator(
                $middleware,
                $this->responseFactory->createResponse()
            );
        } else {
            throw new \InvalidArgumentException('Middleware definition is invalid');
        }
    }
}
