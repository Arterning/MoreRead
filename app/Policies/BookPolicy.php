<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /**
     * Determine if the user can view the book.
     */
    public function view(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * Determine if the user can update the book.
     */
    public function update(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * Determine if the user can delete the book.
     */
    public function delete(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }
}
