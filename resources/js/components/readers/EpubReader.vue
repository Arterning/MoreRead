<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import ePub, { Book, Rendition } from 'epubjs';
import { ChevronLeft, ChevronRight, ZoomIn, ZoomOut, AlignLeft, Maximize2 } from 'lucide-vue-next';

const props = defineProps<{
    bookId: number;
}>();

const emit = defineEmits<{
    locationChange: [location: string];
}>();

const viewerRef = ref<HTMLDivElement>();
const containerRef = ref<HTMLDivElement>();
const book = ref<Book | null>(null);
const rendition = ref<Rendition | null>(null);
const loading = ref(true);
const error = ref('');
const currentLocation = ref('');
const progress = ref(0);
const fontSize = ref(100);

const loadEpub = async () => {
    try {
        loading.value = true;
        error.value = '';
        console.log('Starting EPUB load...');

        // Fetch the EPUB file as ArrayBuffer
        const response = await fetch(`/books/${props.bookId}/serve`);
        if (!response.ok) {
            throw new Error('Failed to fetch EPUB file');
        }
        const arrayBuffer = await response.arrayBuffer();
        console.log('EPUB file fetched, size:', arrayBuffer.byteLength);

        // Load the EPUB file from ArrayBuffer
        book.value = ePub(arrayBuffer);
        console.log('ePub instance created');

        // Wait for book to be ready
        await book.value.ready;
        console.log('Book ready');

        // Render the book
        if (!viewerRef.value) {
            console.error('viewerRef is null!');
            throw new Error('Viewer container not found');
        }

        console.log('Rendering to container:', viewerRef.value);
        rendition.value = book.value.renderTo(viewerRef.value, {
            width: '100%',
            height: '100%',
            spread: 'none',
        });
        console.log('Rendition created');

        await rendition.value.display();
        console.log('Rendition displayed');

        // Apply initial theme
        updateTheme();

        // Hide loading indicator now that content is visible
        loading.value = false;
        console.log('EPUB loaded successfully!');

        // Track location changes
        rendition.value.on('relocated', (location: any) => {
            currentLocation.value = location.start.cfi;

            // Calculate reading progress
            const percentage = book.value?.locations.percentageFromCfi(location.start.cfi);
            if (percentage !== undefined) {
                progress.value = Math.round(percentage * 100);
            }

            emit('locationChange', location.start.cfi);
        });

        // Generate locations for progress tracking (in background)
        console.log('Generating locations...');
        book.value.locations.generate(1024).then(() => {
            console.log('Locations generated');
            // Trigger a location update to show initial progress
            const currentCfi = rendition.value?.location?.start?.cfi;
            if (currentCfi && book.value) {
                const percentage = book.value.locations.percentageFromCfi(currentCfi);
                if (percentage !== undefined) {
                    progress.value = Math.round(percentage * 100);
                }
            }
        });
    } catch (err) {
        console.error('Error loading EPUB:', err);
        error.value = 'Failed to load EPUB file';
        loading.value = false;
    }
};

const nextPage = () => {
    rendition.value?.next();
};

const previousPage = () => {
    rendition.value?.prev();
};

const increaseFontSize = () => {
    fontSize.value = Math.min(fontSize.value + 10, 200);
    updateTheme();
};

const decreaseFontSize = () => {
    fontSize.value = Math.max(fontSize.value - 10, 50);
    updateTheme();
};

const updateTheme = () => {
    rendition.value?.themes.fontSize(`${fontSize.value}%`);

    // Apply dark mode if active
    const isDark = document.documentElement.classList.contains('dark');
    if (isDark) {
        rendition.value?.themes.override('color', '#e5e5e5');
        rendition.value?.themes.override('background', '#1a1a1a');
    } else {
        rendition.value?.themes.override('color', '#000');
        rendition.value?.themes.override('background', '#fff');
    }
};

const toggleFullscreen = () => {
    if (!containerRef.value) return;

    if (!document.fullscreenElement) {
        containerRef.value.requestFullscreen();
    } else {
        document.exitFullscreen();
    }
};

// Keyboard navigation
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
            increaseFontSize();
            break;
        case '-':
            decreaseFontSize();
            break;
        case 'f':
            toggleFullscreen();
            break;
    }
};

onMounted(() => {
    loadEpub();
    window.addEventListener('keydown', handleKeyDown);

    // Watch for dark mode changes
    const observer = new MutationObserver(() => {
        updateTheme();
    });
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    rendition.value?.destroy();
});
</script>

<template>
    <div ref="containerRef" class="flex flex-col h-full bg-muted/30 rounded-lg">
        <!-- Toolbar -->
        <div class="flex items-center justify-between bg-card border-b border-border px-4 py-3">
            <!-- Navigation -->
            <div class="flex items-center gap-2">
                <button
                    @click="previousPage"
                    class="p-2 rounded-md hover:bg-accent transition-colors"
                    title="Previous page (←)"
                >
                    <ChevronLeft class="h-5 w-5" />
                </button>

                <div class="text-sm text-muted-foreground">
                    {{ progress }}% complete
                </div>

                <button
                    @click="nextPage"
                    class="p-2 rounded-md hover:bg-accent transition-colors"
                    title="Next page (→)"
                >
                    <ChevronRight class="h-5 w-5" />
                </button>
            </div>

            <!-- Controls -->
            <div class="flex items-center gap-2">
                <button
                    @click="decreaseFontSize"
                    class="p-2 rounded-md hover:bg-accent transition-colors"
                    title="Decrease font size (-)"
                >
                    <ZoomOut class="h-5 w-5" />
                </button>

                <span class="text-sm text-muted-foreground min-w-[4rem] text-center">
                    {{ fontSize }}%
                </span>

                <button
                    @click="increaseFontSize"
                    class="p-2 rounded-md hover:bg-accent transition-colors"
                    title="Increase font size (+)"
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

        <!-- Progress Bar -->
        <div class="h-1 bg-muted">
            <div
                class="h-full bg-primary transition-all duration-300"
                :style="{ width: `${progress}%` }"
            />
        </div>

        <!-- EPUB Viewer -->
        <div class="flex-1 overflow-hidden relative">
            <div v-show="loading" class="absolute inset-0 flex items-center justify-center bg-background">
                <div class="text-center">
                    <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-primary border-r-transparent mb-4" />
                    <p class="text-sm text-muted-foreground">Loading EPUB...</p>
                </div>
            </div>

            <div v-show="error" class="absolute inset-0 flex items-center justify-center bg-background">
                <div class="text-center">
                    <p class="text-destructive mb-2">{{ error }}</p>
                    <button
                        @click="loadEpub"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-md text-sm hover:bg-primary/90"
                    >
                        Retry
                    </button>
                </div>
            </div>

            <div
                ref="viewerRef"
                class="h-full w-full"
                :class="{ 'invisible': loading || error }"
            />
        </div>

        <!-- Keyboard Shortcuts Help -->
        <div class="bg-card border-t border-border px-4 py-2">
            <p class="text-xs text-muted-foreground text-center">
                Use arrow keys to navigate • +/- to change font size • F for fullscreen
            </p>
        </div>
    </div>
</template>

<style scoped>
/* Override EPUB.js default styles */
:deep(.epub-container) {
    background: transparent !important;
}

:deep(.epub-view) {
    background: transparent !important;
}
</style>
