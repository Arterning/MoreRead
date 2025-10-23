<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';
import * as pdfjsLib from 'pdfjs-dist';
import { ChevronLeft, ChevronRight, ZoomIn, ZoomOut, Maximize2 } from 'lucide-vue-next';

// Set up PDF.js worker
pdfjsLib.GlobalWorkerOptions.workerSrc = `//cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjsLib.version}/pdf.worker.min.js`;

const props = defineProps<{
    bookId: number;
}>();

const emit = defineEmits<{
    pageChange: [page: number];
}>();

const canvasRef = ref<HTMLCanvasElement>();
const containerRef = ref<HTMLDivElement>();
const pdfDoc = ref<any>(null);
const currentPage = ref(1);
const totalPages = ref(0);
const scale = ref(1.5);
const loading = ref(true);
const error = ref('');

const canGoPrevious = computed(() => currentPage.value > 1);
const canGoNext = computed(() => currentPage.value < totalPages.value);

const loadPdf = async () => {
    try {
        loading.value = true;
        error.value = '';

        const loadingTask = pdfjsLib.getDocument(`/books/${props.bookId}/serve`);
        pdfDoc.value = await loadingTask.promise;
        totalPages.value = pdfDoc.value.numPages;

        await renderPage(currentPage.value);
        loading.value = false;
    } catch (err) {
        console.error('Error loading PDF:', err);
        error.value = 'Failed to load PDF file';
        loading.value = false;
    }
};

const renderPage = async (pageNum: number) => {
    if (!pdfDoc.value || !canvasRef.value) return;

    try {
        const page = await pdfDoc.value.getPage(pageNum);
        const viewport = page.getViewport({ scale: scale.value });

        const canvas = canvasRef.value;
        const context = canvas.getContext('2d');
        if (!context) return;

        canvas.height = viewport.height;
        canvas.width = viewport.width;

        const renderContext = {
            canvasContext: context,
            viewport: viewport,
        };

        await page.render(renderContext).promise;
        emit('pageChange', pageNum);
    } catch (err) {
        console.error('Error rendering page:', err);
        error.value = 'Failed to render page';
    }
};

const goToPage = (pageNum: number) => {
    if (pageNum >= 1 && pageNum <= totalPages.value) {
        currentPage.value = pageNum;
        renderPage(pageNum);
    }
};

const previousPage = () => {
    if (canGoPrevious.value) {
        goToPage(currentPage.value - 1);
    }
};

const nextPage = () => {
    if (canGoNext.value) {
        goToPage(currentPage.value + 1);
    }
};

const zoomIn = () => {
    scale.value = Math.min(scale.value + 0.25, 3);
    renderPage(currentPage.value);
};

const zoomOut = () => {
    scale.value = Math.max(scale.value - 0.25, 0.5);
    renderPage(currentPage.value);
};

const toggleFullscreen = () => {
    if (!containerRef.value) return;

    if (!document.fullscreenElement) {
        containerRef.value.requestFullscreen();
    } else {
        document.exitFullscreen();
    }
};

// Handle keyboard navigation
const handleKeyDown = (event: KeyboardEvent) => {
    switch (event.key) {
        case 'ArrowLeft':
            previousPage();
            break;
        case 'ArrowRight':
            nextPage();
            break;
        case '+':
        case '=':
            zoomIn();
            break;
        case '-':
            zoomOut();
            break;
        case 'f':
            toggleFullscreen();
            break;
    }
};

onMounted(() => {
    loadPdf();
    window.addEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <div ref="containerRef" class="flex flex-col h-full bg-muted/30 rounded-lg">
        <!-- Toolbar -->
        <div class="flex items-center justify-between bg-card border-b border-border px-4 py-3">
            <!-- Page Navigation -->
            <div class="flex items-center gap-2">
                <button
                    @click="previousPage"
                    :disabled="!canGoPrevious"
                    class="p-2 rounded-md hover:bg-accent disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    title="Previous page (←)"
                >
                    <ChevronLeft class="h-5 w-5" />
                </button>

                <div class="flex items-center gap-2 text-sm">
                    <input
                        v-model.number="currentPage"
                        @change="goToPage(currentPage)"
                        type="number"
                        min="1"
                        :max="totalPages"
                        class="w-16 px-2 py-1 text-center rounded border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                    />
                    <span class="text-muted-foreground">/ {{ totalPages }}</span>
                </div>

                <button
                    @click="nextPage"
                    :disabled="!canGoNext"
                    class="p-2 rounded-md hover:bg-accent disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    title="Next page (→)"
                >
                    <ChevronRight class="h-5 w-5" />
                </button>
            </div>

            <!-- Zoom Controls -->
            <div class="flex items-center gap-2">
                <button
                    @click="zoomOut"
                    class="p-2 rounded-md hover:bg-accent transition-colors"
                    title="Zoom out (-)"
                >
                    <ZoomOut class="h-5 w-5" />
                </button>

                <span class="text-sm text-muted-foreground min-w-[4rem] text-center">
                    {{ Math.round(scale * 100) }}%
                </span>

                <button
                    @click="zoomIn"
                    class="p-2 rounded-md hover:bg-accent transition-colors"
                    title="Zoom in (+)"
                >
                    <ZoomIn class="h-5 w-5" />
                </button>

                <div class="h-4 w-px bg-border mx-2" />

                <button
                    @click="toggleFullscreen"
                    class="p-2 rounded-md hover:bg-accent transition-colors"
                    title="Fullscreen (F)"
                >
                    <Maximize2 class="h-5 w-5" />
                </button>
            </div>
        </div>

        <!-- PDF Canvas -->
        <div class="flex-1 overflow-auto p-4">
            <div v-if="loading" class="flex items-center justify-center h-full">
                <div class="text-center">
                    <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-primary border-r-transparent mb-4" />
                    <p class="text-sm text-muted-foreground">Loading PDF...</p>
                </div>
            </div>

            <div v-else-if="error" class="flex items-center justify-center h-full">
                <div class="text-center">
                    <p class="text-destructive mb-2">{{ error }}</p>
                    <button
                        @click="loadPdf"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-md text-sm hover:bg-primary/90"
                    >
                        Retry
                    </button>
                </div>
            </div>

            <div v-else class="flex justify-center">
                <canvas
                    ref="canvasRef"
                    class="shadow-lg bg-white dark:bg-gray-100"
                />
            </div>
        </div>

        <!-- Keyboard Shortcuts Help -->
        <div class="bg-card border-t border-border px-4 py-2">
            <p class="text-xs text-muted-foreground text-center">
                Use arrow keys to navigate • +/- to zoom • F for fullscreen
            </p>
        </div>
    </div>
</template>
