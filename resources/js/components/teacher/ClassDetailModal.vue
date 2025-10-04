<template>
  <Dialog v-model:open="open">
    <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>
          {{ classData ? classData.name : 'Class Details' }}
        </DialogTitle>
        <DialogDescription>
          View and manage class information, students, and activities
        </DialogDescription>
      </DialogHeader>

      <div v-if="loading" class="flex items-center justify-center py-8">
        <Loader2 class="h-8 w-8 animate-spin" />
      </div>

      <div v-else-if="classData" class="space-y-6">
        <!-- Class Info -->
        <div class="bg-gray-50 rounded-lg p-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <h3 class="font-semibold text-gray-900 mb-2">Class Information</h3>
              <div class="space-y-2 text-sm">
                <div><span class="font-medium">Class Code:</span> {{ classData.class_code }}</div>
                <div><span class="font-medium">Section:</span> {{ classData.section }}</div>
                <div><span class="font-medium">Subject:</span> {{ classData.subject }}</div>
                <div><span class="font-medium">Course:</span> {{ classData.course }}</div>
                <div><span class="font-medium">Year:</span> {{ classData.year }}</div>
              </div>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 mb-2">Schedule & Location</h3>
              <div class="space-y-2 text-sm">
                <div><span class="font-medium">Room:</span> {{ classData.room || 'Not assigned' }}</div>
                <div><span class="font-medium">Time:</span> {{ classData.schedule_time || 'Not set' }}</div>
                <div><span class="font-medium">Days:</span> {{ classData.schedule_days?.join(', ') || 'Not set' }}</div>
                <div>
                  <span class="font-medium">Status:</span>
                  <Badge :variant="classData.is_active ? 'default' : 'secondary'" class="ml-2">
                    {{ classData.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <Tabs v-model="activeTab" class="w-full">
          <TabsList class="grid w-full grid-cols-3">
            <TabsTrigger value="students">Students ({{ classData.students?.length || 0 }})</TabsTrigger>
            <TabsTrigger value="attendance">Attendance Sessions</TabsTrigger>
            <TabsTrigger value="files">Class Files</TabsTrigger>
          </TabsList>

          <!-- Students Tab -->
          <TabsContent value="students" class="space-y-4">
            <div class="flex items-center justify-between">
              <h4 class="font-semibold">Enrolled Students</h4>
              <div class="flex gap-2">
                <Button size="sm" variant="outline" @click="exportStudents">
                  <Download class="h-4 w-4 mr-2" />
                  Export
                </Button>
                <Button size="sm" @click="addStudents">
                  <Plus class="h-4 w-4 mr-2" />
                  Add Students
                </Button>
              </div>
            </div>

            <div v-if="!classData.students || classData.students.length === 0" class="text-center py-8">
              <Users class="h-16 w-16 mx-auto text-gray-300 mb-4" />
              <p class="text-gray-500">No students enrolled yet</p>
              <Button @click="addStudents" class="mt-4">
                <Plus class="h-4 w-4 mr-2" />
                Add Students
              </Button>
            </div>

            <div v-else class="space-y-2">
              <div v-for="student in classData.students" :key="student.id" 
                   class="flex items-center justify-between p-3 bg-white border rounded-lg">
                <div class="flex items-center space-x-3">
                  <div class="h-8 w-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-medium">
                    {{ getStudentInitials(student) }}
                  </div>
                  <div>
                    <p class="font-medium">{{ student.first_name }} {{ student.last_name }}</p>
                    <p class="text-sm text-gray-500">{{ student.student_id }}</p>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <Badge variant="outline" class="text-xs">
                    {{ student.attendance_rate || 0 }}% Attendance
                  </Badge>
                  <Button size="sm" variant="ghost" @click="removeStudent(student.id)">
                    <X class="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </div>
          </TabsContent>

          <!-- Attendance Tab -->
          <TabsContent value="attendance" class="space-y-4">
            <div class="flex items-center justify-between">
              <h4 class="font-semibold">Attendance Sessions</h4>
              <Button size="sm" @click="startNewSession">
                <QrCode class="h-4 w-4 mr-2" />
                Start New Session
              </Button>
            </div>

            <div v-if="!classData.attendance_sessions || classData.attendance_sessions.length === 0" 
                 class="text-center py-8">
              <Calendar class="h-16 w-16 mx-auto text-gray-300 mb-4" />
              <p class="text-gray-500">No attendance sessions yet</p>
              <Button @click="startNewSession" class="mt-4">
                <QrCode class="h-4 w-4 mr-2" />
                Start First Session
              </Button>
            </div>

            <div v-else class="space-y-3">
              <div v-for="session in classData.attendance_sessions" :key="session.id"
                   class="p-4 bg-white border rounded-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <h5 class="font-medium">{{ formatDateTime(session.created_at) }}</h5>
                    <p class="text-sm text-gray-500">
                      {{ session.present_count || 0 }} present, 
                      {{ session.absent_count || 0 }} absent
                    </p>
                  </div>
                  <div class="flex items-center space-x-2">
                    <Badge :variant="session.status === 'active' ? 'default' : 'secondary'">
                      {{ session.status }}
                    </Badge>
                    <Button size="sm" variant="outline" @click="viewSession(session.id)">
                      View Details
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </TabsContent>

          <!-- Files Tab -->
          <TabsContent value="files" class="space-y-4">
            <div class="flex items-center justify-between">
              <h4 class="font-semibold">Class Files</h4>
              <Button size="sm" @click="uploadFile">
                <Upload class="h-4 w-4 mr-2" />
                Upload File
              </Button>
            </div>

            <div v-if="!classData.files || classData.files.length === 0" class="text-center py-8">
              <FileText class="h-16 w-16 mx-auto text-gray-300 mb-4" />
              <p class="text-gray-500">No files uploaded yet</p>
              <Button @click="uploadFile" class="mt-4">
                <Upload class="h-4 w-4 mr-2" />
                Upload First File
              </Button>
            </div>

            <div v-else class="space-y-2">
              <div v-for="file in classData.files" :key="file.id"
                   class="flex items-center justify-between p-3 bg-white border rounded-lg">
                <div class="flex items-center space-x-3">
                  <FileText class="h-8 w-8 text-blue-500" />
                  <div>
                    <p class="font-medium">{{ file.filename }}</p>
                    <p class="text-sm text-gray-500">
                      {{ file.file_size_formatted }} • {{ formatDateTime(file.uploaded_at) }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <Button size="sm" variant="outline" @click="downloadFile(file.id)">
                    <Download class="h-4 w-4" />
                  </Button>
                  <Button size="sm" variant="ghost" @click="deleteFile(file.id)">
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </div>
          </TabsContent>
        </Tabs>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="open = false">Close</Button>
        <Button @click="editClass">
          <Edit class="h-4 w-4 mr-2" />
          Edit Class
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Loader2,
  Users,
  Plus,
  Download,
  X,
  Calendar,
  QrCode,
  FileText,
  Upload,
  Trash2,
  Edit
} from 'lucide-vue-next'

interface Props {
  open: boolean
  classId: string
}

const props = defineProps<Props>()
const emit = defineEmits(['update:open'])

// State
const loading = ref(false)
const activeTab = ref('students')
const classData = ref<any>(null)

// Computed
const open = computed({
  get: () => props.open,
  set: (value: boolean) => emit('update:open', value)
})

// Watch for classId changes
watch(() => props.classId, (newClassId) => {
  if (newClassId && props.open) {
    fetchClassData(newClassId)
  }
})

watch(() => props.open, (isOpen) => {
  if (isOpen && props.classId) {
    fetchClassData(props.classId)
  }
})

// Methods
const fetchClassData = async (classId: string) => {
  loading.value = true
  try {
    // In a real app, this would fetch from the API
    // For now, we'll simulate the data structure
    setTimeout(() => {
      classData.value = {
        id: classId,
        name: 'Computer Science 101',
        class_code: 'CS101',
        section: 'A',
        subject: 'Introduction to Computer Science',
        course: 'Computer Science',
        year: '2024-2025',
        room: 'Room 301',
        schedule_time: '10:00 AM - 11:30 AM',
        schedule_days: ['Monday', 'Wednesday', 'Friday'],
        is_active: true,
        students: [
          {
            id: '1',
            student_id: 'STU001',
            first_name: 'John',
            last_name: 'Doe',
            attendance_rate: 95
          },
          {
            id: '2',
            student_id: 'STU002',
            first_name: 'Jane',
            last_name: 'Smith',
            attendance_rate: 87
          }
        ],
        attendance_sessions: [
          {
            id: '1',
            created_at: '2024-01-15T10:00:00Z',
            status: 'completed',
            present_count: 25,
            absent_count: 3
          }
        ],
        files: [
          {
            id: '1',
            filename: 'Syllabus.pdf',
            file_size_formatted: '2.3 MB',
            uploaded_at: '2024-01-10T09:00:00Z'
          }
        ]
      }
      loading.value = false
    }, 1000)
  } catch (error) {
    console.error('Error fetching class data:', error)
    loading.value = false
  }
}

const getStudentInitials = (student: any) => {
  return `${student.first_name.charAt(0)}${student.last_name.charAt(0)}`
}

const formatDateTime = (dateString: string) => {
  return new Date(dateString).toLocaleString()
}

const exportStudents = () => {
  console.log('Export students')
}

const addStudents = () => {
  console.log('Add students')
}

const removeStudent = (studentId: string) => {
  if (confirm('Are you sure you want to remove this student?')) {
    console.log('Remove student:', studentId)
  }
}

const startNewSession = () => {
  console.log('Start new session')
}

const viewSession = (sessionId: string) => {
  console.log('View session:', sessionId)
}

const uploadFile = () => {
  console.log('Upload file')
}

const downloadFile = (fileId: string) => {
  console.log('Download file:', fileId)
}

const deleteFile = (fileId: string) => {
  if (confirm('Are you sure you want to delete this file?')) {
    console.log('Delete file:', fileId)
  }
}

const editClass = () => {
  console.log('Edit class')
  open.value = false
}
</script>