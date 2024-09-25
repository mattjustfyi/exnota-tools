<?php

namespace App\Http\Controllers;

use App\Jobs\DuplicateSearch;
use App\Models\DuplicateResultsDTO;
use App\Models\NotionObject;
use App\Models\ToolDuplicateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Log;
use Throwable;

class DuplicateCreateController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'notion_object_id' => 'required|integer',
        ]);

        $notionObject = NotionObject::where('id', $request->notion_object_id)->first();

        if ($request->user()->cant('view', $notionObject)) {
            return response()->json([
                'message' => 'You do not have permission to access this Notion object',
            ], 403);
        }

        $existingDuplicateRequest = ToolDuplicateRequest::where('notion_object_id', $request->notion_object_id)
            ->orderByDesc('created_at')
            ->first();

        if ($existingDuplicateRequest) {
            // Couldn't get back slashes escaping properly, so subtituted with wildcards.
            $incompleteJobs = DB::table('jobs')
                ->whereRaw('payload ~ ?', '"displayName":"App.{2}Jobs.{2}DuplicateSearch"')
                ->whereRaw('payload ~ ?', ';s:[0-9]+:.{1}"App.{2}Models.{2}ToolDuplicateRequest.{1}";s:[0-9]+:.{1}"id.{1}";i:' . strval($existingDuplicateRequest->id) . ';')
                ->count();

            if ($incompleteJobs > 0) {
                return to_route('duplicates.request.confirm', ['toolDuplicateRequest' => $existingDuplicateRequest->id]);
            }
        }

        $toolDuplicateRequest = ToolDuplicateRequest::create([
            'notion_object_id' => $request->notion_object_id,
        ]);

        $batch = Bus::batch([
            new DuplicateSearch($toolDuplicateRequest)
        ])
            ->before(function (Batch $batch) use ($toolDuplicateRequest) {
                // The batch has been created but no jobs have been added...
                Log::debug('Batch search created for request: ' . $toolDuplicateRequest->id);
            })->progress(function (Batch $batch) use ($toolDuplicateRequest) {
                // A single job has completed successfully...
                Log::debug('Batch search progress for request: ' . $toolDuplicateRequest->id);
            })->then(function (Batch $batch) use ($toolDuplicateRequest) {
                // All jobs completed successfully...
                Log::debug('Batch search completed for request: ' . $toolDuplicateRequest->id);
            })->catch(function (Batch $batch, Throwable $e) use ($toolDuplicateRequest) {
                // First batch job failure detected...
                Log::debug('Batch search failed for request: ' . $toolDuplicateRequest->id);
            })->finally(function (Batch $batch) use ($toolDuplicateRequest) {
                // The batch has finished executing...
                Log::debug('Batch search finished for reqjuest: ' . $toolDuplicateRequest->id);

                Log::debug('Processing duplicates for request: ' . $toolDuplicateRequest->id);
                $this->processDuplicates($toolDuplicateRequest);
                Log::debug('Finished processing duplicates for request: ' . $toolDuplicateRequest->id);

                $toolDuplicateRequest->search_started_at = now();
                $toolDuplicateRequest->save();
            })
            ->name('Delete duplicates for request: ' . $toolDuplicateRequest->id)
            ->dispatch();

        $toolDuplicateRequest->update([
            'search_batch_id' => $batch->id
        ]);

        // TODO: If user has already confirmed, instead redirect to /search
        return to_route('duplicates.request.confirm', ['toolDuplicateRequest' => $toolDuplicateRequest->id]);
    }

    /**
     * Process the collected results to find duplicates.
     */
    private function processDuplicates(ToolDuplicateRequest $toolDuplicateRequest): void
    {
        $allResults = collect($toolDuplicateRequest->search_results);

        // Group objects by title
        $groupedByTitle = $allResults->groupBy(function (NotionDTO $object) {
            return $object->title;
        });

        // Filter out groups with only one item (no duplicates)
        $duplicates = $groupedByTitle->filter(function ($group) {
            return $group->count() > 1;
        });

        // Create the list of duplicate pairs
        $duplicatePairs = $duplicates->map(function ($group) {
            return $group->values()->all();
        })->values()->all();

        $duplicateResults = [];
        foreach ($duplicatePairs as $pair) {
            $oldest = null;
            foreach ($pair as $item) {
                if ($oldest === null || $item->created_date < $oldest->created_date) {
                    $oldest = $item;
                }
            }

            $remainingDuplicates = array_values(array_filter($pair, function ($item) use ($oldest) {
                return $item !== $oldest;
            }));

            array_push($duplicateResults, new DuplicateResultsDTO($oldest, $remainingDuplicates));
        }

        // Update the toolDuplicateRequest with the final duplicate results
        $toolDuplicateRequest->update([
            'search_results' => $duplicateResults,
        ]);
    }
}
