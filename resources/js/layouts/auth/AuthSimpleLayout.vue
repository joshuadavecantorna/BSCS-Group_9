<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
  title?: string;
  description?: string;
}>();
</script>

<template>
  <div class="flex min-h-svh flex-col items-center justify-center bg-background p-6 md:p-10">
    <div class="w-full max-w-md">
      <div class="flex flex-col gap-8 rounded-2xl border bg-card p-6 shadow-sm">
        <!-- Header: Logo + Title/Description -->
        <div class="flex flex-col items-center gap-4">
          <Link
            :href="home()"
            class="flex items-center gap-4 transition-transform hover:scale-105"
          >
            <!-- Logo -->
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-muted/30">
              <AppLogoIcon class="size-10 fill-current text-[var(--foreground)] dark:text-white" />
            </div>
            <!-- Title + Description -->
            <div class="flex flex-col justify-center">
              <!-- Gradient Blue Title -->
              <h1
                class="text-2xl font-semibold tracking-tight bg-gradient-to-r from-blue-500 via-blue-600 to-indigo-500 bg-clip-text text-transparent"
              >
                {{ title }}
              </h1>
              <p class="text-sm text-muted-foreground">{{ description }}</p>
            </div>
          </Link>
        </div>

        <!-- Slot content -->
        <slot />
      </div>
    </div>
  </div>
</template>
