<?php

namespace App\Jobs;

use App\Enums\NotionObjectType;
use App\Models\Workspace;
use App\Models\NotionObject;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Notion\Common\RichText;
use Notion\Databases\Database;
use Notion\Notion;
use Notion\Pages\Page;
use Notion\Pages\Properties\PropertyType;
use Notion\Pages\Properties\Title;
use Notion\Search\Query;
use Notion\Search\SortDirection;

class WorkspaceRefresh implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $workspace;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Workspace $workspace)
    {
        $this->workspace = $workspace;
        logger('Workspace refresh job created for workspace: ' . $workspace->id);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug('Workspace refresh job started for workspace: ' . $this->workspace->id);

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
                    // Skip if already exists
                    //
                    // TODO: Future: Update existing if it was created more than
                    // some number of minutes ago. Maybe the user changed the
                    // name.
                    //
                    // TODO: Handled archived/trashed.
                    if ($notionObjectIds->contains($notionResult->id)) {
                        Log::debug('NotionObject already exists for workspace: ' . $this->workspace->id . ' with id: ' . $notionResult->id);
                        continue;
                    }

                    // Get type and title
                    $objectType = null;
                    $title = '';
                    if ($notionResult instanceof Page) {
                        $objectType = NotionObjectType::PAGE;
                        $title = $notionResult->title()->toString();
                    } elseif ($notionResult instanceof Database) {
                        $objectType = NotionObjectType::DATABASE;
                        foreach ($notionResult->title as $richText) {
                            $title .= $richText->toString();
                        }
                    } else {
                        // Log unknown object type and continue to next result
                        Log::warning('Unknown object type: ' . $notionResult->object . ' for workspace: ' . $this->workspace->id);
                        continue;
                    }

                    // Persist
                    NotionObject::create([
                        'workspace_id' => $this->workspace->id,
                        'type' => $objectType,
                        'name' => $title,
                        'data' => $notionResult,
                    ]);
                }
                Log::debug('All NotionObjects created for workspace: ' . $this->workspace->id);
            } else {
                Log::warning('No results found for workspace: ' . $this->workspace->id);
            }
        } catch (\Exception $e) {
            Log::error('Error in workspace refresh job for workspace ' . $this->workspace->id . ': ' . $e->getMessage());
            throw $e;
        }

        Log::debug('Workspace refresh job completed for workspace: ' . $this->workspace->id);
    }
}
