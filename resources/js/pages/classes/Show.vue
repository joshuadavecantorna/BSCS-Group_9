<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Student { id: number; student_identifier: string; name: string; status: 'Active' | 'Dropped' }
interface Classroom { id: number; name: string; section?: string | null; description?: string | null; students: Student[] }

const props = defineProps<{ classroom: Classroom }>();

const form = useForm({ student_identifier: '', name: '', status: 'Active' });
function addStudent() { form.post(`/classes/${props.classroom.id}/students`); }
</script>

<template>
  <Head :title="props.classroom.name" />
  <AppLayout>
    <div class="mb-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold">{{ props.classroom.name }}</h1>
          <p class="text-muted-foreground">Section: {{ props.classroom.section || '-' }}</p>
        </div>
        <div class="flex gap-2">
          <Link :href="`/classes/${props.classroom.id}/edit`">
            <Button variant="secondary">Edit</Button>
          </Link>
          <Link href="/classes">
            <Button variant="outline">Back</Button>
          </Link>
        </div>
      </div>
    </div>

    <div class="flex gap-6">
      <div class="w-full">
        <div class="flex gap-6 border-b mb-4">
          <button class="py-2 px-1 font-medium border-b-2 border-primary">Students</button>
          <button class="py-2 px-1 text-muted-foreground">Attendance Records</button>
          <button class="py-2 px-1 text-muted-foreground">Files</button>
        </div>

        <div class="grid md:grid-cols-[2fr_1fr] gap-6">
          <div>
            <div class="overflow-x-auto rounded-lg border bg-card shadow-sm">
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-left border-b bg-muted/40 sticky top-0 backdrop-blur supports-[backdrop-filter]:bg-muted/60">
                    <th class="py-3 pr-4 pl-4 font-medium text-xs uppercase tracking-wide text-muted-foreground">Student ID</th>
                    <th class="py-3 pr-4 font-medium text-xs uppercase tracking-wide text-muted-foreground">Name</th>
                    <th class="py-3 pr-4 font-medium text-xs uppercase tracking-wide text-muted-foreground">Status</th>
                    <th class="py-3 pr-4 font-medium text-xs uppercase tracking-wide text-muted-foreground">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="s in props.classroom.students" :key="s.id" class="border-b odd:bg-background even:bg-muted/20 hover:bg-muted/40 transition-colors">
                    <td class="py-4 pr-4 pl-4">{{ s.student_identifier }}</td>
                    <td class="py-4 pr-4">{{ s.name }}</td>
                    <td class="py-4 pr-4">{{ s.status }}</td>
                    <td class="py-4 pr-4">
                      <div class="flex gap-2">
                        <Link :href="`/classes/${props.classroom.id}/students/${s.id}`" method="delete" as="button">
                          <Button size="sm" variant="destructive">Delete</Button>
                        </Link>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="rounded-lg border p-4 md:p-5 bg-card shadow-sm">
            <h3 class="font-medium mb-4">Add student</h3>
            <form class="space-y-4" @submit.prevent="addStudent">
              <div>
                <Label for="sid">Student ID</Label>
                <Input id="sid" v-model="form.student_identifier" />
                <div v-if="form.errors.student_identifier" class="text-sm text-red-500">{{ form.errors.student_identifier }}</div>
              </div>
              <div>
                <Label for="sname">Name</Label>
                <Input id="sname" v-model="form.name" />
                <div v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</div>
              </div>
              <div>
                <Label for="status">Status</Label>
                <select id="status" v-model="form.status" class="border rounded-md w-full h-9 px-2 bg-background">
                  <option value="Active">Active</option>
                  <option value="Dropped">Dropped</option>
                </select>
                <div v-if="form.errors.status" class="text-sm text-red-500">{{ form.errors.status }}</div>
              </div>
              <Button type="submit" :disabled="form.processing">Add</Button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>



