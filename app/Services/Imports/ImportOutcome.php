<?php

namespace App\Services\Imports;

/**
 * What {@see ImportRunner} did with a confirmed mapping.
 *
 * `final readonly` with a `toArray()`. `toArray()`'s
 * keys are what lands in `sheet_imports.created_count` / `updated_count` /
 * `skipped_count` / `rejected_count` and in the `sheet-import` `updated`
 * audit's `new_values` — the counts a reader needs to answer "what did this
 * import actually do?" without opening the spreadsheet again.
 *
 * `rejected` and `notes` stay empty for every all-or-nothing blueprint; only
 * an {@see ImportRowWriter} fills them.
 */
final readonly class ImportOutcome
{
    /**
     * @param  list<array{sheet: string, line: int, message: string}>  $rejected
     * @param  list<string>  $notes
     */
    public function __construct(
        public int $created,
        public int $updated,
        public int $skipped,
        public array $rejected = [],
        public array $notes = [],
    ) {}

    public function rowCount(): int
    {
        return $this->created + $this->updated + $this->skipped + count($this->rejected);
    }

    /**
     * @return array{created: int, updated: int, skipped: int, rejected: int}
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'rejected' => count($this->rejected),
        ];
    }
}
