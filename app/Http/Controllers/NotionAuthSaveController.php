<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotionAuthSaveRequest;
use App\Jobs\WorkspaceRefresh;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Auth;

class NotionAuthSaveController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(NotionAuthSaveRequest $request): JsonResponse
    {
        $request->validate($request->rules());

        // ...

        try {
            $redirectUrl = url('/api/notion/auth/redirect');
            $token = env('NOTION_CLIENT_ID') . ':' . env('NOTION_SECRET');
            $token = base64_encode($token);
            $client = new Client();
            $response = $client->post('https://api.notion.com/v1/oauth/token', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . $token,
                ],
                'json' => [
                    'grant_type' => 'authorization_code',
                    'code' => $request->input('code'),
                    'redirect_uri' => $redirectUrl,
                ],
            ]);

            // Process the response here
            $responseData = json_decode($response->getBody(), true);
            logger('Response: ' . print_r($responseData, true));

            // Create a new user
            if (isset($responseData['owner']['user']['name'])) {
                $name = $responseData['owner']['user']['name'];
            } else {
                logger('Error: Name not found in response data');
                return response()->json([
                    'message' => 'An error occurred',
                ], 500);
            }

            if (isset($responseData['owner']['user']['person']['email'])) {
                $email = $responseData['owner']['user']['person']['email'];
            } else {
                logger('Error: Email not found in response data');
                return response()->json([
                    'message' => 'An error occurred',
                ], 500);
            }

            // TODO: Check if user/workspace exists

            // Create a new user
            $user = User::create([
                'email' => $email,
                'name' => $name,
                // Password is required
                'password' => bcrypt(Str::random(32)),
            ]);

            // Save the auth data to the workspace


            $workspace = Workspace::create([
                'authData' => $responseData,
                'user_id' => $user->id,
                'user_avatar' => $responseData['owner']['user']['avatar_url'] ?? null,
                'workspace_name' => $responseData['workspace_name'] ?? null,
                'workspace_icon' => $responseData['workspace_icon'] ?? null,
            ]);

            Auth::login($user, true);

            WorkspaceRefresh::dispatch($workspace);

            return response()->json([
                'message' => 'Workspace created successfully',
                'data' => [
                    'workspace_id' => $workspace->id,
                ]
            ], 201);
        } catch (RequestException $e) {
            // Handle the request exception here
            logger('Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'An error occurred',
            ], 500);
        }
    }
}
