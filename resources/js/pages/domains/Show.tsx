import {
    AlertCircle,
    CheckCircle,
    Clock,
    Globe,
    Pencil,
    RefreshCw,
    Shield,
    Trash2,
    XCircle,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { StatusBadge } from '@/components/StatusBadge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { apiUrl, apiFetch, timeAgo } from '@/lib/utils';
import type { Check, CheckType, Domain, Incident } from '@/types';

const CHECK_ICONS: Record<CheckType, React.ElementType> = {
    ssl: Shield,
    whois: Globe,
    uptime: CheckCircle,
    dns: AlertCircle,
};

const CHECK_LABELS: Record<CheckType, string> = {
    ssl: 'SSL Certificate',
    whois: 'Domain Expiry',
    uptime: 'Uptime',
    dns: 'DNS',
};

function CheckCard({ type, check }: { type: CheckType; check: Check | undefined }) {
    const Icon = CHECK_ICONS[type];
    return (
        <div className="flex flex-col gap-2 rounded-xl border p-4">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm font-medium">
                    <Icon className="size-4" />
                    {CHECK_LABELS[type]}
                </div>
                <StatusBadge status={check?.status} />
            </div>
            {check ? (
                <>
                    <p className="text-sm text-muted-foreground">{check.message}</p>
                    {check.value !== null && (
                        <p className="text-xs font-semibold">
                            {type === 'uptime' ? `${check.value}ms response` : `${check.value} days remaining`}
                        </p>
                    )}
                    <p className="text-xs text-muted-foreground">Checked {timeAgo(check.checked_at)}</p>
                </>
            ) : (
                <p className="text-sm text-muted-foreground flex items-center gap-1">
                    <Clock className="size-3" /> Pending first check
                </p>
            )}
        </div>
    );
}

export default function ShowDomain() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const [domain, setDomain] = useState<Domain | null>(null);
    const [recentChecks, setRecentChecks] = useState<Partial<Record<CheckType, Check[]>>>({});
    const [incidents, setIncidents] = useState<Incident[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        Promise.all([
            apiFetch<{ data: Domain }>(apiUrl(`/domains/${id}`)),
            apiFetch<{ data: Check[] }>(apiUrl(`/domains/${id}/checks`)),
            apiFetch<{ data: Incident[] }>(apiUrl(`/domains/${id}/incidents`)),
        ]).then(([domainRes, checksRes, incidentsRes]) => {
            setDomain(domainRes.data);
            setIncidents(incidentsRes.data);

            const grouped: Partial<Record<CheckType, Check[]>> = {};
            for (const check of checksRes.data) {
                if (!grouped[check.type]) grouped[check.type] = [];
                grouped[check.type]!.push(check);
            }
            setRecentChecks(grouped);
        }).finally(() => setLoading(false));
    }, [id]);

    async function handleCheck() {
        await apiFetch(apiUrl(`/domains/${id}/check`), { method: 'POST' });
    }

    async function handleDelete() {
        if (!confirm(`Remove ${domain?.name} from monitoring?`)) return;
        await apiFetch(apiUrl(`/domains/${id}`), { method: 'DELETE' });
        navigate('/domains');
    }

    if (loading || !domain) {
        return <div className="flex items-center justify-center h-64 text-muted-foreground">Loading…</div>;
    }

    const uptimeChecks = recentChecks.uptime ?? [];
    const successfulChecks = uptimeChecks.filter((c) => c.status === 'ok').length;
    const uptimePercent = uptimeChecks.length > 0 ? ((successfulChecks / uptimeChecks.length) * 100).toFixed(1) : null;

    return (
        <div className="flex flex-col gap-6 p-4">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <div className="flex items-center gap-2">
                        <h1 className="text-2xl font-semibold">{domain.name}</h1>
                        {!domain.is_active && <Badge variant="outline">Paused</Badge>}
                    </div>
                    {uptimePercent && (
                        <p className="text-sm text-muted-foreground">{uptimePercent}% uptime (last {uptimeChecks.length} checks)</p>
                    )}
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" size="sm" onClick={handleCheck}>
                        <RefreshCw className="mr-1 size-3" />
                        Run Checks
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <Link to={`/domains/${id}/edit`}>
                            <Pencil className="mr-1 size-3" />
                            Edit
                        </Link>
                    </Button>
                    <Button variant="outline" size="sm" onClick={handleDelete} className="text-destructive hover:text-destructive">
                        <Trash2 className="size-3" />
                    </Button>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {(['ssl', 'whois', 'uptime', 'dns'] as CheckType[]).map((type) => (
                    <CheckCard key={type} type={type} check={domain.latest_checks[type]} />
                ))}
            </div>

            {incidents.length > 0 && (
                <>
                    <Separator />
                    <div>
                        <h2 className="mb-3 text-lg font-semibold">Incidents</h2>
                        <div className="space-y-2">
                            {incidents.map((incident) => (
                                <div key={incident.id} className="flex items-center justify-between rounded-lg border px-4 py-3">
                                    <div className="flex items-center gap-3">
                                        {incident.status === 'open' ? (
                                            <XCircle className="size-4 text-red-500" />
                                        ) : (
                                            <CheckCircle className="size-4 text-green-500" />
                                        )}
                                        <div>
                                            <p className="text-sm font-medium capitalize">{incident.type} incident</p>
                                            <p className="text-xs text-muted-foreground">{incident.details}</p>
                                        </div>
                                    </div>
                                    <div className="text-right text-xs text-muted-foreground">
                                        <p>{timeAgo(incident.started_at)}</p>
                                        {incident.duration_minutes && <p>{incident.duration_minutes}m duration</p>}
                                        <Badge
                                            className={
                                                incident.status === 'open'
                                                    ? 'border-transparent bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                                    : 'border-transparent bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                            }
                                        >
                                            {incident.status}
                                        </Badge>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </>
            )}

            <Separator />

            <div>
                <h2 className="mb-3 text-lg font-semibold">Recent Check History</h2>
                <div className="grid gap-4 lg:grid-cols-2">
                    {(['uptime', 'ssl', 'whois', 'dns'] as CheckType[]).map((type) => {
                        const checks = recentChecks[type] ?? [];
                        if (checks.length === 0) return null;
                        return (
                            <div key={type} className="rounded-xl border">
                                <div className="border-b px-4 py-2 text-sm font-medium capitalize">{CHECK_LABELS[type]}</div>
                                <div className="max-h-64 overflow-y-auto divide-y">
                                    {checks.slice(0, 10).map((check) => (
                                        <div key={check.id} className="flex items-center justify-between px-4 py-2 text-sm">
                                            <div className="flex items-center gap-2">
                                                <StatusBadge status={check.status} />
                                                <span className="text-muted-foreground truncate max-w-48">{check.message}</span>
                                            </div>
                                            <span className="shrink-0 text-xs text-muted-foreground">{timeAgo(check.checked_at)}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
