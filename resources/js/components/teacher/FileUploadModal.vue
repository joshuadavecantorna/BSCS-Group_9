<template>
  <Dialog v-model:open="open">
    <DialogContent class="max-w-md">
      <DialogHeader>
        <DialogTitle>Upload File</DialogTitle>
        <DialogDescription>
          Upload files to share with your students
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="uploadFile" class="space-y-4">
        <!-- Class Selection -->
        <div>
          <Label for="class-select">Select Class *</Label>
          <Select v-model="selectedClass" required>
            <SelectTrigger id="class-select">
              <SelectValue placeholder="Choose a class" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="cls in classes" :key="cls.id" :value="cls.id">
                {{ cls.name }} ({{ cls.section }})
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- File Upload -->
        <div>
          <Label for="file-input">Select File *</Label>
          <div class="mt-1">
            <input
              id="file-input"
              ref="fileInput"
              type="file"
              @change="handleFileSelect"
              class="hidden"
              multiple
              accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.mp4,.mov,.avi,.txt,.zip,.rar"
            />
            <div
              @click="fileInput?.click()"
              @drop="handleDrop"
              @dragover.prevent
              @dragenter.prevent
              class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-gray-400 transition-colors"
              :class="{ 'border-blue-400 bg-blue-50': isDragOver }"
            >
              <Upload class="h-12 w-12 mx-auto text-gray-400 mb-4" />
              <p class="text-sm text-gray-600 mb-2">
                <span class="font-medium">Click to upload</span> or drag and drop
              </p>
              <p class="text-xs text-gray-500">
                PDF, Word, PowerPoint, Excel, Images, Videos (Max 10MB each)
              </p>
            </div>
          </div>
        </div>

        <!-- Selected Files -->
        <div v-if="selectedFiles.length > 0" class="space-y-2">
          <Label>Selected Files ({{ selectedFiles.length }})</Label>
          <div class="max-h-40 overflow-y-auto space-y-2">
            <div v-for="(file, index) in selectedFiles" :key="index"
                 class="flex items-center justify-between p-2 bg-gray-50 rounded">
              <div class="flex items-center space-x-2 flex-1 min-w-0">
                <component :is="getFileIcon(file.name)" class="h-4 w-4 text-gray-500 flex-shrink-0" />
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-medium truncate">{{ file.name }}</p>
                  <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                </div>
              </div>
              <Button size="sm" variant="ghost" @click="removeFile(index)">
                <X class="h-3 w-3" />
              </Button>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div>
          <Label for="description">Description (Optional)</Label>
          <Textarea
            id="description"
            v-model="description"
            placeholder="Add a description for these files..."
            rows="3"
          />
        </div>

        <!-- File Settings -->
        <div class="space-y-3">
          <div class="flex items-center space-x-2">
            <Checkbox id="allow-download" v-model="allowDownload" />
            <Label for="allow-download" class="text-sm">Allow students to download</Label>
          </div>
          <div class="flex items-center space-x-2">
            <Checkbox id="notify-students" v-model="notifyStudents" />
            <Label for="notify-students" class="text-sm">Notify students about new files</Label>
          </div>
        </div>

        <!-- Upload Progress -->
        <div v-if="isUploading" class="space-y-2">
          <div class="flex items-center justify-between text-sm">
            <span>Uploading...</span>
            <span>{{ uploadProgress }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div 
              class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
              :style="{ width: `${uploadProgress}%` }"
            ></div>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 rounded-md">
          <p class="text-sm text-red-600">{{ errorMessage }}</p>
        </div>

        <!-- Actions -->
        <DialogFooter>
          <Button type="button" variant="outline" @click="open = false" :disabled="isUploading">
            Cancel
          </Button>
          <Button type="submit" :disabled="!canUpload || isUploading">
            <Loader2 v-if="isUploading" class="h-4 w-4 mr-2 animate-spin" />
            <Upload v-else class="h-4 w-4 mr-2" />
            {{ isUploading ? 'Uploading...' : 'Upload Files' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import {
  Upload,
  X,
  Loader2,
  FileText,
  File,
  FileImage,
  Video,
  FileSpreadsheet
} from 'lucide-vue-next'

interface Props {
  open: boolean
  classes?: Array<{
    id: number
    name: string
    section: string
    course?: string
  }>
}

const props = withDefaults(defineProps<Props>(), {
  classes: () => []
})

const emit = defineEmits(['update:open', 'file-uploaded'])

// Refs
const fileInput = ref<HTMLInputElement>()

// State
const selectedClass = ref('')
const selectedFiles = ref<File[]>([])
const description = ref('')
const allowDownload = ref(true)
const notifyStudents = ref(true)
const isUploading = ref(false)
const uploadProgress = ref(0)
const errorMessage = ref('')
const isDragOver = ref(false)

// Computed
const open = computed({
  get: () => props.open,
  set: (value: boolean) => emit('update:open', value)
})

const canUpload = computed(() => {
  return selectedClass.value && selectedFiles.value.length > 0
})

// Methods
const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files) {
    addFiles(Array.from(target.files))
  }
}

const handleDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = false
  
  if (event.dataTransfer?.files) {
    addFiles(Array.from(event.dataTransfer.files))
  }
}

const addFiles = (files: File[]) => {
  const maxSize = 10 * 1024 * 1024 // 10MB
  const validFiles = files.filter(file => {
    if (file.size > maxSize) {
      errorMessage.value = `File "${file.name}" is too large. Maximum size is 10MB.`
      return false
    }
    return true
  })

  selectedFiles.value = [...selectedFiles.value, ...validFiles]
  errorMessage.value = ''
}

const removeFile = (index: number) => {
  selectedFiles.value.splice(index, 1)
}

const getFileIcon = (filename: string) => {
  const ext = filename.split('.').pop()?.toLowerCase() || ''
  switch (ext) {
    case 'pdf':
    case 'doc':
    case 'docx':
      return FileText
    case 'xls':
    case 'xlsx':
      return FileSpreadsheet
    case 'jpg':
    case 'jpeg':
    case 'png':
    case 'gif':
    case 'svg':
      return FileImage
    case 'mp4':
    case 'mov':
    case 'avi':
    case 'mkv':
      return Video
    default:
      return File
  }
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const uploadFile = async () => {
  if (!selectedClass.value || selectedFiles.value.length === 0) {
    errorMessage.value = 'Please select a class and at least one file.';
    return;
  }

  isUploading.value = true;
  uploadProgress.value = 0;
  errorMessage.value = '';

  try {
    const formData = new FormData();

    // Add files
    selectedFiles.value.forEach((file, index) => {
      formData.append('files[]', file);
    });

    // Add other data
    formData.append('class_id', selectedClass.value);
    formData.append('description', description.value);
    formData.append('allow_download', allowDownload.value.toString());
    formData.append('notify_students', notifyStudents.value.toString());

    const response = await router.post('/teacher/files/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: (progressEvent) => {
        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
        uploadProgress.value = percentCompleted;
      }
    });

    if (response.data.success) {
      emit('file-uploaded', response.data.files);
      open.value = false;
      // Reset form
      resetForm();
    } else {
      errorMessage.value = response.data.message || 'Upload failed.';
    }
  } catch (error) {
    console.error('Upload error:', error);
    errorMessage.value = error.response?.data?.message || 'An error occurred during upload.';
  } finally {
    isUploading.value = false;
    uploadProgress.value = 0;
  }
};

const resetForm = () => {
  selectedClass.value = ''
  selectedFiles.value = []
  description.value = ''
  allowDownload.value = true
  notifyStudents.value = true
  errorMessage.value = ''
  uploadProgress.value = 0
  
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

// Reset form when modal closes
watch(() => props.open, (isOpen: boolean) => {
  if (!isOpen) {
    resetForm()
  }
})
</script>