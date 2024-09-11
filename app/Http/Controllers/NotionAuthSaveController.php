<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotionAuthSaveRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

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
            $redirectUrl = app('url')->to('/api/notion/auth/redirect');
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

            // // Create a new user
            // $user = User::create([
            //     'name' => $responseData['name'],
            //     'email' => $responseData['email'],
            // ]);

            // // Save the auth data to the workspace
            // $workspace = Workspace::create([
            //     'authData' => $responseData,
            // ]);

        } catch (RequestException $e) {
            // Handle the request exception here
            logger('Error: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Workspace created successfully',
            'data' => [
                // 'id' => $workspace->id,
            ]
        ], 201);
    }
}
