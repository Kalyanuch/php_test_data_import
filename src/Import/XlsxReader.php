<?php

declare(strict_types=1);

namespace Kolya\Test\Import;

use DOMElement;
use Generator;
use XMLReader;
use ZipArchive;

final class XlsxReader
{
    public const HEADERS = [
        'external_id', 'created_at', 'first_name', 'last_name', 'phone',
        'email', 'city', 'source', 'utm_campaign', 'product', 'budget_uah',
        'status', 'manager', 'comment', 'next_contact_at',
    ];

    /** @var list<string> */
    private array $sharedStrings = [];

    public function __construct(private readonly string $filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new \InvalidArgumentException('XLSX-файл не знайдено або він недоступний для читання.');
        }

        $zip = new ZipArchive();
        if ($zip->open($filename) !== true) {
            throw new \UnexpectedValueException('Файл не є коректним ZIP/XLSX-архівом.');
        }
        if ($zip->locateName('xl/worksheets/sheet1.xml') === false) {
            $zip->close();
            throw new \UnexpectedValueException('У XLSX відсутній перший аркуш.');
        }
        $zip->close();
    }

    /** @return Generator<int, array{row: int, values: list<?string>}> */
    public function rows(): Generator
    {
        $this->loadSharedStrings();
        $reader = new XMLReader();
        if (!$reader->open($this->zipUri('xl/worksheets/sheet1.xml'), null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new \RuntimeException('Не вдалося відкрити аркуш XLSX.');
        }

        $headerChecked = false;
        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }

                $rowNumber = (int) $reader->getAttribute('r');
                $node = $reader->expand();
                if (!$node instanceof DOMElement) {
                    continue;
                }

                $values = array_fill(0, count(self::HEADERS), null);
                foreach ($node->getElementsByTagName('c') as $cell) {
                    if (!$cell instanceof DOMElement) {
                        continue;
                    }
                    $index = self::columnIndex($cell->getAttribute('r'));
                    if ($index < 0 || $index >= count(self::HEADERS)) {
                        continue;
                    }
                    $values[$index] = $this->cellValue($cell);
                }

                if (!$headerChecked) {
                    if ($values !== self::HEADERS) {
                        throw new \UnexpectedValueException('Заголовки XLSX не відповідають очікуваному формату.');
                    }
                    $headerChecked = true;
                    continue;
                }

                yield ['row' => $rowNumber, 'values' => $values];
            }
        } finally {
            $reader->close();
            $this->sharedStrings = [];
        }

        if (!$headerChecked) {
            throw new \UnexpectedValueException('XLSX не містить рядка заголовків.');
        }
    }

    private function loadSharedStrings(): void
    {
        $reader = new XMLReader();
        if (!$reader->open($this->zipUri('xl/sharedStrings.xml'), null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new \RuntimeException('Не вдалося прочитати sharedStrings.xml.');
        }

        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'si') {
                    continue;
                }
                $node = $reader->expand();
                $text = '';
                if ($node instanceof DOMElement) {
                    foreach ($node->getElementsByTagName('t') as $part) {
                        $text .= $part->textContent;
                    }
                }
                $this->sharedStrings[] = $text;
            }
        } finally {
            $reader->close();
        }
    }

    private function cellValue(DOMElement $cell): ?string
    {
        $values = $cell->getElementsByTagName('v');
        if ($values->length === 0) {
            return null;
        }

        $value = $values->item(0)?->textContent;
        if ($value === null) {
            return null;
        }

        if ($cell->getAttribute('t') === 's') {
            $index = (int) $value;
            if (!array_key_exists($index, $this->sharedStrings)) {
                throw new \UnexpectedValueException("Некоректний індекс shared string: {$index}");
            }
            return $this->sharedStrings[$index];
        }

        // Excel сприйняв телефони з початковим '+' як формули. Беремо сам вираз,
        // лише якщо це безпечне числове значення, і ніколи не виконуємо формулу.
        $formulas = $cell->getElementsByTagName('f');
        if ($formulas->length > 0) {
            $formula = $formulas->item(0)?->textContent ?? '';
            if (preg_match('/^\+\d+$/', $formula) === 1) {
                return $formula;
            }
        }

        return $value;
    }

    private static function columnIndex(string $reference): int
    {
        if (preg_match('/^([A-Z]+)/', $reference, $matches) !== 1) {
            return -1;
        }

        $index = 0;
        foreach (str_split($matches[1]) as $letter) {
            $index = $index * 26 + ord($letter) - 64;
        }

        return $index - 1;
    }

    private function zipUri(string $path): string
    {
        return 'zip://' . $this->filename . '#' . $path;
    }
}

