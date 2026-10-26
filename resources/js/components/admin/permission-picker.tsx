import { useState } from 'react';
import {
    ModuleHelpCard,
    PermissionHelpCard,
} from '@/components/admin/permission-help-card';
import { Checkbox } from '@/components/ui/checkbox';
import { HoverCard } from '@/components/ui/hover-card';
import type { ModuleHelpMap, PermissionGroups } from '@/types';

/**
 * Checkbox grid over the permission catalogue, grouped by module.
 *
 * Submits as `permissions[]`, so it drops straight into an Inertia `<Form>`.
 * Hovering or focusing a row or a module heading opens a card explaining what
 * it grants.
 */
export default function PermissionPicker({
    groups,
    moduleHelp = {},
    defaultSelected = [],
    disabled = false,
}: {
    groups: PermissionGroups;
    moduleHelp?: ModuleHelpMap;
    defaultSelected?: string[];
    disabled?: boolean;
}) {
    const [selected, setSelected] = useState<string[]>(defaultSelected);

    const toggle = (name: string) => {
        setSelected((current) =>
            current.includes(name)
                ? current.filter((item) => item !== name)
                : [...current, name],
        );
    };

    const toggleModule = (module: string, checked: boolean) => {
        const names = groups[module].map((permission) => permission.name);

        setSelected((current) =>
            checked
                ? [...new Set([...current, ...names])]
                : current.filter((item) => !names.includes(item)),
        );
    };

    const modules = Object.keys(groups);

    if (modules.length === 0) {
        return (
            <p className="text-sm text-base-content/60">
                No permissions exist yet. Run the RBAC seeder (`php artisan
                db:seed`).
            </p>
        );
    }

    return (
        <div className="grid gap-4 md:grid-cols-2">
            {modules.map((module) => {
                const permissions = groups[module];
                const checkedCount = permissions.filter((permission) =>
                    selected.includes(permission.name),
                ).length;

                const legend = (
                    <label
                        className="flex items-center gap-2 text-sm font-medium"
                        data-module={module}
                    >
                        <Checkbox
                            checked={checkedCount === permissions.length}
                            ref={(node) => {
                                if (node) {
                                    node.indeterminate =
                                        checkedCount > 0 &&
                                        checkedCount < permissions.length;
                                }
                            }}
                            disabled={disabled}
                            onChange={(event) =>
                                toggleModule(module, event.target.checked)
                            }
                        />
                        {module}
                    </label>
                );

                return (
                    <fieldset
                        key={module}
                        className="rounded-box border border-base-300/70 p-4"
                    >
                        <legend className="px-1">
                            {moduleHelp[module] ? (
                                <HoverCard
                                    content={
                                        <ModuleHelpCard
                                            module={module}
                                            help={moduleHelp[module]}
                                        />
                                    }
                                    testId="module-help"
                                >
                                    {legend}
                                </HoverCard>
                            ) : (
                                legend
                            )}
                        </legend>

                        <div className="mt-2 grid gap-1.5">
                            {permissions.map((permission) => (
                                <HoverCard
                                    key={permission.id}
                                    className="w-fit"
                                    content={
                                        <PermissionHelpCard
                                            name={permission.name}
                                            help={permission.help}
                                        />
                                    }
                                    testId="permission-help"
                                >
                                    <label
                                        className="flex items-center gap-2 text-sm"
                                        data-permission={permission.name}
                                    >
                                        <Checkbox
                                            name="permissions[]"
                                            value={permission.name}
                                            checked={selected.includes(
                                                permission.name,
                                            )}
                                            disabled={disabled}
                                            onChange={() =>
                                                toggle(permission.name)
                                            }
                                        />
                                        <span className="font-mono text-xs">
                                            {permission.resource}.
                                            {permission.action}
                                        </span>
                                    </label>
                                </HoverCard>
                            ))}
                        </div>
                    </fieldset>
                );
            })}
        </div>
    );
}
