<script setup lang="ts">
import { ref, computed } from 'vue';
import { Maximize2, Download, Palette } from 'lucide-vue-next';

const props = defineProps<{
    bookId: number;
}>();

const containerRef = ref<HTMLDivElement>();
const loading = ref(true);
const error = ref(false);
const showThemeMenu = ref(false);

// Reading themes
type Theme = 'default' | 'dark' | 'green' | 'sepia';

const readingTheme = ref<Theme>((localStorage.getItem('readingTheme') as Theme) || 'default');

const themes = {
    default: {
        name: '默认',
        filter: 'none',
        background: '#f5f5f5',
    },
    dark: {
        name: '暗色',
        filter: 'invert(1) hue-rotate(180deg)',
        background: '#1a1a1a',
    },
    green: {
        name: '护眼绿',
        filter: 'sepia(0.3) hue-rotate(60deg) saturate(1.2)',
        background: '#c7edcc',
    },
    sepia: {
        name: '复古',
        filter: 'sepia(0.5)',
        background: '#f4ecd8',
    },
};

const setTheme = (theme: Theme) => {
    readingTheme.value = theme;
    localStorage.setItem('readingTheme', theme);
    showThemeMenu.value = false;
};

const iframeStyle = computed(() => {
    const currentTheme = themes[readingTheme.value];
    return {
        filter: currentTheme.filter,
    };
});

const containerStyle = computed(() => {
    return {
        backgroundColor: themes[readingTheme.value].background,
    };
});

const pdfUrl = computed(() => `/books/${props.bookId}/serve`);

const handleLoad = () => {
    loading.value = false;
    error.value = false;
};

const handleError = () => {
    loading.value = false;
    error.value = true;
};

const toggleFullscreen = () => {
    if (!containerRef.value) return;

    if (!document.fullscreenElement) {
        containerRef.value.requestFullscreen();
    } else {
        document.exitFullscreen();
    }
};

const downloadPdf = () => {
    const link = document.createElement('a');
    link.href = pdfUrl.value;
    link.download = `book-${props.bookId}.pdf`;
    link.click();
};
</script>

<template>
    <div ref="containerRef" class="flex flex-col h-full bg-muted/30 rounded-lg">
        <!-- Toolbar -->
        <div class="flex items-center justify-between bg-card border-b border-border px-4 py-3">
            <div class="text-sm text-muted-foreground">
                使用浏览器内置的 PDF 阅读器
            </div>

            <div class="flex items-center gap-2">
                <!-- Theme Selector -->
                <div class="relative">
                    <button
                        @click="showThemeMenu = !showThemeMenu"
                        class="p-2 rounded-md hover:bg-accent transition-colors"
                        title="阅读主题"
                    >
                        <Palette class="h-5 w-5" />
                    </button>

                    <!-- Theme Menu -->
                    <div
                        v-if="showThemeMenu"
                        class="absolute right-0 top-full mt-2 bg-card border border-border rounded-lg shadow-lg py-1 min-w-[120px] z-50"
                    >
                        <button
                            v-for="(theme, key) in themes"
                            :key="key"
                            @click="setTheme(key as Theme)"
                            class="w-full px-4 py-2 text-left text-sm hover:bg-accent transition-colors flex items-center justify-between"
                            :class="{ 'bg-accent': readingTheme === key }"
                        >
                            <span>{{ theme.name }}</span>
                            <span
                                class="w-4 h-4 rounded-full border border-border"
                                :style="{ backgroundColor: theme.background }"
                            />
                        </button>
                    </div>
                </div>

                <div class="h-4 w-px bg-border mx-2" />

                <button
                    @click="downloadPdf"
                    class="p-2 rounded-md hover:bg-accent transition-colors"
                    title="下载 PDF"
                >
                    <Download class="h-5 w-5" />
                </button>

                <div class="h-4 w-px bg-border mx-2" />

                <button
                    @click="toggleFullscreen"
                    class="p-2 rounded-md hover:bg-accent transition-colors"
                    title="全屏 (F11)"
                >
                    <Maximize2 class="h-5 w-5" />
                </button>
            </div>
        </div>

        <!-- PDF Viewer -->
        <div class="flex-1 relative" :style="containerStyle">
            <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-background">
                <div class="text-center">
                    <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-primary border-r-transparent mb-4" />
                    <p class="text-sm text-muted-foreground">加载 PDF 中...</p>
                </div>
            </div>

            <div v-if="error" class="absolute inset-0 flex items-center justify-center bg-background">
                <div class="text-center">
                    <p class="text-destructive mb-4">PDF 加载失败</p>
                    <p class="text-sm text-muted-foreground mb-4">
                        您的浏览器可能不支持内置 PDF 查看器
                    </p>
                    <button
                        @click="downloadPdf"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-md text-sm hover:bg-primary/90"
                    >
                        下载 PDF
                    </button>
                </div>
            </div>

            <iframe
                :src="pdfUrl"
                @load="handleLoad"
                @error="handleError"
                :style="iframeStyle"
                class="w-full h-full border-0"
                title="PDF Viewer"
            />
        </div>

        <!-- Info -->
        <div class="bg-card border-t border-border px-4 py-2">
            <p class="text-xs text-muted-foreground text-center">
                使用浏览器的 PDF 工具进行缩放、搜索和导航
            </p>
        </div>
    </div>
</template>
