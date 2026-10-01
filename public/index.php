<?php

declare(strict_types=1);

use Kolya\Test\Database\Connection;
use Kolya\Test\Import\Importer;
use Kolya\Test\Import\ImportRepository;
use Kolya\Test\Import\LeadTransformer;

$root = dirname(__DIR__);
$config = require $root . '/bootstrap.php';
date_default_timezone_set($config['timezone']);
$maxUploadMb = (int) ceil($config['upload']['max_bytes'] / 1024 / 1024);

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['xlsx']) || !is_array($_FILES['xlsx'])) {
            if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
                throw new RuntimeException('Запит перевищує серверний ліміт post_max_size. Встановіть post_max_size не менше 51M.');
            }
            throw new RuntimeException('Оберіть XLSX-файл.');
        }

        $upload = $_FILES['xlsx'];
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Помилка завантаження файлу: код ' . ($upload['error'] ?? 'невідомий'));
        }
        if (($upload['size'] ?? 0) > $config['upload']['max_bytes']) {
            throw new RuntimeException("Файл перевищує дозволений розмір {$maxUploadMb} МБ.");
        }

        $name = basename((string) ($upload['name'] ?? 'import.xlsx'));
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new RuntimeException('Підтримуються лише файли XLSX.');
        }

        $temporaryPath = (string) $upload['tmp_name'];
        if (!is_uploaded_file($temporaryPath)) {
            throw new RuntimeException('Завантажений файл не пройшов перевірку HTTP upload.');
        }

        $pdo = Connection::create($config['database']);
        Connection::migrate($pdo, $root);
        $importer = new Importer(
            $pdo,
            new ImportRepository($pdo),
            new LeadTransformer(),
            $config['import']['batch_size'],
        );
        $result = $importer->import($temporaryPath, $name);
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Імпорт заявок</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #172033; background: #f3f5f8; }
        body { margin: 0; padding: 48px 20px; }
        main { max-width: 680px; margin: auto; background: white; padding: 32px; border-radius: 14px; box-shadow: 0 8px 30px #1f293714; }
        h1 { margin-top: 0; }
        p { line-height: 1.55; }
        form { display: grid; gap: 18px; margin-top: 28px; }
        input[type=file] { padding: 16px; border: 1px dashed #8290a8; border-radius: 8px; }
        button { width: fit-content; padding: 11px 22px; border: 0; border-radius: 8px; color: white; background: #185adb; font-weight: 650; cursor: pointer; }
        .message { padding: 14px 16px; border-radius: 8px; }
        .success { background: #e8f7ee; color: #155d32; }
        .error { background: #ffeded; color: #8d2020; }
        .hint { color: #5a6578; font-size: .94rem; }
    </style>
</head>
<body>
<main>
    <h1>Імпорт заявок</h1>
    <p>Завантажте книгу XLSX зі стандартними 15 колонками. Дані будуть перевірені та записані до бази пакетами.</p>

    <?php if ($result !== null): ?>
        <p class="message success">
            Імпорт №<?= (int) $result['import_id'] ?> завершено: записано
            <?= number_format($result['rows'], 0, ',', ' ') ?> рядків за
            <?= number_format($result['duration_ms'] / 1000, 2, ',', ' ') ?> с.
            Пікове використання пам’яті:
            <?= number_format($result['peak_memory_bytes'] / 1024 / 1024, 2, ',', ' ') ?> MiB.
        </p>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <p class="message error">Помилка: <?= escape($error) ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) $config['upload']['max_bytes'] ?>">
        <label for="xlsx"><strong>Файл XLSX</strong></label>
        <input id="xlsx" name="xlsx" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
        <button type="submit">Імпортувати</button>
    </form>
    <p class="hint">Максимальний розмір файлу: <?= $maxUploadMb ?> МБ. Не закривайте сторінку до завершення імпорту.</p>
</main>
</body>
</html>
