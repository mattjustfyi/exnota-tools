<?php

namespace App\Jobs;

use App\Models\ToolDuplicateRequest;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Notion\Notion;

class DuplicateDelete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected $toolDuplicateRequest;
    protected $notion_ids;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(ToolDuplicateRequest $toolDuplicateRequest, array $notion_ids)
    {
        $this->toolDuplicateRequest = $toolDuplicateRequest;
        $this->notion_ids = $notion_ids;
        Log::debug('Duplicate delete job created for request: ' . $toolDuplicateRequest->id);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            if (!$this->toolDuplicateRequest->refresh()->delete_started_at) {
                $this->toolDuplicateRequest->update([
                    'delete_started_at' => Carbon::now(),
                ]);
            }
        });

        if (empty($this->notion_ids)) {
            Log::debug('No notion IDs to delete for request: ' . $this->toolDuplicateRequest->id);
            return;
        }

        $notionObject = $this->toolDuplicateRequest->notionObject;
        $workspace = $notionObject->workspace;

        $notion = Notion::create($workspace->authData['access_token']);

        $deletedPageIds = [];
        $erroredPageIds = [];

        foreach ($this->notion_ids as $pageId) {
            try {
                if ($this->batch()->cancelled()) {
                    Log::debug('Duplicate delete batch cancelled for request: ' . $this->toolDuplicateRequest->id);
                    break;
                }

                Log::debug('Fetching page: ' . $pageId);
                $page = $notion->pages()->find($pageId);

                // Notion API will return an error if trying to archive an
                // already archived page: "Can't edit block that is archived. You must unarchive the block before editing."
                if ($page->archived) {
                    // Still flag as deleted to track that it was (probably)
                    // done in a previous attempt at this job.
                    Log::debug('Skipping already-deleted page: ' . $pageId);
                    $deletedPageIds[] = $pageId;
                    continue;
                }

                Log::debug('Deleting page: ' . $pageId);
                $notion->pages()->delete($page);
                $deletedPageIds[] = $pageId;
            } catch (\Exception $e) {
                Log::error('Error deleting page: ' . $pageId . '. Error: ' . $e->getMessage());
                $erroredPageIds[] = $pageId;
            }
        }

        if (!empty($erroredPageIds)) {
            Log::debug('Re-queueing errored pages for request: ' . $this->toolDuplicateRequest->id);
            $this->batch()->add(new DuplicateDelete($this->toolDuplicateRequest, $erroredPageIds));
        }

        DB::transaction(function () use ($deletedPageIds) {
            $existingDeletedPageIds = $this->toolDuplicateRequest->refresh()->delete_results;

            $mergedDeletedPageIds = $existingDeletedPageIds ?? [];
            $mergedDeletedPageIds = array_merge($mergedDeletedPageIds, $deletedPageIds);

            $this->toolDuplicateRequest->update([
                'delete_results' => $mergedDeletedPageIds,
            ]);
        });

        Log::debug('Duplicate delete job completed for request: ' . $this->toolDuplicateRequest->id);
    }
}
