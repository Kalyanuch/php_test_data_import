<?php

declare(strict_types=1);

namespace Kolya\Test\Config;

final class Env
{
    public static function load(string $filename): void
    {
        if (!is_file($filename)) {
            return;
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new \RuntimeException("Не вдалося прочитати файл {$filename}.");
        }

        foreach ($lines as $number => $line) {
            $line = trim($number === 0 ? ltrim($line, "\xEF\xBB\xBF") : $line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            [$name, $rawValue] = array_pad(explode('=', $line, 2), 2, null);
            $name = trim($name);
            if ($rawValue === null || preg_match('/^[A-Z_][A-Z0-9_]*$/i', $name) !== 1) {
                throw new \UnexpectedValueException(sprintf(
                    'Некоректний запис у %s, рядок %d.',
                    basename($filename),
                    $number + 1,
                ));
            }

            // Змінні, передані ОС або сервером, мають пріоритет над локальним .env.
            if (getenv($name) !== false) {
                continue;
            }

            $value = self::parseValue(trim($rawValue), $filename, $number + 1);
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }

    private static function parseValue(string $value, string $filename, int $line): string
    {
        if ($value === '') {
            return '';
        }

        $quote = $value[0];
        if ($quote === '"' || $quote === "'") {
            if (!str_ends_with($value, $quote)) {
                throw new \UnexpectedValueException(sprintf(
                    'Незакрита лапка у %s, рядок %d.',
                    basename($filename),
                    $line,
                ));
            }
            $value = substr($value, 1, -1);

            return $quote === '"'
                ? strtr($value, ['\\n' => "\n", '\\r' => "\r", '\\t' => "\t", '\\"' => '"', '\\\\' => '\\'])
                : $value;
        }

        return preg_replace('/\s+#.*$/', '', $value) ?? $value;
    }
}

