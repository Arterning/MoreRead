<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Note;
use App\Models\Tag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    use AuthorizesRequests;
    /**
     * Store a new note.
     */
    public function store(Request $request, Book $book): RedirectResponse
    {
        $this->authorize('view', $book);

        $validated = $request->validate([
            'content' => 'required|string',
            'page_number' => 'nullable|string|max:50',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        DB::transaction(function () use ($request, $book, $validated) {
            // Create note
            $note = $request->user()->notes()->create([
                'book_id' => $book->id,
                'content' => $validated['content'],
                'page_number' => $validated['page_number'] ?? null,
            ]);

            // Handle tags
            if (!empty($validated['tags'])) {
                $tagIds = [];
                foreach ($validated['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['user_id' => $request->user()->id, 'name' => $tagName]
                    );
                    $tagIds[] = $tag->id;
                }
                $note->tags()->sync($tagIds);
            }
        });

        return back()->with('success', 'Note created successfully!');
    }

    /**
     * Update the note.
     */
    public function update(Request $request, Note $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $validated = $request->validate([
            'content' => 'required|string',
            'page_number' => 'nullable|string|max:50',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        DB::transaction(function () use ($request, $note, $validated) {
            // Update note
            $note->update([
                'content' => $validated['content'],
                'page_number' => $validated['page_number'] ?? null,
            ]);

            // Handle tags
            if (isset($validated['tags'])) {
                $tagIds = [];
                foreach ($validated['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['user_id' => $request->user()->id, 'name' => $tagName]
                    );
                    $tagIds[] = $tag->id;
                }
                $note->tags()->sync($tagIds);
            }
        });

        return back()->with('success', 'Note updated successfully!');
    }

    /**
     * Delete the note.
     */
    public function destroy(Note $note): RedirectResponse
    {
        $this->authorize('delete', $note);

        $note->delete();

        return back()->with('success', 'Note deleted successfully!');
    }
}
