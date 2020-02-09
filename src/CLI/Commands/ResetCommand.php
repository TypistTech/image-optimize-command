<?php

declare(strict_types=1);

namespace TypistTech\ImageOptimizeCommand\CLI\Commands;

use Symfony\Component\Filesystem\Filesystem;
use TypistTech\ImageOptimizeCommand\CLI\LoggerFactory;
use TypistTech\ImageOptimizeCommand\Operations\AttachmentImages\Restore;
use TypistTech\ImageOptimizeCommand\Operations\AttachmentImages\RestoreFactory;
use TypistTech\ImageOptimizeCommand\Repositories\AttachmentRepository;
use WP_CLI;

class ResetCommand
{
    /**
     * Restore all full sized images and drop all meta flags.
     *
     * ## OPTIONS
     *
     * [--yes]
     * : Answer yes to the confirmation message.
     *
     * ## EXAMPLES
     *
     *     # Reset all attachment changes
     *     $ wp image-optimize reset
     *     $ wp media regenerate
     */
    public function __invoke($args, $assocArgs): void
    {
        $logger = LoggerFactory::create();

        $logger->section('Going to reset all attachment changes');
        $logger->info('    * find all optimized attachments');
        $logger->info('    * restore the original full sized images');
        $logger->info('    * drop all meta flags (mark as non-optimized)');

        WP_CLI::confirm('Are you sure you want to reset all attachment optimization?', $assocArgs);

        $logger->section('Finding all optimized attachments');
        $repo = new AttachmentRepository();
        $ids = $repo->takeOptimized(PHP_INT_MAX);
        $logger->notice(count($ids) . ' optimized attachment(s) found.');

        $logger->section('Restoring the original full sized images');
        $restoreOperation = RestoreFactory::create(
            $repo,
            new Filesystem(),
            $logger
        );
        $restoreOperation->execute(...$ids);
        $logger->notice('Original full sized images restored.');

        $logger->section("Dropping all optimized attachments' meta flags");
        $repo->markAllAsUnoptimized();
        $logger->notice('Meta flags dropped.');

        $logger->section('Actions Required!');
        $logger->warning('You should regenerate the all thumbnails now');
        $logger->warning('by running the following command -');
        $logger->warning('    $ wp media regenerate');
    }
}
