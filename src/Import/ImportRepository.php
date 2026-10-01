<?php

declare(strict_types=1);

namespace Kolya\Test\Import;

use PDO;

final class ImportRepository
{
    private const LEAD_COLUMNS = [
        'import_id', 'source_row', 'external_id', 'created_at', 'first_name',
        'last_name', 'phone', 'email', 'city', 'source', 'utm_campaign',
        'product', 'budget_uah', 'status', 'manager', 'comment',
        'next_contact_at', 'phone_is_valid', 'email_is_valid',
    ];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(string $filename, string $sha256): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO imports (original_filename, file_sha256, status, started_at)
             VALUES (?, ?, 'processing', CURRENT_TIMESTAMP)"
        );
        $statement->execute([$filename, $sha256]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param list<list<int|string|null>> $rows */
    public function insertLeads(int $importId, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $rowPlaceholder = '(' . implode(',', array_fill(0, count(self::LEAD_COLUMNS), '?')) . ')';
        $sql = 'INSERT INTO leads (' . implode(',', self::LEAD_COLUMNS) . ') VALUES '
            . implode(',', array_fill(0, count($rows), $rowPlaceholder));
        $parameters = [];
        foreach ($rows as $row) {
            $parameters[] = $importId;
            array_push($parameters, ...$row);
        }
        $this->pdo->prepare($sql)->execute($parameters);
    }

    public function complete(int $importId, int $rows, int $durationMs): void
    {
        $statement = $this->pdo->prepare(
            "UPDATE imports SET status = 'completed', rows_read = ?, rows_inserted = ?,
             duration_ms = ?, finished_at = CURRENT_TIMESTAMP WHERE id = ?"
        );
        $statement->execute([$rows, $rows, $durationMs, $importId]);
    }

    public function fail(int $importId, int $rows, int $durationMs, string $message): void
    {
        $statement = $this->pdo->prepare(
            "UPDATE imports SET status = 'failed', rows_read = ?, rows_inserted = 0,
             duration_ms = ?, error_message = ?, finished_at = CURRENT_TIMESTAMP WHERE id = ?"
        );
        $statement->execute([$rows, $durationMs, mb_substr($message, 0, 2000), $importId]);
    }
}

