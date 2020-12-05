<?php

declare(strict_types=1);

namespace Sokil\Psr\Http\Server\Stub;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as SinglePassMiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SinglePassMiddleware implements SinglePassMiddlewareInterface
{
    /**
     * @var string[]
     */
    private $configurationParameters;

    /**
     * @param string $key
     * @param string $value
     */
    public function setConfigurationParameter(string $key, string $value): void
    {
        $this->configurationParameters[$key] = $value;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        foreach ($this->configurationParameters as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        $response = $handler->handle($request);

        foreach ($this->configurationParameters as $key => $value) {
            $response = $response->withHeader('X-' . $key, $value);
        }

        return $response;
    }

}