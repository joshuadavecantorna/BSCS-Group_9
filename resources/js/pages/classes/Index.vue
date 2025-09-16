<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';

interface Classroom {
  id: number;
  name: string;
  section?: string | null;
  description?: string | null;
  teacher_id: number;
  teacher?: { id: number; name: string; email: string };
  students_count?: number;
  last_session_at?: string | null;
}

interface Props {
  classrooms: {
    data: Classroom[];
    links: any[];
  };
}

defineProps<Props>();
</script>

<template>
  <Head title="Classes" />
  <AppLayout>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Class Management</h1>
      <Link href="/classes/create">
        <Button>Create Class</Button>
      </Link>
    </div>

    <Separator class="mb-4" />

    <Card class="p-4 md:p-6 shadow-sm">
      <div class="overflow-x-auto rounded-lg border bg-card">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left border-b bg-muted/40 sticky top-0 backdrop-blur supports-[backdrop-filter]:bg-muted/60">
              <th class="py-3 pr-4 pl-4 font-medium text-xs uppercase tracking-wide text-muted-foreground">Class Name</th>
              <th class="py-3 pr-4 font-medium text-xs uppercase tracking-wide text-muted-foreground">Section</th>
              <th class="py-3 pr-4 font-medium text-xs uppercase tracking-wide text-muted-foreground"># Students</th>
              <th class="py-3 pr-4 font-medium text-xs uppercase tracking-wide text-muted-foreground">Last Session</th>
              <th class="py-3 pr-4 font-medium text-xs uppercase tracking-wide text-muted-foreground">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in classrooms.data" :key="c.id" class="border-b odd:bg-background even:bg-muted/20 hover:bg-muted/40 transition-colors">
              <td class="py-4 pr-4 pl-4 font-medium">
                <Link :href="`/classes/${c.id}`" class="hover:underline text-foreground">{{ c.name }}</Link>
              </td>
              <td class="py-4 pr-4">{{ c.section || '-' }}</td>
              <td class="py-4 pr-4">{{ c.students_count ?? 0 }}</td>
              <td class="py-4 pr-4">{{ c.last_session_at ? new Date(c.last_session_at).toLocaleString() : '-' }}</td>
              <td class="py-4 pr-4">
                <div class="flex gap-2">
                  <Link :href="`/classes/${c.id}`">
                    <Button size="sm" variant="outline">View</Button>
                  </Link>
                  <Link :href="`/classes/${c.id}/edit`">
                    <Button size="sm" variant="secondary">Edit</Button>
                  </Link>
                  <Link :href="`/classes/${c.id}`" method="delete" as="button">
                    <Button size="sm" variant="destructive">Delete</Button>
                  </Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </Card>
  </AppLayout>
  </template>


