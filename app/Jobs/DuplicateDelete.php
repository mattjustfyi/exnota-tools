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

        $notionObject = $this->toolDuplicateRequest->notionObject;
        $workspace = $notionObject->workspace;

        $notion = Notion::create($workspace->authData['access_token']);

        $deletedPageIds = [];

        foreach ($this->toolDuplicateRequest->search_results as $result) {
            foreach ($result->delete as $deleteItem) {
                try {
                    $page = $notion->pages()->find($deleteItem->notion_id);
                    $notion->pages()->update($page, [
                        'archived' => true
                    ]);
                    $deletedPageIds[] = $deleteItem->notion_id;
                } catch (\Exception $e) {
                    logger('Error deleting page: ' . $deleteItem->notion_id . '. Error: ' . $e->getMessage());
                }
            }
        }

        // Update the toolDuplicateRequest with the delete results
        $this->toolDuplicateRequest->update([
            'delete_results' => $deletedPageIds,
            'delete_started_at' => $deleteStartedAt,
            'delete_ended_at' => Carbon::now(),
        ]);

        logger('Duplicate delete job completed for request: ' . $this->toolDuplicateRequest->id);
    }
}
