<?php

declare(strict_types=1);

namespace Sokil\Psr\Http\Server;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sokil\Psr\Http\Server\Stub\SinglePassMiddleware;

class RequestDispatcherTest extends TestCase
{
    use ProphecyTrait;

    public function testSinglePassMiddlewareArrayDefinition()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('middlewareServiceName')->willReturn(new SinglePassMiddleware());


        $requestHandler = new class implements RequestHandlerInterface {
            /**
             * @var array
             */
            private $requestAttributes;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->requestAttributes = $request->getAttributes();
                return new Response();
            }

            /**
             * @return array
             */
            public function getRequestAttributesAfterHandle(): array
            {
                return $this->requestAttributes;
            }
        };

        $dispatcher = new RequestDispatcher(
            $container->reveal(),
            new ResponseFactory(),
            $requestHandler,
            [
                [
                    'middlewareServiceName',
                    function(SinglePassMiddleware $middleware) {
                        $middleware->setConfigurationParameter('key1', '42');
                    }
                ],
                [
                    'middlewareServiceName',
                    function(SinglePassMiddleware $middleware) {
                        $middleware->setConfigurationParameter('key2', '42');
                    }
                ]
            ]
        );

        $response = $dispatcher->handle(new ServerRequest());

        // assert request modified in middlewares before handler
        $this->assertSame(
            [
                'key1' => '42',
                'key2' => '42',
            ],
            $requestHandler->getRequestAttributesAfterHandle()
        );

        // assert response modified after handlers
        $this->assertSame(
            [
                'X-key1' => ['42'],
                'X-key2' => ['42'],
            ],
            $response->getHeaders()
        );
    }
}