<?php

namespace PeterSowah\Heimdall\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use PeterSowah\Heimdall\Models\Domain;
use PeterSowah\Heimdall\Resources\CheckResource;

class CheckController extends Controller
{
    public function index(Request $request, Domain $domain): AnonymousResourceCollection
    {
        $this->authorize('view', $domain);

        $checks = $domain->checks()
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->latest('checked_at')
            ->paginate(50);

        return CheckResource::collection($checks);
    }
}
