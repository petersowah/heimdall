import { Badge } from '@/components/ui/badge';
import type { CheckStatus } from '@/types';

const config: Record<CheckStatus, { label: string; className: string }> = {
    ok: { label: 'OK', className: 'border-transparent bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' },
    warning: { label: 'Warning', className: 'border-transparent bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' },
    critical: { label: 'Critical', className: 'border-transparent bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' },
    error: { label: 'Error', className: 'border-transparent bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' },
};

export function StatusBadge({ status }: { status: CheckStatus | undefined }) {
    if (!status) return <Badge variant="outline">Pending</Badge>;
    const { label, className } = config[status];
    return <Badge className={className}>{label}</Badge>;
}
