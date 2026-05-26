import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { apiUrl, apiFetch } from '@/lib/utils';
import type { Domain } from '@/types';

export default function EditDomain() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const [domain, setDomain] = useState<Domain | null>(null);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    useEffect(() => {
        apiFetch<{ data: Domain }>(apiUrl(`/domains/${id}`)).then((res) => setDomain(res.data));
    }, [id]);

    async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const form = e.currentTarget;
        const data = {
            name: (form.elements.namedItem('name') as HTMLInputElement).value,
            check_interval_minutes: parseInt((form.elements.namedItem('check_interval_minutes') as HTMLInputElement).value),
            is_active: (form.elements.namedItem('is_active') as HTMLInputElement).checked,
            notify_ssl: (form.elements.namedItem('notify_ssl') as HTMLInputElement).checked,
            notify_domain_expiry: (form.elements.namedItem('notify_domain_expiry') as HTMLInputElement).checked,
            notify_uptime: (form.elements.namedItem('notify_uptime') as HTMLInputElement).checked,
            notify_dns: (form.elements.namedItem('notify_dns') as HTMLInputElement).checked,
        };

        try {
            await apiFetch(apiUrl(`/domains/${id}`), { method: 'PUT', body: JSON.stringify(data) });
            navigate(`/domains/${id}`);
        } catch (err: unknown) {
            const e = err as { body?: { errors?: Record<string, string[]> } };
            if (e?.body?.errors) {
                const flat: Record<string, string> = {};
                for (const [k, v] of Object.entries(e.body.errors)) flat[k] = v[0];
                setErrors(flat);
            }
        } finally {
            setProcessing(false);
        }
    }

    if (!domain) {
        return <div className="flex items-center justify-center h-64 text-muted-foreground">Loading…</div>;
    }

    return (
        <div className="flex flex-col gap-6 p-4">
            <div>
                <h1 className="text-2xl font-semibold">Edit {domain.name}</h1>
                <p className="text-sm text-muted-foreground">Update monitoring settings for this domain.</p>
            </div>

            <form onSubmit={handleSubmit} className="max-w-lg space-y-6">
                <div className="grid gap-2">
                    <Label htmlFor="name">Domain name</Label>
                    <Input id="name" name="name" defaultValue={domain.name} required />
                    {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="check_interval_minutes">Check interval (minutes)</Label>
                    <Input id="check_interval_minutes" name="check_interval_minutes" type="number" defaultValue={domain.check_interval_minutes} min={1} max={1440} />
                    {errors.check_interval_minutes && <p className="text-xs text-destructive">{errors.check_interval_minutes}</p>}
                </div>

                <div className="flex items-center gap-2">
                    <Checkbox id="is_active" name="is_active" defaultChecked={domain.is_active} />
                    <Label htmlFor="is_active">Active monitoring</Label>
                </div>

                <fieldset className="grid gap-3">
                    <legend className="text-sm font-medium">Alert notifications</legend>
                    <div className="flex items-center gap-2">
                        <Checkbox id="notify_ssl" name="notify_ssl" defaultChecked={domain.notify_ssl} />
                        <Label htmlFor="notify_ssl">SSL certificate expiry</Label>
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox id="notify_domain_expiry" name="notify_domain_expiry" defaultChecked={domain.notify_domain_expiry} />
                        <Label htmlFor="notify_domain_expiry">Domain registration expiry</Label>
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox id="notify_uptime" name="notify_uptime" defaultChecked={domain.notify_uptime} />
                        <Label htmlFor="notify_uptime">Uptime / downtime</Label>
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox id="notify_dns" name="notify_dns" defaultChecked={domain.notify_dns} />
                        <Label htmlFor="notify_dns">DNS changes</Label>
                    </div>
                </fieldset>

                <div className="flex gap-3">
                    <Button type="submit" disabled={processing}>{processing ? 'Saving…' : 'Save Changes'}</Button>
                    <Button type="button" variant="outline" onClick={() => navigate(-1)}>Cancel</Button>
                </div>
            </form>
        </div>
    );
}
