<?php

namespace App\Jobs;

use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WorkspaceRefresh implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $workspace;

    /**
     * Create a new job instance.
     */
    public function __construct(Workspace $workspace)
    {
        // TODO: Is the workspace passed here?
        // TODO: Can users only access their own workspaces?

        logger('Workspace refresh job created for workspace: ' . $workspace->id);
        $this->workspace = $workspace;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Add your workspace refresh logic here
        // For example:
        // $this->workspace->refresh();
        // $this->workspace->save();
        logger('Workspace refresh job executed for workspace: ' . $this->workspace->id);
    }
}
