<?php

declare(strict_types=1);

namespace Kolya\Test\Import;

use PDO;
use Throwable;

final class Importer
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ImportRepository $repository,
        private readonly LeadTransformer $transformer,
        private readonly int $batchSize = 500,
    ) {
        if ($batchSize <= 0) {
            throw new \InvalidArgumentException('Розмір пакета має бути більшим за нуль.');
        }
    }

    /** @return array{import_id: int, rows: int, duration_ms: int, peak_memory_bytes: int} */
    public function import(string $path, string $originalFilename): array
    {
        memory_reset_peak_usage();
        $started = hrtime(true);
        $hash = hash_file('sha256', $path);
        if ($hash === false) {
            throw new \RuntimeException('Не вдалося обчислити хеш файлу.');
        }

        $importId = $this->repository->create($originalFilename, $hash);
        $count = 0;

        try {
            $this->pdo->beginTransaction();
            $batch = [];
            foreach ((new XlsxReader($path))->rows() as $source) {
                $batch[] = $this->transformer->transform($source['row'], $source['values']);
                $count++;
                if (count($batch) >= $this->batchSize) {
                    $this->repository->insertLeads($importId, $batch);
                    $batch = [];
                }
            }
            $this->repository->insertLeads($importId, $batch);
            $this->pdo->commit();

            $durationMs = (int) round((hrtime(true) - $started) / 1_000_000);
            $this->repository->complete($importId, $count, $durationMs);

            return [
                'import_id' => $importId,
                'rows' => $count,
                'duration_ms' => $durationMs,
                'peak_memory_bytes' => memory_get_peak_usage(true),
            ];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $durationMs = (int) round((hrtime(true) - $started) / 1_000_000);
            $this->repository->fail($importId, $count, $durationMs, $exception->getMessage());
            throw $exception;
        }
    }
}
