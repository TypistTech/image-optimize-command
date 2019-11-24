<?php

declare(strict_types=1);

namespace TypistTech\ImageOptimizeCommand\Operations\AttachmentImages;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use TypistTech\ImageOptimizeCommand\LoggerInterface;
use TypistTech\ImageOptimizeCommand\Operations\Backup;
use TypistTech\ImageOptimizeCommand\Repositories\AttachmentRepository;

use function WP_CLI\Utils\normalize_path;

class Restore
{
    /**
     * The logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * The filesystem.
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * The repo.
     *
     * @var AttachmentRepository
     */
    protected $repo;

    /**
     * Optimize constructor.
     *
     * @param AttachmentRepository $repo       The repo.
     * @param Filesystem           $filesystem The file system.
     * @param LoggerInterface      $logger     The logger.
     */
    public function __construct(AttachmentRepository $repo, Filesystem $filesystem, LoggerInterface $logger)
    {
        $this->repo = $repo;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * Override attachment full sized images with their `.original` versions.
     *
     * @param int|int[] ...$ids The attachment IDs.
     *
     * @return void
     */
    public function execute(int ...$ids): void
    {
        $total = count($ids);
        $this->logger->section('Restoring full sized images for ' . $total . ' attachment(s)');

        array_map(function (int $id): void {
            $this->restore($id);
        }, $ids);

        $this->logger->info('Finished');
    }

    /**
     * Override an attachment full sized image with its `.original` version.
     *
     * @param int $id The attachment ID.
     *
     * @return void
     */
    protected function restore(int $id): void
    {
        try {
            $this->logger->debug('Restoring attachment ID: ' . $id);

            $paths = $this->repo->getFullSizedPaths($id);
            $path = $paths[0] ?? null;

            if (empty($path)) {
                $this->logger->error('Full sized image not found for attachment ID: ' . $id);
                return;
            }

            $path = normalize_path($path);
            $this->filesystem->rename(
                $path . Backup::ORIGINAL_EXTENSION,
                $path,
                true
            );

            $this->logger->debug('Marking attachment ID: ' . $id . ' as non-optimized.');
            $this->repo->markAsNonOptimized($id);
            $this->logger->notice('Restored attachment ID: ' . $id);
        } catch (IOException $exception) {
            $this->logger->error('Failed to restore full sized image for attachment ID: ' . $id);
        }
    }
}
