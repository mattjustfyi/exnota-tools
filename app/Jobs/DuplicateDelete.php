<?php

namespace App\Jobs;

use App\Models\ToolDuplicateRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Notion\Notion;

class DuplicateDelete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $toolDuplicateRequest;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(ToolDuplicateRequest $toolDuplicateRequest)
    {
        $this->toolDuplicateRequest = $toolDuplicateRequest;
        logger('Duplicate delete job created for request: ' . $toolDuplicateRequest->id);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $deleteStartedAt = Carbon::now();

        // TODO: Don't allow deleting if search_results is empty.

        $notionObject = $this->toolDuplicateRequest->notionObject;
        $workspace = $notionObject->workspace;

        $notion = Notion::create($workspace->authData['access_token']);

        $deletedPageIds = [];

        foreach ($this->toolDuplicateRequest->search_results as $result) {
            foreach ($result->delete as $deleteItem) {
                try {
                    $pageId = $deleteItem->notion_id;
                    $page = $notion->pages()->find($pageId);

                    // Notion API will return an error if trying to archive an
                    // already archived page: "Can't edit block that is archived. You must unarchive the block before editing."
                    if ($page->archived) {
                        // Still flag as deleted to track that it was (probably)
                        // done in a previous attempt at this job.
                        $deletedPageIds[] = $pageId;
                        continue;
                    }

                    $notion->pages()->delete($page);
                    $deletedPageIds[] = $pageId;
                } catch (\Exception $e) {
                    logger('Error deleting page: ' . $deleteItem->notion_id . '. Error: ' . $e->getMessage());
                }
            }
        }

        $this->toolDuplicateRequest->update([
            'delete_results' => $deletedPageIds,
            'delete_started_at' => $deleteStartedAt,
            'delete_ended_at' => Carbon::now(),
        ]);

        logger('Duplicate delete job completed for request: ' . $this->toolDuplicateRequest->id);
    }
}
