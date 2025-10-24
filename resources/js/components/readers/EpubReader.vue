<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue';
import ePub, { Book, Rendition, NavItem } from 'epubjs';
import { ChevronLeft, ChevronRight, ZoomIn, ZoomOut, List, Maximize2, X } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

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
const showToc = ref(false);
const toc = ref<NavItem[]>([]);
const isDraggingProgress = ref(false);

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

        // Load table of contents
        const navigation = await book.value.loaded.navigation;
        toc.value = navigation.toc;
        console.log('TOC loaded:', toc.value);

        // Track location changes
        rendition.value.on('relocated', (location: any) => {
            currentLocation.value = location.start.cfi;

            // Calculate reading progress
            const percentage = book.value?.locations.percentageFromCfi(location.start.cfi);
            if (percentage !== undefined) {
                progress.value = Math.round(percentage * 100);
                saveProgress(progress.value);
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
                    saveProgress(progress.value);
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

const toggleToc = () => {
    showToc.value = !showToc.value;
};

const goToChapter = (href: string) => {
    rendition.value?.display(href);
    showToc.value = false;
};

// Progress bar dragging
const handleProgressClick = (event: MouseEvent) => {
    const target = event.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();
    const percentage = ((event.clientX - rect.left) / rect.width) * 100;
    goToProgress(percentage);
};

const handleProgressDragStart = () => {
    isDraggingProgress.value = true;
};

const handleProgressDrag = (event: MouseEvent) => {
    if (!isDraggingProgress.value) return;
    const progressBar = event.currentTarget as HTMLElement;
    const rect = progressBar.getBoundingClientRect();
    const percentage = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100));
    progress.value = Math.round(percentage);
};

const handleProgressDragEnd = (event: MouseEvent) => {
    if (!isDraggingProgress.value) return;
    isDraggingProgress.value = false;
    const progressBar = event.currentTarget as HTMLElement;
    const rect = progressBar.getBoundingClientRect();
    const percentage = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100));
    goToProgress(percentage);
};

const goToProgress = (percentage: number) => {
    if (!book.value?.locations?.length()) return;

    const location = book.value.locations.cfiFromPercentage(percentage / 100);
    rendition.value?.display(location);
};

// Save reading progress to server
let saveProgressTimer: number | null = null;
const saveProgress = (progressValue: number) => {
    // Debounce: wait 2 seconds after user stops reading before saving
    if (saveProgressTimer) {
        clearTimeout(saveProgressTimer);
    }

    saveProgressTimer = window.setTimeout(() => {
        router.put(
            `/books/${props.bookId}`,
            {
                reading_progress: progressValue,
                status: progressValue > 0 && progressValue < 100 ? 'reading' : (progressValue >= 100 ? 'completed' : 'unread'),
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: [],
            }
        );
    }, 2000);
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
        case 't':
            toggleToc();
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
    <div ref="containerRef" class="flex h-full bg-muted/30 rounded-lg overflow-hidden">
        <!-- Table of Contents Sidebar -->
        <div
            v-if="showToc"
            class="w-80 bg-card border-r border-border overflow-y-auto flex-shrink-0"
        >
            <div class="p-4 border-b border-border flex items-center justify-between sticky top-0 bg-card z-10">
                <h3 class="font-semibold">目录</h3>
                <button @click="toggleToc" class="p-1 hover:bg-accent rounded">
                    <X class="h-5 w-5" />
                </button>
            </div>
            <div class="p-2">
                <button
                    v-for="(chapter, index) in toc"
                    :key="index"
                    @click="goToChapter(chapter.href)"
                    class="w-full text-left px-3 py-2 rounded-md hover:bg-accent transition-colors text-sm"
                >
                    <div class="font-medium">{{ chapter.label }}</div>
                    <div v-if="chapter.subitems && chapter.subitems.length > 0" class="ml-4 mt-1">
                        <button
                            v-for="(sub, subIndex) in chapter.subitems"
                            :key="subIndex"
                            @click.stop="goToChapter(sub.href)"
                            class="w-full text-left px-2 py-1 rounded-md hover:bg-accent/50 text-xs text-muted-foreground"
                        >
                            {{ sub.label }}
                        </button>
                    </div>
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Toolbar -->
            <div class="flex items-center justify-between bg-card border-b border-border px-4 py-3">
                <!-- Navigation -->
                <div class="flex items-center gap-2">
                    <button
                        @click="toggleToc"
                        class="p-2 rounded-md hover:bg-accent transition-colors"
                        title="目录 (T)"
                    >
                        <List class="h-5 w-5" />
                    </button>

                    <div class="h-4 w-px bg-border mx-2" />

                    <button
                        @click="previousPage"
                        class="p-2 rounded-md hover:bg-accent transition-colors"
                        title="上一页 (←)"
                    >
                        <ChevronLeft class="h-5 w-5" />
                    </button>

                    <div class="text-sm text-muted-foreground">
                        {{ progress }}% 已完成
                    </div>

                    <button
                        @click="nextPage"
                        class="p-2 rounded-md hover:bg-accent transition-colors"
                        title="下一页 (→)"
                    >
                        <ChevronRight class="h-5 w-5" />
                    </button>
                </div>

                <!-- Controls -->
                <div class="flex items-center gap-2">
                    <button
                        @click="decreaseFontSize"
                        class="p-2 rounded-md hover:bg-accent transition-colors"
                        title="减小字体 (-)"
                    >
                        <ZoomOut class="h-5 w-5" />
                    </button>

                    <span class="text-sm text-muted-foreground min-w-[4rem] text-center">
                        {{ fontSize }}%
                    </span>

                    <button
                        @click="increaseFontSize"
                        class="p-2 rounded-md hover:bg-accent transition-colors"
                        title="增大字体 (+)"
                    >
                        <ZoomIn class="h-5 w-5" />
                    </button>

                    <div class="h-4 w-px bg-border mx-2" />

                    <button
                        @click="toggleFullscreen"
                        class="p-2 rounded-md hover:bg-accent transition-colors"
                        title="全屏 (F)"
                    >
                        <Maximize2 class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Progress Bar (Draggable) -->
            <div
                class="h-2 bg-muted cursor-pointer relative group"
                @click="handleProgressClick"
                @mousedown="handleProgressDragStart"
                @mousemove="handleProgressDrag"
                @mouseup="handleProgressDragEnd"
                @mouseleave="handleProgressDragEnd"
            >
                <div
                    class="h-full bg-primary transition-all"
                    :class="{ 'transition-none': isDraggingProgress }"
                    :style="{ width: `${progress}%` }"
                />
                <!-- Progress indicator -->
                <div
                    class="absolute top-1/2 -translate-y-1/2 w-4 h-4 bg-primary rounded-full opacity-0 group-hover:opacity-100 transition-opacity shadow-lg"
                    :style="{ left: `calc(${progress}% - 8px)` }"
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
                    使用方向键翻页 • +/- 调整字体 • F 全屏 • T 切换目录
                </p>
            </div>
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
