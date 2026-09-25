<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/core/Database.php';

$db = (new Database())->getConnection();
$db->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        migration VARCHAR(180) NOT NULL PRIMARY KEY,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$applied = $db->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$files = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];
sort($files, SORT_STRING);
$pending = array_values(array_filter($files, static fn (string $file): bool => !in_array(basename($file), $applied, true)));

if ($pending === []) {
    echo "No hay migraciones pendientes.\n";
    exit(0);
}

if (!in_array('--run', $argv, true)) {
    echo "Migraciones pendientes:\n";
    foreach ($pending as $file) echo ' - ' . basename($file) . "\n";
    echo "Ejecuta php bin/migrate.php --run despues de crear un respaldo.\n";
    exit(0);
}

foreach ($pending as $file) {
    $sql = (string) file_get_contents($file);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
    echo 'Aplicando ' . basename($file) . "...\n";
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement !== '') $db->exec($statement);
    }
    $record = $db->prepare('INSERT IGNORE INTO schema_migrations (migration) VALUES (?)');
    $record->execute([basename($file)]);
}

echo "Migraciones aplicadas correctamente.\n";
