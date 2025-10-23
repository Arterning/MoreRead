<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    /**
     * Get all authors.
     */
    public function index(): JsonResponse
    {
        return response()->json(Author::all());
    }

    /**
     * Store a new author.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string',
        ]);

        $author = Author::create($validated);

        return response()->json($author, 201);
    }
}
