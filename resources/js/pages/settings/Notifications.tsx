import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { apiUrl, apiFetch } from '@/lib/utils';
import type { NotificationSettings } from '@/types';

function TestButton({ label, endpoint }: { label: string; endpoint: string }) {
    const [status, setStatus] = useState<'idle' | 'sending' | 'ok' | 'error'>('idle');
    const [message, setMessage] = useState('');

    async function handleTest() {
        setStatus('sending');
        setMessage('');
        try {
            await apiFetch(endpoint, { method: 'POST' });
            setStatus('ok');
            setMessage('Sent!');
        } catch (err: unknown) {
            setStatus('error');
            const e = err as { body?: { message?: string } };
            setMessage(e?.body?.message ?? 'Failed.');
        } finally {
            setTimeout(() => setStatus('idle'), 4000);
        }
    }

    return (
        <div className="flex items-center gap-3">
            <Button type="button" variant="outline" size="sm" onClick={handleTest} disabled={status === 'sending'}>
                {status === 'sending' ? 'Sending…' : `Test ${label}`}
            </Button>
            {status === 'ok' && <span className="text-xs text-green-600">{message}</span>}
            {status === 'error' && <span className="text-xs text-destructive">{message}</span>}
        </div>
    );
}

export default function NotificationSettingsPage() {
    const [settings, setSettings] = useState<NotificationSettings | null>(null);
    const [processing, setProcessing] = useState(false);
    const [saved, setSaved] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    useEffect(() => {
        apiFetch<NotificationSettings>(apiUrl('/notification-settings')).then(setSettings);
    }, []);

    async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const form = e.currentTarget;
        const data: Record<string, unknown> = {
            slack_webhook_url: (form.elements.namedItem('slack_webhook_url') as HTMLInputElement).value || null,
            telegram_bot_token: (form.elements.namedItem('telegram_bot_token') as HTMLInputElement).value || null,
            telegram_chat_id: (form.elements.namedItem('telegram_chat_id') as HTMLInputElement).value || null,
        };

        const emailsRaw = (form.elements.namedItem('notification_emails') as HTMLInputElement).value;
        if (emailsRaw.trim()) {
            data.notification_emails = emailsRaw.split(',').map((e) => e.trim()).filter(Boolean);
        }

        try {
            await apiFetch(apiUrl('/notification-settings'), { method: 'PUT', body: JSON.stringify(data) });
            setSaved(true);
            setTimeout(() => setSaved(false), 3000);
            const updated = await apiFetch<NotificationSettings>(apiUrl('/notification-settings'));
            setSettings(updated);
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

    if (!settings) {
        return <div className="flex items-center justify-center h-64 text-muted-foreground">Loading…</div>;
    }

    return (
        <div className="flex flex-col gap-6 p-4">
            <div>
                <h1 className="text-2xl font-semibold">Notification Settings</h1>
                <p className="text-sm text-muted-foreground">Configure Slack, Telegram, and email to receive monitoring alerts.</p>
            </div>

            <form onSubmit={handleSubmit} className="max-w-lg space-y-8">
                <div className="space-y-4">
                    <h3 className="text-sm font-semibold">Slack</h3>
                    <div className="grid gap-2">
                        <Label htmlFor="slack_webhook_url">Incoming Webhook URL</Label>
                        <Input
                            id="slack_webhook_url"
                            name="slack_webhook_url"
                            type="url"
                            defaultValue={settings.slack_webhook_url ?? ''}
                            placeholder="https://hooks.slack.com/services/…"
                        />
                        <p className="text-xs text-muted-foreground">Create an Incoming Webhook in your Slack app settings.</p>
                        {errors.slack_webhook_url && <p className="text-xs text-destructive">{errors.slack_webhook_url}</p>}
                    </div>
                    {settings.has_slack && (
                        <TestButton label="Slack" endpoint={apiUrl('/notification-settings/test/slack')} />
                    )}
                </div>

                <div className="space-y-4">
                    <h3 className="text-sm font-semibold">Telegram</h3>
                    <div className="grid gap-2">
                        <Label htmlFor="telegram_bot_token">Bot Token</Label>
                        <Input
                            id="telegram_bot_token"
                            name="telegram_bot_token"
                            type="password"
                            defaultValue=""
                            placeholder={settings.has_telegram ? '••••••••••••• (saved)' : 'From @BotFather'}
                        />
                        <p className="text-xs text-muted-foreground">Create a bot via @BotFather and paste the token here.</p>
                        {errors.telegram_bot_token && <p className="text-xs text-destructive">{errors.telegram_bot_token}</p>}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="telegram_chat_id">Chat ID</Label>
                        <Input
                            id="telegram_chat_id"
                            name="telegram_chat_id"
                            defaultValue={settings.telegram_chat_id ?? ''}
                            placeholder="-100123456789"
                        />
                        <p className="text-xs text-muted-foreground">Your chat or channel ID. Use @userinfobot to find yours.</p>
                        {errors.telegram_chat_id && <p className="text-xs text-destructive">{errors.telegram_chat_id}</p>}
                    </div>
                    {settings.has_telegram && (
                        <TestButton label="Telegram" endpoint={apiUrl('/notification-settings/test/telegram')} />
                    )}
                </div>

                <div className="space-y-4">
                    <h3 className="text-sm font-semibold">Email</h3>
                    <div className="grid gap-2">
                        <Label htmlFor="notification_emails">Alert Recipients</Label>
                        <Input
                            id="notification_emails"
                            name="notification_emails"
                            type="text"
                            defaultValue={(settings.notification_emails ?? []).join(', ')}
                            placeholder="alerts@example.com, ops@example.com"
                        />
                        <p className="text-xs text-muted-foreground">Comma-separated list of email addresses.</p>
                        {errors.notification_emails && <p className="text-xs text-destructive">{errors.notification_emails}</p>}
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    <Button disabled={processing}>{processing ? 'Saving…' : 'Save Settings'}</Button>
                    {saved && <span className="text-xs text-green-600">Settings saved!</span>}
                </div>
            </form>
        </div>
    );
}
