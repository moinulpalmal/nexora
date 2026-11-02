<?php

namespace App\Services\Admin;

use App\Contracts\IdentifiedEnum;
use App\Enums\EnumRegistry;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Admin → Enums: the read-only catalogue of every fixed list of values the
 * application ships — what each enum is for, the cases it allows, and what is
 * stored for each.
 *
 * Read from {@see EnumRegistry}, so an enum appears here the moment it is
 * registered there. Enums are code, like permissions: nothing on this screen
 * writes, and there is no deletion blocker because there is nothing to delete.
 */
class EnumService
{
    /**
     * Every registered enum, in registry order, with a one-paragraph summary
     * taken from its class docblock.
     *
     * @return list<array{
     *     id: string,
     *     name: string,
     *     module: string,
     *     summary: string,
     *     labelled: bool,
     *     cases: list<array{case: string, value: int|string, label: ?string}>,
     * }>
     */
    public function catalogue(): array
    {
        return array_map(fn (array $enum): array => [
            'id' => $enum['id'],
            'name' => $enum['name'],
            'module' => Str::headline($enum['group']),
            'summary' => $this->summary($enum['class']),
            'labelled' => $enum['labelled'],
            'cases' => $enum['cases'],
        ], EnumRegistry::toArray());
    }

    /**
     * The distinct modules, for the module filter.
     *
     * @return list<array{value: string, label: string}>
     */
    public function moduleOptions(): array
    {
        $modules = array_values(array_unique(array_column($this->catalogue(), 'module')));

        return array_map(fn (string $module): array => ['value' => $module, 'label' => $module], $modules);
    }

    /**
     * The first paragraph of the enum's docblock, as plain text.
     *
     * `{@see Foo}` becomes `Foo`; Markdown emphasis and code ticks are dropped.
     *
     * @param  class-string<IdentifiedEnum>  $class
     */
    protected function summary(string $class): string
    {
        $docComment = (new ReflectionClass($class))->getDocComment();

        if ($docComment === false) {
            return '';
        }

        $lines = [];

        foreach (preg_split('/\R/', $docComment) ?: [] as $line) {
            $line = trim(preg_replace('#^\s*(/\*\*|\*/|\*)#', '', $line) ?? '');

            if ($line === '' && $lines !== []) {
                break;
            }

            if ($line !== '' && $line !== '/') {
                $lines[] = $line;
            }
        }

        $summary = preg_replace('/\{@see\s+\\\\?(?:[\w\\\\]+\\\\)?([\w:()]+)\}/', '$1', implode(' ', $lines)) ?? '';

        return trim(str_replace(['**', '`'], '', $summary));
    }
}
