<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ToolDuplicateRequest;
use App\Models\Workspace;

class ToolDuplicateRequestPolicy
{
    /**
     * Determine whether the user can view the tool duplicate request.
     */
    public function view(User $user, ToolDuplicateRequest $toolDuplicateRequest): bool
    {
        return $this->userOwnsNotionObject($user, $toolDuplicateRequest);
    }

    /**
     * Determine whether the user can create tool duplicate requests.
     */
    public function create(User $user, int $notionObject): bool
    {
        return $user->workspaces()
            ->whereHas('notionObjects', function ($query) use ($notionObject) {
                $query->where('id', $notionObject);
            })
            ->exists();
    }

    /**
     * Determine whether the user can update the tool duplicate request.
     */
    public function update(User $user, ToolDuplicateRequest $toolDuplicateRequest): bool
    {
        return $this->userOwnsNotionObject($user, $toolDuplicateRequest);
    }

    /**
     * Determine whether the user can delete the tool duplicate request.
     */
    public function delete(User $user, ToolDuplicateRequest $toolDuplicateRequest): bool
    {
        return $this->userOwnsNotionObject($user, $toolDuplicateRequest);
    }

    /**
     * Check if the user owns the Notion object associated with the tool duplicate request.
     */
    private function userOwnsNotionObject(User $user, ToolDuplicateRequest $toolDuplicateRequest): bool
    {
        return $user->workspaces()
            ->whereHas('notionObjects', function ($query) use ($toolDuplicateRequest) {
                $query->where('id', $toolDuplicateRequest->notion_object_id);
            })
            ->exists();
    }
}
