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
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Files', href: '#' }
];

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
  class_linked?: string;
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
const uploadCategory = ref('attendance-reports');
const uploadClassLinked = ref('General');
const isLoading = ref(false);
const isDragOver = ref(false);
const shareDialogOpen = ref(false);
const selectedFileForSharing = ref<FileItem | null>(null);
const shareEmails = ref('');
const selectedFiles = ref<File[]>([]);
const fileInput = ref<HTMLInputElement | null>(null);


const categories = [
  { value: 'all', label: 'All Files' },
  { value: 'attendance-reports', label: 'Attendance Reports' },
  { value: 'student-records', label: 'Student Records' },
  { value: 'class-schedules', label: 'Class Schedules' },
  { value: 'parent-communications', label: 'Parent Communications' },
  { value: 'administrative', label: 'Administrative Documents' },
  { value: 'templates', label: 'Templates & Forms' }
];

const classOptions = [
  { value: 'General', label: 'General' },
  { value: 'BSCS-A', label: 'BSCS Section A' },
  { value: 'BSCS-B', label: 'BSCS Section B' },
  { value: 'BSIT-A', label: 'BSIT Section A' },
  { value: 'BSIT-B', label: 'BSIT Section B' },
  { value: 'BSCE-A', label: 'BSCE Section A' },
  { value: 'BSME-A', label: 'BSME Section A' }
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
  if (target.files) {
    for (let i = 0; i < target.files.length; i++) {
      selectedFiles.value.push(target.files[i]);
    }
  }
  // Reset input to allow re-selecting same file
  target.value = '';
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
  if (droppedFiles) {
    for (let i = 0; i < droppedFiles.length; i++) {
      selectedFiles.value.push(droppedFiles[i]);
    }
    isUploadDialogOpen.value = true;
  }
};

const handleShare = (file: FileItem) => {
  selectedFileForSharing.value = file;
  shareDialogOpen.value = true;
};

const handleShareSubmit = () => {
  if (!selectedFileForSharing.value || !shareEmails.value) return;

  const file = selectedFileForSharing.value;

  router.post(`/files/${encodeURIComponent(file.name)}/share`, {
    emails: shareEmails.value
  }, {
    onSuccess: () => {
      alert(`File "${file.name}" shared successfully!`);
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
  // Open file in new tab for preview/download
  window.open(`/files/download/${encodeURIComponent(file.name)}`, '_blank');
};

const handleUpload = async () => {
  if (selectedFiles.value.length === 0) return;

  isLoading.value = true;

  try {
    for (const file of selectedFiles.value) {
      const formData = new FormData();
      formData.append('file', file);
      formData.append('category', uploadCategory.value);
      formData.append('class_linked', uploadClassLinked.value);

      await new Promise<void>((resolve, reject) => {
        router.post('/files', formData, {
          onSuccess: (page) => {
            // Assume page.props has updated files, or add mock
            if (page.props && page.props.files && Array.isArray(page.props.files)) {
              files.value = page.props.files as FileItem[];
            } else {
              // Add mock file
              const mockFile: FileItem = {
                id: Date.now().toString(),
                name: file.name,
                size: file.size,
                type: file.type,
                uploaded_at: new Date().toISOString(),
                uploaded_by: 'You', // Assume current user
                category: uploadCategory.value,
                class_linked: uploadClassLinked.value,
              };
              files.value.push(mockFile);
            }
            resolve();
          },
          onError: (errors) => {
            reject(errors);
          }
        });
      });
    }
    selectedFiles.value = [];
    uploadCategory.value = 'attendance-reports';
    uploadClassLinked.value = 'General';
    isUploadDialogOpen.value = false;
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
        // Remove from local files list
        const index = files.value.findIndex(f => f.id === file.id);
        if (index > -1) {
          files.value.splice(index, 1);
        }
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

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
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
                  class="border-2 border-dashed border-muted-foreground/25 rounded-lg p-8 text-center transition-all duration-200 hover:border-muted-foreground/50"
                  :class="{ 'border-primary bg-primary/5 shadow-inner': isDragOver }"
                  @dragover="handleDragOver"
                  @dragleave="handleDragLeave"
                  @drop="handleDrop"
                  role="button"
                  tabindex="0"
                  aria-label="Drag and drop files here or click to browse"
                  @keydown.enter="fileInput?.click()"
                  @keydown.space="fileInput?.click()"
                >
                  <Upload class="mx-auto h-10 w-10 text-muted-foreground mb-3" />
                  <p class="text-sm font-medium text-muted-foreground mb-2">
                    Drag and drop your file here, or click to browse
                  </p>
                  <Input
                    id="file-input"
                    type="file"
                    multiple
                    @change="handleFileSelect"
                    accept=".pdf,.csv,.xls,.xlsx"
                    class="hidden"
                    ref="fileInput"
                    aria-label="Select files to upload"
                  />
                  <label for="file-input" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3 py-2 mt-2 cursor-pointer">
                    Browse Files
                  </label>
                  <p class="text-xs text-muted-foreground mt-3">
                    Supports: PDF, CSV, Excel files
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

              <div class="grid gap-2">
                <Label for="class-linked">Class Linked</Label>
                <select
                  id="class-linked"
                  v-model="uploadClassLinked"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  <option v-for="classOption in classOptions" :key="classOption.value" :value="classOption.value">
                    {{ classOption.label }}
                  </option>
                </select>
              </div>

              <div v-if="selectedFiles.length > 0" class="grid gap-2">
                <Label>Selected Files</Label>
                <div class="space-y-2 max-h-32 overflow-y-auto border rounded p-2">
                  <div v-for="(file, index) in selectedFiles" :key="index" class="flex items-center justify-between p-2 bg-muted rounded">
                    <span class="text-sm">{{ file.name }} ({{ formatFileSize(file.size) }})</span>
                    <Button variant="ghost" size="sm" @click="selectedFiles.splice(index, 1)" aria-label="Remove file">
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </div>
            </div>
            
            <DialogFooter>
              <Button variant="outline" @click="selectedFiles = []; isUploadDialogOpen = false">
                Cancel
              </Button>
              <Button @click="handleUpload" :disabled="selectedFiles.length === 0 || isLoading">
                <Upload class="mr-2 h-4 w-4" />
                {{ isLoading ? 'Uploading...' : 'Upload' }}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Filters -->
      <Card>
        <CardContent class="pt-6">
          <div class="flex flex-col lg:flex-row gap-4">
            <div class="flex-1">
              <Label for="search" class="text-sm font-medium mb-2 block">Search Files</Label>
              <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                <Input
                  id="search"
                  v-model="searchQuery"
                  placeholder="Search by filename or uploader..."
                  class="pl-10 h-11"
                />
              </div>
            </div>

            <div class="lg:w-64">
              <Label for="category-filter" class="text-sm font-medium mb-2 block">Category</Label>
              <select
                id="category-filter"
                v-model="selectedCategory"
                class="flex h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
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
            <div class="flex items-start gap-3">
              <span class="text-2xl flex-shrink-0 mt-1 cursor-pointer hover:scale-110 transition-transform" @click="handleDownload(file)">{{ getFileIcon(file.type) }}</span>
              <div class="min-w-0 flex-1">
                <CardTitle class="text-sm truncate pr-2 cursor-pointer hover:text-primary" @click="handleDownload(file)">{{ file.name }}</CardTitle>
                <CardDescription class="text-xs">
                  {{ formatFileSize(file.size) }}
                </CardDescription>
              </div>
            </div>
            <div class="flex items-center space-x-1 mt-2">
              <Button variant="ghost" size="sm" @click="handlePreview(file)" title="Preview" aria-label="Preview file">
                <Eye class="h-4 w-4" />
              </Button>
              <Button variant="ghost" size="sm" @click="handleShare(file)" title="Share" aria-label="Share file">
                <Share2 class="h-4 w-4" />
              </Button>
              <Button variant="ghost" size="sm" @click="handleDownload(file)" title="Download" aria-label="Download file">
                <Download class="h-4 w-4" />
              </Button>
              <Button variant="ghost" size="sm" @click="handleDelete(file)" title="Delete" aria-label="Delete file" class="text-destructive hover:text-destructive">
                <Trash2 class="h-4 w-4" />
              </Button>
            </div>
          </CardHeader>
          
          <CardContent class="pt-0">
            <div class="space-y-2">
              <div class="flex items-center justify-between text-xs text-muted-foreground">
                <span>Uploaded by</span>
                <span>{{ file.uploaded_by }}</span>
              </div>
              
              <div class="flex items-center justify-between text-xs text-muted-foreground">
                <span>Class Linked</span>
                <Badge variant="outline" class="text-xs">
                  {{ file.class_linked || 'General' }}
                </Badge>
              </div>
              
              <div class="flex items-center justify-between text-xs text-muted-foreground">
                <span>Date Uploaded</span>
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
