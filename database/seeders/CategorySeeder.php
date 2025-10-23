<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Fiction', 'description' => 'Fictional stories and novels', 'color' => '#6366f1'],
            ['name' => 'Non-Fiction', 'description' => 'Factual books and biographies', 'color' => '#8b5cf6'],
            ['name' => 'Science', 'description' => 'Scientific and technical books', 'color' => '#3b82f6'],
            ['name' => 'Technology', 'description' => 'Programming, IT, and technology', 'color' => '#06b6d4'],
            ['name' => 'History', 'description' => 'Historical accounts and research', 'color' => '#f59e0b'],
            ['name' => 'Philosophy', 'description' => 'Philosophical texts and thoughts', 'color' => '#ec4899'],
            ['name' => 'Self-Help', 'description' => 'Personal development and improvement', 'color' => '#10b981'],
            ['name' => 'Business', 'description' => 'Business and entrepreneurship', 'color' => '#f97316'],
            ['name' => 'Fantasy', 'description' => 'Fantasy and magical stories', 'color' => '#a855f7'],
            ['name' => 'Mystery', 'description' => 'Mystery and thriller books', 'color' => '#ef4444'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
