<?php

declare(strict_types=1);

namespace Sokil\Psr\Http\Server\Stub;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sokil\Psr\Http\Server\Middleware\DoublePassMiddlewareInterface;

class DoublePassMiddleware implements DoublePassMiddlewareInterface
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

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        foreach ($this->configurationParameters as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        $next($request, $response);

        foreach ($this->configurationParameters as $key => $value) {
            $response = $response->withHeader('X-' . $key, $value);
        }

        return $response;
    }
}