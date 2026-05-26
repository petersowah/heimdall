<?php

namespace PeterSowah\Heimdall\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class Authorize
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Gate::check('viewHeimdall')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Forbidden.'], 403)
                : abort(403);
        }

        return $next($request);
    }
}
