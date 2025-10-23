<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of books.
     */
    public function index(Request $request): Response
    {
        $query = Book::with(['author', 'category'])
            ->where('user_id', $request->user()->id);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('author', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $books = $query->latest()->paginate(20);

        return Inertia::render('Books/Index', [
            'books' => $books,
            'categories' => Category::all(),
            'filters' => $request->only(['category_id', 'status', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new book.
     */
    public function create(): Response
    {
        return Inertia::render('Books/Create', [
            'categories' => Category::all(),
            'authors' => Author::all(),
        ]);
    }

    /**
     * Store a newly created book.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author_id' => 'nullable|exists:authors,id',
            'category_id' => 'nullable|exists:categories,id',
            'isbn' => 'nullable|string|max:20',
            'publisher' => 'nullable|string|max:255',
            'publish_date' => 'nullable|date',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:5120', // 5MB
            'file' => 'required|file|mimes:pdf,epub,mobi|max:102400', // 100MB
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        // Handle book file upload
        $file = $request->file('file');
        $filePath = $file->store('', 'books');
        $fileType = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();

        // Handle cover image upload
        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('', 'covers');
        }

        // Create book
        $book = $request->user()->books()->create([
            'title' => $validated['title'],
            'author_id' => $validated['author_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'isbn' => $validated['isbn'] ?? null,
            'publisher' => $validated['publisher'] ?? null,
            'publish_date' => $validated['publish_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'cover_image' => $coverPath,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'rating' => $validated['rating'] ?? null,
        ]);

        return redirect()->route('books.show', $book)->with('success', 'Book uploaded successfully!');
    }

    /**
     * Display the specified book.
     */
    public function show(Book $book): Response
    {
        $this->authorize('view', $book);

        $book->load(['author', 'category', 'notes.tags']);

        return Inertia::render('Books/Show', [
            'book' => $book,
        ]);
    }

    /**
     * Show the form for editing the book.
     */
    public function edit(Book $book): Response
    {
        $this->authorize('update', $book);

        return Inertia::render('Books/Edit', [
            'book' => $book->load(['author', 'category']),
            'categories' => Category::all(),
            'authors' => Author::all(),
        ]);
    }

    /**
     * Update the book.
     */
    public function update(Request $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author_id' => 'nullable|exists:authors,id',
            'category_id' => 'nullable|exists:categories,id',
            'isbn' => 'nullable|string|max:20',
            'publisher' => 'nullable|string|max:255',
            'publish_date' => 'nullable|date',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:5120',
            'rating' => 'nullable|integer|min:1|max:5',
            'status' => 'nullable|in:unread,reading,completed',
            'reading_progress' => 'nullable|numeric|min:0|max:100',
        ]);

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            // Delete old cover
            if ($book->cover_image) {
                Storage::disk('covers')->delete($book->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')->store('', 'covers');
        }

        $book->update($validated);

        return redirect()->route('books.show', $book)->with('success', 'Book updated successfully!');
    }

    /**
     * Remove the book.
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        // Delete files
        Storage::disk('books')->delete($book->file_path);
        if ($book->cover_image) {
            Storage::disk('covers')->delete($book->cover_image);
        }

        $book->delete();

        return redirect()->route('books.index')->with('success', 'Book deleted successfully!');
    }

    /**
     * Serve the book file for reading.
     */
    public function serve(Book $book)
    {
        $this->authorize('view', $book);

        $path = Storage::disk('books')->path($book->file_path);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path);
    }
}
