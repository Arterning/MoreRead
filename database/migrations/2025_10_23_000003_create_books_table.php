<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            // Basic information
            $table->string('title');
            $table->string('isbn')->nullable();
            $table->string('publisher')->nullable();
            $table->date('publish_date')->nullable();
            $table->text('description')->nullable();

            // File information
            $table->string('cover_image')->nullable();
            $table->string('file_path');
            $table->string('file_type'); // pdf, epub, mobi, etc.
            $table->unsignedBigInteger('file_size'); // in bytes

            // Reading information
            $table->integer('pages')->nullable();
            $table->decimal('reading_progress', 5, 2)->default(0); // 0-100%
            $table->enum('status', ['unread', 'reading', 'completed'])->default('unread');
            $table->tinyInteger('rating')->nullable(); // 1-5

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('author_id');
            $table->index('category_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
