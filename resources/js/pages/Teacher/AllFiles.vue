<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';

interface FileItem {
  id: number;
  file_name: string;
  file_type: string;
  file_size_formatted: string;
  class_name: string;
  class_course: string;
  description: string;
  created_at: string;
  download_url: string;
}

interface Props {
  teacher: {
    id: number;
    first_name: string;
    last_name: string;
    department: string;
    position: string;
    email: string;
  };
  classes?: Array<{
    id: number;
    name: string;
    course: string;
    section: string;
  }>;
  files?: FileItem[];
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
  { title: 'Files', href: '/teacher/files' },
  { title: 'All Files', href: '/teacher/files/all' }
];

const files = ref<FileItem[]>(props.files || []);
const loading = ref(false);
const search = ref('');
const selectedClass = ref('');

// Download file function
const downloadFile = (fileUrl: string, fileName: string) => {
  const link = document.createElement('a');
  link.href = fileUrl;
  link.download = fileName;
  link.target = '_blank';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

// Fetch files
const fetchFiles = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (selectedClass.value) params.append('class_id', selectedClass.value);

    const response = await fetch(`/teacher/files/list?${params}`);
    const data = await response.json();

    if (data.success) {
      files.value = data.files.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch files:', error);
  } finally {
    loading.value = false;
  }
};

// File type icon helper
const getFileIcon = (fileType: string) => {
  if (fileType.includes('image')) return '🖼️';
  if (fileType.includes('video')) return '🎥';
  if (fileType.includes('pdf')) return '📄';
  if (fileType.includes('doc')) return '📊';
  if (fileType.includes('text')) return '📝';
  return '📄';
};

onMounted(() => {
  fetchFiles();
});
</script>

<template>
  <Head title="All Files - Teacher Files" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">
      
      <!-- Header Section -->
      <div class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight">All Files</h1>
        <p class="text-muted-foreground">
          Manage all your uploaded files
        </p>
      </div>

      <!-- Filters -->
      <div class="flex gap-4 items-center">
        <Input 
          v-model="search" 
          placeholder="Search files..." 
          class="max-w-sm"
          @keyup.enter="fetchFiles"
        />
        
        <select 
          v-model="selectedClass" 
          class="px-3 py-2 border rounded-md"
          @change="fetchFiles"
        >
          <option value="">All Classes</option>
          <option v-for="classItem in props.classes" :key="classItem.id" :value="classItem.id">
            {{ classItem.name }}
          </option>
        </select>
        
        <Button @click="fetchFiles" :disabled="loading">
          {{ loading ? 'Searching...' : 'Search' }}
        </Button>
      </div>

      <!-- Files List -->
      <Card>
        <CardHeader>
          <CardTitle>Files ({{ files.length }})</CardTitle>
          <CardDescription>Your uploaded files</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <template v-if="files.length > 0">
              <div v-for="file in files" :key="file.id" class="flex items-center justify-between p-4 border rounded-lg hover:bg-accent/50 transition-colors">
                <div class="flex items-center gap-3 flex-1">
                  <div class="text-2xl">{{ getFileIcon(file.file_type) }}</div>
                  <div class="flex-1">
                    <p class="font-medium">{{ file.file_name }}</p>
                    <p class="text-sm text-muted-foreground">
                      {{ file.description || 'No description' }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                      {{ new Date(file.created_at).toLocaleString() }} • {{ file.file_size_formatted }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <div class="text-right">
                    <Badge variant="secondary">{{ file.class_name }}</Badge>
                    <p class="text-xs text-muted-foreground mt-1">{{ file.class_course }}</p>
                  </div>
                  <Button 
                    size="sm"
                    @click="downloadFile(file.download_url, file.file_name)"
                  >
                    Download
                  </Button>
                </div>
              </div>
            </template>
            
            <div v-else-if="!loading" class="text-center py-8 text-muted-foreground">
              <div class="text-4xl mb-2">📁</div>
              <p class="font-medium">No files found</p>
              <p class="text-sm">Try adjusting your search criteria</p>
            </div>
            
            <div v-if="loading" class="text-center py-8">
              <div class="text-2xl mb-2">⏳</div>
              <p>Loading files...</p>
            </div>
          </div>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>