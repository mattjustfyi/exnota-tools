<?php

namespace App\Http\Middleware;

use App\Models\ToolDuplicateRequest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CreateDuplicateRequestPolicyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $notionObjectId = $request->input('notion_object_id');

        if (!$request->user() || !$notionObjectId) {
            abort(403);
        }

        if (!$request->user()->can('create', [ToolDuplicateRequest::class,  $notionObjectId])) {
            abort(403);
        }

        return $next($request);
    }
}
