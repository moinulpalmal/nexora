import type { ImportReject } from '@/types';

/**
 * The rows a partial import rejects, and why — shown by the mapping step's
 * review and by the result panel after Import.
 *
 * `total` is the full count; `rejects` is what the server sent, capped, so
 * the remainder is named rather than silently dropped.
 */
export default function RejectList({
    rejects,
    total,
}: {
    rejects: ImportReject[];
    total: number;
}) {
    const remaining = total - rejects.length;

    return (
        <div className="max-h-48 overflow-y-auto rounded-box border border-base-300/70">
            <table className="table table-xs">
                <thead>
                    <tr>
                        <th>Tab</th>
                        <th>Row</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    {rejects.map((reject) => (
                        <tr key={`${reject.sheet}-${reject.line}`}>
                            <td className="whitespace-nowrap">
                                {reject.sheet}
                            </td>
                            <td>{reject.line}</td>
                            <td className="text-base-content/80">
                                {reject.message}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            {remaining > 0 && (
                <p className="px-3 py-2 text-xs text-base-content/60">
                    …and {remaining} more.
                </p>
            )}
        </div>
    );
}
