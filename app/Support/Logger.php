<?php
declare(strict_types=1);

namespace App\Support;

use Throwable;

final class Logger
{
    public static function error(Throwable $error): string
    {
        $reference = strtoupper(substr(hash('sha256', uniqid('', true)), 0, 10));
        $directory = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($directory)) {
            @mkdir($directory, 0770, true);
        }
        $entry = sprintf("[%s] [%s] %s in %s:%d\n%s\n", date(DATE_ATOM), $reference, $error->getMessage(), $error->getFile(), $error->getLine(), $error->getTraceAsString());
        $logFile = $directory . '/app.log';
        if (!is_dir($directory) || !is_writable($directory) || @error_log($entry, 3, $logFile) === false) {
            error_log($entry);
        }
        return $reference;
    }
}
