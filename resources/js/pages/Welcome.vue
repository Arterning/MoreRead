<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, Library, StickyNote } from 'lucide-vue-next';
import ThemeToggle from '@/components/ThemeToggle.vue';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();
</script>

<template>
    <Head title="欢迎 - 我的书库" />

    <div class="min-h-screen bg-white dark:bg-gray-950 transition-colors">
        <!-- Navigation -->
        <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center gap-2">
                        <BookOpen class="h-8 w-8 text-primary" />
                        <span class="text-xl font-bold text-gray-900 dark:text-white">我的书库</span>
                    </div>

                    <div class="flex items-center gap-4">
                        <ThemeToggle />
                        <div v-if="canLogin" class="flex items-center gap-4">
                            <Link href="/login" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                                登录
                            </Link>
                            <Link v-if="canRegister" href="/register" class="px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg transition-colors">
                                注册
                            </Link>
                            <Link v-else href="/dashboard" class="px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg transition-colors">
                                控制台
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative pt-32 pb-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto text-center">
                <h1 class="text-5xl md:text-7xl font-bold text-gray-900 dark:text-white mb-6">
                    打造专属于你的
                    <br />
                    <span class="bg-gradient-to-r from-primary to-blue-600 bg-clip-text text-transparent">数字图书馆</span>
                </h1>

                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto mb-12">
                    上传、管理、阅读你的电子书籍<br />
                    支持 PDF、EPUB 格式，在线阅读，添加笔记
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link v-if="canRegister" href="/register" class="px-8 py-4 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-lg transition-all shadow-lg">
                        开始使用
                    </Link>
                    <Link v-else href="/books" class="px-8 py-4 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-lg transition-all shadow-lg">
                        我的书库
                    </Link>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="py-20 px-4 bg-gray-50 dark:bg-gray-900">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white text-center mb-16">核心功能</h2>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="bg-white dark:bg-gray-800 backdrop-blur-sm rounded-xl p-8 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
                        <BookOpen class="h-12 w-12 text-primary mb-4" />
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">在线阅读</h3>
                        <p class="text-gray-600 dark:text-gray-400">支持 PDF 和 EPUB 格式的专业阅读体验</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 backdrop-blur-sm rounded-xl p-8 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
                        <Library class="h-12 w-12 text-blue-600 dark:text-blue-500 mb-4" />
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">智能管理</h3>
                        <p class="text-gray-600 dark:text-gray-400">分类、标签、评分，让书库井然有序</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 backdrop-blur-sm rounded-xl p-8 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
                        <StickyNote class="h-12 w-12 text-green-600 dark:text-green-500 mb-4" />
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">笔记系统</h3>
                        <p class="text-gray-600 dark:text-gray-400">记录阅读心得，支持标签分类</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="py-8 text-center border-t border-gray-200 dark:border-gray-800">
            <p class="text-gray-500 dark:text-gray-400">© 2025 我的书库 · 基于 Laravel + Vue 构建</p>
        </footer>
    </div>
</template>
