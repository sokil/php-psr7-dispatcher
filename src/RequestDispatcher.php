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
    private $handler;

    /**
     * @var string[]
     */
    private $middlewares = [];

    /**
     * @param ContainerInterface $container
     * @param ResponseFactoryInterface $responseFactory
     * @param string|array|RequestHandlerInterface $handler
     * @param string[]|array[]|callable[]|SinglePassMiddlewareInterface[]|DoublePassMiddlewareInterface[] $middlewares
     */
    public function __construct(
        ContainerInterface $container,
        ResponseFactoryInterface $responseFactory,
        $handler,
        array $middlewares
    ) {
        if (!is_string($handler) && !is_array($handler) && !$handler instanceof RequestHandlerInterface) {
            throw new \InvalidArgumentException(
                'Handler must be defined as string, array or instance of RequestHandlerInterface'
            );
        }

        foreach ($middlewares as $middleware) {
            if (
                !is_string($middleware) &&
                !is_array($middleware) &&
                !is_callable($middleware) &&
                !$middleware instanceof SinglePassMiddlewareInterface
            ) {
                throw new \InvalidArgumentException(
                    'Handler must be defined as string, array, double pass middleware callable or instance of PSR-15 MiddlewareInterface'
                );
            }
        }

        $this->container = $container;
        $this->responseFactory = $responseFactory;
        $this->handler = $handler;
        $this->middlewares = $middlewares;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (count($this->middlewares) === 0) {
            return $this
                ->resolveHandler($this->handler)
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
                    $this->handler,
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
        } elseif (is_array($handler)) {
            if (empty($handler[0]) || !is_string($handler[0])) {
                throw new \InvalidArgumentException(
                    'If handler configured as array, first element must be service name'
                );
            }

            if (empty($handler[1]) || !is_callable($handler[1])) {
                throw new \InvalidArgumentException(
                    'If handler configured as array, second element must be callable configurator'
                );
            }

            $handlerInstance = $this->container->get($handler[0]);
            ($handler[1])($handlerInstance);

            return $handlerInstance;
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
        if ($middleware instanceof SinglePassMiddlewareInterface) {
            return $middleware;
        } elseif ($middleware instanceof DoublePassMiddlewareInterface || is_callable($middleware)) {
            return new DoublePassToSinglePassMiddlewareDecorator(
                $middleware,
                $this->responseFactory->createResponse()
            );
        } elseif (is_string($middleware)) {
            return $this->container->get($middleware);
        } elseif (is_array($middleware)) {
            if (empty($middleware[0]) || !is_string($middleware[0])) {
                throw new \InvalidArgumentException(
                    'If middleware configured as array, first element must be service name'
                );
            }

            if (empty($middleware[1]) || !is_callable($middleware[1])) {
                throw new \InvalidArgumentException(
                    'If middleware configured as array, second element must be callable configurator'
                );
            }

            $middlewareInstance = $this->container->get($middleware[0]);
            ($middleware[1])($middlewareInstance);

            return $middlewareInstance;
        } else {
            throw new \InvalidArgumentException('Middleware definition is invalid');
        }
    }
}
