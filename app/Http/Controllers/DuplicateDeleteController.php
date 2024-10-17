<?php

namespace App\Http\Controllers;

use App\Constants\QueueConstants;
use App\Jobs\DuplicateDelete;
use App\Models\NotionObject;
use App\Models\ToolDuplicateRequest;
use Illuminate\Bus\Batch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DuplicateDeleteController extends Controller
{
    public function delete(ToolDuplicateRequest $toolDuplicateRequest): RedirectResponse
    {
        if ($toolDuplicateRequest->delete_batch_id) {
            return redirect()->back()->with('error', 'Delete request already in progress.');
        }

        if (!is_array($toolDuplicateRequest->duplicates)) {
            throw new \Exception('Invalid duplicates data. Expected an array.');
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
        $toolDuplicateRequest->delete_list_count = count($deleteList);
        $toolDuplicateRequest->save();

        $jobs = [];
        $remainingList = $deleteList;

        // Create initial jobs with size 5, up to 4 jobs or until we run out of items
        /** @var \App\Models\User $user */
        $user = Auth::user();
        while (count($jobs) < 4 && !empty($remainingList)) {
            $chunk = array_slice($remainingList, 0, 5);
            $remainingList = array_slice($remainingList, 5);
            $jobs[] = new DuplicateDelete($user, $toolDuplicateRequest, $chunk);
        }

        // Remaining jobs with size 10
        $remainingChunks = array_chunk($remainingList, 10);
        foreach ($remainingChunks as $chunk) {
            $jobs[] = new DuplicateDelete($user, $toolDuplicateRequest, $chunk);
        }

        // Check if user has delete jobs already in a queue. If so, add to the
        // same queue.

        $workspaces = $user->workspaces;
        $notionObjects = NotionObject::whereIn('workspace_id', $workspaces->pluck('id'))->get();
        $deletionsInProgress = ToolDuplicateRequest::whereIn('notion_object_id', $notionObjects->pluck('id'))
            ->whereNotNull('delete_batch_id')
            ->whereNull('delete_ended_at')
            ->orderBy('delete_started_at', 'desc')
            ->get();

        $queue = null;
        foreach ($deletionsInProgress as $deletion) {
            $batch = Bus::findBatch($deletion->delete_batch_id);
            if ($batch && $batch->finished() === false) {
                if ($batch->queue === QueueConstants::DELETE_QUEUE_1) {
                    $queue = QueueConstants::DELETE_QUEUE_1;
                } else {
                    $queue = QueueConstants::DELETE_QUEUE_2;
                }
                break;
            }
        }

        // If user has no jobs in a queue, find the queue with the least jobs
        // remaining.
        if (!$queue) {
            $queue1Size = DB::table('jobs')->where('queue', QueueConstants::DELETE_QUEUE_1)->count();
            $queue2Size = DB::table('jobs')->where('queue', QueueConstants::DELETE_QUEUE_2)->count();
            $queue = $queue1Size <= $queue2Size ? QueueConstants::DELETE_QUEUE_1 : QueueConstants::DELETE_QUEUE_2;
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

                $toolDuplicateRequest->refresh();
                $not_deleted_ids = is_array($toolDuplicateRequest->delete_list) && is_array($toolDuplicateRequest->delete_results)
                    ? array_diff($toolDuplicateRequest->delete_list, $toolDuplicateRequest->delete_results)
                    : [];
                $not_deleted_count = count($not_deleted_ids);

                $toolDuplicateRequest->not_deleted_count = $not_deleted_count;
                $toolDuplicateRequest->delete_ended_at = now();
                $toolDuplicateRequest->save();
            })
            ->name('Delete duplicates for request: ' . $toolDuplicateRequest->id)
            // Prevent a job failure from stopping the batch
            ->allowFailures()
            ->onQueue($queue)->dispatch();

        $toolDuplicateRequest->delete_batch_id = $batch->id;
        $toolDuplicateRequest->save();

        return to_route('duplicates.request.confirm', ['toolDuplicateRequest' => $toolDuplicateRequest->id]);
    }
}
