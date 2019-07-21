<?php
declare(strict_types=1);
namespace Soatok\Valence\Endpoints;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Container;
use Soatok\AnthroKit\Endpoint;
use Soatok\Valence\Splices\Publishers as PublisherSplice;

/**
 * Class Publishers
 * @package Soatok\Valence\Endpoints
 */
class Publishers extends Endpoint
{
    /** @var PublisherSplice $publishers */
    private $publishers;

    /**
     * Publish constructor.
     * @param Container $container
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->publishers = $this->splice('Publishers');
    }

    public function __invoke(
        RequestInterface $request,
        ?ResponseInterface $response = null,
        array $routerParams = []
    ): ResponseInterface {
        $name = $routerParams['name'] ?? '';
        if ($name) {
            try {
                return $this->json([
                    'projects' =>
                        $this->publishers->getProjects($name)
                ]);
            } catch (\Exception $ex) {
                // No such publisher
                return $this->redirect('/publishers');
            }
        }
        return $this->json([
            'publishers' => $this->publishers->listPublishers()
        ]);
    }
}
