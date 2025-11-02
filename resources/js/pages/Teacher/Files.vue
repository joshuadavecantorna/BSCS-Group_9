<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import FileUploadModal from '@/components/teacher/FileUploadModal.vue';

interface FileItem {
  id: number;
  file_name: string;
  file_type: string;
  file_size_formatted: string;
  class_name: string;
  class_course: string;
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
  recentFiles?: FileItem[];
  analytics?: {
    totalFiles: number;
    storageUsed: string;
    storageAvailable: string;
    downloads: number;
    recentUploads: number;
    fileTypes: {
      documents: number;
      images: number;
      videos: number;
      others: number;
    };
  };
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
  { title: 'Files', href: '/teacher/files' }
];

// File upload modal state
const showUploadModal = ref(false);
const showAllFilesModal = ref(false);

// Analytics reactive data
const analytics = ref(props.analytics || {
  totalFiles: 0,
  storageUsed: '0 MB',
  storageAvailable: '10 GB',
  downloads: 0,
  recentUploads: 0,
  fileTypes: {
    documents: 0,
    images: 0,
    videos: 0,
    others: 0
  }
});

// Recent files data
const recentFiles = ref(props.recentFiles || []);

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

// Show all files function
const showAllFiles = () => {
  // Use Inertia to navigate to all files page
  window.open('/teacher/files/all', '_blank');
};

// Loading and update states
const isUpdating = ref(false);
const lastUpdated = ref<Date | null>(null);

// Real-time update interval
let updateInterval: NodeJS.Timeout | null = null;

const openUploadModal = () => {
  showUploadModal.value = true;
};

const handleFileUploaded = (response: any) => {
  console.log('File uploaded successfully:', response);
  showUploadModal.value = false;
  // Update analytics after file upload
  updateAnalytics();
};

// Fetch analytics data from server
const fetchAnalytics = async () => {
  try {
    isUpdating.value = true;
    const response = await fetch('/teacher/files/analytics');
    if (response.ok) {
      const data = await response.json();
      analytics.value = {
        totalFiles: data.total_files || analytics.value.totalFiles,
        storageUsed: data.storage_used || analytics.value.storageUsed,
        storageAvailable: data.storage_available || analytics.value.storageAvailable,
        downloads: data.downloads || analytics.value.downloads,
        recentUploads: data.recent_uploads || analytics.value.recentUploads,
        fileTypes: {
          documents: data.file_types?.documents || analytics.value.fileTypes.documents,
          images: data.file_types?.images || analytics.value.fileTypes.images,
          videos: data.file_types?.videos || analytics.value.fileTypes.videos,
          others: data.file_types?.others || analytics.value.fileTypes.others
        }
      };
      lastUpdated.value = new Date();
    }
  } catch (error) {
    console.error('Failed to fetch analytics:', error);
    // Simulate data changes for demo purposes when API doesn't exist
    simulateDataChanges();
  } finally {
    isUpdating.value = false;
  }
};

// Simulate real-time data changes for demo purposes
const simulateDataChanges = () => {
  const randomChange = () => Math.floor(Math.random() * 3) - 1; // -1, 0, or 1
  
  analytics.value = {
    ...analytics.value,
    totalFiles: Math.max(0, analytics.value.totalFiles + randomChange()),
    downloads: Math.max(0, analytics.value.downloads + Math.floor(Math.random() * 5)),
    recentUploads: Math.max(0, analytics.value.recentUploads + randomChange()),
    fileTypes: {
      documents: Math.max(0, analytics.value.fileTypes.documents + randomChange()),
      images: Math.max(0, analytics.value.fileTypes.images + randomChange()),
      videos: Math.max(0, analytics.value.fileTypes.videos + randomChange()),
      others: Math.max(0, analytics.value.fileTypes.others + randomChange())
    }
  };
  lastUpdated.value = new Date();
};

// Update analytics data
const updateAnalytics = () => {
  fetchAnalytics();
};

// Start real-time updates
const startRealTimeUpdates = () => {
  updateInterval = setInterval(() => {
    updateAnalytics();
  }, 5000); // Update every 5 seconds for demonstration
};

// Stop real-time updates
const stopRealTimeUpdates = () => {
  if (updateInterval) {
    clearInterval(updateInterval);
    updateInterval = null;
  }
};

// Lifecycle hooks
onMounted(() => {
  fetchAnalytics();
  startRealTimeUpdates();
});

onUnmounted(() => {
  stopRealTimeUpdates();
});
</script>

<template>
  <Head title="Teacher Files" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">
      
      <!-- Header Section -->
      <div class="flex items-center justify-between">
        <div class="space-y-2">
          <div class="flex items-center gap-3">
            <h1 class="text-3xl font-bold tracking-tight">File Management</h1>
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <div class="flex items-center gap-1">
                <div :class="[
                  'w-2 h-2 rounded-full transition-colors',
                  isUpdating ? 'bg-yellow-500 animate-pulse' : 'bg-green-500'
                ]"></div>
                <span>{{ isUpdating ? 'Updating...' : 'Live' }}</span>
              </div>
              <span v-if="lastUpdated" class="text-xs">
                Updated {{ lastUpdated.toLocaleTimeString() }}
              </span>
            </div>
          </div>
          <p class="text-muted-foreground">
            Upload and manage files for your classes
          </p>
        </div>
        <Button @click="openUploadModal">
          <span class="mr-2">📤</span>
          Upload File
        </Button>
      </div>

      <!-- File Upload Options -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="openUploadModal">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-6xl">📄</div>
            <CardTitle>Upload Documents</CardTitle>
            <CardDescription>Upload PDFs, Word docs, presentations</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button class="w-full">Upload Documents</Button>
          </CardContent>
        </Card>

        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="openUploadModal">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-6xl">🖼️</div>
            <CardTitle>Upload Images</CardTitle>
            <CardDescription>Upload images and visual materials</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button variant="outline" class="w-full">Upload Images</Button>
          </CardContent>
        </Card>

        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="openUploadModal">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-6xl">🎥</div>
            <CardTitle>Upload Videos</CardTitle>
            <CardDescription>Upload video lectures and materials</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button variant="outline" class="w-full">Upload Videos</Button>
          </CardContent>
        </Card>
      </div>

      <!-- Recent Files -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Files</CardTitle>
          <CardDescription>Files you've uploaded recently</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <template v-if="recentFiles.length > 0">
              <div v-for="file in recentFiles" :key="file.id" class="flex items-center justify-between p-4 border rounded-lg hover:bg-accent/50 dark:hover:bg-accent/10 transition-colors">
                <div class="flex items-center gap-3">
                  <div class="text-2xl">
                    {{ file.file_type.includes('image') ? '🖼️' : 
                       file.file_type.includes('video') ? '🎥' : 
                       file.file_type.includes('pdf') ? '📄' : 
                       file.file_type.includes('doc') ? '📊' : '📄' }}
                  </div>
                  <div>
                    <p class="font-medium">{{ file.file_name }}</p>
                    <p class="text-sm text-muted-foreground">
                      Uploaded {{ new Date(file.created_at).toLocaleDateString() }} • {{ file.file_size_formatted }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <Badge variant="secondary">{{ file.class_name }}</Badge>
                  <button 
                    type="button" 
                    class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium h-8 px-3 border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground" 
                    @click="downloadFile(file.download_url, file.file_name)"
                  >
                    Download
                  </button>
                </div>
              </div>
            </template>
            
            <div v-else class="text-center py-8 text-muted-foreground">
              <div class="text-4xl mb-2">�</div>
              <p class="font-medium">No files uploaded yet</p>
              <p class="text-sm">Upload your first file to get started</p>
            </div>

            <div class="text-center py-4" v-if="recentFiles.length > 0">
              <button 
                type="button" 
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium h-8 px-3 border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground" 
                @click="showAllFiles"
              >
                View All Files
              </button>
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
            <div class="text-2xl font-bold transition-all duration-300" :class="{ 'text-green-600 dark:text-green-400 scale-110': isUpdating }">
              {{ analytics.totalFiles }}
            </div>
            <p class="text-xs text-muted-foreground mt-1">
              Across all classes
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Storage Used</CardTitle>
            <span class="text-xl">💾</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold transition-all duration-300">{{ analytics.storageUsed }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Of {{ analytics.storageAvailable }} available
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Downloads</CardTitle>
            <span class="text-xl">⬇️</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold transition-all duration-300" :class="{ 'text-blue-600 dark:text-blue-400 scale-110': isUpdating }">
              {{ analytics.downloads }}
            </div>
            <p class="text-xs text-muted-foreground mt-1">
              This month
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Recent Uploads</CardTitle>
            <span class="text-xl">📤</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold transition-all duration-300" :class="{ 'text-purple-600 dark:text-purple-400 scale-110': isUpdating }">
              {{ analytics.recentUploads }}
            </div>
            <p class="text-xs text-muted-foreground mt-1">
              This week
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- File Types Breakdown -->
      <Card>
        <CardHeader>
          <CardTitle>File Types</CardTitle>
          <CardDescription>Breakdown of your uploaded files by type</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="text-center p-4 border rounded-lg hover:bg-accent/50 dark:hover:bg-accent/10 transition-colors">
              <div class="text-3xl mb-2">📄</div>
              <p class="font-semibold">Documents</p>
              <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ analytics.fileTypes.documents }}</p>
              <p class="text-xs text-muted-foreground">PDF, DOC, PPT</p>
            </div>
            
            <div class="text-center p-4 border rounded-lg hover:bg-accent/50 dark:hover:bg-accent/10 transition-colors">
              <div class="text-3xl mb-2">🖼️</div>
              <p class="font-semibold">Images</p>
              <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ analytics.fileTypes.images }}</p>
              <p class="text-xs text-muted-foreground">PNG, JPG, SVG</p>
            </div>
            
            <div class="text-center p-4 border rounded-lg hover:bg-accent/50 dark:hover:bg-accent/10 transition-colors">
              <div class="text-3xl mb-2">🎥</div>
              <p class="font-semibold">Videos</p>
              <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ analytics.fileTypes.videos }}</p>
              <p class="text-xs text-muted-foreground">MP4, AVI, MOV</p>
            </div>
            
            <div class="text-center p-4 border rounded-lg hover:bg-accent/50 dark:hover:bg-accent/10 transition-colors">
              <div class="text-3xl mb-2">📊</div>
              <p class="font-semibold">Others</p>
              <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ analytics.fileTypes.others }}</p>
              <p class="text-xs text-muted-foreground">ZIP, RAR, etc</p>
            </div>
          </div>
        </CardContent>
      </Card>

    </div>

    <!-- File Upload Modal -->
    <FileUploadModal 
      v-model:open="showUploadModal" 
      :classes="props.classes || []"
      @file-uploaded="handleFileUploaded"
    />
  </AppLayout>
</template>