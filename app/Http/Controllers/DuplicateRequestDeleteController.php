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

class DuplicateRequestDeleteController extends Controller
{
    public function delete(ToolDuplicateRequest $toolDuplicateRequest): RedirectResponse
    {
        if ($toolDuplicateRequest->batch_id) {
            return redirect()->back()->with('error', 'Delete request already in progress.');
        }

        $deleteList = [];
        foreach ($toolDuplicateRequest->search_results as $result) {
            $dtos = $result->delete;
            $notion_ids = array_map(function ($dto) {
                return $dto->notion_id;
            }, $dtos);
            $deleteList = array_merge($deleteList, $notion_ids);
        }

        $toolDuplicateRequest->delete_list = $deleteList;
        $toolDuplicateRequest->save();

        $batchSize = 5;
        $deleteListChunks = array_chunk($deleteList, $batchSize);

        $jobs = [];
        foreach ($deleteListChunks as $chunk) {
            $jobs[] = new DuplicateDelete($toolDuplicateRequest, $chunk);
        }

        $batch = Bus::batch($jobs)
            ->before(function (Batch $batch) {
                // The batch has been created but no jobs have been added...
                Log::debug('Batch created for request: ' . $batch->id);
            })->progress(function (Batch $batch) {
                // A single job has completed successfully...
                Log::debug('Batch progress for request: ' . $batch->id);
            })->then(function (Batch $batch) {
                // All jobs completed successfully...
                Log::debug('Batch completed for request: ' . $batch->id);
            })->catch(function (Batch $batch, Throwable $e) {
                // First batch job failure detected...
                Log::debug('Batch failed for request: ' . $batch->id);
            })->finally(function (Batch $batch) use ($toolDuplicateRequest) {
                // The batch has finished executing...
                Log::debug('Batch finished for request: ' . $batch->id);

                $toolDuplicateRequest->delete_ended_at = now();
                $toolDuplicateRequest->save();
            })
            ->name('Delete duplicates for request: ' . $toolDuplicateRequest->id)
            ->onQueue(QueueConstants::DELETE_QUEUE_1)->dispatch();

        $toolDuplicateRequest->batch_id = $batch->id;
        $toolDuplicateRequest->save();

        return to_route('duplicates.request.confirm', ['toolDuplicateRequest' => $toolDuplicateRequest->id]);
    }
}
