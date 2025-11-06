<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface Props {
  student: {
    id: number;
    name: string;
    student_id: string;
    email: string;
    course: string;
    year: string;
    section: string;
  };
  files: {
    data: Array<{
      id: number;
      original_name: string;
      file_name: string;
      file_type: string;
      file_size: number;
      formatted_size: string;
      description: string;
      created_at: string;
      formatted_date: string;
      class_name: string;
      class_course: string;
      class_section: string;
      teacher_name: string;
    }>;
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Student Dashboard', href: '/student/dashboard' },
  { title: 'Files', href: '/student/files' }
];

// Helper functions
const getFileIcon = (fileType: string) => {
  if (fileType.includes('image')) return '🖼️';
  if (fileType.includes('video')) return '🎥';
  if (fileType.includes('pdf')) return '📄';
  if (fileType.includes('doc') || fileType.includes('word')) return '📊';
  if (fileType.includes('text')) return '📝';
  if (fileType.includes('spreadsheet') || fileType.includes('excel')) return '📊';
  if (fileType.includes('presentation') || fileType.includes('powerpoint')) return '📊';
  return '📄';
};

const downloadFile = (fileId: number, fileName: string) => {
  const link = document.createElement('a');
  link.href = `/student/files/download/${fileId}`;
  link.download = fileName;
  link.target = '_blank';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};
</script>

<template>
  <Head title="Student Files" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">

      <!-- Header Section -->
      <div class="flex items-center justify-between">
        <div class="space-y-2">
          <h1 class="text-3xl font-bold tracking-tight">Class Files</h1>
          <p class="text-muted-foreground">
            Download files uploaded by your teachers
          </p>
        </div>
      </div>

      <!-- Files List -->
      <Card>
        <CardHeader>
          <CardTitle>Available Files</CardTitle>
          <CardDescription>
            Files from your enrolled classes ({{ files.total }} total)
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <template v-if="files.data && files.data.length > 0">
              <div v-for="file in files.data" :key="file.id"
                   class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                <div class="flex items-center gap-3">
                  <div class="text-2xl">{{ getFileIcon(file.file_type) }}</div>
                  <div class="flex-1">
                    <p class="font-medium">{{ file.original_name }}</p>
                    <p class="text-sm text-muted-foreground">
                      {{ file.formatted_size }} • Uploaded {{ file.formatted_date }}
                    </p>
                    <p class="text-sm text-muted-foreground" v-if="file.description">
                      {{ file.description }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <div class="text-right">
                    <Badge variant="secondary" class="mb-1">{{ file.class_name }}</Badge>
                    <p class="text-xs text-muted-foreground">{{ file.teacher_name }}</p>
                  </div>
                  <Button size="sm" @click="downloadFile(file.id, file.original_name)">
                    <span class="mr-1">⬇️</span>
                    Download
                  </Button>
                </div>
              </div>
            </template>

            <div v-else class="text-center py-12">
              <div class="text-6xl mb-4">📁</div>
              <p class="text-muted-foreground mb-2">No files available</p>
              <p class="text-sm text-muted-foreground">
                Files uploaded by your teachers will appear here
              </p>
            </div>
          </div>

          <!-- Pagination -->
          <div class="mt-6" v-if="files.last_page > 1">
            <div class="flex justify-center">
              <div class="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  :disabled="files.current_page === 1"
                  @click="$inertia.visit('/student/files?page=' + (files.current_page - 1))"
                >
                  Previous
                </Button>

                <template v-for="page in Math.min(5, files.last_page)" :key="page">
                  <Button
                    variant="outline"
                    size="sm"
                    :class="{ 'bg-primary text-primary-foreground': page === files.current_page }"
                    @click="$inertia.visit('/student/files?page=' + page)"
                  >
                    {{ page }}
                  </Button>
                </template>

                <Button
                  variant="outline"
                  size="sm"
                  :disabled="files.current_page === files.last_page"
                  @click="$inertia.visit('/student/files?page=' + (files.current_page + 1))"
                >
                  Next
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- File Statistics -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Files</CardTitle>
            <span class="text-xl">📁</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ files.total }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Available for download
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Classes</CardTitle>
            <span class="text-xl">🏫</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ [...new Set(files.data.map(f => f.class_name))].length }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              With available files
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Teachers</CardTitle>
            <span class="text-xl">👨‍🏫</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ [...new Set(files.data.map(f => f.teacher_name))].length }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Who uploaded files
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Size</CardTitle>
            <span class="text-xl">💾</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ (files.data.reduce((sum, file) => sum + file.file_size, 0) / (1024 * 1024)).toFixed(1) }} MB
            </div>
            <p class="text-xs text-muted-foreground mt-1">
              Combined file size
            </p>
          </CardContent>
        </Card>
      </div>

    </div>
  </AppLayout>
</template>
