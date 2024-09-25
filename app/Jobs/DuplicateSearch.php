<?php

namespace App\Jobs;

use App\DTOs\NotionDTO;
use App\Helpers\NotionHelper;
use App\Models\ToolDuplicateRequest;
use Carbon\Carbon;
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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $toolDuplicateRequest;
    protected $nextCursor;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(ToolDuplicateRequest $toolDuplicateRequest, $nextCursor = null)
    {
        $this->toolDuplicateRequest = $toolDuplicateRequest;
        $this->nextCursor = $nextCursor;
        Log::debug('Duplicate search job created for request: ' . $toolDuplicateRequest->id . ' with cursor: ' . $nextCursor . ' (Job ID: ' . $this->job->getJobId() . ')');
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
        Log::debug('Duplicate search job started for request: ' . $this->toolDuplicateRequest->id . ' with cursor: ' . $this->nextCursor . ' (Job ID: ' . $this->job->getJobId() . ')');

        if (!$this->nextCursor) {
            $this->toolDuplicateRequest->update([
                'search_started_at' => $startedAt,
            ]);
        }

        // Get database
        $notionObject = $this->toolDuplicateRequest->notionObject;
        $workspace = $notionObject->workspace;
        $notion = Notion::create($workspace->authData['access_token']);
        $database = $notion->databases()->find($notionObject->data['id']);

        $allResults = collect($this->toolDuplicateRequest->search_results ?? []);
        $hasMore = true;
        $nextCursor = $this->nextCursor;
        $queryCount = 0;

        while ($hasMore && $queryCount < 6) {
            // Create query
            $query = Query::create();
            if ($nextCursor) {
                $query = $query->changeStartCursor($nextCursor);
            }

            // Submit and get results
            Log::debug('Submitting query ' . ($queryCount + 1) . ' for request: ' . $this->toolDuplicateRequest->id . ' (Job ID: ' . $this->job->getJobId() . ')');
            $result = $notion->databases()->query($database, $query);
            Log::debug('Received results for request: ' . $this->toolDuplicateRequest->id . ' (Job ID: ' . $this->job->getJobId() . ')');

            $pages = $result->pages;
            $pageDTOs = collect($pages)->map(function ($page) {
                return NotionHelper::createNotionDTO($page);
            });

            $allResults = $allResults->concat($pageDTOs);

            $hasMore = $result->hasMore;
            $nextCursor = $result->nextCursor;
            $queryCount++;
        }

        $this->toolDuplicateRequest->update([
            'search_results' => $allResults->toArray(),
        ]);

        if ($hasMore) {
            Log::debug('Dispatching new job for remaining results. Request: ' . $this->toolDuplicateRequest->id . ' Next cursor: ' . $nextCursor . ' (Job ID: ' . $this->job->getJobId() . ')');
            self::dispatch($this->toolDuplicateRequest, $nextCursor)->onQueue('default');
        } else {
            $this->processDuplicates();
            $this->toolDuplicateRequest->update([
                'search_ended_at' => now(),
            ]);
        }

        $durationInSeconds = round($startedAt->diffInSeconds(Carbon::now()), 1);
        Log::debug('Duplicate search job batch completed in ' . strval($durationInSeconds) . 's for request: ' . $this->toolDuplicateRequest->id . ' (Job ID: ' . $this->job->getJobId() . ')');
    }

    /**
     * Process the collected results to find duplicates.
     */
    private function processDuplicates(): void
    {
        $allResults = collect($this->toolDuplicateRequest->search_results);

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
        $this->toolDuplicateRequest->update([
            'duplicates' => $duplicateResults,
        ]);
    }
}
