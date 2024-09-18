<?php

namespace App\Policies;

use App\Models\User;
use App\Models\NotionObject;
use Illuminate\Auth\Access\Response;

class NotionObjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, NotionObject $notionObject): bool
    {
        return $this->userOwnsNotionObject($user, $notionObject);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, int $workspace): bool
    {
        return $user->workspaces()
            ->where('id', $workspace)
            ->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, NotionObject $notionObject): bool
    {
        return $this->userOwnsNotionObject($user, $notionObject);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, NotionObject $notionObject): bool
    {
        return $this->userOwnsNotionObject($user, $notionObject);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, NotionObject $notionObject): bool
    {
        return $this->userOwnsNotionObject($user, $notionObject);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, NotionObject $notionObject): bool
    {
        return $this->userOwnsNotionObject($user, $notionObject);
    }

    /**
     * Check if the user owns the Notion object through their workspace.
     */
    private function userOwnsNotionObject(User $user, NotionObject $notionObject): bool
    {
        return $user->workspaces()
            ->whereHas('notionObjects', function ($query) use ($notionObject) {
                $query->where('id', $notionObject->id);
            })
            ->exists();
    }
}
