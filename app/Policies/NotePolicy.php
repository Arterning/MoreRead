<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    /**
     * Determine if the user can update the note.
     */
    public function update(User $user, Note $note): bool
    {
        return $user->id === $note->user_id;
    }

    /**
     * Determine if the user can delete the note.
     */
    public function delete(User $user, Note $note): bool
    {
        return $user->id === $note->user_id;
    }
}
