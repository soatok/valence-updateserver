<?php
declare(strict_types=1);
namespace Soatok\Valence\Endpoints;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soatok\AnthroKit\Endpoint;

/**
 * Class PublicKey
 * @package Soatok\Valence\Endpoints
 */
class PublicKey extends Endpoint
{
    public function __invoke(
        RequestInterface $request,
        ?ResponseInterface $response = null,
        array $routerParams = []
    ): ResponseInterface {

        // NOT IMPLEMENTED YET

        $action = $routerParams['action'] ?? '';
        switch ($action) {
            default:
                return $this->redirect('/');
        }
    }
}
