<?php

declare(strict_types=1);

use Kolya\Test\Database\Connection;
use Kolya\Test\Import\Importer;
use Kolya\Test\Import\ImportRepository;
use Kolya\Test\Import\LeadTransformer;

$root = dirname(__DIR__);
$config = require $root . '/bootstrap.php';
date_default_timezone_set($config['timezone']);

$path = $argv[1] ?? null;
if ($path === null) {
    fwrite(STDERR, "Використання: php bin/import.php <file.xlsx>\n");
    exit(2);
}

try {
    $realPath = realpath($path);
    if ($realPath === false) {
        throw new RuntimeException("Файл не знайдено: {$path}");
    }
    $pdo = Connection::create($config['database']);
    Connection::migrate($pdo, $root);
    $importer = new Importer(
        $pdo,
        new ImportRepository($pdo),
        new LeadTransformer(),
        $config['import']['batch_size'],
    );
    $result = $importer->import($realPath, basename($realPath));
    printf(
        "Імпорт №%d завершено: %d рядків за %.3f с. Пікове використання пам'яті: %.2f MiB.\n",
        $result['import_id'],
        $result['rows'],
        $result['duration_ms'] / 1000,
        $result['peak_memory_bytes'] / 1024 / 1024,
    );
} catch (Throwable $exception) {
    fwrite(STDERR, 'Помилка: ' . $exception->getMessage() . "\n");
    exit(1);
}
