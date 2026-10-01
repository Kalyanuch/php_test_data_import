<?php

declare(strict_types=1);

namespace Kolya\Test\Import;

final class LeadTransformer
{
    /** @param list<?string> $values
     *  @return list<int|string|null>
     */
    public function transform(int $sourceRow, array $values): array
    {
        if (count($values) !== count(XlsxReader::HEADERS)) {
            throw new \UnexpectedValueException("Рядок {$sourceRow}: неочікувана кількість колонок.");
        }

        $values = array_map(static fn (?string $value): ?string => $value === '' ? null : $value, $values);
        $phone = $values[4];
        $email = $values[5];

        return [
            $sourceRow,
            $values[0],
            ExcelDate::toSql($values[1]),
            $values[2],
            $values[3],
            $phone,
            $email,
            $values[6],
            $values[7],
            $values[8],
            $values[9],
            $values[10] === null ? null : number_format((float) $values[10], 2, '.', ''),
            $values[11],
            $values[12],
            $values[13],
            ExcelDate::toSql($values[14]),
            $phone !== null && preg_match('/^\+?380\d{9}$/', $phone) === 1 ? 1 : 0,
            $email === null || filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? 1 : 0,
        ];
    }
}

