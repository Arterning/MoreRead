<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    $user = auth()->user();

    $stats = [
        'totalBooks' => $user->books()->count(),
        'readBooks' => $user->books()->where('status', 'completed')->count(),
        'totalNotes' => $user->books()->withCount('notes')->get()->sum('notes_count'),
    ];

    return Inertia::render('Dashboard', ['stats' => $stats]);
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/books.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
