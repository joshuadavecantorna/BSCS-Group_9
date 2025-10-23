<template>
  <Dialog v-model:open="open">
    <DialogContent class="max-w-md">
      <DialogHeader>
        <DialogTitle>Attendance QR Code</DialogTitle>
        <DialogDescription>
          Students can scan this QR code to mark their attendance
        </DialogDescription>
      </DialogHeader>

      <div v-if="loading" class="flex items-center justify-center py-8">
        <Loader2 class="h-8 w-8 animate-spin" />
      </div>

      <div v-else-if="sessionData" class="space-y-6">
        <!-- Session Info -->
        <div class="text-center">
          <h3 class="font-semibold text-lg">{{ sessionData.class.name }}</h3>
          <p class="text-sm text-gray-600">{{ sessionData.class.section }} • {{ sessionData.class.subject }}</p>
          <Badge :variant="sessionData.status === 'active' ? 'default' : 'secondary'" class="mt-2">
            {{ sessionData.status }}
          </Badge>
        </div>

        <!-- QR Code Display -->
        <div class="flex flex-col items-center space-y-4">
          <div class="w-64 h-64 bg-white border-2 border-gray-200 rounded-lg flex items-center justify-center">
            <!-- In a real app, this would be an actual QR code -->
            <div class="text-center">
              <QrCode class="h-24 w-24 mx-auto text-gray-400 mb-4" />
              <p class="text-sm text-gray-500">QR Code</p>
              <p class="text-xs text-gray-400 mt-1">Session: {{ sessionId }}</p>
            </div>
          </div>

          <!-- Session URL -->
          <div class="w-full">
            <Label class="text-sm font-medium">Attendance URL:</Label>
            <div class="flex items-center mt-1">
              <Input
                :value="attendanceUrl"
                readonly
                class="text-sm"
              />
              <Button size="sm" variant="outline" @click="copyUrl" class="ml-2">
                <Copy class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>

        <!-- Instructions -->
        <div class="bg-blue-50 rounded-lg p-4">
          <h4 class="font-medium text-blue-900 mb-2">Instructions for Students:</h4>
          <ol class="text-sm text-blue-800 space-y-1">
            <li>1. Open your camera app or QR code scanner</li>
            <li>2. Point your camera at the QR code above</li>
            <li>3. Tap the notification or link that appears</li>
            <li>4. Confirm your attendance on the webpage</li>
          </ol>
        </div>

        <!-- Live Updates -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="font-medium text-gray-900 mb-3">Live Attendance Count</h4>
          <div class="grid grid-cols-3 gap-4 text-center">
            <div>
              <div class="text-2xl font-bold text-green-600">{{ sessionData.present_count || 0 }}</div>
              <div class="text-xs text-gray-500">Present</div>
            </div>
            <div>
              <div class="text-2xl font-bold text-yellow-600">{{ sessionData.late_count || 0 }}</div>
              <div class="text-xs text-gray-500">Late</div>
            </div>
            <div>
              <div class="text-2xl font-bold text-red-600">{{ sessionData.absent_count || 0 }}</div>
              <div class="text-xs text-gray-500">Absent</div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div v-if="recentActivity.length > 0" class="space-y-2">
          <h4 class="font-medium text-gray-900">Recent Activity</h4>
          <div class="max-h-32 overflow-y-auto space-y-2">
            <div v-for="activity in recentActivity" :key="activity.id" 
                 class="flex items-center justify-between text-sm p-2 bg-white rounded border">
              <span>{{ activity.student_name }}</span>
              <div class="flex items-center space-x-2">
                <Badge :variant="getActivityVariant(activity.status)" size="sm">
                  {{ activity.status }}
                </Badge>
                <span class="text-xs text-gray-500">{{ formatTime(activity.marked_at) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="open = false">Close</Button>
        <Button @click="refreshSession">
          <RefreshCw class="h-4 w-4 mr-2" />
          Refresh
        </Button>
        <Button v-if="sessionData?.status === 'active'" @click="endSession" variant="destructive">
          End Session
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'
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
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Loader2,
  QrCode,
  Copy,
  RefreshCw
} from 'lucide-vue-next'

interface Props {
  open: boolean
  sessionId: string
}

const props = defineProps<Props>()
const emit = defineEmits(['update:open'])

// State
const loading = ref(false)
const sessionData = ref<any>(null)
const recentActivity = ref<any[]>([])
let refreshInterval: NodeJS.Timeout | null = null

// Computed
const open = computed({
  get: () => props.open,
  set: (value: boolean) => emit('update:open', value)
})

const attendanceUrl = computed(() => {
  if (!props.sessionId) return ''
  return `${window.location.origin}/attendance/${props.sessionId}`
})

// Watch for sessionId changes
watch(() => props.sessionId, (newSessionId) => {
  if (newSessionId && props.open) {
    fetchSessionData(newSessionId)
    startRefreshInterval()
  }
})

watch(() => props.open, (isOpen) => {
  if (isOpen && props.sessionId) {
    fetchSessionData(props.sessionId)
    startRefreshInterval()
  } else {
    stopRefreshInterval()
  }
})

// Methods
const fetchSessionData = async (sessionId: string) => {
  loading.value = true
  try {
    // In a real app, this would fetch from the API
    setTimeout(() => {
      sessionData.value = {
        id: sessionId,
        class: {
          name: 'Computer Science 101',
          section: 'A',
          subject: 'Introduction to Computer Science'
        },
        status: 'active',
        present_count: 15,
        late_count: 2,
        absent_count: 3
      }
      
      recentActivity.value = [
        {
          id: '1',
          student_name: 'John Doe',
          status: 'present',
          marked_at: new Date().toISOString()
        },
        {
          id: '2',
          student_name: 'Jane Smith',
          status: 'late',
          marked_at: new Date(Date.now() - 60000).toISOString()
        }
      ]
      
      loading.value = false
    }, 500)
  } catch (error) {
    console.error('Error fetching session data:', error)
    loading.value = false
  }
}

const copyUrl = async () => {
  try {
    await navigator.clipboard.writeText(attendanceUrl.value)
    // You could show a toast notification here
    console.log('URL copied to clipboard')
  } catch (error) {
    console.error('Failed to copy URL:', error)
  }
}

const formatTime = (dateString: string) => {
  return new Date(dateString).toLocaleTimeString([], { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

const getActivityVariant = (status: string) => {
  switch (status) {
    case 'present': return 'default'
    case 'late': return 'secondary'
    case 'absent': return 'destructive'
    default: return 'outline'
  }
}

const refreshSession = () => {
  if (props.sessionId) {
    fetchSessionData(props.sessionId)
  }
}

const endSession = () => {
  if (confirm('Are you sure you want to end this attendance session?')) {
    router.post(`/teacher/attendance/sessions/${props.sessionId}/end`, {}, {
      onSuccess: () => {
        open.value = false
      }
    })
  }
}

const startRefreshInterval = () => {
  // Refresh every 10 seconds when modal is open
  refreshInterval = setInterval(() => {
    if (props.sessionId && sessionData.value?.status === 'active') {
      fetchSessionData(props.sessionId)
    }
  }, 10000)
}

const stopRefreshInterval = () => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
    refreshInterval = null
  }
}

// Cleanup
onUnmounted(() => {
  stopRefreshInterval()
})
</script>