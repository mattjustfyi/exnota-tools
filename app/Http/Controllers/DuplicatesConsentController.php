<?php

namespace App\Http\Controllers;

use App\DTOs\ConsentGrant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DuplicatesConsentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // If email was provided, it should be valid
        $validated = $request->validate([
            'email' => 'email:rfc,dns',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Redirect is already consented
        if (!empty($user->consent)) {
            $latestWorkspace = $user->workspaces()->latest('updated_at')->first();
            return to_route('duplicates.workspace', ['workspace' => $latestWorkspace->id]);
        }

        // Update email
        $email = $validated['email'];
        if (!empty($email) && $email !== $user->email) {
            $user->email = $email;
            $user->save();
        }

        // Set consent
        $consentGrant = new ConsentGrant(
            Carbon::now(),
            '1.0', // terms version
            '2.0', // privacy version
            url('/duplicates/create-account'),
            '2/4 Create account\n\nOne-step sign up to save your Notion details.\n\nNo spam.\n\nBy continuing, you acknowledge that you understand and agree to the Terms & Conditions and Privacy Policy. Button: Create account',
        );
        $consent = $user->refresh()->consent;
        $consent = DB::table('users')
            ->where('id', $user->id)
            ->lockForUpdate()
            ->value('consent');
        $mergedConsent = $consent ?? [];
        $mergedConsent = array_merge($mergedConsent, [$consentGrant]);
        DB::table('users')
            ->where('id', $user->id)
            ->update(['consent' => $mergedConsent]);

        // Redirect to workspace
        $latestWorkspace = $user->workspaces()->latest('updated_at')->first();
        return to_route('duplicates.workspace', ['workspace' => $latestWorkspace->id]);
    }
}
