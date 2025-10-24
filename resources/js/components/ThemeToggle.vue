<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Moon, Sun } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const isDark = ref(false);

// 检查本地存储或系统偏好
const initTheme = () => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else if (savedTheme === 'light') {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    } else {
        // 使用系统偏好
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        isDark.value = prefersDark;
        if (prefersDark) {
            document.documentElement.classList.add('dark');
        }
    }
};

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

onMounted(() => {
    initTheme();
});
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        @click="toggleTheme"
        :title="isDark ? '切换到浅色模式' : '切换到深色模式'"
    >
        <Sun v-if="isDark" class="h-5 w-5" />
        <Moon v-else class="h-5 w-5" />
    </Button>
</template>
