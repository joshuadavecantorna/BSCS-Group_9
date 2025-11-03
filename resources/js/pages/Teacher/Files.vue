<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import FileUploadModal from '@/components/teacher/FileUploadModal.vue';

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
  recentFiles?: Array<{
    id: number;
    file_name: string;
    file_type: string;
    file_size_formatted: string;
    class_name: string;
    class_course: string;
    created_at: string;
    download_url: string;
  }>;
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

// Destructure props for easier access
const { teacher, classes } = props;

// Reactive data for real-time updates
const currentRecentFiles = ref(props.recentFiles || []);
const currentAnalytics = ref(props.analytics || {
  totalFiles: 0,
  storageUsed: '0 B',
  storageAvailable: '10 GB',
  downloads: 0,
  recentUploads: 0,
  fileTypes: { documents: 0, images: 0, videos: 0, others: 0 }
});

const breadcrumbs = [
  { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
  { title: 'Files', href: '/teacher/files' }
];

// File upload modal state
const showUploadModal = ref(false);

const openUploadModal = () => {
  showUploadModal.value = true;
};

const handleFileUploaded = async (response: any) => {
  console.log('File uploaded successfully:', response);
  showUploadModal.value = false;
  
  // Update analytics in real-time without page reload
  await refreshAnalytics();
  await refreshRecentFiles();
};

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

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInHours = Math.abs(now.getTime() - date.getTime()) / (1000 * 60 * 60);
  
  if (diffInHours < 24) {
    return `Today at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
  } else if (diffInHours < 48) {
    return `Yesterday at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
  } else {
    return date.toLocaleDateString();
  }
};

const downloadFile = (downloadUrl: string, fileName: string) => {
  const link = document.createElement('a');
  link.href = downloadUrl;
  link.download = fileName;
  link.target = '_blank';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const viewAllFiles = () => {
  window.location.href = '/teacher/files/all';
};

// Real-time data refresh functions
const refreshAnalytics = async () => {
  try {
    const response = await fetch('/teacher/files/analytics');
    const data = await response.json();
    
    if (data.success) {
      currentAnalytics.value = {
        totalFiles: data.total_files,
        storageUsed: data.storage_used,
        storageAvailable: data.storage_available,
        downloads: data.downloads,
        recentUploads: data.recent_uploads,
        fileTypes: data.file_types
      };
    }
  } catch (error) {
    console.error('Failed to refresh analytics:', error);
  }
};

const refreshRecentFiles = async () => {
  try {
    const response = await fetch('/teacher/files/recent');
    const data = await response.json();
    
    if (data.success) {
      currentRecentFiles.value = data.recentFiles;
    }
  } catch (error) {
    console.error('Failed to refresh recent files:', error);
  }
};
</script>

<template>
  <Head title="Teacher Files" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">
      
      <!-- Header Section -->
      <div class="flex items-center justify-between">
        <div class="space-y-2">
          <h1 class="text-3xl font-bold tracking-tight">File Management</h1>
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
            <template v-if="currentRecentFiles && currentRecentFiles.length > 0">
              <div v-for="file in currentRecentFiles" :key="file.id" 
                   class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                <div class="flex items-center gap-3">
                  <div class="text-2xl">{{ getFileIcon(file.file_type) }}</div>
                  <div>
                    <p class="font-medium">{{ file.file_name }}</p>
                    <p class="text-sm text-muted-foreground">
                      {{ formatDate(file.created_at) }} • {{ file.file_size_formatted }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <Badge variant="secondary">{{ file.class_name }}</Badge>
                  <Button size="sm" variant="outline" @click="downloadFile(file.download_url, file.file_name)">
                    Download
                  </Button>
                </div>
              </div>
            </template>
            
            <div v-else class="text-center py-8">
              <div class="text-6xl mb-4">�</div>
              <p class="text-muted-foreground mb-4">No files uploaded yet</p>
              <Button @click="openUploadModal">
                Upload Your First File
              </Button>
            </div>

            <div class="text-center py-4" v-if="currentRecentFiles && currentRecentFiles.length > 0">
              <Button variant="outline" @click="viewAllFiles">
                View All Files
              </Button>
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
            <div class="text-2xl font-bold">{{ currentAnalytics?.totalFiles || 0 }}</div>
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
            <div class="text-2xl font-bold">{{ currentAnalytics?.storageUsed || '0 B' }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Of {{ currentAnalytics?.storageAvailable || '10 GB' }} available
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Downloads</CardTitle>
            <span class="text-xl">⬇️</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ currentAnalytics?.downloads || 0 }}</div>
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
            <div class="text-2xl font-bold">{{ currentAnalytics?.recentUploads || 0 }}</div>
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
            <div class="text-center p-4 border rounded-lg">
              <div class="text-3xl mb-2">📄</div>
              <p class="font-semibold">Documents</p>
              <p class="text-2xl font-bold text-blue-600">{{ currentAnalytics?.fileTypes?.documents || 0 }}</p>
              <p class="text-xs text-muted-foreground">PDF, DOC, PPT</p>
            </div>
            
            <div class="text-center p-4 border rounded-lg">
              <div class="text-3xl mb-2">🖼️</div>
              <p class="font-semibold">Images</p>
              <p class="text-2xl font-bold text-green-600">{{ currentAnalytics?.fileTypes?.images || 0 }}</p>
              <p class="text-xs text-muted-foreground">PNG, JPG, SVG</p>
            </div>
            
            <div class="text-center p-4 border rounded-lg">
              <div class="text-3xl mb-2">🎥</div>
              <p class="font-semibold">Videos</p>
              <p class="text-2xl font-bold text-purple-600">{{ currentAnalytics?.fileTypes?.videos || 0 }}</p>
              <p class="text-xs text-muted-foreground">MP4, AVI, MOV</p>
            </div>
            
            <div class="text-center p-4 border rounded-lg">
              <div class="text-3xl mb-2">📊</div>
              <p class="font-semibold">Others</p>
              <p class="text-2xl font-bold text-orange-600">{{ currentAnalytics?.fileTypes?.others || 0 }}</p>
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