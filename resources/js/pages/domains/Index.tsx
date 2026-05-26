import { AlertCircle, CheckCircle, Clock, Globe, Plus, RefreshCw, XCircle } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { StatusBadge } from '@/components/StatusBadge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { apiUrl, apiFetch } from '@/lib/utils';
import type { CheckType, Domain } from '@/types';

function OverallStatusIcon({ domain }: { domain: Domain }) {
    const checks = Object.values(domain.latest_checks);
    if (checks.length === 0) return <Clock className="size-5 text-muted-foreground" />;
    if (checks.some((c) => c?.status === 'critical' || c?.status === 'error')) {
        return <XCircle className="size-5 text-red-500" />;
    }
    if (checks.some((c) => c?.status === 'warning')) {
        return <AlertCircle className="size-5 text-yellow-500" />;
    }
    return <CheckCircle className="size-5 text-green-500" />;
}

export default function DomainsIndex() {
    const [domains, setDomains] = useState<Domain[]>([]);
    const [loading, setLoading] = useState(true);
    const [spinning, setSpinning] = useState<number | null>(null);

    useEffect(() => {
        apiFetch<{ data: Domain[] }>(apiUrl('/domains'))
            .then((res) => setDomains(res.data))
            .finally(() => setLoading(false));
    }, []);

    async function handleCheck(domainId: number) {
        setSpinning(domainId);
        try {
            await apiFetch(apiUrl(`/domains/${domainId}/check`), { method: 'POST' });
        } finally {
            setSpinning(null);
        }
    }

    if (loading) {
        return <div className="flex items-center justify-center h-64 text-muted-foreground">Loading…</div>;
    }

    return (
        <div className="flex flex-col gap-6 p-4">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">Monitored Domains</h1>
                    <p className="text-sm text-muted-foreground">{domains.length} domain{domains.length !== 1 ? 's' : ''} monitored</p>
                </div>
                <Button asChild>
                    <Link to="/domains/create">
                        <Plus className="mr-2 size-4" />
                        Add Domain
                    </Link>
                </Button>
            </div>

            {domains.length === 0 ? (
                <div className="flex flex-col items-center justify-center rounded-xl border border-dashed py-16 text-center">
                    <Globe className="mb-4 size-12 text-muted-foreground" />
                    <h2 className="text-lg font-medium">No domains yet</h2>
                    <p className="mt-1 text-sm text-muted-foreground">Add your first domain to start monitoring.</p>
                    <Button className="mt-4" asChild>
                        <Link to="/domains/create">
                            <Plus className="mr-2 size-4" />
                            Add Domain
                        </Link>
                    </Button>
                </div>
            ) : (
                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">Domain</th>
                                <th className="px-4 py-3 text-left font-medium">SSL</th>
                                <th className="px-4 py-3 text-left font-medium">WHOIS</th>
                                <th className="px-4 py-3 text-left font-medium">Uptime</th>
                                <th className="px-4 py-3 text-left font-medium">DNS</th>
                                <th className="px-4 py-3 text-left font-medium">Incidents</th>
                                <th className="px-4 py-3 text-left font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {domains.map((domain) => (
                                <tr key={domain.id} className="hover:bg-muted/30">
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <OverallStatusIcon domain={domain} />
                                            <div>
                                                <Link to={`/domains/${domain.id}`} className="font-medium hover:underline">
                                                    {domain.name}
                                                </Link>
                                                {!domain.is_active && (
                                                    <span className="ml-2 text-xs text-muted-foreground">(paused)</span>
                                                )}
                                            </div>
                                        </div>
                                    </td>
                                    {(['ssl', 'whois', 'uptime', 'dns'] as CheckType[]).map((type) => (
                                        <td key={type} className="px-4 py-3">
                                            <div className="flex flex-col gap-1">
                                                <StatusBadge status={domain.latest_checks[type]?.status} />
                                                {domain.latest_checks[type]?.value != null && (
                                                    <span className="text-xs text-muted-foreground">
                                                        {type === 'uptime' ? `${domain.latest_checks[type]!.value}ms` : `${domain.latest_checks[type]!.value}d`}
                                                    </span>
                                                )}
                                            </div>
                                        </td>
                                    ))}
                                    <td className="px-4 py-3">
                                        {domain.open_incidents_count > 0 ? (
                                            <Badge className="border-transparent bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                {domain.open_incidents_count} open
                                            </Badge>
                                        ) : (
                                            <span className="text-xs text-muted-foreground">None</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex gap-2">
                                            <Button variant="outline" size="sm" onClick={() => handleCheck(domain.id)}>
                                                <RefreshCw className={`size-3 transition-transform ${spinning === domain.id ? 'animate-spin' : ''}`} />
                                            </Button>
                                            <Button variant="outline" size="sm" asChild>
                                                <Link to={`/domains/${domain.id}`}>View</Link>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
