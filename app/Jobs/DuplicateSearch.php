<?php

namespace App\Jobs;

use App\Helpers\NotionHelper;
use App\Models\ToolDuplicateRequest;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Notion\Databases\Query;
use Notion\Notion;
use App\Models\DuplicateResultsDTO;
use Illuminate\Foundation\Queue\Queueable;

class DuplicateSearch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $toolDuplicateRequest;
    protected $next_cursor;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(ToolDuplicateRequest $toolDuplicateRequest, ?string $next_cursor = null)
    {
        $this->toolDuplicateRequest = $toolDuplicateRequest;
        $this->next_cursor = $next_cursor;
        Log::debug('Duplicate search job created for request: ' . $toolDuplicateRequest->id);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [2, 10];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startedAt = Carbon::now();
        Log::debug('Duplicate search job started for request: ' . $this->toolDuplicateRequest->id);
        DB::transaction(function () use ($startedAt) {
            if (!$this->toolDuplicateRequest->refresh()->search_started_at) {
                $this->toolDuplicateRequest->update([
                    'search_started_at' => $startedAt,
                ]);
            }
        });

        // Get database
        $notionObject = $this->toolDuplicateRequest->notionObject;
        $workspace = $notionObject->workspace;
        $notion = Notion::create($workspace->authData['access_token']);
        $database = $notion->databases()->find($notionObject->data['id']);

        // Create query
        $query = Query::create();
        if ($this->next_cursor) {
            $query = $query->changeStartCursor($this->next_cursor);
        }

        // Submit and get results
        Log::debug('Submitting query for request: ' . $this->toolDuplicateRequest->id);
        $result = $notion->databases()->query($database, $query);
        Log::debug('Received results for request: ' . $this->toolDuplicateRequest->id);
        $pages = $result->pages;
        $pageDTOs = collect($pages)->map(function ($page) {
            return NotionHelper::createNotionDTO($page);
        });

        // Create next job, if needed
        //
        // This dispatch was originally in the transaction after the update, but
        // the batch was executing the finished callback after the first job
        // was run, so I moved it here.
        //
        // Batches might not be the best approach here, since it's not a known
        // amount ahead of time. If there are issues, perhaps refactor to a job
        // that simply creates another job when done. And the last job can
        // set `duplicates` and `search_ended_at`. `search_batch_id` can be
        // removed, and the frontend queued status can simply assume if it's
        // created and has no `search_started_at` that it's waiting in queue.
        if ($result->hasMore) {
            Log::debug('Creating next job for request: ' . $this->toolDuplicateRequest->id);
            self::dispatch($this->toolDuplicateRequest, $result->nextCursor)->onQueue('default');
        } else {
            Log::debug('No more jobs needed for request: ' . $this->toolDuplicateRequest->id);
        }

        DB::transaction(function () use ($pageDTOs, $result) {
            // Save results
            Log::debug('Saving results for request: ' . $this->toolDuplicateRequest->id);
            $existingResults = $this->toolDuplicateRequest->refresh()->search_results ?? [];
            $mergedResults = array_merge($existingResults, $pageDTOs->toArray());

            $this->toolDuplicateRequest->update([
                'search_results' => $mergedResults,
            ]);
        });

        $durationInSeconds = round($startedAt->diffInSeconds(Carbon::now()), 1);
        Log::debug('Duplicate search job completed in ' . strval($durationInSeconds) . 's for request: ' . $this->toolDuplicateRequest->id);
    }
}
