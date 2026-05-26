export type CheckStatus = 'ok' | 'warning' | 'critical' | 'error';
export type CheckType = 'ssl' | 'whois' | 'uptime' | 'dns';
export type IncidentStatus = 'open' | 'resolved';
export type IncidentType = 'uptime' | 'ssl' | 'dns' | 'whois';

export type Check = {
    id: number;
    type: CheckType;
    status: CheckStatus;
    checked_at: string;
    value: number | null;
    message: string | null;
};

export type Incident = {
    id: number;
    type: IncidentType;
    status: IncidentStatus;
    started_at: string;
    resolved_at: string | null;
    duration_minutes: number | null;
    details: string;
};

export type Domain = {
    id: number;
    name: string;
    is_active: boolean;
    check_interval_minutes: number;
    notify_ssl: boolean;
    notify_domain_expiry: boolean;
    notify_uptime: boolean;
    notify_dns: boolean;
    created_at: string;
    latest_checks: Partial<Record<CheckType, Check>>;
    open_incidents_count: number;
};

export type User = {
    name: string;
    email: string;
};

export type NotificationSettings = {
    slack_webhook_url: string | null;
    telegram_bot_token: string | null;
    telegram_chat_id: string | null;
    notification_emails: string[];
    has_slack: boolean;
    has_telegram: boolean;
};
