import { cn } from '@/utils/helpers';
import EmptyState from './EmptyState';

export default function DataTable({
    columns = [],
    rows = [],
    keyField = 'id',
    onRowClick,
    emptyTitle = 'No records found',
    emptyDescription = 'Try adjusting your filters or check back later.',
    className = '',
}) {
    if (!rows.length) {
        return <EmptyState title={emptyTitle} description={emptyDescription} />;
    }

    return (
        <div className={cn('table-wrap', className)}>
            <table className="data-table">
                <thead>
                    <tr>
                        {columns.map((column) => (
                            <th key={column.key} className={column.headerClassName}>
                                {column.label}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 bg-white">
                    {rows.map((row) => (
                        <tr
                            key={row[keyField]}
                            onClick={onRowClick ? () => onRowClick(row) : undefined}
                            className={cn(onRowClick && 'cursor-pointer hover:bg-slate-50')}
                        >
                            {columns.map((column) => (
                                <td key={column.key} className={column.className}>
                                    {column.render
                                        ? column.render(row)
                                        : row[column.key]}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
