<script setup lang="ts">
import { ref, computed } from 'vue';
import { Maximize2, Download } from 'lucide-vue-next';

const props = defineProps<{
    bookId: number;
}>();

const containerRef = ref<HTMLDivElement>();
const loading = ref(true);
const error = ref(false);

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
        <div class="flex-1 relative">
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
