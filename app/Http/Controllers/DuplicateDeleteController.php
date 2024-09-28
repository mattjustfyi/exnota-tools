<?php

namespace App\Http\Controllers;

use App\Constants\QueueConstants;
use App\Jobs\DuplicateDelete;
use App\Models\ToolDuplicateRequest;
use Illuminate\Bus\Batch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class DuplicateDeleteController extends Controller
{
    public function delete(ToolDuplicateRequest $toolDuplicateRequest): RedirectResponse
    {
        if ($toolDuplicateRequest->delete_batch_id) {
            return redirect()->back()->with('error', 'Delete request already in progress.');
        }

        $deleteList = [];
        foreach ($toolDuplicateRequest->duplicates as $result) {
            $dtos = $result->delete;
            $notion_ids = array_map(function ($dto) {
                return $dto->notion_id;
            }, $dtos);
            $deleteList = array_merge($deleteList, $notion_ids);
        }

        $toolDuplicateRequest->delete_list = $deleteList;
        $toolDuplicateRequest->save();

        $jobs = [];
        $remainingList = $deleteList;

        // Create initial jobs with size 5, up to 4 jobs or until we run out of items
        while (count($jobs) < 4 && !empty($remainingList)) {
            $chunk = array_slice($remainingList, 0, 5);
            $remainingList = array_slice($remainingList, 5);
            $jobs[] = new DuplicateDelete($toolDuplicateRequest, $chunk);
        }

        // Remaining jobs with size 10
        $remainingChunks = array_chunk($remainingList, 10);
        foreach ($remainingChunks as $chunk) {
            $jobs[] = new DuplicateDelete($toolDuplicateRequest, $chunk);
        }

        $batch = Bus::batch($jobs)
            ->before(function (Batch $batch) use ($toolDuplicateRequest) {
                // The batch has been created but no jobs have been added...
                Log::debug('Batch delete created for request: ' . $toolDuplicateRequest->id);
            })->progress(function (Batch $batch) use ($toolDuplicateRequest) {
                // A single job has completed successfully...
                Log::debug('Batch delete progress for request: ' . $toolDuplicateRequest->id);
            })->then(function (Batch $batch) use ($toolDuplicateRequest) {
                // All jobs completed successfully...
                Log::debug('Batch delete completed for request: ' . $toolDuplicateRequest->id);
            })->catch(function (Batch $batch, Throwable $e) use ($toolDuplicateRequest) {
                // First batch job failure detected...
                Log::debug('Batch delete failed for request: ' . $toolDuplicateRequest->id);
            })->finally(function (Batch $batch) use ($toolDuplicateRequest) {
                // The batch has finished executing...
                Log::debug('Batch delete finished for request: ' . $toolDuplicateRequest->id);

                $toolDuplicateRequest->delete_ended_at = now();
                $toolDuplicateRequest->save();
            })
            ->name('Delete duplicates for request: ' . $toolDuplicateRequest->id)
            // Prevent a job failure from stopping the batch
            ->allowFailures()
            ->onQueue(QueueConstants::DELETE_QUEUE_1)->dispatch();

        $toolDuplicateRequest->delete_batch_id = $batch->id;
        $toolDuplicateRequest->save();

        return to_route('duplicates.request.confirm', ['toolDuplicateRequest' => $toolDuplicateRequest->id]);
    }
}
