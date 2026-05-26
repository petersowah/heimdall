<?php

namespace PeterSowah\Heimdall\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    protected $table = 'heimdall_domains';

    protected $fillable = [
        'user_id',
        'name',
        'is_active',
        'check_interval_minutes',
        'notify_ssl',
        'notify_domain_expiry',
        'notify_uptime',
        'notify_dns',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'notify_ssl' => 'boolean',
            'notify_domain_expiry' => 'boolean',
            'notify_uptime' => 'boolean',
            'notify_dns' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', User::class));
    }

    public function checks(): HasMany
    {
        return $this->hasMany(Check::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function alertLogs(): HasMany
    {
        return $this->hasMany(AlertLog::class);
    }

    public function latestChecks(): HasMany
    {
        return $this->hasMany(Check::class)
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('heimdall_checks')
                    ->groupBy('domain_id', 'type');
            });
    }
}
