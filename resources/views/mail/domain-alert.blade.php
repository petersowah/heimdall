<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f5; margin: 0; padding: 24px; }
        .card { background: #fff; border-radius: 8px; padding: 32px; max-width: 520px; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; }
        .badge-critical { background: #fee2e2; color: #b91c1c; }
        .badge-warning { background: #fef9c3; color: #92400e; }
        .badge-resolved { background: #dcfce7; color: #15803d; }
        .badge-info { background: #e0f2fe; color: #0369a1; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .domain { font-size: 16px; font-weight: 600; color: #1d4ed8; }
        .message { color: #374151; margin: 16px 0; }
        .fields { background: #f9fafb; border-radius: 6px; padding: 12px 16px; margin: 12px 0; }
        .field-row { display: flex; justify-content: space-between; margin: 4px 0; font-size: 14px; }
        .field-label { color: #6b7280; }
        .footer { margin-top: 24px; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 16px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Heimdall Alert</h1>
        <p class="domain">{{ $domain->name }}</p>

        <span class="badge {{ match(true) {
            str_contains($alertType, 'resolved') => 'badge-resolved',
            str_contains($alertType, 'critical') => 'badge-critical',
            str_contains($alertType, 'warning') => 'badge-warning',
            default => 'badge-info'
        } }}">{{ ucwords(str_replace('_', ' ', $alertType)) }}</span>

        <p class="message">{{ $alertMessage }}</p>

        @if (!empty($fields))
        <div class="fields">
            @foreach ($fields as $label => $value)
            <div class="field-row">
                <span class="field-label">{{ $label }}</span>
                <span>{{ $value }}</span>
            </div>
            @endforeach
        </div>
        @endif

        <div class="footer">Sent by Heimdall domain monitoring.</div>
    </div>
</body>
</html>
