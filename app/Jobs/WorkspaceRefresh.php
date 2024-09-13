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

            $result = $notion->search()->search($query);

            if (!empty($result->results)) {
                // Select a random result
                $randomResult = $result->results[array_rand($result->results)];


                // Create a variable $objectType of type NotionObjectType (enum)
                $objectType = null;
                $title = '';
                if ($randomResult instanceof Page) {
                    $objectType = NotionObjectType::PAGE;
                    $title = $randomResult->title()->toString();
                } elseif ($randomResult instanceof Database) {
                    $objectType = NotionObjectType::DATABASE;
                    foreach ($randomResult->title as $richText) {
                        $title .= $richText->toString();
                    }
                } else {
                    // Persist to NotionObject
                    Log::error('Unknown object type: ' . $randomResult->object);
                    throw new \Exception('Unknown object type: ' . $randomResult->object);
                }

                NotionObject::create([
                    // TODO: Update these
                    'workspace_id' => $this->workspace->id,
                    'type' => $objectType,
                    'name' => $title,
                    'data' => $randomResult,
                ]);
                Log::debug('NotionObject created for workspace: ' . $this->workspace->id);
            } else {
                Log::warning('No results found for workspace: ' . $this->workspace->id);

                // Throw so job is retried, in case results were not cached yet.
                throw new \Exception('No results found for workspace: ' . $this->workspace->id);
            }
        } catch (\Exception $e) {
            Log::error('Error in workspace refresh job for workspace ' . $this->workspace->id . ': ' . $e->getMessage());
            throw $e;
        }

        Log::debug('Workspace refresh job completed for workspace: ' . $this->workspace->id);
    }
}
