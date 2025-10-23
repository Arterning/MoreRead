<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { BookOpen, Edit, StickyNote, Plus, X, Tag } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import PdfReader from '@/components/readers/PdfReader.vue';
import EpubReader from '@/components/readers/EpubReader.vue';

interface Author {
    id: number;
    name: string;
}

interface Category {
    id: number;
    name: string;
    color: string;
}

interface Tag {
    id: number;
    name: string;
}

interface Note {
    id: number;
    content: string;
    page_number?: string;
    tags: Tag[];
    created_at: string;
}

interface Book {
    id: number;
    title: string;
    cover_image?: string;
    author?: Author;
    category?: Category;
    isbn?: string;
    publisher?: string;
    publish_date?: string;
    description?: string;
    file_type: string;
    file_size: number;
    formatted_file_size: string;
    pages?: number;
    status: 'unread' | 'reading' | 'completed';
    reading_progress: number;
    rating?: number;
    notes: Note[];
    created_at: string;
}

const props = defineProps<{
    book: Book;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Books', href: '/books' },
    { title: props.book.title, href: `/books/${props.book.id}` },
];

const showReader = ref(false);
const showNoteForm = ref(false);
const editingNote = ref<Note | null>(null);
const currentPage = ref<number | string>(1);

const noteForm = useForm({
    content: '',
    page_number: '',
    tags: [] as string[],
});

const newTag = ref('');

const openReader = () => {
    showReader.value = true;
};

const closeReader = () => {
    showReader.value = false;
};

const handlePageChange = (page: number | string) => {
    currentPage.value = page;
    noteForm.page_number = String(page);
};

const addTag = () => {
    if (newTag.value.trim() && !noteForm.tags.includes(newTag.value.trim())) {
        noteForm.tags.push(newTag.value.trim());
        newTag.value = '';
    }
};

const removeTag = (index: number) => {
    noteForm.tags.splice(index, 1);
};

const openNoteForm = () => {
    showNoteForm.value = true;
    editingNote.value = null;
    noteForm.reset();
    noteForm.page_number = String(currentPage.value);
};

const editNote = (note: Note) => {
    editingNote.value = note;
    showNoteForm.value = true;
    noteForm.content = note.content;
    noteForm.page_number = note.page_number || '';
    noteForm.tags = note.tags.map(t => t.name);
};

const saveNote = () => {
    if (editingNote.value) {
        noteForm.put(`/notes/${editingNote.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showNoteForm.value = false;
                noteForm.reset();
            },
        });
    } else {
        noteForm.post(`/books/${props.book.id}/notes`, {
            preserveScroll: true,
            onSuccess: () => {
                showNoteForm.value = false;
                noteForm.reset();
            },
        });
    }
};

const deleteNote = (noteId: number) => {
    if (confirm('Are you sure you want to delete this note?')) {
        router.delete(`/notes/${noteId}`, {
            preserveScroll: true,
        });
    }
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
    <Head :title="book.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Reading View (Full Screen) -->
        <div v-if="showReader" class="fixed inset-0 z-50 bg-background">
            <div class="h-full flex flex-col">
                <!-- Reader Header -->
                <div class="flex items-center justify-between border-b border-border px-6 py-4">
                    <div class="flex-1">
                        <h2 class="text-lg font-semibold text-foreground">{{ book.title }}</h2>
                        <p v-if="book.author" class="text-sm text-muted-foreground">{{ book.author.name }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <button
                            @click="openNoteForm"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm hover:bg-primary/90"
                        >
                            <Plus class="h-4 w-4" />
                            Add Note
                        </button>
                        <button
                            @click="closeReader"
                            class="p-2 hover:bg-accent rounded-lg"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Reader Component -->
                <div class="flex-1 p-6">
                    <PdfReader
                        v-if="book.file_type === 'pdf'"
                        :book-id="book.id"
                        @page-change="handlePageChange"
                    />
                    <EpubReader
                        v-else-if="book.file_type === 'epub'"
                        :book-id="book.id"
                        @location-change="handlePageChange"
                    />
                    <div v-else class="flex items-center justify-center h-full">
                        <p class="text-muted-foreground">Unsupported file format: {{ book.file_type }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Book Details View -->
        <div v-else class="flex h-full flex-1 flex-col gap-6 p-6 max-w-6xl mx-auto">
            <!-- Header Actions -->
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold text-foreground">{{ book.title }}</h1>
                <div class="flex gap-2">
                    <Link
                        :href="`/books/${book.id}/edit`"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-border rounded-lg text-sm hover:bg-accent"
                    >
                        <Edit class="h-4 w-4" />
                        Edit
                    </Link>
                    <button
                        @click="openReader"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm hover:bg-primary/90"
                    >
                        <BookOpen class="h-4 w-4" />
                        Read Book
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Book Info -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Cover -->
                    <div class="rounded-lg border border-border bg-card overflow-hidden">
                        <div class="aspect-[2/3] bg-muted">
                            <img
                                v-if="book.cover_image"
                                :src="`/storage/covers/${book.cover_image}`"
                                :alt="book.title"
                                class="h-full w-full object-cover"
                            />
                            <div v-else class="flex h-full items-center justify-center">
                                <BookOpen class="h-24 w-24 text-muted-foreground" />
                            </div>
                        </div>

                        <!-- Reading Progress -->
                        <div class="p-4 space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Progress</span>
                                <span class="font-medium">{{ book.reading_progress }}%</span>
                            </div>
                            <div class="h-2 bg-muted rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-primary transition-all"
                                    :style="{ width: `${book.reading_progress}%` }"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Book Metadata -->
                    <div class="rounded-lg border border-border bg-card p-4 space-y-3">
                        <div v-if="book.author">
                            <div class="text-xs text-muted-foreground mb-1">Author</div>
                            <div class="font-medium">{{ book.author.name }}</div>
                        </div>

                        <div v-if="book.category">
                            <div class="text-xs text-muted-foreground mb-1">Category</div>
                            <span
                                class="inline-block px-2 py-1 rounded text-sm"
                                :style="{ backgroundColor: book.category.color + '20', color: book.category.color }"
                            >
                                {{ book.category.name }}
                            </span>
                        </div>

                        <div>
                            <div class="text-xs text-muted-foreground mb-1">Status</div>
                            <span :class="getStatusColor(book.status)" class="inline-block px-2 py-1 rounded text-sm">
                                {{ book.status }}
                            </span>
                        </div>

                        <div v-if="book.rating">
                            <div class="text-xs text-muted-foreground mb-1">Rating</div>
                            <div class="flex gap-1">
                                <span v-for="i in 5" :key="i" class="text-yellow-500">
                                    {{ i <= book.rating ? '★' : '☆' }}
                                </span>
                            </div>
                        </div>

                        <div v-if="book.isbn">
                            <div class="text-xs text-muted-foreground mb-1">ISBN</div>
                            <div class="text-sm">{{ book.isbn }}</div>
                        </div>

                        <div v-if="book.publisher">
                            <div class="text-xs text-muted-foreground mb-1">Publisher</div>
                            <div class="text-sm">{{ book.publisher }}</div>
                        </div>

                        <div v-if="book.publish_date">
                            <div class="text-xs text-muted-foreground mb-1">Published</div>
                            <div class="text-sm">{{ new Date(book.publish_date).toLocaleDateString() }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-muted-foreground mb-1">File Type</div>
                            <div class="text-sm uppercase">{{ book.file_type }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-muted-foreground mb-1">File Size</div>
                            <div class="text-sm">{{ book.formatted_file_size }}</div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Description & Notes -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Description -->
                    <div v-if="book.description" class="rounded-lg border border-border bg-card p-6">
                        <h2 class="text-xl font-semibold mb-4">Description</h2>
                        <p class="text-muted-foreground whitespace-pre-wrap">{{ book.description }}</p>
                    </div>

                    <!-- Notes Section -->
                    <div class="rounded-lg border border-border bg-card p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold flex items-center gap-2">
                                <StickyNote class="h-5 w-5" />
                                My Notes ({{ book.notes.length }})
                            </h2>
                            <button
                                @click="openNoteForm"
                                class="inline-flex items-center gap-2 px-3 py-1.5 bg-primary text-primary-foreground rounded-md text-sm hover:bg-primary/90"
                            >
                                <Plus class="h-4 w-4" />
                                Add Note
                            </button>
                        </div>

                        <!-- Notes List -->
                        <div v-if="book.notes.length > 0" class="space-y-4">
                            <div
                                v-for="note in book.notes"
                                :key="note.id"
                                class="border border-border rounded-lg p-4 hover:bg-accent/50 transition-colors"
                            >
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <span v-if="note.page_number" class="text-xs text-muted-foreground">
                                            Page {{ note.page_number }}
                                        </span>
                                        <p class="text-sm mt-1 whitespace-pre-wrap">{{ note.content }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button
                                            @click="editNote(note)"
                                            class="text-xs text-primary hover:text-primary/80"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="deleteNote(note.id)"
                                            class="text-xs text-destructive hover:text-destructive/80"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>

                                <!-- Tags -->
                                <div v-if="note.tags.length > 0" class="flex flex-wrap gap-1 mt-2">
                                    <span
                                        v-for="tag in note.tags"
                                        :key="tag.id"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary rounded text-xs"
                                    >
                                        <Tag class="h-3 w-3" />
                                        {{ tag.name }}
                                    </span>
                                </div>

                                <div class="text-xs text-muted-foreground mt-2">
                                    {{ new Date(note.created_at).toLocaleDateString() }}
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-muted-foreground">
                            No notes yet. Start reading and add your first note!
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Note Form Modal -->
        <div
            v-if="showNoteForm"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="showNoteForm = false"
        >
            <div class="bg-card rounded-lg border border-border p-6 w-full max-w-2xl mx-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">{{ editingNote ? 'Edit Note' : 'Add Note' }}</h3>
                    <button @click="showNoteForm = false" class="p-1 hover:bg-accent rounded">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="saveNote" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Page / Location</label>
                        <input
                            v-model="noteForm.page_number"
                            type="text"
                            class="w-full rounded-md border border-input bg-background px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            placeholder="e.g., 42 or Chapter 3"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Note</label>
                        <textarea
                            v-model="noteForm.content"
                            rows="6"
                            required
                            class="w-full rounded-md border border-input bg-background px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            placeholder="Write your thoughts, insights, or highlights..."
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Tags</label>
                        <div class="flex gap-2 mb-2">
                            <input
                                v-model="newTag"
                                type="text"
                                class="flex-1 rounded-md border border-input bg-background px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                placeholder="Add a tag"
                                @keyup.enter="addTag"
                            />
                            <button
                                type="button"
                                @click="addTag"
                                class="px-4 py-2 bg-secondary text-secondary-foreground rounded-md text-sm hover:bg-secondary/90"
                            >
                                Add
                            </button>
                        </div>
                        <div v-if="noteForm.tags.length > 0" class="flex flex-wrap gap-2">
                            <span
                                v-for="(tag, index) in noteForm.tags"
                                :key="index"
                                class="inline-flex items-center gap-1 px-2 py-1 bg-primary/10 text-primary rounded text-sm"
                            >
                                {{ tag }}
                                <button type="button" @click="removeTag(index)">
                                    <X class="h-3 w-3" />
                                </button>
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button
                            type="submit"
                            :disabled="noteForm.processing"
                            class="flex-1 bg-primary text-primary-foreground px-4 py-2 rounded-md hover:bg-primary/90 disabled:opacity-50"
                        >
                            {{ noteForm.processing ? 'Saving...' : 'Save Note' }}
                        </button>
                        <button
                            type="button"
                            @click="showNoteForm = false"
                            class="px-4 py-2 border border-border rounded-md hover:bg-accent"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
