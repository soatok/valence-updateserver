<?php
declare(strict_types=1);
namespace Soatok\Valence\Splices;

use Interop\Container\Exception\ContainerException;
use ParagonIE\ConstantTime\Base32;
use ParagonIE\ConstantTime\Hex;
use ParagonIE\Quill\Quill;
use Slim\Container;
use Soatok\AnthroKit\Splice;
use Soatok\DholeCrypto\SymmetricFile;
use Soatok\Valence\ChronicleTrait;

/**
 * Class Products
 * @package Soatok\Valence\Splices
 */
class Projects extends Splice
{
    use ChronicleTrait;

    /** @var Quill $quill */
    protected $quill;

    /**
     * Projects constructor.
     * @param Container $container
     * @throws ContainerException
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->quill = $container['quill'];
    }

    /**
     * @param int $projectId
     * @param int $channelId
     * @param int $publisher
     * @param string $filepath
     * @param array $post
     * @return bool
     * @throws \Exception
     */
    public function appendUpdate(
        int $projectId,
        int $channelId,
        int $publisher,
        string $filepath,
        array $post
    ): bool {
        $this->db->beginTransaction();
        $publicKeyId = $this->db->cell(
            "SELECT publickeyid
            FROM valence_publisher_publickeys
            WHERE publickey = ? AND publisher = ?",
            $post['publickey'],
            $publisher
        );
        do {
            $publicId = Base32::encodeUnpadded(random_bytes(20));
        } while ($this->db->exists(
            "SELECT count(*) FROM valence_project_updates WHERE publicid = ?",
            $publicId
        ));
        $updateId = $this->db->insertGet(
            'valence_project_updates',
            [
                'project' => $projectId,
                'publickey' => $publicKeyId,
                'channel' => $channelId,
                'version' => $post['version'],
                'signature' => $post['signature'],
                'filepath' => $filepath,
                'publicid' => $publicId
            ],
            'updateid'
        );
        $response = $this->writeAndParseChronicle([
            'action' => 'RELEASE-UPDATE',
            'project' => $post['project'],
            'channel' => $post['channel'],
            'version' => $post['version'],
            'publickey' => $post['publickey'],
            'signature' => $post['signature'],
            'filehash' => Hex::encode(
                SymmetricFile::hash(APP_ROOT . '/files/' . $filepath)
            ),
            'server-time' => (new \DateTime())->format(\DateTime::ATOM)
        ]);
        if (empty($response['results']['summaryhash'])) {
            $this->db->rollBack();
            return false;
        }
        $this->db->update(
            'valence_project_updates',
            [
                'chronicle_publish' =>
                    $response['results']['summaryhash']
            ],
            ['updateid' => $updateId]
        );
        return $this->db->commit();
    }

    /**
     * @param string $name
     * @return int|null
     */
    public function getChannelByName(string $name): ?int
    {
        $channel = $this->db->cell(
            "SELECT channelid FROM valence_project_channels WHERE name = ?",
            $name
        );
        if (!$channel) {
            return null;
        }
        return (int) $channel;
    }

    /**
     * @param string $name
     * @return int|null
     */
    public function getIdByName(string $name): ?int
    {
        $project = $this->db->cell(
            "SELECT projectid FROM valence_projects WHERE name = ?",
            $name
        );
        if (!$project) {
            return null;
        }
        return (int) $project;
    }

    /**
     * @return array
     */
    public function listProjects(): array
    {
        $products = $this->db->run(
            "SELECT 
                projectid AS id,
                publisher,
                name
             FROM valence_projects
             ORDER BY publisher ASC, name ASC"
        );
        if (empty($products)) {
            return [];
        }
        return $products;
    }

    /**
     * @param string $name
     * @param string $channel
     * @return array
     */
    public function listUpdates(string $name, string $channel = ''): array
    {
        $project = $this->db->cell(
            "SELECT projectid FROM valence_projects WHERE name = ?",
            $name
        );
        if (!$project) {
            return [];
        }

        if ($channel) {
            $updates = $this->db->run(
                "SELECT 
                vpu.*,
                vp.publisher AS publisher,
                vp.name AS project_name,
                pk.publickey,
                vpc.name AS channel,
                vpc.value AS channel_value
             FROM valence_project_updates vpu
             JOIN valence_projects vp ON vpu.project = vp.projectid
             JOIN valence_publisher_publickeys pk ON vpu.publickey = pk.publickeyid
             JOIN valence_project_channels vpc on vpu.channel = vpc.channelid
             WHERE vp.name = ? AND vpc.name = ? AND NOT vpu.revoked
             ORDER BY vpu.created DESC",
                $name,
                $channel
            );
        } else {
            $updates = $this->db->run(
                "SELECT 
                vpu.*,
                vp.publisher AS publisher,
                vp.name AS project_name,
                pk.publickey,
                vpc.name AS channel,
                vpc.value AS channel_value
             FROM valence_project_updates vpu
             JOIN valence_projects vp ON vpu.project = vp.projectid
             JOIN valence_publisher_publickeys pk ON vpu.publickey = pk.publickeyid
             JOIN valence_project_channels vpc on vpu.channel = vpc.channelid
             WHERE vp.name = ? AND NOT vpu.revoked
             ORDER BY vpu.created DESC",
                $name
            );
        }
        if (!$updates) {
            return [];
        }
        return $updates;
    }

    /**
     * @param string $projectName
     * @param string $filePublicId
     * @return array
     */
    public function getUpdate(string $projectName, string $filePublicId): array
    {
        $update = $this->db->row(
            "SELECT 
                vpu.*,
                vpu.chronicle_publish AS chronicle,
                vp.publisher AS publisher,
                pk.publickey,
                vpc.name AS channel,
                vpc.value AS channel_value,
                vp.name AS project_name
             FROM valence_project_updates vpu
             JOIN valence_projects vp ON vpu.project = vp.projectid
             JOIN valence_publisher_publickeys pk ON vpu.publickey = pk.publickeyid
             JOIN valence_project_channels vpc on vpu.channel = vpc.channelid
             WHERE vp.name = ? AND vpu.publicid = ? AND NOT vpu.revoked",
            $projectName,
            $filePublicId
        );
        if (!$update) {
            return [];
        }
        return $update;
    }
}
