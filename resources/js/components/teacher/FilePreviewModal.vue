<template>
  <Dialog v-model:open="open">
    <DialogContent class="max-w-4xl max-h-[90vh] overflow-hidden">
      <DialogHeader>
        <DialogTitle>{{ file?.filename || 'File Preview' }}</DialogTitle>
        <DialogDescription>
          <div class="flex items-center space-x-4 text-sm">
            <span>{{ file?.class_name }}</span>
            <span>{{ file?.file_size_formatted }}</span>
            <span>{{ formatDate(file?.uploaded_at) }}</span>
          </div>
        </DialogDescription>
      </DialogHeader>

      <div v-if="file" class="flex-1 overflow-hidden">
        <!-- PDF Preview -->
        <div v-if="file.file_extension.toLowerCase() === 'pdf'" class="h-96 w-full">
          <iframe
            :src="`/teacher/files/${file.id}/preview`"
            class="w-full h-full border rounded"
            title="PDF Preview"
          />
        </div>

        <!-- Image Preview -->
        <div v-else-if="isImage(file.file_extension)" class="h-96 flex items-center justify-center bg-gray-50 rounded">
          <img
            :src="`/teacher/files/${file.id}/preview`"
            :alt="file.filename"
            class="max-h-full max-w-full object-contain"
          />
        </div>

        <!-- Video Preview -->
        <div v-else-if="isVideo(file.file_extension)" class="h-96 flex items-center justify-center bg-black rounded">
          <video
            :src="`/teacher/files/${file.id}/preview`"
            controls
            class="max-h-full max-w-full"
          >
            Your browser does not support the video tag.
          </video>
        </div>

        <!-- Text Preview -->
        <div v-else-if="isText(file.file_extension)" class="h-96 overflow-auto bg-gray-50 rounded p-4">
          <pre v-if="textContent" class="text-sm font-mono whitespace-pre-wrap">{{ textContent }}</pre>
          <div v-else class="flex items-center justify-center h-full">
            <Loader2 class="h-8 w-8 animate-spin" />
          </div>
        </div>

        <!-- Unsupported File Type -->
        <div v-else class="h-96 flex flex-col items-center justify-center bg-gray-50 rounded">
          <component :is="getFileIcon(file.file_extension)" class="h-24 w-24 text-gray-300 mb-4" />
          <h3 class="text-lg font-medium text-gray-900 mb-2">Preview not available</h3>
          <p class="text-gray-500 text-center mb-6">
            This file type cannot be previewed in the browser.<br>
            Click download to view the file.
          </p>
          <Button @click="downloadFile">
            <Download class="h-4 w-4 mr-2" />
            Download File
          </Button>
        </div>

        <!-- File Information -->
        <div class="mt-6 bg-gray-50 rounded-lg p-4">
          <h4 class="font-medium text-gray-900 mb-3">File Information</h4>
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
              <dt class="font-medium text-gray-500">Filename</dt>
              <dd class="mt-1 text-gray-900">{{ file.filename }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">File Size</dt>
              <dd class="mt-1 text-gray-900">{{ file.file_size_formatted }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">File Type</dt>
              <dd class="mt-1 text-gray-900">{{ file.file_extension.toUpperCase() }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">Class</dt>
              <dd class="mt-1 text-gray-900">{{ file.class_name }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">Uploaded</dt>
              <dd class="mt-1 text-gray-900">{{ formatDateTime(file.uploaded_at) }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-500">Downloads</dt>
              <dd class="mt-1 text-gray-900">{{ file.download_count || 0 }} times</dd>
            </div>
          </dl>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="open = false">Close</Button>
        <Button @click="shareFile">
          <Share2 class="h-4 w-4 mr-2" />
          Share
        </Button>
        <Button @click="downloadFile">
          <Download class="h-4 w-4 mr-2" />
          Download
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import {
  Download,
  Share2,
  Loader2,
  FileText,
  File,
  FileImage,
  Video,
  FileSpreadsheet
} from 'lucide-vue-next'

interface Props {
  open: boolean
  file: {
    id: string
    filename: string
    file_extension: string
    file_size_formatted: string
    class_name: string
    uploaded_at: string
    download_count: number
  } | null
}

const props = defineProps<Props>()
const emit = defineEmits(['update:open'])

// State
const textContent = ref('')

// Computed
const open = computed({
  get: () => props.open,
  set: (value: boolean) => emit('update:open', value)
})

// Methods
const isImage = (extension: string) => {
  const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp']
  return imageExts.includes(extension.toLowerCase())
}

const isVideo = (extension: string) => {
  const videoExts = ['mp4', 'mov', 'avi', 'mkv', 'webm', 'ogg', 'm4v']
  return videoExts.includes(extension.toLowerCase())
}

const isText = (extension: string) => {
  const textExts = ['txt', 'md', 'json', 'xml', 'csv', 'log', 'js', 'ts', 'html', 'css']
  return textExts.includes(extension.toLowerCase())
}

const getFileIcon = (extension: string) => {
  const ext = extension.toLowerCase()
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

const formatDate = (dateString: string | undefined) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString()
}

const formatDateTime = (dateString: string | undefined) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString()
}

const downloadFile = () => {
  if (props.file) {
    window.open(`/teacher/files/${props.file.id}/download`, '_blank')
  }
}

const shareFile = () => {
  if (props.file) {
    const shareUrl = `${window.location.origin}/shared/files/${props.file.id}`
    navigator.clipboard.writeText(shareUrl).then(() => {
      console.log('Share link copied to clipboard')
      // You could show a toast notification here
    })
  }
}

const loadTextContent = async (fileId: string) => {
  try {
    const response = await fetch(`/teacher/files/${fileId}/content`)
    if (response.ok) {
      textContent.value = await response.text()
    }
  } catch (error) {
    console.error('Error loading text content:', error)
    textContent.value = 'Error loading file content'
  }
}

// Watch for file changes
watch(() => props.file, (newFile) => {
  if (newFile && isText(newFile.file_extension) && props.open) {
    loadTextContent(newFile.id)
  } else {
    textContent.value = ''
  }
})

// Watch for modal open/close
watch(() => props.open, (isOpen) => {
  if (isOpen && props.file && isText(props.file.file_extension)) {
    loadTextContent(props.file.id)
  } else if (!isOpen) {
    textContent.value = ''
  }
})
</script>