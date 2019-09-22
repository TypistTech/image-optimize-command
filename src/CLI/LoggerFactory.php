<?php

declare(strict_types=1);

namespace TypistTech\ImageOptimizeCommand\CLI;

use TypistTech\ImageOptimizeCommand\LoggerInterface;

class LoggerFactory
{
    public const HOOK = 'TypistTech/ImageOptimizeCommand/CLI/Logger';

    /**
     * Creates a logger.
     *
     * @return LoggerInterface
     */
    public static function create(): LoggerInterface
    {
        return apply_filters(
            static::HOOK,
            new Logger()
        );
    }
}
