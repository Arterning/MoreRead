<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import PdfReader from '@/components/readers/PdfReader.vue';
import EpubReader from '@/components/readers/EpubReader.vue';

interface Book {
    id: number;
    title: string;
    author?: {
        id: number;
        name: string;
    };
    file_type: string;
}

const props = defineProps<{
    book: Book;
}>();

const closeReader = () => {
    window.close();
};
</script>

<template>
    <Head :title="`阅读 - ${book.title}`" />

    <div class="fixed inset-0 bg-background flex flex-col">
        <!-- Reader Header -->
        <div class="flex items-center justify-between border-b border-border px-6 py-4 bg-card">
            <div class="flex-1">
                <h2 class="text-lg font-semibold text-foreground">{{ book.title }}</h2>
                <p v-if="book.author" class="text-sm text-muted-foreground">{{ book.author.name }}</p>
            </div>
            <button
                @click="closeReader"
                class="p-2 hover:bg-accent rounded-lg transition-colors"
                title="关闭 (Esc)"
            >
                <X class="h-5 w-5" />
            </button>
        </div>

        <!-- Reader Component -->
        <div class="flex-1 overflow-hidden">
            <PdfReader
                v-if="book.file_type === 'pdf'"
                :book-id="book.id"
            />
            <EpubReader
                v-else-if="book.file_type === 'epub'"
                :book-id="book.id"
            />
            <div v-else class="flex items-center justify-center h-full">
                <p class="text-muted-foreground">不支持的文件格式: {{ book.file_type }}</p>
            </div>
        </div>
    </div>
</template>
