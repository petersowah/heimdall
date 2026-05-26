<?php

namespace PeterSowah\Heimdall\Policies;

use Illuminate\Database\Eloquent\Model;
use PeterSowah\Heimdall\Models\Domain;

class DomainPolicy
{
    public function view(Model $user, Domain $domain): bool
    {
        return $user->id === $domain->user_id;
    }

    public function update(Model $user, Domain $domain): bool
    {
        return $user->id === $domain->user_id;
    }

    public function delete(Model $user, Domain $domain): bool
    {
        return $user->id === $domain->user_id;
    }
}
