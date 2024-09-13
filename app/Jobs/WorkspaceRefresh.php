<?php

namespace App\Jobs;

use App\Models\Workspace;
use App\Models\NotionObject;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Notion\Notion;
use Notion\Search\SearchRequest;

class WorkspaceRefresh implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $workspace;

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
        logger('Workspace refresh job started for workspace: ' . $this->workspace->id);

        try {
            // Create Notion client
            $notion = Notion::create($this->workspace->authData['access_token']);

            // Search Notion without a query
            $request = new SearchRequest();
            $results = $notion->search($request);

            if (count($results) > 0) {
                // Select a random result
                $randomResult = $results[array_rand($results)];

                // Persist to NotionObject
                NotionObject::create([
                    'workspace_id' => $this->workspace->id,
                    'notion_id' => $randomResult->id(),
                    'type' => $randomResult->type(),
                    'data' => json_encode($randomResult->toArray()),
                ]);

                logger('NotionObject created for workspace: ' . $this->workspace->id);
            } else {
                logger('No results found for workspace: ' . $this->workspace->id);
            }
        } catch (\Exception $e) {
            logger('Error in workspace refresh job for workspace ' . $this->workspace->id . ': ' . $e->getMessage());
        }

        logger('Workspace refresh job completed for workspace: ' . $this->workspace->id);
    }
}
