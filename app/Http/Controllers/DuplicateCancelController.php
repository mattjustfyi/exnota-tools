<?php

namespace App\Http\Controllers;

use App\Models\ToolDuplicateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class DuplicateCancelController extends Controller
{
    public function cancel(ToolDuplicateRequest $toolDuplicateRequest): RedirectResponse
    {
        if (!$toolDuplicateRequest->delete_batch_id) {
            return redirect()->back()->with('error', 'No delete request in progress to cancel.');
        }

        $batch = Bus::findBatch($toolDuplicateRequest->delete_batch_id);

        if (!$batch) {
            Log::warning('Batch not found for delete_batch_id: ' . $toolDuplicateRequest->delete_batch_id);
            $toolDuplicateRequest->delete_batch_id = null;
            $toolDuplicateRequest->save();
            return redirect()->back()->with('warning', 'Delete request not found. It may have already completed.');
        }

        $batch->cancel();

        $toolDuplicateRequest->delete_batch_id = null;
        $toolDuplicateRequest->delete_ended_at = now();
        $toolDuplicateRequest->save();

        Log::info('Delete request cancelled for ToolDuplicateRequest ID: ' . $toolDuplicateRequest->id);

        return redirect()->back()->with('success', 'Delete request has been cancelled successfully.');
    }
}
