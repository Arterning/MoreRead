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
use Smalot\PdfParser\Parser as PdfParser;

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
            'file' => [
                'required',
                'file',
                'max:102400', // 100MB
                function ($attribute, $value, $fail) {
                    $allowedExtensions = ['pdf', 'epub', 'mobi'];
                    $extension = strtolower($value->getClientOriginalExtension());
                    if (!in_array($extension, $allowedExtensions)) {
                        $fail('The file must be a PDF, EPUB, or MOBI file.');
                    }
                },
            ],
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
        } elseif (strtolower($fileType) === 'pdf') {
            // Generate text-based cover from PDF metadata if no cover uploaded
            try {
                $pdfPath = Storage::disk('books')->path($filePath);
                $coverPath = $this->generatePdfCover($pdfPath, $validated['title']);
            } catch (\Exception $e) {
                // Log error but don't fail the upload
                \Log::warning('Failed to generate PDF cover: ' . $e->getMessage());
            }
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

        // Different validation rules for progress updates vs full updates
        $rules = [
            'title' => 'sometimes|required|string|max:255',
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
        ];

        $validated = $request->validate($rules);

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
     * Show the reading page.
     */
    public function read(Book $book): Response
    {
        $this->authorize('view', $book);

        return Inertia::render('Books/Read', [
            'book' => $book->load('author'),
        ]);
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

    /**
     * Generate a text-based cover image from PDF metadata.
     */
    private function generatePdfCover(string $pdfPath, string $fallbackTitle): ?string
    {
        try {
            // Parse PDF to extract metadata
            $parser = new PdfParser();
            $pdf = $parser->parseFile($pdfPath);
            $details = $pdf->getDetails();

            // Get title and author from PDF metadata or use fallback
            $title = $details['Title'] ?? $fallbackTitle;
            $author = $details['Author'] ?? '';
            $pages = $pdf->getPages();
            $pageCount = count($pages);

            // Create image with GD
            $width = 600;
            $height = 800;
            $image = imagecreatetruecolor($width, $height);

            // Colors
            $colors = [
                ['bg' => [41, 128, 185], 'text' => [255, 255, 255]],    // Blue
                ['bg' => [46, 204, 113], 'text' => [255, 255, 255]],    // Green
                ['bg' => [155, 89, 182], 'text' => [255, 255, 255]],    // Purple
                ['bg' => [52, 73, 94], 'text' => [255, 255, 255]],      // Dark
                ['bg' => [230, 126, 34], 'text' => [255, 255, 255]],    // Orange
            ];

            // Choose random color scheme
            $colorScheme = $colors[array_rand($colors)];
            $bgColor = imagecolorallocate($image, ...$colorScheme['bg']);
            $textColor = imagecolorallocate($image, ...$colorScheme['text']);
            $lightColor = imagecolorallocate($image, 255, 255, 255);

            // Fill background
            imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

            // Add decorative elements
            $accentColor = imagecolorallocatealpha($image, 255, 255, 255, 100);
            imagefilledellipse($image, -50, -50, 200, 200, $accentColor);
            imagefilledellipse($image, $width + 50, $height + 50, 300, 300, $accentColor);

            // Font settings - using built-in GD fonts
            $titleLines = $this->wrapText($title, 30);
            $yPosition = 250;

            // Draw title (centered, multi-line)
            foreach ($titleLines as $line) {
                $bbox = imagettfbbox(32, 0, $this->getSystemFont(), $line);
                $textWidth = abs($bbox[4] - $bbox[0]);
                $x = ($width - $textWidth) / 2;
                imagettftext($image, 32, 0, $x, $yPosition, $textColor, $this->getSystemFont(), $line);
                $yPosition += 50;
            }

            // Draw author
            if ($author) {
                $bbox = imagettfbbox(20, 0, $this->getSystemFont(), $author);
                $textWidth = abs($bbox[4] - $bbox[0]);
                $x = ($width - $textWidth) / 2;
                imagettftext($image, 20, 0, $x, $yPosition + 50, $lightColor, $this->getSystemFont(), $author);
            }

            // Draw page count at bottom
            $pageText = "{$pageCount} 页";
            $bbox = imagettfbbox(16, 0, $this->getSystemFont(), $pageText);
            $textWidth = abs($bbox[4] - $bbox[0]);
            $x = ($width - $textWidth) / 2;
            imagettftext($image, 16, 0, $x, $height - 50, $lightColor, $this->getSystemFont(), $pageText);

            // Save to temp file
            $coverFilename = pathinfo($pdfPath, PATHINFO_FILENAME) . '_cover.jpg';
            $tempCoverPath = sys_get_temp_dir() . '/' . $coverFilename;
            imagejpeg($image, $tempCoverPath, 90);
            imagedestroy($image);

            // Store the generated cover
            $storedPath = Storage::disk('covers')->putFileAs(
                '',
                new \Illuminate\Http\File($tempCoverPath),
                $coverFilename
            );

            // Clean up temp file
            @unlink($tempCoverPath);

            return $storedPath;
        } catch (\Exception $e) {
            \Log::error('Failed to generate PDF cover: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Wrap text to fit within a specified character limit per line.
     */
    private function wrapText(string $text, int $maxChars): array
    {
        $words = mb_str_split($text, $maxChars);
        return array_slice($words, 0, 3); // Max 3 lines
    }

    /**
     * Get system font path (fallback to GD built-in font).
     */
    private function getSystemFont(): string
    {
        // Try common font paths
        $fonts = [
            'C:/Windows/Fonts/msyh.ttc',      // Microsoft YaHei (supports Chinese)
            'C:/Windows/Fonts/simhei.ttf',    // SimHei
            'C:/Windows/Fonts/arial.ttf',     // Arial
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', // Linux
        ];

        foreach ($fonts as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }

        // If no font found, throw exception
        throw new \Exception('No suitable font found for cover generation');
    }
}
