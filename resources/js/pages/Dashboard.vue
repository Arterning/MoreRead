<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Library, BookCheck, FileText } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

defineProps<{
    stats: {
        totalBooks: number;
        readBooks: number;
        totalNotes: number;
    };
}>();
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Stats Cards -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <!-- Total Books Card -->
                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border p-6">
                    <div class="flex items-start justify-between">
                        <div class="space-y-2">
                            <p class="text-sm font-medium text-muted-foreground">书籍总数</p>
                            <p class="text-3xl font-bold">{{ stats.totalBooks }}</p>
                        </div>
                        <div class="rounded-lg bg-primary/10 p-3">
                            <Library class="h-6 w-6 text-primary" />
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-muted-foreground">
                        您的图书馆藏书
                    </p>
                </div>

                <!-- Read Books Card -->
                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border p-6">
                    <div class="flex items-start justify-between">
                        <div class="space-y-2">
                            <p class="text-sm font-medium text-muted-foreground">已读完</p>
                            <p class="text-3xl font-bold">{{ stats.readBooks }}</p>
                        </div>
                        <div class="rounded-lg bg-green-500/10 p-3">
                            <BookCheck class="h-6 w-6 text-green-600 dark:text-green-500" />
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-muted-foreground">
                        完成阅读的书籍
                    </p>
                </div>

                <!-- Total Notes Card -->
                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border p-6">
                    <div class="flex items-start justify-between">
                        <div class="space-y-2">
                            <p class="text-sm font-medium text-muted-foreground">笔记总数</p>
                            <p class="text-3xl font-bold">{{ stats.totalNotes }}</p>
                        </div>
                        <div class="rounded-lg bg-blue-500/10 p-3">
                            <FileText class="h-6 w-6 text-blue-600 dark:text-blue-500" />
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-muted-foreground">
                        您创建的笔记
                    </p>
                </div>
            </div>

            <!-- Recent Activity or Additional Content -->
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border md:min-h-min p-6">
                <h2 class="text-lg font-semibold mb-4">最近活动</h2>
                <div class="flex items-center justify-center h-64 text-muted-foreground">
                    <p>暂无最近活动</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
