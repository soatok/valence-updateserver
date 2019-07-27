<?php
declare(strict_types=1);
namespace Soatok\Valence\Endpoints;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Container;
use Slim\Http\StatusCode;
use Soatok\AnthroKit\Endpoint;
use Soatok\DholeCrypto\AsymmetricFile;
use Soatok\DholeCrypto\Key\AsymmetricPublicKey;
use Soatok\DholeCrypto\Keyring;
use Soatok\Valence\AccessTrait;
use Soatok\Valence\Filter\PublishFilter;
use Soatok\Valence\Splices\Projects;
use Soatok\Valence\Splices\Publishers;

/**
 * Class Publish
 * @package Soatok\Valence\Endpoints
 */
class Publish extends Endpoint
{
    use AccessTrait;

    /** @var Projects $projects */
    private $projects;

    /** @var Publishers $publishers */
    private $publishers;

    /**
     * Publish constructor.
     * @param Container $container
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->projects = $this->splice('Projects');
        $this->publishers = $this->splice('Publishers');
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function makeFilePath(): string
    {
        $a = bin2hex(random_bytes(1));
        if (!is_dir(APP_ROOT . '/files/' . $a)) {
            mkdir(APP_ROOT . '/files/' . $a, 0777);
        }
        $b = bin2hex(random_bytes(1));
        if (!is_dir(APP_ROOT . '/files/' . $a . '/' . $b)) {
            mkdir(APP_ROOT . '/files/' . $a . '/' . $b, 0777);
        }
        $dir = APP_ROOT . '/files/' . $a . '/' . $b;

        // Guarantee uniqueness
        do {
            $random = bin2hex(random_bytes(32));
        } while (file_exists($dir . '/' . $random));
        return implode('/', [$a, $b, $random]);
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     * @param array $routerParams
     * @return ResponseInterface
     * @throws \Exception
     */
    public function __invoke(
        RequestInterface $request,
        ?ResponseInterface $response = null,
        array $routerParams = []
    ): ResponseInterface {
        if (empty($_FILES['file'])) {
            return $this->json(
                [
                    'error' => 'No file provided'
                ],
                StatusCode::HTTP_NOT_ACCEPTABLE
            );
        }
        try {
            $filter = new PublishFilter();
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
        $projectId = $this->projects->getIdByName($post['project']);
        if (!$this->publisherCheck($projectId)) {
            return $this->json(
                [
                    'error' => 'You are not the publisher'
                ],
                StatusCode::HTTP_FORBIDDEN
            );
        }
        $publisher = (int) $this->container['active_publisher_id'];
        if (!$this->publishers->publicKeyBelongsTo($post['publickey'], $publisher)) {
            return $this->json(
                [
                    'error' => 'Incorrect public key for your publisher ID'
                ],
                StatusCode::HTTP_FORBIDDEN
            );
        }
        $kr = new Keyring();
        $publicKey = $kr->load($post['publickey']);
        if (!($publicKey instanceof AsymmetricPublicKey)) {
            return $this->json(
                [
                    'error' => 'Invalid public key'
                ],
                StatusCode::HTTP_FORBIDDEN
            );
        }

        if (! AsymmetricFile::verify(
            $post['signature'],
            $publicKey,
            $_FILES['file']['tmp_name']
        )) {
            return $this->json(
                [
                    'error' => 'Invalid Ed25519 signature'
                ],
                StatusCode::HTTP_FORBIDDEN
            );
        }

        $filepath = $this->makeFilePath();
        if (!move_uploaded_file(
            $_FILES['file']['tmp_name'],
            APP_ROOT . '/' . $filepath
        )) {
            return $this->json(
                ['error' => 'Could not upload file.'],
                StatusCode::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        $channelId = $this->projects->getChannelByName($post['channel']);
        if (empty($channelId)) {
            $channelId = 1;
        }
        try {
            $this->projects->appendUpdate(
                $projectId,
                $channelId,
                $publisher,
                $filepath,
                $post
            );
            return $this->json([
                'message' => 'Update released successfully.'
            ]);
        } catch (\Throwable $ex) {
            return $this->json(['error' => $ex->getMessage()], 500);
        }
    }
}
