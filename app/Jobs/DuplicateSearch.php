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

class DuplicateSearch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, SerializesModels;

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

        $notionObject = $this->toolDuplicateRequest->notionObject;
        $workspace = $notionObject->workspace;

        $notion = Notion::create($workspace->authData['access_token']);
        $database = $notion->databases()->find($notionObject->data['id']);

        $query = Query::create();
        if ($this->next_cursor) {
            $query = $query->changeStartCursor($this->next_cursor);
        }

        $result = $notion->databases()->query($database, $query);
        $pages = $result->pages;

        $remoteNotionObjects = collect($pages)->map(function ($page) {
            return NotionHelper::createNotionDTO($page);
        });

        DB::transaction(function () use ($remoteNotionObjects, $result) {
            $existingResults = $this->toolDuplicateRequest->refresh()->search_results ?? [];
            $mergedResults = array_merge($existingResults, $remoteNotionObjects->toArray());

            $this->toolDuplicateRequest->update([
                'search_results' => $mergedResults,
            ]);

            if ($result->hasMore) {
                self::dispatch($this->toolDuplicateRequest, $result->nextCursor)->onQueue('default');
            } else {
                $this->processDuplicates();
            }
        });

        $durationInSeconds = round($startedAt->diffInSeconds(Carbon::now()), 1);
        Log::debug('Duplicate search job completed in ' . strval($durationInSeconds) . 's for request: ' . $this->toolDuplicateRequest->id);
    }

    /**
     * Process the collected results to find duplicates.
     */
    private function processDuplicates(): void
    {
        $allResults = collect($this->toolDuplicateRequest->search_results);

        // Group objects by title
        $groupedByTitle = $allResults->groupBy(function ($object) {
            return $object['title'];
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
                if ($oldest === null || $item['created_date'] < $oldest['created_date']) {
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
            'search_results' => $duplicateResults,
            'search_ended_at' => Carbon::now(),
        ]);
    }
}
