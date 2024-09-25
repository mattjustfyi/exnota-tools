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
    public $tries = 4;

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
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [2, 10, 20];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startedAt = Carbon::now();
        DB::transaction(function () use ($startedAt) {
            if (!$this->toolDuplicateRequest->refresh()->delete_started_at) {
                $this->toolDuplicateRequest->update([
                    'delete_started_at' => $startedAt,
                ]);
            }
        });

        $pageIds = array_diff($this->notion_ids, $this->toolDuplicateRequest->delete_results);
        if (empty($pageIds)) {
            Log::debug('No notion IDs to delete for request: ' . $this->toolDuplicateRequest->id . ' (Job ID: ' . $this->job->getJobId() . ')');
            return;
        }

        $notionObject = $this->toolDuplicateRequest->notionObject;
        $workspace = $notionObject->workspace;

        $notion = Notion::create($workspace->authData['access_token']);

        $deletedPageIds = [];
        $errorPageIds = [];
        foreach ($pageIds as $pageId) {
            // Back off to handle temporary issues, such as rate limiting and
            // "Unsaved transactions: Unrecognized error."
            if ($this->job->attempts() === 1) {
                usleep(10000); // 10ms
            } elseif ($this->job->attempts() === 2) {
                usleep(50000); // 50ms
            } elseif ($this->job->attempts() === 3) {
                usleep(200000); // 200ms
            } elseif ($this->job->attempts() === 4) {
                usleep(500000); // 500ms
            }

            try {
                if ($this->batch()->cancelled()) {
                    Log::debug('Duplicate delete batch cancelled for request: ' . $this->toolDuplicateRequest->id . ' (Job ID: ' . $this->job->getJobId() . ')');
                    break;
                }

                Log::debug('Fetching page: ' . $pageId . ' (Job ID: ' . $this->job->getJobId() . ')');
                $page = $notion->pages()->find($pageId);

                // Notion API will return an error if trying to archive an
                // already archived page: "Can't edit block that is archived. You must unarchive the block before editing."
                if ($page->archived) {
                    // Still flag as deleted to track that it was (probably)
                    // done in a previous attempt at this job.
                    Log::debug('Skipping already-deleted page: ' . $pageId . ' (Job ID: ' . $this->job->getJobId() . ')');
                    $deletedPageIds[] = $pageId;
                    continue;
                }

                Log::debug('Deleting page: ' . $pageId . ' (Job ID: ' . $this->job->getJobId() . ')');
                $notion->pages()->delete($page);
                $deletedPageIds[] = $pageId;
            } catch (\Exception $e) {
                $errorPageIds[] = $pageId;
                if ($e instanceof \Notion\Exceptions\ApiException) {
                    if ($e->notionCode === 'internal_server_error') {
                        // Known errors:
                        // 
                        // 1. Transaction issue. Always temporary.
                        // - message: "Unsaved transactions: Unrecognized error."
                        // - notionCode: "internal_server_error"
                        // - response->statusCode: 500
                        Log::warning('Error deleting page. Notion internal server error. ', ['statusCode' => $e->response->getStatusCode(),  'pageId' => $pageId, 'message' => $e->getMessage(), 'notionCode' => $e->notionCode, 'jobId' => $this->job->getJobId()]);
                    } else if ($e->notionCode === 'validation_error') {
                        // Known errors
                        //
                        // 1. Property too large. Happened when I imported a CSV
                        //    with long text columns.
                        // - message: "body failed validation: body.properties.Description.rich_text[0].text.content.length should be ≤ `2000`, instead was `2950`."
                        // - notionCode: "validation_error
                        // - response->statusCode: 400
                        //
                        Log::warning('Error deleting page. Notion validation error. ', ['statusCode' => $e->response->getStatusCode(),  'pageId' => $pageId, 'message' => $e->getMessage(), 'notionCode' => $e->notionCode, 'jobId' => $this->job->getJobId()]);
                    } else {
                        Log::error('Error deleting page. Other Notion error. ', ['statusCode' => $e->response->getStatusCode(),  'pageId' => $pageId, 'message' => $e->getMessage(), 'notionCode' => $e->notionCode, 'jobId' => $this->job->getJobId()]);
                    }
                } else {
                    Log::error('Error deleting page. Other error. ', ['pageId' => $pageId, 'message' => $e->getMessage(), 'jobId' => $this->job->getJobId()]);
                }
            }
        }

        DB::transaction(function () use ($deletedPageIds) {
            $existingDeletedPageIds = $this->toolDuplicateRequest->refresh()->delete_results;

            $mergedDeletedPageIds = $existingDeletedPageIds ?? [];
            $mergedDeletedPageIds = array_merge($mergedDeletedPageIds, $deletedPageIds);

            $this->toolDuplicateRequest->update([
                'delete_results' => $mergedDeletedPageIds,
            ]);
        });

        $durationInSeconds = round($startedAt->diffInSeconds(Carbon::now()), 1);
        if (!empty($errorPageIds)) {
            Log::warning('Error deleting pages for request: ' . $this->toolDuplicateRequest->id, ['errorPageIds' => $errorPageIds, 'jobId' => $this->job->getJobId()]);
            Log::debug('Duplicate delete job completed in ' . strval($durationInSeconds) . 's for request: ' . $this->toolDuplicateRequest->id . ' (Job ID: ' . $this->job->getJobId() . ')');
            throw new \Exception('Error deleting pages. Job should be retried.');
        } else {
            Log::debug('Duplicate delete job completed in ' . strval($durationInSeconds) . 's for request: ' . $this->toolDuplicateRequest->id . ' (Job ID: ' . $this->job->getJobId() . ')');
        }
    }
}
