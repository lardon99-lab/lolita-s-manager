<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$sessionPath = dirname(__DIR__) . '/storage/sessions';
if (!is_dir($sessionPath)) mkdir($sessionPath, 0770, true);
session_save_path($sessionPath);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
