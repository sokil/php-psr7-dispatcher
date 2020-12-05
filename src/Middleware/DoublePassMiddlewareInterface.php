<?php

declare(strict_types=1);

namespace Sokil\Psr\Http\Server\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface DoublePassMiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next Callable had a signature fn(request, response, next): response
     *
     * @link https://www.php-fig.org/psr/psr-15/meta/#51-double-pass
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    );
}