<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Plus, Search, Filter } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Books', href: '/books' },
];

interface Author {
    id: number;
    name: string;
}

interface Category {
    id: number;
    name: string;
    color: string;
}

interface Book {
    id: number;
    title: string;
    cover_image?: string;
    author?: Author;
    category?: Category;
    file_type: string;
    file_size: number;
    formatted_file_size: string;
    status: 'unread' | 'reading' | 'completed';
    rating?: number;
    reading_progress: number;
    created_at: string;
}

interface PaginatedBooks {
    data: Book[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    books: PaginatedBooks;
    categories: Category[];
    filters: {
        category_id?: number;
        status?: string;
        search?: string;
    };
}>();

const search = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category_id || '');
const selectedStatus = ref(props.filters.status || '');

const filterBooks = () => {
    router.get('/books', {
        search: search.value,
        category_id: selectedCategory.value,
        status: selectedStatus.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    selectedCategory.value = '';
    selectedStatus.value = '';
    router.get('/books');
};

const getStatusColor = (status: string) => {
    const colors = {
        unread: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        reading: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    };
    return colors[status as keyof typeof colors] || colors.unread;
};
</script>

<template>
    <Head title="My Books" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-foreground">My Books</h1>
                    <p class="text-muted-foreground mt-1">Manage your personal library</p>
                </div>
                <Link
                    href="/books/create"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                >
                    <Plus class="h-4 w-4" />
                    Add Book
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-4 rounded-lg border border-border bg-card p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="relative md:col-span-2">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search books or authors..."
                            class="w-full rounded-md border border-input bg-background pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            @keyup.enter="filterBooks"
                        />
                    </div>

                    <!-- Category Filter -->
                    <select
                        v-model="selectedCategory"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        @change="filterBooks"
                    >
                        <option value="">All Categories</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>

                    <!-- Status Filter -->
                    <select
                        v-model="selectedStatus"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        @change="filterBooks"
                    >
                        <option value="">All Status</option>
                        <option value="unread">Unread</option>
                        <option value="reading">Reading</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                <button
                    v-if="filters.search || filters.category_id || filters.status"
                    @click="resetFilters"
                    class="text-sm text-muted-foreground hover:text-foreground transition-colors self-start"
                >
                    Clear filters
                </button>
            </div>

            <!-- Books Grid -->
            <div v-if="books.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <Link
                    v-for="book in books.data"
                    :key="book.id"
                    :href="`/books/${book.id}`"
                    class="group flex flex-col rounded-lg border border-border bg-card overflow-hidden hover:shadow-lg transition-all duration-200"
                >
                    <!-- Book Cover -->
                    <div class="relative aspect-[2/3] bg-muted overflow-hidden">
                        <img
                            v-if="book.cover_image"
                            :src="`/storage/covers/${book.cover_image}`"
                            :alt="book.title"
                            class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-200"
                        />
                        <div v-else class="flex h-full items-center justify-center bg-gradient-to-br from-primary/20 to-primary/5">
                            <BookOpen class="h-16 w-16 text-primary/40" />
                        </div>

                        <!-- Reading Progress -->
                        <div v-if="book.reading_progress > 0" class="absolute bottom-0 left-0 right-0 h-1 bg-black/20">
                            <div
                                class="h-full bg-primary transition-all"
                                :style="{ width: `${book.reading_progress}%` }"
                            />
                        </div>
                    </div>

                    <!-- Book Info -->
                    <div class="flex flex-1 flex-col p-4">
                        <h3 class="font-semibold text-foreground line-clamp-2 mb-1">
                            {{ book.title }}
                        </h3>
                        <p v-if="book.author" class="text-sm text-muted-foreground mb-2">
                            {{ book.author.name }}
                        </p>

                        <div class="mt-auto flex items-center justify-between pt-3">
                            <span
                                :class="getStatusColor(book.status)"
                                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                            >
                                {{ book.status }}
                            </span>
                            <span class="text-xs text-muted-foreground uppercase">
                                {{ book.file_type }}
                            </span>
                        </div>

                        <div v-if="book.rating" class="flex items-center gap-1 mt-2">
                            <span v-for="i in 5" :key="i" class="text-yellow-500">
                                {{ i <= book.rating ? '★' : '☆' }}
                            </span>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                <BookOpen class="h-16 w-16 text-muted-foreground mb-4" />
                <h3 class="text-lg font-semibold text-foreground mb-2">No books found</h3>
                <p class="text-muted-foreground mb-6">
                    {{ filters.search || filters.category_id || filters.status
                        ? 'Try adjusting your filters'
                        : 'Start building your library by adding your first book'
                    }}
                </p>
                <Link
                    v-if="!filters.search && !filters.category_id && !filters.status"
                    href="/books/create"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                >
                    <Plus class="h-4 w-4" />
                    Add Your First Book
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="books.last_page > 1" class="flex items-center justify-center gap-2 mt-6">
                <Link
                    v-for="page in books.last_page"
                    :key="page"
                    :href="`/books?page=${page}`"
                    :class="[
                        'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                        page === books.current_page
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-card text-foreground hover:bg-accent'
                    ]"
                    preserve-state
                >
                    {{ page }}
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
