<?php

namespace App\Http\Middleware;

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
        $user = Auth::user();
        $notionObjectId = $request->input('notion_object_id');

        if (!$user || !$notionObjectId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $ws = $user->workspaces;
        $notionObjectBelongsToUser = false;

        foreach ($ws as $workspace) {
            if ($workspace->notionObjects->contains('id', $notionObjectId)) {
                $notionObjectBelongsToUser = true;
                break;
            }
        }

        if (!$notionObjectBelongsToUser) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}
