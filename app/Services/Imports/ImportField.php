<?php

namespace App\Services\Imports;

use Closure;

/**
 * One field a blueprint's target model accepts, and how a sheet cell becomes it.
 *
 * `final readonly` with promoted properties: it describes a spreadsheet cell
 * before anything decides which model attribute it becomes, a shape no Eloquent
 * model has.
 */
final readonly class ImportField
{
    /**
     * @param  string  $key  The model attribute this field writes — `code`.
     * @param  string  $label  What the mapping step calls it — `Code`.
     * @param  bool  $required  Whether a confirm without this field mapped is refused.
     * @param  int|null  $maxLength  Enforced in {@see ImportRunner}, not the database — the
     *                               same reason a form request validates before the insert.
     * @param  Closure(mixed): mixed|null  $transform  Applied after the raw cell is read and
     *                                                 before validation — trim + uppercase for a code field.
     *                                                 Absent means "use the value exactly as the cell holds it".
     */
    public function __construct(
        public string $key,
        public string $label,
        public bool $required = false,
        public ?int $maxLength = null,
        public ?Closure $transform = null,
    ) {}

    /**
     * Apply this field's transform to a raw cell value, or hand it back unchanged.
     */
    public function apply(mixed $value): mixed
    {
        return $this->transform === null ? $value : ($this->transform)($value);
    }

    /**
     * How the mapping step renders this field — never the transform, which is
     * a closure and has no meaning on the client.
     *
     * @return array{key: string, label: string, required: bool}
     */
    public function toArray(): array
    {
        return ['key' => $this->key, 'label' => $this->label, 'required' => $this->required];
    }
}
