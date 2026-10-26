<?php

namespace App\Services\Admin;

use App\Models\Admin\Permission;
use Illuminate\Support\Collection;

/**
 * Read-side shaping of the permission catalogue for the role picker and the
 * permissions list. There is no write side: the catalogue is code
 * (`RolePermissionSeeder`), see ARCHITECTURE.md §9.1.
 */
class PermissionService
{
    public function __construct(private readonly PermissionDescriptions $descriptions) {}

    /**
     * Every permission, grouped by module, each with its help — the role
     * picker's data.
     *
     * @return array<string, list<array{id: int, name: string, resource: string, action: string, help: array{summary: string, note: ?string, where: ?string, curated: bool}}>>
     */
    public function groupedByModule(): array
    {
        return Permission::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->groupBy(fn (Permission $permission): string => $permission->module())
            ->map(fn (Collection $permissions): array => array_values(
                $permissions->map($this->describe(...))->all(),
            ))
            ->all();
    }

    /**
     * The module filter's options.
     *
     * @return list<array{value: string, label: string}>
     */
    public function moduleOptions(): array
    {
        return array_values(Permission::query()
            ->pluck('name')
            ->map(fn (string $name): string => explode('.', $name)[0])
            ->unique()
            ->sort()
            ->map(fn (string $module): array => ['value' => $module, 'label' => ucfirst($module)])
            ->all());
    }

    /**
     * Help per module, for the picker's group headings.
     *
     * @param  list<string>  $modules
     * @return array<string, array{summary: string, where: ?string}>
     */
    public function moduleHelp(array $modules): array
    {
        return collect($modules)
            ->mapWithKeys(fn (string $module): array => [$module => $this->descriptions->forModule($module)])
            ->all();
    }

    /**
     * @return array{summary: string, note: ?string, where: ?string, curated: bool}
     */
    public function help(string $name): array
    {
        return $this->descriptions->for($name);
    }

    /**
     * @return array{id: int, name: string, resource: string, action: string, help: array{summary: string, note: ?string, where: ?string, curated: bool}}
     */
    protected function describe(Permission $permission): array
    {
        [, $resource, $action] = array_pad(explode('.', $permission->name, 3), 3, '');

        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'resource' => $resource,
            'action' => $action,
            'help' => $this->descriptions->for($permission->name),
        ];
    }
}
