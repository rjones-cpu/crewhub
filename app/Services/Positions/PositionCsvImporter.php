<?php

namespace App\Services\Positions;

use App\Models\Position;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class PositionCsvImporter
{
    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function import(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new RuntimeException('The CSV file could not be read.');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $seen = [];
        $map = null;

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if ($this->isEmpty($row)) {
                    continue;
                }

                if ($map === null) {
                    $map = $this->columnMap($row);

                    if ($map['header']) {
                        continue;
                    }
                }

                $name = $this->cell($row, $map['name']);
                $code = $this->cell($row, $map['code']);
                $description = $this->cell($row, $map['description']);

                if ($name === '') {
                    $skipped++;

                    continue;
                }

                $key = Str::lower($name);

                if (isset($seen[$key])) {
                    $skipped++;

                    continue;
                }

                $seen[$key] = true;

                $position = Position::query()
                    ->whereRaw('LOWER(name) = ?', [$key])
                    ->first();

                $payload = [
                    'name' => $name,
                    'code' => $code !== '' ? $code : null,
                    'description' => $description !== '' ? $description : null,
                    'is_active' => true,
                ];

                if ($position) {
                    $position->fill($payload)->save();
                    $updated++;

                    continue;
                }

                Position::query()->create($payload);
                $created++;
            }
        } finally {
            fclose($handle);
        }

        return compact('created', 'updated', 'skipped');
    }

    /**
     * @param  list<string|null>  $row
     * @return array{header: bool, name: int, code: int|null, description: int|null}
     */
    private function columnMap(array $row): array
    {
        $normalized = array_map(
            fn ($value) => Str::lower(trim((string) $value)),
            $row,
        );

        $nameIndex = array_search('name', $normalized, true);

        if ($nameIndex === false) {
            $nameIndex = array_search('position', $normalized, true);
        }

        if ($nameIndex !== false) {
            $codeIndex = array_search('code', $normalized, true);
            $descriptionIndex = array_search('description', $normalized, true);

            return [
                'header' => true,
                'name' => (int) $nameIndex,
                'code' => $codeIndex === false ? null : (int) $codeIndex,
                'description' => $descriptionIndex === false ? null : (int) $descriptionIndex,
            ];
        }

        return [
            'header' => false,
            'name' => 0,
            'code' => isset($row[1]) ? 1 : null,
            'description' => isset($row[2]) ? 2 : null,
        ];
    }

    /**
     * @param  list<string|null>  $row
     */
    private function cell(array $row, ?int $index): string
    {
        if ($index === null || ! array_key_exists($index, $row)) {
            return '';
        }

        return trim((string) $row[$index]);
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
