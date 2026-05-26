# Heimdall

[![Latest Version on Packagist](https://img.shields.io/packagist/v/petersowah/heimdall.svg?style=flat-square)](https://packagist.org/packages/petersowah/heimdall)
[![Tests](https://img.shields.io/github/actions/workflow/status/petersowah/heimdall/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/petersowah/heimdall/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/petersowah/heimdall.svg?style=flat-square)](https://packagist.org/packages/petersowah/heimdall)

Domain monitoring dashboard for Laravel. Tracks SSL expiry, uptime, DNS changes, and WHOIS data — with alerts via email, Slack, and Telegram.

## Requirements

- PHP ^8.3
- Laravel 11, 12, or 13

## Installation

```bash
composer require petersowah/heimdall
```

Publish the config:

```bash
php artisan vendor:publish --tag=heimdall-config
```

Run the migrations:

```bash
php artisan migrate
```

Optionally publish frontend assets:

```bash
php artisan vendor:publish --tag=heimdall-assets
```

## Configuration

`config/heimdall.php`:

```php
return [
    'path' => env('HEIMDALL_PATH', 'heimdall'),   // Dashboard URL path
    'middleware' => ['web', 'auth'],               // Applied to all routes
    'domain' => null,                             // Subdomain, if any
    'alert_emails' => env('HEIMDALL_ALERT_EMAILS', ''), // Comma-separated emails
];
```

Add to `.env`:

```env
HEIMDALL_PATH=heimdall
HEIMDALL_ALERT_EMAILS=you@example.com,ops@example.com
```

## Dashboard

Visit `/heimdall` (or your configured path) after installation. The dashboard is protected by the middleware stack defined in config — `auth` by default.

## What gets monitored

| Check | What it does |
|-------|-------------|
| **SSL** | Checks certificate validity and days until expiry. Status: `ok` > 30 days, `warning` ≤ 30 days, `critical` ≤ 7 days |
| **Uptime** | HTTP reachability check |
| **DNS** | Detects changes in DNS records |
| **WHOIS** | Tracks domain registration and expiry |

## Notifications

Supports three alert channels. Configure via the dashboard's notification settings UI or directly via the `heimdall_notification_settings` table.

- **Email** — set `HEIMDALL_ALERT_EMAILS` in `.env`
- **Slack** — provide an incoming webhook URL
- **Telegram** — provide a bot token and chat ID

Per-domain notification toggles: `notify_ssl`, `notify_domain_expiry`, `notify_uptime`, `notify_dns`.

## API Routes

All routes are prefixed with `/{path}/api` and named under `heimdall.api.*`.

```
GET    /heimdall/api/dashboard
GET    /heimdall/api/domains
POST   /heimdall/api/domains
GET    /heimdall/api/domains/{domain}
PUT    /heimdall/api/domains/{domain}
DELETE /heimdall/api/domains/{domain}
POST   /heimdall/api/domains/{domain}/check          # trigger manual check
GET    /heimdall/api/domains/{domain}/checks
GET    /heimdall/api/domains/{domain}/incidents
GET    /heimdall/api/notification-settings
PUT    /heimdall/api/notification-settings
POST   /heimdall/api/notification-settings/test/slack
POST   /heimdall/api/notification-settings/test/telegram
```

## Database Tables

| Table | Purpose |
|-------|---------|
| `heimdall_domains` | Tracked domains |
| `heimdall_checks` | Check results per domain per type |
| `heimdall_incidents` | Incidents opened/closed per domain |
| `heimdall_notification_settings` | Per-user notification config |
| `heimdall_alert_logs` | History of sent alerts |

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

PRs welcome. Open an issue first for large changes.

## Security

Report vulnerabilities via [GitHub Security Advisories](../../security/advisories/new).

## License

MIT — see [LICENSE](LICENSE.md).
