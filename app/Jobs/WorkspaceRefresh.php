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
        log('Workspace refresh job executed for workspace: ' . $this->workspace->id);
    }
}
