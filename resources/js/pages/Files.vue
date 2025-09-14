<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Upload, FileText, Download, Trash2, Folder, Plus, Search, Share2, Eye, Users } from 'lucide-vue-next';

// File interface
interface FileItem {
  id: string;
  name: string;
  size: number;
  type: string;
  uploaded_at: string;
  uploaded_by: string;
  category: string;
  path?: string;
  shared_with?: string[];
  is_shared?: boolean;
  download_count?: number;
}

// Props from Inertia
const props = defineProps<{
  files: FileItem[];
}>();

// Reactive data
const files = ref<FileItem[]>(props.files || []);
const searchQuery = ref('');
const selectedCategory = ref('all');
const isUploadDialogOpen = ref(false);
const uploadFile = ref<File | null>(null);
const uploadCategory = ref('attendance-reports');
const isLoading = ref(false);
const isDragOver = ref(false);
const shareDialogOpen = ref(false);
const selectedFileForSharing = ref<FileItem | null>(null);
const shareEmails = ref('');


const categories = [
  { value: 'all', label: 'All Files' },
  { value: 'attendance-reports', label: 'Attendance Reports' },
  { value: 'student-records', label: 'Student Records' },
  { value: 'class-schedules', label: 'Class Schedules' },
  { value: 'parent-communications', label: 'Parent Communications' },
  { value: 'administrative', label: 'Administrative Documents' },
  { value: 'templates', label: 'Templates & Forms' }
];

// Computed properties
const filteredFiles = computed(() => {
  let filtered = files.value;

  // Filter by category
  if (selectedCategory.value !== 'all') {
    filtered = filtered.filter(file => file.category === selectedCategory.value);
  }

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(file => 
      file.name.toLowerCase().includes(query) ||
      file.uploaded_by.toLowerCase().includes(query)
    );
  }

  return filtered;
});

// Methods
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getFileIcon = (type: string) => {
  if (type.includes('pdf')) return '📄';
  if (type.includes('word') || type.includes('document')) return '📝';
  if (type.includes('sheet') || type.includes('excel')) return '📊';
  if (type.includes('image')) return '🖼️';
  if (type.includes('video')) return '🎥';
  if (type.includes('audio')) return '🎵';
  return '📁';
};

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    uploadFile.value = target.files[0];
  }
};

const handleDragOver = (event: DragEvent) => {
  event.preventDefault();
  isDragOver.value = true;
};

const handleDragLeave = (event: DragEvent) => {
  event.preventDefault();
  isDragOver.value = false;
};

const handleDrop = (event: DragEvent) => {
  event.preventDefault();
  isDragOver.value = false;
  
  const droppedFiles = event.dataTransfer?.files;
  if (droppedFiles && droppedFiles[0]) {
    uploadFile.value = droppedFiles[0];
    isUploadDialogOpen.value = true;
  }
};

const handleShare = (file: FileItem) => {
  selectedFileForSharing.value = file;
  shareDialogOpen.value = true;
};

const handleShareSubmit = () => {
  if (!selectedFileForSharing.value || !shareEmails.value) return;
  
  router.post(`/files/${encodeURIComponent(selectedFileForSharing.value.name)}/share`, {
    emails: shareEmails.value
  }, {
    onSuccess: () => {
      alert(`File "${selectedFileForSharing.value.name}" shared successfully!`);
      shareEmails.value = '';
      selectedFileForSharing.value = null;
      shareDialogOpen.value = false;
    },
    onError: (errors) => {
      console.error('Sharing failed:', errors);
      alert('Sharing failed. Please try again.');
    }
  });
};

const handlePreview = (file: FileItem) => {
  // In a real app, this would open a preview modal
  alert(`Previewing ${file.name}...`);
};

const handleUpload = async () => {
  if (!uploadFile.value) return;

  isLoading.value = true;
  
  try {
    const formData = new FormData();
    formData.append('file', uploadFile.value);
    formData.append('category', uploadCategory.value);
    
    router.post('/files', formData, {
      onSuccess: () => {
        // Refresh the page to get updated file list
        router.reload();
        uploadFile.value = null;
        uploadCategory.value = 'attendance-reports';
        isUploadDialogOpen.value = false;
      },
      onError: (errors) => {
        console.error('Upload failed:', errors);
        alert('Upload failed. Please try again.');
      }
    });
  } catch (error) {
    console.error('Upload failed:', error);
    alert('Upload failed. Please try again.');
  } finally {
    isLoading.value = false;
  }
};

const handleDownload = (file: FileItem) => {
  // Trigger download from server
  window.open(`/files/download/${encodeURIComponent(file.name)}`, '_blank');
};

const handleDelete = (file: FileItem) => {
  if (confirm(`Are you sure you want to delete "${file.name}"?`)) {
    router.delete(`/files/${encodeURIComponent(file.name)}`, {
      onSuccess: () => {
        // Refresh the page to get updated file list
        router.reload();
      },
      onError: (errors) => {
        console.error('Delete failed:', errors);
        alert('Delete failed. Please try again.');
      }
    });
  }
};

// Initialize data
onMounted(() => {
  // Files are already loaded from props, no need to set sample data
});
</script>

<template>
  <Head title="Files" />

  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Attendance Documents</h1>
          <p class="text-muted-foreground">
            Upload, share, and manage attendance-related documents and reports
          </p>
        </div>
        
        <Dialog v-model:open="isUploadDialogOpen">
          <DialogTrigger as-child>
            <Button>
              <Plus class="mr-2 h-4 w-4" />
              Upload File
            </Button>
          </DialogTrigger>
          <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
              <DialogTitle>Upload New File</DialogTitle>
              <DialogDescription>
                Select a file to upload to your classroom files.
              </DialogDescription>
            </DialogHeader>
            
            <div class="grid gap-4 py-4">
              <div class="grid gap-2">
                <Label for="file-input">File</Label>
                <div 
                  class="border-2 border-dashed border-muted-foreground/25 rounded-lg p-6 text-center transition-colors"
                  :class="{ 'border-primary bg-primary/5': isDragOver }"
                  @dragover="handleDragOver"
                  @dragleave="handleDragLeave"
                  @drop="handleDrop"
                >
                  <Upload class="mx-auto h-8 w-8 text-muted-foreground mb-2" />
                  <p class="text-sm text-muted-foreground mb-2">
                    Drag and drop your file here, or click to browse
                  </p>
                  <Input
                    id="file-input"
                    type="file"
                    @change="handleFileSelect"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif"
                    class="hidden"
                  />
                  <Button variant="outline" size="sm" @click="document.getElementById('file-input')?.click()">
                    Browse Files
                  </Button>
                  <p class="text-xs text-muted-foreground mt-2">
                    Supports: PDF, Word, Excel, PowerPoint, Images
                  </p>
                </div>
              </div>
              
              <div class="grid gap-2">
                <Label for="category">Category</Label>
                <select
                  id="category"
                  v-model="uploadCategory"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  <option v-for="category in categories.slice(1)" :key="category.value" :value="category.value">
                    {{ category.label }}
                  </option>
                </select>
              </div>
            </div>
            
            <DialogFooter>
              <Button variant="outline" @click="isUploadDialogOpen = false">
                Cancel
              </Button>
              <Button @click="handleUpload" :disabled="!uploadFile || isLoading">
                <Upload class="mr-2 h-4 w-4" />
                {{ isLoading ? 'Uploading...' : 'Upload' }}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle>Filter Files</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
              <Label for="search">Search files</Label>
              <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                <Input
                  id="search"
                  v-model="searchQuery"
                  placeholder="Search by filename or uploader..."
                  class="pl-10"
                />
              </div>
            </div>
            
            <div class="sm:w-48">
              <Label for="category-filter">Category</Label>
              <select
                id="category-filter"
                v-model="selectedCategory"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              >
                <option v-for="category in categories" :key="category.value" :value="category.value">
                  {{ category.label }}
                </option>
              </select>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Files Grid -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <Card v-for="file in filteredFiles" :key="file.id" class="hover:shadow-md transition-shadow">
          <CardHeader class="pb-3">
            <div class="flex items-start justify-between">
              <div class="flex items-center space-x-2">
                <span class="text-2xl">{{ getFileIcon(file.type) }}</span>
                <div class="min-w-0 flex-1">
                  <CardTitle class="text-sm truncate">{{ file.name }}</CardTitle>
                  <CardDescription class="text-xs">
                    {{ formatFileSize(file.size) }}
                  </CardDescription>
                </div>
              </div>
              
              <div class="flex space-x-1">
                <Button variant="ghost" size="sm" @click="handlePreview(file)" title="Preview">
                  <Eye class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" @click="handleShare(file)" title="Share">
                  <Share2 class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" @click="handleDownload(file)" title="Download">
                  <Download class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" @click="handleDelete(file)" title="Delete">
                  <Trash2 class="h-4 w-4" />
                </Button>
              </div>
            </div>
          </CardHeader>
          
          <CardContent class="pt-0">
            <div class="space-y-2">
              <div class="flex items-center justify-between text-xs text-muted-foreground">
                <span>Uploaded by</span>
                <span>{{ file.uploaded_by }}</span>
              </div>
              
              <div class="flex items-center justify-between text-xs text-muted-foreground">
                <span>Date</span>
                <span>{{ formatDate(file.uploaded_at) }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <Badge variant="secondary" class="text-xs">
                  {{ categories.find(c => c.value === file.category)?.label || 'General' }}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Empty State -->
      <div v-if="filteredFiles.length === 0" class="text-center py-12">
        <Folder class="mx-auto h-12 w-12 text-muted-foreground" />
        <h3 class="mt-4 text-lg font-semibold">No files found</h3>
        <p class="text-muted-foreground">
          {{ searchQuery || selectedCategory !== 'all' 
            ? 'Try adjusting your search or filter criteria.' 
            : 'Upload your first file to get started.' }}
        </p>
        <Button v-if="!searchQuery && selectedCategory === 'all'" class="mt-4" @click="isUploadDialogOpen = true">
          <Plus class="mr-2 h-4 w-4" />
          Upload File
        </Button>
      </div>
    </div>

    <!-- Sharing Dialog -->
    <Dialog v-model:open="shareDialogOpen">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Share File</DialogTitle>
          <DialogDescription>
            Share "{{ selectedFileForSharing?.name }}" with colleagues or parents
          </DialogDescription>
        </DialogHeader>
        
        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label for="share-emails">Email Addresses</Label>
            <Input
              id="share-emails"
              v-model="shareEmails"
              placeholder="Enter email addresses separated by commas"
              type="email"
            />
            <p class="text-xs text-muted-foreground">
              Separate multiple emails with commas (e.g., parent1@email.com, parent2@email.com)
            </p>
          </div>
        </div>
        
        <DialogFooter>
          <Button variant="outline" @click="shareDialogOpen = false">
            Cancel
          </Button>
          <Button @click="handleShareSubmit" :disabled="!shareEmails">
            <Share2 class="mr-2 h-4 w-4" />
            Share File
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
