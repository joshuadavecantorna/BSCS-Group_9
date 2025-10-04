<template>
  <Dialog v-model:open="open">
    <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>
          Attendance Session Details
        </DialogTitle>
        <DialogDescription>
          View and manage individual student attendance records
        </DialogDescription>
      </DialogHeader>

      <div v-if="loading" class="flex items-center justify-center py-8">
        <Loader2 class="h-8 w-8 animate-spin" />
      </div>

      <div v-else-if="sessionData" class="space-y-6">
        <!-- Session Info -->
        <div class="bg-gray-50 rounded-lg p-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <h3 class="font-semibold text-gray-900 mb-2">Session Information</h3>
              <div class="space-y-2 text-sm">
                <div><span class="font-medium">Class:</span> {{ sessionData.class.name }} - {{ sessionData.class.section }}</div>
                <div><span class="font-medium">Subject:</span> {{ sessionData.class.subject }}</div>
                <div><span class="font-medium">Started:</span> {{ formatDateTime(sessionData.created_at) }}</div>
                <div><span class="font-medium">Status:</span> 
                  <Badge :variant="getStatusVariant(sessionData.status)" class="ml-2">
                    {{ sessionData.status }}
                  </Badge>
                </div>
              </div>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 mb-2">Attendance Summary</h3>
              <div class="space-y-2 text-sm">
                <div class="flex items-center">
                  <CheckCircle class="h-4 w-4 text-green-500 mr-2" />
                  <span>Present: {{ sessionData.present_count || 0 }}</span>
                </div>
                <div class="flex items-center">
                  <XCircle class="h-4 w-4 text-red-500 mr-2" />
                  <span>Absent: {{ sessionData.absent_count || 0 }}</span>
                </div>
                <div class="flex items-center">
                  <Clock class="h-4 w-4 text-yellow-500 mr-2" />
                  <span>Late: {{ sessionData.late_count || 0 }}</span>
                </div>
                <div class="flex items-center">
                  <BarChart3 class="h-4 w-4 text-blue-500 mr-2" />
                  <span>Rate: {{ sessionData.attendance_rate }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <Input
              v-model="searchQuery"
              placeholder="Search students..."
              class="w-64"
            >
              <template #prefix>
                <Search class="h-4 w-4" />
              </template>
            </Input>
            <Select v-model="statusFilter">
              <SelectTrigger class="w-32">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All</SelectItem>
                <SelectItem value="present">Present</SelectItem>
                <SelectItem value="absent">Absent</SelectItem>
                <SelectItem value="late">Late</SelectItem>
                <SelectItem value="excused">Excused</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="flex items-center space-x-2">
            <Button variant="outline" @click="exportSession">
              <Download class="h-4 w-4 mr-2" />
              Export
            </Button>
            <Button v-if="sessionData.status === 'active'" @click="endSession">
              End Session
            </Button>
          </div>
        </div>

        <!-- Student Records -->
        <div class="border rounded-lg">
          <div class="p-4 border-b bg-gray-50">
            <h4 class="font-semibold">Student Attendance Records</h4>
          </div>
          <div class="divide-y">
            <div v-for="record in filteredRecords" :key="record.id" 
                 class="p-4 flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="h-10 w-10 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-medium">
                  {{ getStudentInitials(record.student) }}
                </div>
                <div>
                  <p class="font-medium">{{ record.student.first_name }} {{ record.student.last_name }}</p>
                  <p class="text-sm text-gray-500">{{ record.student.student_id }}</p>
                </div>
              </div>

              <div class="flex items-center space-x-4">
                <div v-if="record.marked_at" class="text-sm text-gray-500">
                  Marked at: {{ formatTime(record.marked_at) }}
                </div>
                <div class="flex items-center space-x-2">
                  <Badge :variant="getAttendanceVariant(record.status)">
                    {{ record.status || 'Not marked' }}
                  </Badge>
                  <DropdownMenu v-if="sessionData.status === 'active'">
                    <DropdownMenuTrigger asChild>
                      <Button size="sm" variant="outline">
                        Mark
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent>
                      <DropdownMenuItem @click="markAttendance(record.id, 'present')">
                        <CheckCircle class="h-4 w-4 mr-2 text-green-500" />
                        Present
                      </DropdownMenuItem>
                      <DropdownMenuItem @click="markAttendance(record.id, 'absent')">
                        <XCircle class="h-4 w-4 mr-2 text-red-500" />
                        Absent
                      </DropdownMenuItem>
                      <DropdownMenuItem @click="markAttendance(record.id, 'late')">
                        <Clock class="h-4 w-4 mr-2 text-yellow-500" />
                        Late
                      </DropdownMenuItem>
                      <DropdownMenuItem @click="markAttendance(record.id, 'excused')">
                        <AlertCircle class="h-4 w-4 mr-2 text-blue-500" />
                        Excused
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="open = false">Close</Button>
        <Button v-if="sessionData?.status === 'active'" @click="endSession">
          End Session
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'
import {
  Loader2,
  CheckCircle,
  XCircle,
  Clock,
  BarChart3,
  Search,
  Download,
  AlertCircle
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
const searchQuery = ref('')
const statusFilter = ref('all')

// Computed
const open = computed({
  get: () => props.open,
  set: (value: boolean) => emit('update:open', value)
})

const filteredRecords = computed(() => {
  if (!sessionData.value?.records) return []
  
  let records = [...sessionData.value.records]
  
  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    records = records.filter(record => 
      record.student.first_name.toLowerCase().includes(query) ||
      record.student.last_name.toLowerCase().includes(query) ||
      record.student.student_id.toLowerCase().includes(query)
    )
  }
  
  // Status filter
  if (statusFilter.value !== 'all') {
    records = records.filter(record => record.status === statusFilter.value)
  }
  
  return records
})

// Watch for sessionId changes
watch(() => props.sessionId, (newSessionId) => {
  if (newSessionId && props.open) {
    fetchSessionData(newSessionId)
  }
})

watch(() => props.open, (isOpen) => {
  if (isOpen && props.sessionId) {
    fetchSessionData(props.sessionId)
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
        created_at: '2024-01-15T10:00:00Z',
        present_count: 22,
        absent_count: 5,
        late_count: 1,
        attendance_rate: 82,
        records: [
          {
            id: '1',
            student: {
              id: '1',
              student_id: 'STU001',
              first_name: 'John',
              last_name: 'Doe'
            },
            status: 'present',
            marked_at: '2024-01-15T10:05:00Z'
          },
          {
            id: '2',
            student: {
              id: '2',
              student_id: 'STU002',
              first_name: 'Jane',
              last_name: 'Smith'
            },
            status: 'absent',
            marked_at: null
          },
          {
            id: '3',
            student: {
              id: '3',
              student_id: 'STU003',
              first_name: 'Bob',
              last_name: 'Johnson'
            },
            status: 'late',
            marked_at: '2024-01-15T10:15:00Z'
          }
        ]
      }
      loading.value = false
    }, 1000)
  } catch (error) {
    console.error('Error fetching session data:', error)
    loading.value = false
  }
}

const formatDateTime = (dateString: string) => {
  return new Date(dateString).toLocaleString()
}

const formatTime = (dateString: string) => {
  return new Date(dateString).toLocaleTimeString()
}

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'active': return 'default'
    case 'completed': return 'secondary'
    case 'cancelled': return 'destructive'
    default: return 'outline'
  }
}

const getAttendanceVariant = (status: string) => {
  switch (status) {
    case 'present': return 'default'
    case 'absent': return 'destructive'
    case 'late': return 'secondary'
    case 'excused': return 'outline'
    default: return 'outline'
  }
}

const getStudentInitials = (student: any) => {
  return `${student.first_name.charAt(0)}${student.last_name.charAt(0)}`
}

const markAttendance = (recordId: string, status: string) => {
  router.post(`/teacher/attendance/records/${recordId}/mark`, {
    status: status
  }, {
    onSuccess: () => {
      // Refresh the session data
      fetchSessionData(props.sessionId)
    }
  })
}

const exportSession = () => {
  window.open(`/teacher/attendance/sessions/${props.sessionId}/export`, '_blank')
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
</script>