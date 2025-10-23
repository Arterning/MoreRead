<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

// Books management
Route::middleware(['auth', 'verified'])->group(function () {
    // Books
    Route::resource('books', BookController::class);
    Route::get('books/{book}/serve', [BookController::class, 'serve'])->name('books.serve');

    // Notes
    Route::post('books/{book}/notes', [NoteController::class, 'store'])->name('notes.store');
    Route::put('notes/{note}', [NoteController::class, 'update'])->name('notes.update');
    Route::delete('notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

    // Categories API
    Route::get('api/categories', [CategoryController::class, 'index'])->name('api.categories.index');
    Route::post('api/categories', [CategoryController::class, 'store'])->name('api.categories.store');

    // Authors API
    Route::get('api/authors', [AuthorController::class, 'index'])->name('api.authors.index');
    Route::post('api/authors', [AuthorController::class, 'store'])->name('api.authors.store');
});
