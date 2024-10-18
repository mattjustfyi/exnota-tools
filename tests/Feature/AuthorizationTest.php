<?php

namespace Tests\Feature;

use App\DTOs\ConsentGrant;
use App\Models\NotionObject;
use App\Models\ToolDuplicateRequest;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_consent_for_other_user(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $userOne */
        $userOne = User::factory()->create([
            // Don't use default generated email as the @example domain is
            // rejected by the `email:rfc,dns` validation rule.
            'email' => 'userone@matt.fyi',
        ]);

        /** @var \Illuminate\Contracts\Auth\Authenticatable $userTwo */
        $userTwo = User::factory()->create([
            // Don't use default generated email as the @example domain is
            // rejected by the `email:rfc,dns` validation rule.
            'email' => 'usertwo@matt.fyi',
        ]);

        // Login as user two
        $this->actingAs($userTwo);

        // Try to consent for user one
        $response = $this->post('/duplicates/consent', [
            'email' => $userOne->email,
        ]);
        $response->assertStatus(500);
        $this->assertStringContainsString('duplicate key value violates unique constraint "users_email_unique"', $response->baseResponse->exception->getMessage());
    }

    public function test_cannot_access_other_workspace(): void
    {
        $consentGrant = new ConsentGrant(
            Carbon::now(),
            '1.0', // terms version
            '1.0', // privacy version
            url('/duplicates/create-account'),
            '',
        );

        /** @var \Illuminate\Contracts\Auth\Authenticatable $userOne */
        $userOne = User::factory()->create([
            // Don't use default generated email as the @example domain is
            // rejected by the `email:rfc,dns` validation rule.
            'email' => 'userone@matt.fyi',
            'consent' => [$consentGrant]
        ]);
        $workspaceUserOne = Workspace::create([
            'authData' => '{}',
            'user_id' => $userOne->id,
            'user_avatar' => null,
            'workspace_name' => null,
            'workspace_icon' => null,
        ]);

        /** @var \Illuminate\Contracts\Auth\Authenticatable $userTwo */
        $userTwo = User::factory()->create([
            // Don't use default generated email as the @example domain is
            // rejected by the `email:rfc,dns` validation rule.
            'email' => 'usertwo@matt.fyi',
            'consent' => [$consentGrant]
        ]);
        $workspaceUserTwo = Workspace::create([
            'authData' => '{}',
            'user_id' => $userTwo->id,
            'user_avatar' => null,
            'workspace_name' => null,
            'workspace_icon' => null,
        ]);

        // Login as user two
        $this->actingAs($userTwo);

        // Try to access user one's workspace
        // - Sanity test own
        $response = $this->get('/duplicates/workspace/' . $workspaceUserTwo->id);
        $response->assertStatus(200);

        // - Request other user's
        $response = $this->get('/duplicates/workspace/' . $workspaceUserOne->id);
        $response->assertStatus(403);

        // Try to refresh user one's workspace
        // - Sanity test own
        $response = $this->post('/test/workspace/' . $workspaceUserTwo->id . '/refresh');
        $response->assertStatus(500);
        $this->assertEquals("Cannot access offset of type string on string", $response->baseResponse->exception->getMessage());

        // - Request other user's
        $response = $this->post('/test/workspace/' . $workspaceUserOne->id . '/refresh');
        $response->assertStatus(403);
    }

    public function test_cannot_access_other_duplicate_request(): void
    {
        $consentGrant = new ConsentGrant(
            Carbon::now(),
            '1.0', // terms version
            '1.0', // privacy version
            url('/duplicates/create-account'),
            '',
        );

        /** @var \Illuminate\Contracts\Auth\Authenticatable $userOne */
        $userOne = User::factory()->create([
            // Don't use default generated email as the @example domain is
            // rejected by the `email:rfc,dns` validation rule.
            'email' => 'userone@matt.fyi',
            'consent' => [$consentGrant]
        ]);
        $workspaceUserOne = Workspace::create([
            'authData' => '{}',
            'user_id' => $userOne->id,
            'user_avatar' => null,
            'workspace_name' => null,
            'workspace_icon' => null,
        ]);
        $notionObjectUserOne = NotionObject::create([
            'workspace_id' => $workspaceUserOne->id,
            'type' => 'database',
            'name' => 'Test Database User One',
            'data' => '{}',
        ]);
        $duplicateRequestUserOne = ToolDuplicateRequest::create([
            'notion_object_id' => $notionObjectUserOne->id,
        ]);

        /** @var \Illuminate\Contracts\Auth\Authenticatable $userTwo */
        $userTwo = User::factory()->create([
            // Don't use default generated email as the @example domain is
            // rejected by the `email:rfc,dns` validation rule.
            'email' => 'usertwo@matt.fyi',
            'consent' => [$consentGrant]
        ]);
        $workspaceUserTwo = Workspace::create([
            'authData' => '{}',
            'user_id' => $userTwo->id,
            'user_avatar' => null,
            'workspace_name' => null,
            'workspace_icon' => null,
        ]);
        $notionObjectUserTwo = NotionObject::create([
            'workspace_id' => $workspaceUserTwo->id,
            'type' => 'database',
            'name' => 'Test Database User Two',
            'data' => '{}',
        ]);
        $duplicateRequestUserTwo = ToolDuplicateRequest::create([
            'notion_object_id' => $notionObjectUserTwo->id,
        ]);

        // Login as user two
        $this->actingAs($userTwo);

        // Try to create duplicate request for user one's Notion object
        // - Sanity test own
        $response = $this->post('/duplicates/request', [
            'notion_object_id' => $notionObjectUserTwo->id,
        ]);
        $response->assertRedirect(route('duplicates.request.confirm', ['toolDuplicateRequest' => $duplicateRequestUserTwo->id]));

        // - Request other user's
        $response = $this->post('/duplicates/request', [
            'notion_object_id' => $notionObjectUserOne->id,
        ]);
        $response->assertStatus(403);

        // Try to intiate delete for request
        // - Sanity test own
        $response = $this->post('/duplicates/request/' . $duplicateRequestUserTwo->id . '/delete');
        $response->assertStatus(500);
        $this->assertEquals('Invalid duplicates data. Expected an array.', $response->baseResponse->exception->getMessage());

        // - Request other user's
        $response = $this->post('/duplicates/request/' . $duplicateRequestUserOne->id . '/delete');
        $response->assertStatus(403);

        // Try to access confirm screen
        // - Sanity test own
        $response = $this->get('/duplicates/request/' . $duplicateRequestUserTwo->id . '/confirm');
        $response->assertStatus(200);

        // - Request other user's
        $response = $this->get('/duplicates/request/' . $duplicateRequestUserOne->id . '/confirm');
        $response->assertStatus(403);

        // Try to update
        // - Sanity test own
        $response = $this->patch('/duplicates/request/' . $duplicateRequestUserTwo->id, [
            'delete_logic' => 'keep_oldest',
        ]);
        $response->assertStatus(302);

        // - Request other user's
        $response = $this->patch('/duplicates/request/' . $duplicateRequestUserOne->id, [
            'delete_logic' => 'keep_oldest',
        ]);
        $response->assertStatus(403);

        // Try to cancel
        // - Sanity test own
        $response = $this->post('/duplicates/request/' . $duplicateRequestUserTwo->id . '/cancel');
        $response->assertStatus(302);

        // - Request other user's
        $response = $this->post('/duplicates/request/' . $duplicateRequestUserOne->id . '/cancel');
        $response->assertStatus(403);
    }
}
