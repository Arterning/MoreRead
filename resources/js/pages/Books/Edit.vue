<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Upload, X, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

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
    author?: Author;
    category?: Category;
    author_id?: number;
    category_id?: number;
    isbn?: string;
    publisher?: string;
    publish_date?: string;
    description?: string;
    cover_image?: string;
    file_type: string;
    status: 'unread' | 'reading' | 'completed';
    reading_progress: number;
    rating?: number;
}

const props = defineProps<{
    book: Book;
    categories: Category[];
    authors: Author[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Books', href: '/books' },
    { title: props.book.title, href: `/books/${props.book.id}` },
    { title: 'Edit', href: `/books/${props.book.id}/edit` },
];

const form = useForm({
    title: props.book.title,
    author_id: props.book.author_id || null,
    category_id: props.book.category_id || null,
    isbn: props.book.isbn || '',
    publisher: props.book.publisher || '',
    publish_date: props.book.publish_date || '',
    description: props.book.description || '',
    cover_image: null as File | null,
    rating: props.book.rating || null,
    status: props.book.status,
    reading_progress: props.book.reading_progress,
});

const coverImageInput = ref<HTMLInputElement>();
const coverImagePreview = ref<string>(
    props.book.cover_image ? `/storage/covers/${props.book.cover_image}` : ''
);

watch(() => form.cover_image, (file) => {
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            coverImagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
});

const handleCoverImageChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.cover_image = target.files[0];
    }
};

const removeCoverImage = () => {
    form.cover_image = null;
    coverImagePreview.value = '';
    if (coverImageInput.value) {
        coverImageInput.value.value = '';
    }
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(`/books/${props.book.id}`, {
        forceFormData: true,
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};

const deleteBook = () => {
    if (confirm('Are you sure you want to delete this book? This action cannot be undone.')) {
        router.delete(`/books/${props.book.id}`);
    }
};
</script>

<template>
    <Head :title="`Edit ${book.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6 max-w-4xl mx-auto">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-foreground">Edit Book</h1>
                    <p class="text-muted-foreground mt-1">Update book information</p>
                </div>
                <button
                    @click="deleteBook"
                    type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-destructive text-destructive-foreground rounded-lg text-sm font-medium hover:bg-destructive/90 transition-colors"
                >
                    <Trash2 class="h-4 w-4" />
                    Delete Book
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-foreground mb-2">
                        Title <span class="text-destructive">*</span>
                    </label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        class="w-full rounded-md border border-input bg-background px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        placeholder="Enter book title"
                    />
                    <p v-if="form.errors.title" class="mt-2 text-sm text-destructive">{{ form.errors.title }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Author -->
                    <div>
                        <label for="author" class="block text-sm font-medium text-foreground mb-2">
                            Author
                        </label>
                        <select
                            id="author"
                            v-model="form.author_id"
                            class="w-full rounded-md border border-input bg-background px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option :value="null">Select author</option>
                            <option v-for="author in authors" :key="author.id" :value="author.id">
                                {{ author.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-foreground mb-2">
                            Category
                        </label>
                        <select
                            id="category"
                            v-model="form.category_id"
                            class="w-full rounded-md border border-input bg-background px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option :value="null">Select category</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Reading Status & Progress -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="status" class="block text-sm font-medium text-foreground mb-2">
                            Reading Status
                        </label>
                        <select
                            id="status"
                            v-model="form.status"
                            class="w-full rounded-md border border-input bg-background px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="unread">Unread</option>
                            <option value="reading">Reading</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>

                    <div>
                        <label for="progress" class="block text-sm font-medium text-foreground mb-2">
                            Reading Progress ({{ form.reading_progress }}%)
                        </label>
                        <input
                            id="progress"
                            v-model.number="form.reading_progress"
                            type="range"
                            min="0"
                            max="100"
                            class="w-full"
                        />
                    </div>
                </div>

                <!-- Cover Image Upload -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Cover Image
                    </label>
                    <div v-if="!coverImagePreview" class="space-y-2">
                        <div
                            @click="coverImageInput?.click()"
                            class="relative border-2 border-dashed border-border rounded-lg p-6 text-center hover:border-primary cursor-pointer transition-colors"
                        >
                            <input
                                ref="coverImageInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleCoverImageChange"
                            />
                            <Upload class="mx-auto h-8 w-8 text-muted-foreground mb-2" />
                            <p class="text-sm text-muted-foreground">
                                Click to upload cover image
                            </p>
                        </div>
                    </div>
                    <div v-else class="relative inline-block">
                        <img :src="coverImagePreview" alt="Cover preview" class="h-48 rounded-lg border border-border" />
                        <button
                            type="button"
                            @click="removeCoverImage"
                            class="absolute -top-2 -right-2 bg-destructive text-destructive-foreground rounded-full p-1 hover:bg-destructive/90"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Optional Fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="isbn" class="block text-sm font-medium text-foreground mb-2">
                            ISBN
                        </label>
                        <input
                            id="isbn"
                            v-model="form.isbn"
                            type="text"
                            class="w-full rounded-md border border-input bg-background px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            placeholder="978-0-123456-78-9"
                        />
                    </div>

                    <div>
                        <label for="publisher" class="block text-sm font-medium text-foreground mb-2">
                            Publisher
                        </label>
                        <input
                            id="publisher"
                            v-model="form.publisher"
                            type="text"
                            class="w-full rounded-md border border-input bg-background px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            placeholder="Publisher name"
                        />
                    </div>

                    <div>
                        <label for="publish_date" class="block text-sm font-medium text-foreground mb-2">
                            Publish Date
                        </label>
                        <input
                            id="publish_date"
                            v-model="form.publish_date"
                            type="date"
                            class="w-full rounded-md border border-input bg-background px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-foreground mb-2">
                        Description
                    </label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="w-full rounded-md border border-input bg-background px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        placeholder="Brief description of the book..."
                    />
                </div>

                <!-- Rating -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Rating
                    </label>
                    <div class="flex gap-2">
                        <button
                            v-for="i in 5"
                            :key="i"
                            type="button"
                            @click="form.rating = i"
                            class="text-2xl transition-colors"
                            :class="form.rating && i <= form.rating ? 'text-yellow-500' : 'text-gray-300 dark:text-gray-600'"
                        >
                            {{ form.rating && i <= form.rating ? '★' : '☆' }}
                        </button>
                        <button
                            v-if="form.rating"
                            type="button"
                            @click="form.rating = null"
                            class="ml-2 text-sm text-muted-foreground hover:text-foreground"
                        >
                            Clear
                        </button>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex gap-4 pt-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 bg-primary text-primary-foreground px-6 py-3 rounded-lg font-medium hover:bg-primary/90 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                    <a
                        :href="`/books/${book.id}`"
                        class="px-6 py-3 border border-border rounded-lg font-medium hover:bg-accent transition-colors"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
