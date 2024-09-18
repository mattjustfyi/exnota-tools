<?php

namespace App\Jobs;

use App\Helpers\NotionHelper;
use App\Models\ToolDuplicateRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Notion\Databases\Query;
use Notion\Notion;
use App\Models\DuplicateResultsDTO;

class DuplicateSearch implements ShouldQueue
{
    use Queueable;

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
        logger('Duplicate search job created for request: ' . $toolDuplicateRequest->id);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $notionObject = $this->toolDuplicateRequest->notionObject;
        $workspace = $notionObject->workspace;

        $notion = Notion::create($workspace->authData['access_token']);
        $database = $notion->databases()->find($notionObject->data['id']);

        $pages = [];

        $query = Query::create();
        do {
            $result = $notion->databases()->query($database, $query);
            $pages = array_merge($pages, $result->pages);
            if ($result->hasMore) {
                $query = $query->changeStartCursor($result->nextCursor);
            }
        } while ($result->hasMore);

        $remoteNotionObjects = collect($pages)->map(function ($page) {
            return NotionHelper::createNotionDTO($page);
        });

        // Group objects by title
        $groupedByTitle = $remoteNotionObjects->groupBy(function ($object) {
            return $object->title;
        });

        // Filter out groups with only one item (no duplicates)
        $duplicates = $groupedByTitle->filter(function ($group) {
            return $group->count() > 1;
        });

        // TODO: Update so it's clear, and we decide here, which ones will be
        // deleted if user chooses so.

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

        // Update the toolDuplicateRequest with the duplicate results
        $this->toolDuplicateRequest->update([
            'search_results' => $duplicateResults,
            'searched_at' => Carbon::now(),
        ]);
    }
}
