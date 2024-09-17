<?php

namespace App\Jobs;

use App\Enums\NotionObjectType;
use App\Helpers\NotionHelper;
use App\Models\RemoteNotionObject;
use App\Models\ToolDuplicateRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Notion\Databases\Query;
use Notion\Notion;

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
            return NotionHelper::createRemoteNotionObject($page);
        });

        // Group objects by title
        $groupedByTitle = $remoteNotionObjects->groupBy(function ($object) {
            return $object->getTitle();
        });

        // Filter out groups with only one item (no duplicates)
        $duplicates = $groupedByTitle->filter(function ($group) {
            return $group->count() > 1;
        });

        // Create the list of duplicate pairs
        $duplicatePairs = $duplicates->map(function ($group) {
            return $group->values()->all();
        })->values()->all();

        $this->toolDuplicateRequest->update([
            'search_results' => $remoteNotionObjects,
            'duplicates' => $duplicatePairs,
            'searched_at' => Carbon::now()
        ]);
    }
}
