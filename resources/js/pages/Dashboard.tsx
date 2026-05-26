import { AlertCircle, CheckCircle, Clock, Globe, Plus, XCircle } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { apiUrl, apiFetch } from '@/lib/utils';
import type { CheckStatus, CheckType, Domain } from '@/types';

function OverallStatus({ domain }: { domain: Domain }) {
    const checks = Object.values(domain.latest_checks);
    if (checks.length === 0) return <Clock className="size-4 text-muted-foreground" />;
    if (checks.some((c) => c?.status === 'critical' || c?.status === 'error')) {
        return <XCircle className="size-4 text-red-500" />;
    }
    if (checks.some((c) => c?.status === 'warning')) {
        return <AlertCircle className="size-4 text-yellow-500" />;
    }
    return <CheckCircle className="size-4 text-green-500" />;
}

function StatusDot({ status }: { status: CheckStatus | undefined }) {
    if (!status) return <span className="size-2 rounded-full bg-gray-300" />;
    const colors: Record<CheckStatus, string> = {
        ok: 'bg-green-500',
        warning: 'bg-yellow-500',
        critical: 'bg-red-500',
        error: 'bg-gray-400',
    };
    return <span className={`size-2 rounded-full ${colors[status]}`} />;
}

export default function Dashboard() {
    const [domains, setDomains] = useState<Domain[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        apiFetch<{ data: Domain[] }>(apiUrl('/dashboard'))
            .then((res) => setDomains(res.data))
            .finally(() => setLoading(false));
    }, []);

    const totalDomains = domains.length;
    const domainsDown = domains.filter((d) => Object.values(d.latest_checks).some((c) => c?.status === 'critical')).length;
    const openIncidents = domains.reduce((sum, d) => sum + d.open_incidents_count, 0);
    const domainsWarning = domains.filter((d) => {
        const checks = Object.values(d.latest_checks);
        return !checks.some((c) => c?.status === 'critical') && checks.some((c) => c?.status === 'warning');
    }).length;

    if (loading) {
        return <div className="flex items-center justify-center h-64 text-muted-foreground">Loading…</div>;
    }

    return (
        <div className="flex flex-col gap-6 p-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold">Heimdall</h1>
                <Button asChild size="sm">
                    <Link to="/domains/create">
                        <Plus className="mr-1 size-3" />
                        Add Domain
                    </Link>
                </Button>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div className="rounded-xl border p-4">
                    <p className="text-sm text-muted-foreground">Total Domains</p>
                    <p className="mt-1 text-3xl font-bold">{totalDomains}</p>
                </div>
                <div className="rounded-xl border p-4">
                    <p className="text-sm text-muted-foreground">Down</p>
                    <p className={`mt-1 text-3xl font-bold ${domainsDown > 0 ? 'text-red-500' : ''}`}>{domainsDown}</p>
                </div>
                <div className="rounded-xl border p-4">
                    <p className="text-sm text-muted-foreground">Warnings</p>
                    <p className={`mt-1 text-3xl font-bold ${domainsWarning > 0 ? 'text-yellow-500' : ''}`}>{domainsWarning}</p>
                </div>
                <div className="rounded-xl border p-4">
                    <p className="text-sm text-muted-foreground">Open Incidents</p>
                    <p className={`mt-1 text-3xl font-bold ${openIncidents > 0 ? 'text-red-500' : ''}`}>{openIncidents}</p>
                </div>
            </div>

            {domains.length === 0 ? (
                <div className="flex flex-col items-center justify-center rounded-xl border border-dashed py-16 text-center">
                    <Globe className="mb-4 size-12 text-muted-foreground" />
                    <h2 className="text-lg font-medium">No domains monitored yet</h2>
                    <p className="mt-1 text-sm text-muted-foreground">Add a domain to start monitoring SSL, uptime, DNS, and expiry.</p>
                    <Button className="mt-4" asChild>
                        <Link to="/domains/create">
                            <Plus className="mr-2 size-4" />
                            Add Your First Domain
                        </Link>
                    </Button>
                </div>
            ) : (
                <div className="rounded-xl border overflow-hidden">
                    <div className="border-b px-4 py-3 flex items-center justify-between">
                        <h2 className="font-medium">All Domains</h2>
                        <Link to="/domains" className="text-sm text-muted-foreground hover:underline">View all →</Link>
                    </div>
                    <div className="divide-y">
                        {domains.slice(0, 10).map((domain) => (
                            <Link
                                key={domain.id}
                                to={`/domains/${domain.id}`}
                                className="flex items-center gap-4 px-4 py-3 hover:bg-muted/30 transition-colors"
                            >
                                <OverallStatus domain={domain} />
                                <span className="font-medium flex-1">{domain.name}</span>
                                <div className="flex items-center gap-3">
                                    {(['ssl', 'whois', 'uptime', 'dns'] as CheckType[]).map((type) => (
                                        <div key={type} className="flex items-center gap-1">
                                            <StatusDot status={domain.latest_checks[type]?.status} />
                                            <span className="text-xs text-muted-foreground">{type.toUpperCase()}</span>
                                        </div>
                                    ))}
                                </div>
                                {domain.open_incidents_count > 0 && (
                                    <Badge className="border-transparent bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                        {domain.open_incidents_count} incident{domain.open_incidents_count !== 1 ? 's' : ''}
                                    </Badge>
                                )}
                            </Link>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
