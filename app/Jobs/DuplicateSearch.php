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
        $result = $notion->databases()->query($database, $query);
        $pages = $result->pages;
        $remoteNotionObjects = collect($pages)->map(function ($page) {
            return NotionHelper::createNotionDTO($page);
        });

        DB::transaction(function () use ($remoteNotionObjects, $result) {
            // Save results
            $existingResults = $this->toolDuplicateRequest->refresh()->search_results ?? [];
            $mergedResults = array_merge($existingResults, $remoteNotionObjects->toArray());

            $this->toolDuplicateRequest->update([
                'search_results' => $mergedResults,
            ]);


            // Create next job, if needed
            if ($result->hasMore) {
                self::dispatch($this->toolDuplicateRequest, $result->nextCursor)->onQueue('default');
            }
        });

        $durationInSeconds = round($startedAt->diffInSeconds(Carbon::now()), 1);
        Log::debug('Duplicate search job completed in ' . strval($durationInSeconds) . 's for request: ' . $this->toolDuplicateRequest->id);
    }
}
