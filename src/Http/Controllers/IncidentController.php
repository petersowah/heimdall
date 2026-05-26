<?php

namespace PeterSowah\Heimdall\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use PeterSowah\Heimdall\Models\Domain;
use PeterSowah\Heimdall\Resources\IncidentResource;

class IncidentController extends Controller
{
    public function index(Request $request, Domain $domain): AnonymousResourceCollection
    {
        $this->authorize('view', $domain);

        $incidents = $domain->incidents()
            ->latest('started_at')
            ->paginate(20);

        return IncidentResource::collection($incidents);
    }
}
