<?php
declare(strict_types=1);
namespace Soatok\Valence\Endpoints;

use Interop\Container\Exception\ContainerException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Container;
use Slim\Http\StatusCode;
use Soatok\AnthroKit\Endpoint;
use Soatok\Valence\Filter\PublicKeyAddFilter;
use Soatok\Valence\Filter\PublicKeyRevokeFilter;
use Soatok\Valence\Splices\Publishers;

/**
 * Class PublicKey
 * @package Soatok\Valence\Endpoints
 */
class PublicKey extends Endpoint
{
    /**
     * @var Publishers $publishers
     */
    protected $publishers;

    /**
     * PublicKey constructor.
     * @param Container $container
     * @throws ContainerException
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->publishers = $this->splice('Publishers');
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    protected function addPublicKey(RequestInterface $request): ResponseInterface
    {
        $publisher = (int) $this->container['active_publisher_id'];
        if (!$publisher) {
            return $this->redirect('/');
        }
        try {
            $filter = new PublicKeyAddFilter();
            $post = $filter($_POST);
        } catch (\Throwable $ex) {
            return $this->json(
                [
                    'error' => 'Invalid POST Data',
                    'more' => $ex->getMessage()
                ],
                StatusCode::HTTP_NOT_ACCEPTABLE
            );
        }
        if (empty($post['publickey'])) {
            return $this->redirect('/');
        }
        if ($this->publishers->addPublicKey($publisher, $post['publickey'])) {
            return $this->json([
                'message' => 'Public key added successfully',
                'publisher' => $this->publishers->getById($publisher)
            ]);
        }
        return $this->redirect('/');
    }
    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    protected function revokePublicKey(RequestInterface $request): ResponseInterface
    {
        $publisher = (int) $this->container['active_publisher_id'];
        if (!$publisher) {
            return $this->redirect('/');
        }
        try {
            $filter = new PublicKeyRevokeFilter();
            $post = $filter($_POST);
        } catch (\Throwable $ex) {
            return $this->json(
                [
                    'error' => 'Invalid POST Data',
                    'more' => $ex->getMessage()
                ],
                StatusCode::HTTP_NOT_ACCEPTABLE
            );
        }
        if (empty($post['publickey'])) {
            return $this->redirect('/');
        }
        if ($this->publishers->revokePublicKey($publisher, $post['publickey'])) {
            return $this->json([
                'message' => 'Public key revoked successfully',
                'publisher' => $this->publishers->getById($publisher)
            ]);
        }
        return $this->redirect('/');
    }

    public function __invoke(
        RequestInterface $request,
        ?ResponseInterface $response = null,
        array $routerParams = []
    ): ResponseInterface {
        $action = $routerParams['action'] ?? '';
        switch ($action) {
            case 'add':
                return $this->addPublicKey($request);
            case 'revoke':
                return $this->revokePublicKey($request);
            default:
                return $this->redirect('/');
        }
    }
}
