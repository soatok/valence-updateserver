<?php
declare(strict_types=1);
namespace Soatok\Valence\Endpoints;

use Interop\Container\Exception\ContainerException;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Container;
use Slim\Http\Headers;
use Slim\Http\Response;
use Slim\Http\StatusCode;
use Slim\Http\Stream;
use Soatok\AnthroKit\Endpoint;
use Soatok\Valence\AccessTrait;
use Soatok\Valence\Splices\Projects;

/**
 * Class Download
 * @package Soatok\Valence\Endpoints
 */
class Download extends Endpoint
{
    use AccessTrait;

    /** @var Projects $projects */
    private $projects;

    /**
     * Download constructor.
     *
     * @param Container $container
     * @throws ContainerException
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->projects = $this->splice('Projects');
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     * @param array $routerParams
     * @return ResponseInterface
     * @throws \SodiumException
     */
    public function __invoke(
        RequestInterface $request,
        ?ResponseInterface $response = null,
        array $routerParams = []
    ): ResponseInterface {
        $project = $routerParams['project'] ?? '';
        if (empty($project)) {
            return $this->redirect('/updates');
        }
        $file = $routerParams['file'] ?? '';
        if (empty($file)) {
            return $this->redirect('/updates/' . $project);
        }

        $update = $this->projects->getUpdate($project, $file);
        if (empty($update)) {
            return $this->redirect('/updates/' . $project);
        }

        // If this update isn't public...
        if ($update['channel_value'] > 0) {
            if (!$request->hasHeader('Valence-Access')) {
                // Only authorized users can download files from non-public channels.
                return $this->redirect('/updates/' . $project);
            }
            // Try up to 8 tokens, if passed along
            for ($i = 0; $i < 8; ++$i) {
                $token = $this->extractAccessToken($request, $i);
                if (!$token) {
                    // This isn't a valid token.
                    continue;
                }
                $access = (int) $this->accessCheck($token, (int) $update['project']);
                if ($access < $update['channel_value']) {
                    // This token doesn't grant access to this channel.
                    continue;
                }

                // If you're here, you can download the file
                return $this->serveUpdate($update);
            }
            // Redirect back to the updates list.
            return $this->redirect('/updates/' . $project);
        }

        return $this->serveUpdate($update);
    }

    /**
     * Serves an actual file to the end user.
     *
     * @param array $update
     * @return Response
     * @throws \SodiumException
     */
    protected function serveUpdate(array $update): Response
    {
        $headers = new Headers([
            'Content-Disposition' =>
                'attachment; filename="' . $update['publicid'] . '.zip"',

            'Content-Type' =>
                'application/zip',

            // Summary hash from Chronicle (cryptographic ledger):
            'Chronicle-Summary-Hash' =>
                $update['chronicle'],

            // Hashed so nobody is tempted to just trust what the server sends
            'Valence-Public-Key-ID' =>
                Base64UrlSafe::encodeUnpadded(
                    sodium_crypto_generichash($update['publickey'])
                ),
            // Signature of file contents
            'Valence-Signature' =>
                $update['signature']
        ]);
        $filePath = APP_ROOT . '/files/' . $update['filepath'];
        $fp = fopen($filePath, 'r');
        if (!$fp) {
            return $this->json(
                ['error' => 'Could not open file, server-side!'],
                StatusCode::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        $body = new Stream($fp);
        return new Response(
            StatusCode::HTTP_OK,
            $headers,
            $body
        );
    }
}
