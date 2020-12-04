<?php

declare(strict_types=1);

namespace Sokil\Psr\Http\Server\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as SinglePassMiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DoublePassToSinglePassMiddlewareDecorator implements SinglePassMiddlewareInterface
{
    /**
     * @var DoublePassMiddlewareInterface
     */
    private $doublePassMiddleware;

    /**
     * @var ResponseFactoryInterface
     */
    private $response;

    /**
     * @param DoublePassMiddlewareInterface $doublePassMiddleware
     * @param ResponseInterface $response
     */
    public function __construct(
        DoublePassMiddlewareInterface $doublePassMiddleware,
        ResponseInterface $response
    ) {
        $this->doublePassMiddleware = $doublePassMiddleware;
        $this->response = $response;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return ($this->doublePassMiddleware)(
            $request,
            $this->response,
            function(ServerRequestInterface $request, ResponseInterface $response) use ($handler) {
                return $handler->handle($request);
            }
        );
    }
}