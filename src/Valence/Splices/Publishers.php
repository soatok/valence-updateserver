<?php
declare(strict_types=1);
namespace Soatok\Valence\Splices;

use Interop\Container\Exception\ContainerException;
use ParagonIE\Quill\Quill;
use Slim\Container;
use Soatok\AnthroKit\Splice;

/**
 * Class Publishers
 * @package Soatok\Valence\Splices
 */
class Publishers extends Splice
{
    /** @var Quill $quill */
    protected $quill;

    /**
     * Publishers constructor.
     * @param Container $container
     * @throws ContainerException
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->quill = $container['quill'];
    }

    /**
     * @param int $publisherId
     * @return array
     */
    public function getById(int $publisherId): array
    {
        $publisher = $this->db->row(
            "SELECT * FROM valence_publishers WHERE publisherid = ?",
            $publisherId
        );
        if (!$publisher) {
            return [];
        }
        $publisher['public-keys'] = $this->db->run(
            "SELECT publickey, chronicle_publish FROM valence_publisher_publickeys WHERE NOT revoked"
        );
        return $publisher;
    }

    /**
     * @param int $publisherId
     * @param string $publicKey
     * @return int|null
     *
     * @throws \Exception
     */
    public function addPublicKey(int $publisherId, string $publicKey): bool
    {
        $this->db->beginTransaction();
        $publisherName = $this->db->cell(
            "SELECT name FROM valence_publishers WHERE publisherid = ?",
            $publisherId
        );
        if (!is_string($publisherName)) {
            $this->db->rollBack();
            return false;
        }
        $insertedId = $this->db->insertGet(
            'valence_publisher_publickeys',
            [
                'publisher' => $publisherId,
                'publickey' => $publicKey
            ],
            ' publickeyid'
        );

        $response = $this->writeAndParseChronicle([
            'action' => 'ADD-PUBLIC-KEY',
            'publisher' => $publisherName,
            'public-key' => $publicKey,
            'server-time' => (new \DateTime())->format(\DateTime::ATOM)
        ]);
        if (empty($response['results']['summaryhash'])) {
            $this->db->rollBack();
            return false;
        }
        $summary = $response['results']['summaryhash'];
        $this->db->update(
            'valence_publisher_publickeys',
            [
                'chronicle_publish' => $summary,
                'revoked' => false,
            ],
            [
                'publickeyid' => $insertedId
            ]
        );
        return $this->db->commit();
    }

    /**
     * @param int $publisherId
     * @param string $publicKey
     * @return bool
     *
     * @throws \Exception
     */
    public function revokePublicKey(int $publisherId, string $publicKey): bool
    {
        $this->db->beginTransaction();
        $publisherName = $this->db->cell(
            "SELECT name FROM valence_publishers WHERE publisherid = ?",
            $publisherId
        );
        if (!is_string($publisherName)) {
            $this->db->rollBack();
            return false;
        }
        $publicKeyId = $this->db->cell(
            "SELECT publickeyid FROM valence_publisher_publickeys 
             WHERE publisher = ? AND publickey = ?",
            $publisherId,
            $publicKey
        );

        $response = $this->writeAndParseChronicle([
            'action' => 'REVOKE-PUBLIC-KEY',
            'publisher' => $publisherName,
            'public-key' => $publicKey,
            'server-time' => (new \DateTime())->format(\DateTime::ATOM)
        ]);
        if (empty($response['results']['summaryhash'])) {
            $this->db->rollBack();
            return false;
        }
        $summary = $response['results']['summaryhash'];
        $this->db->update(
            'valence_publisher_publickeys',
            [
                'chronicle_revoke' => $summary,
                'revoked' => true,
            ],
            [
                'publickeyid' => $publicKeyId
            ]
        );
        return $this->db->commit();
    }

    /**
     * @param int $publisherId
     * @param int $project
     * @return bool
     */
    public function publisherOwns(int $publisherId, int $project): bool
    {
        $expect = $this->db->cell(
            "SELECT owner FROM valence_projects WHERE projectid = ?",
            $project
        );
        if ($expect) {
            return false;
        }
        return $publisherId === $expect;
    }

    /**
     * @param string $publicKey
     * @param int $publisherId
     * @return bool
     */
    public function publicKeyBelongsTo(string $publicKey, int $publisherId): bool
    {
        return $this->db->exists(
            "SELECT count(*)
            FROM valence_publisher_publickeys
            WHERE publickey = ? AND publisher = ?
            ",
            $publicKey,
            $publisherId
        );
    }

    /**
     * @param string $name
     * @return array
     * @throws \Exception
     */
    public function getProjects(string $name): array
    {
        $publisher = $this->db->cell(
            "SELECT publisherid FROM valence_publishers WHERE name = ?",
            $name
        );
        if (!$publisher) {
            throw new \Exception('Invalid publisher');
        }
        $projects = $this->db->col(
            "SELECT name FROM valence_projects WHERE owner = ?",
            0,
            $publisher
        );
        if (!$projects) {
            return [];
        }
        return $projects;
    }

    /**
     * @return array
     */
    public function listPublishers(): array
    {
        $publishers = $this->db->col(
            "SELECT name FROM valence_publishers ORDER BY name ASC",
            0
        );
        if (!$publishers) {
            return [];
        }
        return $publishers;
    }

    /**
     * @param array $data
     * @return array
     * @throws \ParagonIE\Sapient\Exception\HeaderMissingException
     * @throws \ParagonIE\Sapient\Exception\InvalidMessageException
     */
    public function writeAndParseChronicle(array $data): array
    {
        $response = $this->quill->write(json_encode($data));
        $json = (string) $response->getBody();
        return json_decode($json, true);
    }
}
