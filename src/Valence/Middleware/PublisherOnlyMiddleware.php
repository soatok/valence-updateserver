<?php
declare(strict_types=1);
namespace Soatok\Valence\Middleware;

use Psr\Http\Message\{
    MessageInterface,
    RequestInterface,
    ResponseInterface
};
use Slim\Http\{
    Headers,
    Response,
    StatusCode
};
use Soatok\AnthroKit\Middleware;
use Soatok\Valence\TokenHash;

/**
 * Class AuthMiddleware
 * @package Soatok\Valence\Middleware
 */
class PublisherOnlyMiddleware extends Middleware
{
    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return MessageInterface
     * @throws \SodiumException
     */
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): MessageInterface {
        $headers = $request->getHeader('Valence-Publisher');
        if (!$headers) {
            return new Response(
                StatusCode::HTTP_SEE_OTHER,
                new Headers([
                    'Location' => '/'
                ])
            );
        }
        if (!is_array($headers)) {
            $headers = [$headers];
        }

        if (!$this->isValidHeader($headers)) {
            return new Response(
                StatusCode::HTTP_SEE_OTHER,
                new Headers([
                    'Location' => '/'
                ])
            );
        }
        return $next($request, $response);
    }

    /**
     * @param array $headers
     * @return bool
     * @throws \SodiumException
     */
    protected function isValidHeader(array $headers = []): bool
    {
        if (count($headers) > 8) {
            // Nice try dude
            return false;
        }
        foreach ($headers as $header) {
            if (!is_string($header)) {
                continue;
            }
            if (TokenHash::publisherAuth($this->container, $header)) {
                return true;
            }
        }
        return false;
    }
}
