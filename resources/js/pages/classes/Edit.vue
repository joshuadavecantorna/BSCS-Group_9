<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Classroom {
  id: number;
  name: string;
  section?: string | null;
  description?: string | null;
  teacher_id: number;
}

const props = defineProps<{ classroom: Classroom }>();

const form = useForm({
  name: props.classroom.name,
  section: props.classroom.section ?? '',
  description: props.classroom.description ?? '',
  teacher_id: String(props.classroom.teacher_id),
});

function submit() {
  form.put(`/classes/${props.classroom.id}`);
}
</script>

<template>
  <Head title="Edit Class" />
  <AppLayout>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Edit Class</h1>
      <Link href="/classes">
        <Button variant="secondary">Back</Button>
      </Link>
    </div>

    <div class="max-w-xl">
      <div class="rounded-lg border bg-card p-4 md:p-6 shadow-sm">
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <Label for="name">Name</Label>
            <Input id="name" v-model="form.name" />
            <div v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</div>
          </div>
          <div>
            <Label for="section">Section</Label>
            <Input id="section" v-model="form.section" />
            <div v-if="form.errors.section" class="text-sm text-red-500">{{ form.errors.section }}</div>
          </div>
          <div>
            <Label for="description">Description</Label>
            <textarea id="description" v-model="form.description" class="border rounded-md w-full min-h-24 p-2 bg-background"></textarea>
            <div v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</div>
          </div>
          <div>
            <Label for="teacher_id">Teacher ID</Label>
            <Input id="teacher_id" v-model="form.teacher_id" type="number" />
            <div v-if="form.errors.teacher_id" class="text-sm text-red-500">{{ form.errors.teacher_id }}</div>
          </div>
          <div class="pt-2">
            <Button type="submit" :disabled="form.processing">Update</Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
  </template>


