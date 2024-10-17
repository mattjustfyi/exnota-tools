<?php

namespace App\Jobs;

use App\Enums\NotionObjectType;
use App\Enums\SyncStatus;
use App\Helpers\NotionHelper;
use App\Models\Workspace;
use App\Models\NotionObject;
use App\Models\User;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Notion\Databases\Database;
use Notion\Notion;
use Notion\Pages\Page;
use Notion\Search\Query;
use Notion\Search\SortDirection;
use Throwable;

class WorkspaceRefresh implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $workspace;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     * 
     * @param User $user The user who initiated the request. Used so jobs can
     * be searched by user ID.
     */
    public function __construct(User $user, Workspace $workspace)
    {
        $this->user = $user;
        $this->workspace = $workspace;
        logger('Workspace refresh job created for workspace: ' . $workspace->id);
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
        Log::debug('Workspace refresh job started for workspace: ' . $this->workspace->id);
        $this->workspace->update(['sync_status' => SyncStatus::IN_PROGRESS]);

        try {
            // Create Notion client
            $notion = Notion::create($this->workspace->authData['access_token']);

            // Search Notion without a query
            $query = Query::all();
            $query = $query->filterByDatabases();
            $query = $query->sortByLastEditedTime(SortDirection::Descending);
            $result = $notion->search()->search($query);

            // Get existing Notion object IDs
            $notionObjects = NotionObject::where('workspace_id', $this->workspace->id)->get();
            $notionObjectIds = $notionObjects->map(function ($notionObject) {
                return $notionObject->data['id'];
            });

            if (!empty($result->results)) {
                foreach ($result->results as $notionResult) {
                    // Get type and title
                    $objectType = null;
                    if ($notionResult instanceof Page) {
                        $objectType = NotionObjectType::PAGE;
                    } elseif ($notionResult instanceof Database) {
                        $objectType = NotionObjectType::DATABASE;
                    } else {
                        // Log unknown object type and continue to next result
                        Log::error('Unknown object type: ' . $notionResult->object . ' for workspace: ' . $this->workspace->id);
                        continue;
                    }

                    $title = NotionHelper::getTitleFromNotionResult($notionResult);

                    // Check if NotionObject already exists
                    $existingNotionObject = NotionObject::where('workspace_id', $this->workspace->id)
                        ->where('data->id', $notionResult->id)
                        ->first();

                    if ($existingNotionObject) {
                        // Update existing NotionObject
                        $existingNotionObject->update([
                            'type' => $objectType,
                            'name' => $title,
                            'data' => $notionResult,
                        ]);
                        Log::debug('NotionObject updated for workspace: ' . $this->workspace->id . ' with id: ' . $notionResult->id);

                        // TODO: Skip importing archived objects. Will require
                        // making a copy of the PHP Notion SDK because
                        // it does not support the archived property on
                        // databases.
                        //
                        // } else if ($notionResult->archived === true) {
                        //     Log::debug('Skipping archived NotionObject for workspace: ' . $this->workspace->id . ' with id: ' . $notionResult->id);
                        // continue;
                    } else {
                        // Create new NotionObject
                        NotionObject::create([
                            'workspace_id' => $this->workspace->id,
                            'type' => $objectType,
                            'name' => $title,
                            'data' => $notionResult,
                        ]);
                        Log::debug('New NotionObject created for workspace: ' . $this->workspace->id . ' with id: ' . $notionResult->id);
                    }
                }
                Log::debug('All NotionObjects created for workspace: ' . $this->workspace->id);
            } else {
                Log::warning('No results found for workspace: ' . $this->workspace->id);
            }
        } catch (\Exception $e) {
            Log::error('Error in workspace refresh job for workspace ' . $this->workspace->id . ': ' . $e->getMessage());
            throw $e;
        }

        $this->workspace->update(['sync_status' => SyncStatus::COMPLETE]);
        Log::debug('Workspace refresh job completed for workspace: ' . $this->workspace->id);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        $this->workspace->update(['sync_status' => SyncStatus::FAILED]);
    }
}
