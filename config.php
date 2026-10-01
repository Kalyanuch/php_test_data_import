<?php

declare(strict_types=1);

$root = __DIR__;
$dsn = getenv('DB_DSN') ?: 'sqlite:' . $root . '/storage/database.sqlite';

if (str_starts_with($dsn, 'sqlite:')) {
    $sqlitePath = substr($dsn, 7);
    if ($sqlitePath !== ':memory:' && $sqlitePath !== '' && $sqlitePath[0] !== '/') {
        $dsn = 'sqlite:' . $root . '/' . $sqlitePath;
    }
}

$integer = static function (string $name, int $default): int {
    $value = getenv($name);
    if ($value === false || $value === '') {
        return $default;
    }
    if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value <= 0) {
        throw new UnexpectedValueException("{$name} має бути додатним цілим числом.");
    }

    return (int) $value;
};

return [
    'timezone' => getenv('APP_TIMEZONE') ?: 'Europe/Kyiv',
    'database' => [
        'dsn' => $dsn,
        'user' => getenv('DB_USER') ?: null,
        'password' => getenv('DB_PASSWORD') ?: null,
    ],
    'upload' => [
        'max_bytes' => $integer('UPLOAD_MAX_BYTES', 50 * 1024 * 1024),
    ],
    'import' => [
        'batch_size' => $integer('IMPORT_BATCH_SIZE', 500),
    ],
];
