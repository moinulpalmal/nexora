import { Head } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import Combobox from '@/components/ui/combobox';
import { index } from '@/routes/admin/enums';
import type { EnumCatalogueItem, StatusOption } from '@/types';

type Props = {
    enums: EnumCatalogueItem[];
    modules: StatusOption[];
};

/**
 * Admin → Enums: the read-only catalogue of every fixed list of values.
 *
 * Enums are code (`EnumRegistry`), a short list that grows one class at a time,
 * so this page is a deliberate exception to the paginated list standard
 * (ARCHITECTURE.md §8.6): one card per enum, grouped by module, with a search
 * box and a module filter applied in the browser.
 */
export default function EnumsIndex({ enums, modules }: Props) {
    const [search, setSearch] = useState('');
    const [module, setModule] = useState('');

    const needle = search.trim().toLowerCase();

    const visible = enums.filter(
        (item) =>
            (module === '' || item.module === module) &&
            (needle === '' || haystack(item).includes(needle)),
    );

    const groups = modules
        .map((option) => ({
            module: option.value,
            items: visible.filter((item) => item.module === option.value),
        }))
        .filter((group) => group.items.length > 0);

    return (
        <>
            <Head title="Enums" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Enums"
                    description="Every fixed list of values the application uses: what each is for, its choices, and the value stored for each. Enums are defined in code; nothing here can be changed."
                />

                <div className="flex flex-wrap items-center gap-3">
                    <label className="input w-full max-w-md">
                        <span className="sr-only">Search enums</span>
                        <input
                            type="search"
                            data-test="enum-search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search by enum, choice or stored value"
                        />
                    </label>

                    <Combobox
                        className="w-56"
                        aria-label="Filter by module"
                        data-test="enum-module-filter"
                        value={module}
                        onChange={(value) => setModule(String(value ?? ''))}
                        options={[
                            { value: '', label: 'All modules' },
                            ...modules,
                        ]}
                    />
                </div>

                {groups.length === 0 && (
                    <p className="text-center text-base-content/60">
                        No enums match these filters.
                    </p>
                )}

                {groups.map((group) => (
                    <section key={group.module} className="space-y-3">
                        <h3 className="text-sm font-semibold tracking-wide text-base-content/70 uppercase">
                            {group.module}
                        </h3>

                        <div className="grid items-start gap-4 lg:grid-cols-2">
                            {group.items.map((item) => (
                                <EnumCard key={item.id} item={item} />
                            ))}
                        </div>
                    </section>
                ))}
            </div>
        </>
    );
}

function EnumCard({ item }: { item: EnumCatalogueItem }) {
    return (
        <article
            className="card border border-base-300/70 bg-base-100"
            data-test={`enum-${item.id}`}
        >
            <div className="card-body gap-3 p-4">
                <div className="flex flex-wrap items-baseline justify-between gap-2">
                    <h4 className="card-title text-base">{item.name}</h4>
                    <span className="badge badge-ghost font-mono badge-sm">
                        {item.id}
                    </span>
                </div>

                {item.summary !== '' && (
                    <p className="text-sm text-base-content/70">
                        {item.summary}
                    </p>
                )}

                <div className="max-h-80 overflow-auto rounded-box border border-base-300/70">
                    <table className="table table-pin-rows table-sm">
                        <thead>
                            <tr>
                                <th>Case</th>
                                <th>Stored value</th>
                                {item.labelled && <th>Label</th>}
                            </tr>
                        </thead>
                        <tbody>
                            {item.cases.map((entry) => (
                                <tr key={entry.case}>
                                    <td className="font-mono text-sm">
                                        {entry.case}
                                    </td>
                                    <td className="font-mono text-sm">
                                        {entry.value}
                                    </td>
                                    {item.labelled && (
                                        <td className="text-sm">
                                            {entry.label}
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </article>
    );
}

/** Everything the search box matches against, lowercased. */
function haystack(item: EnumCatalogueItem): string {
    return [
        item.name,
        item.id,
        item.summary,
        ...item.cases.flatMap((entry) => [
            entry.case,
            String(entry.value),
            entry.label ?? '',
        ]),
    ]
        .join(' ')
        .toLowerCase();
}

EnumsIndex.layout = {
    breadcrumbs: [{ title: 'Enums', href: index() }],
};
