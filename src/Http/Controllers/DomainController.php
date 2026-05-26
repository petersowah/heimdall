<?php

namespace PeterSowah\Heimdall\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use PeterSowah\Heimdall\Jobs\RunDnsCheck;
use PeterSowah\Heimdall\Jobs\RunSslCheck;
use PeterSowah\Heimdall\Jobs\RunUptimeCheck;
use PeterSowah\Heimdall\Jobs\RunWhoisCheck;
use PeterSowah\Heimdall\Models\Domain;
use PeterSowah\Heimdall\Resources\DomainResource;

class DomainController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): AnonymousResourceCollection
    {
        $domains = Domain::where('user_id', $request->user()->id)
            ->with(['latestChecks', 'incidents'])
            ->latest()
            ->get();

        return DomainResource::collection($domains);
    }

    public function dashboard(Request $request): AnonymousResourceCollection
    {
        $domains = Domain::where('user_id', $request->user()->id)
            ->with(['latestChecks', 'incidents'])
            ->latest()
            ->get();

        return DomainResource::collection($domains);
    }

    public function store(Request $request): DomainResource
    {
        $request->merge(['name' => strtolower(trim((string) $request->input('name', ''), '/. '))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:253', Rule::unique('heimdall_domains')->where('user_id', $request->user()->id)],
            'check_interval_minutes' => ['integer', 'min:1', 'max:1440'],
            'notify_ssl' => ['boolean'],
            'notify_domain_expiry' => ['boolean'],
            'notify_uptime' => ['boolean'],
            'notify_dns' => ['boolean'],
        ]);

        $validated['user_id'] = $request->user()->id;
        $domain = Domain::create($validated);

        RunSslCheck::dispatch($domain);
        RunUptimeCheck::dispatch($domain);
        RunDnsCheck::dispatch($domain);
        RunWhoisCheck::dispatch($domain);

        return new DomainResource($domain->load(['latestChecks', 'incidents']));
    }

    public function show(Request $request, Domain $domain): DomainResource
    {
        $this->authorize('view', $domain);

        return new DomainResource($domain->load(['latestChecks', 'incidents']));
    }

    public function update(Request $request, Domain $domain): DomainResource
    {
        $this->authorize('update', $domain);

        $validated = $request->validate([
            'name' => ['string', 'max:253', Rule::unique('heimdall_domains')->where('user_id', $request->user()->id)->ignore($domain->id)],
            'is_active' => ['boolean'],
            'check_interval_minutes' => ['integer', 'min:1', 'max:1440'],
            'notify_ssl' => ['boolean'],
            'notify_domain_expiry' => ['boolean'],
            'notify_uptime' => ['boolean'],
            'notify_dns' => ['boolean'],
        ]);

        $domain->update($validated);

        return new DomainResource($domain->load(['latestChecks', 'incidents']));
    }

    public function destroy(Request $request, Domain $domain): JsonResponse
    {
        $this->authorize('delete', $domain);

        $domain->delete();

        return response()->json(null, 204);
    }

    public function check(Request $request, Domain $domain): JsonResponse
    {
        $this->authorize('update', $domain);

        RunSslCheck::dispatch($domain);
        RunUptimeCheck::dispatch($domain);
        RunDnsCheck::dispatch($domain);
        RunWhoisCheck::dispatch($domain);

        return response()->json(['message' => 'Checks dispatched']);
    }
}
