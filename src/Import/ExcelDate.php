<?php

declare(strict_types=1);

namespace Kolya\Test\Import;

use DateTimeImmutable;
use DateTimeZone;

final class ExcelDate
{
    public static function toSql(?string $serial): ?string
    {
        if ($serial === null || $serial === '') {
            return null;
        }

        if (!is_numeric($serial)) {
            throw new \UnexpectedValueException("Некоректна Excel-дата: {$serial}");
        }

        $value = (float) $serial;
        $days = (int) floor($value);
        $seconds = (int) round(($value - $days) * 86400);
        $origin = new DateTimeImmutable('1899-12-30 00:00:00', new DateTimeZone('UTC'));

        return $origin
            ->modify(sprintf('+%d days', $days))
            ->modify(sprintf('+%d seconds', $seconds))
            ->format('Y-m-d H:i:s');
    }
}

