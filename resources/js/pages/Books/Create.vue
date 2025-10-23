<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Upload, X, Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Books', href: '/books' },
    { title: 'Add Book', href: '/books/create' },
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

const props = defineProps<{
    categories: Category[];
    authors: Author[];
}>();

const form = useForm({
    title: '',
    author_id: null as number | null,
    category_id: null as number | null,
    isbn: '',
    publisher: '',
    publish_date: '',
    description: '',
    cover_image: null as File | null,
    file: null as File | null,
    rating: null as number | null,
});

const bookFileInput = ref<HTMLInputElement>();
const coverImageInput = ref<HTMLInputElement>();
const bookFilePreview = ref<string>('');
const coverImagePreview = ref<string>('');

const newAuthorName = ref('');
const newCategoryName = ref('');
const showNewAuthorInput = ref(false);
const showNewCategoryInput = ref(false);

// Watch for file changes and auto-fill title
watch(() => form.file, (file) => {
    if (file && !form.title) {
        // Extract filename without extension
        const fileName = file.name.replace(/\.[^/.]+$/, '');
        form.title = fileName;
    }
    if (file) {
        bookFilePreview.value = file.name;
    }
});

watch(() => form.cover_image, (file) => {
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            coverImagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
});

const handleBookFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.file = target.files[0];
    }
};

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

const createNewAuthor = async () => {
    if (!newAuthorName.value.trim()) return;

    try {
        const response = await fetch('/api/authors', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ name: newAuthorName.value }),
        });

        if (response.ok) {
            const author = await response.json();
            props.authors.push(author);
            form.author_id = author.id;
            newAuthorName.value = '';
            showNewAuthorInput.value = false;
        }
    } catch (error) {
        console.error('Failed to create author:', error);
    }
};

const createNewCategory = async () => {
    if (!newCategoryName.value.trim()) return;

    try {
        const response = await fetch('/api/categories', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                name: newCategoryName.value,
                color: '#6366f1', // Default color
            }),
        });

        if (response.ok) {
            const category = await response.json();
            props.categories.push(category);
            form.category_id = category.id;
            newCategoryName.value = '';
            showNewCategoryInput.value = false;
        }
    } catch (error) {
        console.error('Failed to create category:', error);
    }
};

const submit = () => {
    form.post('/books', {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Add Book" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6 max-w-4xl mx-auto">
            <div>
                <h1 class="text-3xl font-bold text-foreground">Add New Book</h1>
                <p class="text-muted-foreground mt-1">Upload and manage your book's information</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Book File Upload (Required) -->
                <div class="rounded-lg border border-border bg-card p-6">
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Book File <span class="text-destructive">*</span>
                    </label>
                    <div
                        @click="bookFileInput?.click()"
                        class="relative border-2 border-dashed border-border rounded-lg p-8 text-center hover:border-primary cursor-pointer transition-colors"
                    >
                        <input
                            ref="bookFileInput"
                            type="file"
                            accept=".pdf,.epub,.mobi"
                            class="hidden"
                            @change="handleBookFileChange"
                        />
                        <Upload class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                        <p v-if="!bookFilePreview" class="text-sm text-muted-foreground">
                            Click to upload PDF, EPUB, or MOBI file<br />
                            <span class="text-xs">Maximum file size: 100MB</span>
                        </p>
                        <p v-else class="text-sm text-foreground font-medium">
                            {{ bookFilePreview }}
                        </p>
                    </div>
                    <p v-if="form.errors.file" class="mt-2 text-sm text-destructive">{{ form.errors.file }}</p>
                </div>

                <!-- Title (Required, Auto-filled) -->
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
                        <div class="space-y-2">
                            <select
                                v-if="!showNewAuthorInput"
                                id="author"
                                v-model="form.author_id"
                                class="w-full rounded-md border border-input bg-background px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            >
                                <option :value="null">Select author</option>
                                <option v-for="author in authors" :key="author.id" :value="author.id">
                                    {{ author.name }}
                                </option>
                            </select>
                            <div v-else class="flex gap-2">
                                <input
                                    v-model="newAuthorName"
                                    type="text"
                                    placeholder="Author name"
                                    class="flex-1 rounded-md border border-input bg-background px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                    @keyup.enter="createNewAuthor"
                                />
                                <button
                                    type="button"
                                    @click="createNewAuthor"
                                    class="px-4 py-2 bg-primary text-primary-foreground rounded-md text-sm hover:bg-primary/90"
                                >
                                    Add
                                </button>
                                <button
                                    type="button"
                                    @click="showNewAuthorInput = false"
                                    class="px-4 py-2 bg-secondary text-secondary-foreground rounded-md text-sm hover:bg-secondary/90"
                                >
                                    Cancel
                                </button>
                            </div>
                            <button
                                v-if="!showNewAuthorInput"
                                type="button"
                                @click="showNewAuthorInput = true"
                                class="text-sm text-primary hover:text-primary/80 flex items-center gap-1"
                            >
                                <Plus class="h-4 w-4" />
                                Add new author
                            </button>
                        </div>
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-foreground mb-2">
                            Category
                        </label>
                        <div class="space-y-2">
                            <select
                                v-if="!showNewCategoryInput"
                                id="category"
                                v-model="form.category_id"
                                class="w-full rounded-md border border-input bg-background px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            >
                                <option :value="null">Select category</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <div v-else class="flex gap-2">
                                <input
                                    v-model="newCategoryName"
                                    type="text"
                                    placeholder="Category name"
                                    class="flex-1 rounded-md border border-input bg-background px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                    @keyup.enter="createNewCategory"
                                />
                                <button
                                    type="button"
                                    @click="createNewCategory"
                                    class="px-4 py-2 bg-primary text-primary-foreground rounded-md text-sm hover:bg-primary/90"
                                >
                                    Add
                                </button>
                                <button
                                    type="button"
                                    @click="showNewCategoryInput = false"
                                    class="px-4 py-2 bg-secondary text-secondary-foreground rounded-md text-sm hover:bg-secondary/90"
                                >
                                    Cancel
                                </button>
                            </div>
                            <button
                                v-if="!showNewCategoryInput"
                                type="button"
                                @click="showNewCategoryInput = true"
                                class="text-sm text-primary hover:text-primary/80 flex items-center gap-1"
                            >
                                <Plus class="h-4 w-4" />
                                Add new category
                            </button>
                        </div>
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
                                Click to upload cover image (optional)
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
                        {{ form.processing ? 'Uploading...' : 'Add Book' }}
                    </button>
                    <a
                        href="/books"
                        class="px-6 py-3 border border-border rounded-lg font-medium hover:bg-accent transition-colors"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
