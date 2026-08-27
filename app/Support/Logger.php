<?php
declare(strict_types=1);

namespace App\Support;

use Throwable;

final class Logger
{
    public static function error(Throwable $error): void
    {
        $directory = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($directory)) @mkdir($directory, 0770, true);
        $entry = sprintf("[%s] %s in %s:%d\n%s\n", date(DATE_ATOM), $error->getMessage(), $error->getFile(), $error->getLine(), $error->getTraceAsString());
        @error_log($entry, 3, $directory . '/app.log');
    }
}
