<template>
  <Dialog :open="open" @update:open="handleOpenChange">
    <DialogContent class="max-w-4xl max-h-[80vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>Add Students to Class</DialogTitle>
        <DialogDescription>
          Add new students or search for existing students in the database
        </DialogDescription>
      </DialogHeader>

      <!-- Tab Selection -->
      <div class="flex space-x-1 rounded-lg bg-muted p-1 mb-4">
        <button
          @click="activeTab = 'existing'"
          :class="[
            'flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-all',
            activeTab === 'existing'
              ? 'bg-background text-foreground shadow-sm'
              : 'text-muted-foreground hover:text-foreground'
          ]"
        >
          Search Existing Students
        </button>
        <button
          @click="activeTab = 'new'"
          :class="[
            'flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-all',
            activeTab === 'new'
              ? 'bg-background text-foreground shadow-sm'
              : 'text-muted-foreground hover:text-foreground'
          ]"
        >
          Add New Student
        </button>
      </div>

      <!-- Search Existing Students Tab -->
      <div v-if="activeTab === 'existing'" class="space-y-4">
        <!-- Search Input -->
        <div class="flex gap-2">
          <Input
            v-model="searchQuery"
            placeholder="Search by name, student ID, or email..."
            @input="handleSearch"
            class="flex-1"
          />
          <Button type="button" @click="searchStudents" :disabled="!searchQuery.trim()">
            <Search class="h-4 w-4 mr-2" />
            Search
          </Button>
        </div>

        <!-- Loading State -->
        <div v-if="searching" class="text-center py-4">
          <div class="inline-flex items-center">
            <Loader2 class="h-4 w-4 animate-spin mr-2" />
            Searching students...
          </div>
        </div>

        <!-- Search Results -->
        <div v-if="searchResults.length > 0" class="space-y-2">
          <h4 class="font-medium">Search Results ({{ searchResults.length }})</h4>
          <div class="border rounded-lg max-h-64 overflow-y-auto">
            <div
              v-for="student in searchResults"
              :key="student.id"
              class="flex items-center justify-between p-3 border-b last:border-b-0 hover:bg-muted/50"
            >
              <div class="flex items-center space-x-3">
                <Checkbox
                  :id="`student-${student.id}`"
                  :model-value="selectedStudents.includes(student.id)"
                  @update:model-value="(checked) => toggleStudentCheckbox(student.id, checked)"
                />
                <div>
                  <div class="font-medium">{{ student.name }}</div>
                  <div class="text-sm text-muted-foreground">
                    {{ student.student_id }} • {{ student.email }}
                  </div>
                  <div class="text-xs text-muted-foreground">
                    {{ student.course }} - {{ student.year }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Selected Count and Actions -->
          <div v-if="selectedStudents.length > 0" class="flex items-center justify-between pt-2">
            <span class="text-sm text-muted-foreground">
              {{ selectedStudents.length }} student(s) selected
            </span>
            <div class="flex gap-2">
              <Button variant="outline" @click="selectedStudents = []">
                Clear Selection
              </Button>
              <Button @click="addSelectedStudents" :disabled="processing">
                <Users class="h-4 w-4 mr-2" />
                Add Selected Students
              </Button>
            </div>
          </div>
        </div>

        <!-- No Results -->
        <div v-if="searchPerformed && searchResults.length === 0 && !searching" class="text-center py-8">
          <UserX class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
          <h3 class="text-lg font-semibold mb-2">No students found</h3>
          <p class="text-muted-foreground mb-4">
            No students match your search criteria. Try a different search term.
          </p>
          <Button variant="outline" @click="activeTab = 'new'">
            Add New Student Instead
          </Button>
        </div>
      </div>

      <!-- Add New Student Tab -->
      <div v-if="activeTab === 'new'" class="space-y-4">
        <form @submit.prevent="addNewStudent" class="space-y-4">
          <div class="grid gap-2">
            <Label for="student-id">Student ID</Label>
            <Input 
              id="student-id"
              v-model="newStudentForm.student_id" 
              placeholder="e.g., 25-12345"
              required 
            />
          </div>

          <div class="grid gap-2">
            <Label for="name">Full Name</Label>
            <Input 
              id="name"
              v-model="newStudentForm.name" 
              placeholder="e.g., John Doe"
              required 
            />
          </div>

          <div class="grid gap-2">
            <Label for="email">Email</Label>
            <Input 
              id="email"
              v-model="newStudentForm.email" 
              type="email"
              placeholder="e.g., john.doe@usm.edu.ph"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <Label for="course">Course</Label>
              <Select v-model="newStudentForm.course">
                <SelectTrigger>
                  <SelectValue placeholder="Select course" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Bachelor of Science in Computer Science">BSCS</SelectItem>
                  <SelectItem value="Bachelor of Science in Information Technology">BSIT</SelectItem>
                  <SelectItem value="Bachelor of Library and Information Science">BLIS</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label for="year">Year Level</Label>
              <Select v-model="newStudentForm.year">
                <SelectTrigger>
                  <SelectValue placeholder="Select year" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="1st Year">1st Year</SelectItem>
                  <SelectItem value="2nd Year">2nd Year</SelectItem>
                  <SelectItem value="3rd Year">3rd Year</SelectItem>
                  <SelectItem value="4th Year">4th Year</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="grid gap-2">
            <Label for="section">Section</Label>
            <Input 
              id="section"
              v-model="newStudentForm.section" 
              placeholder="e.g., A"
              required 
            />
          </div>

          <div class="flex justify-end gap-2">
            <Button type="button" variant="outline" @click="$emit('close')">
              Cancel
            </Button>
            <Button type="submit" :disabled="processing">
              <UserPlus class="h-4 w-4 mr-2" />
              Add Student
            </Button>
          </div>
        </form>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Search, Users, UserPlus, UserX, Loader2 } from 'lucide-vue-next'

interface Props {
  open: boolean
  classId: number
}

interface Student {
  id: number
  student_id: string
  name: string
  email: string
  course: string
  year: string
  section: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  studentAdded: [student: any]
}>()

// Handle dialog open/close
const handleOpenChange = (isOpen: boolean) => {
  if (!isOpen) {
    emit('close')
  }
}

// Tab state
const activeTab = ref<'existing' | 'new'>('existing')

// Search state
const searchQuery = ref('')
const searchResults = ref<Student[]>([])
const selectedStudents = ref<number[]>([])
const searching = ref(false)
const searchPerformed = ref(false)
const processing = ref(false)

// New student form
const newStudentForm = reactive({
  student_id: '',
  name: '',
  email: '',
  course: '',
  year: '',
  section: ''
})

// Search functionality
let searchTimeout: NodeJS.Timeout | null = null

const handleSearch = () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  searchTimeout = setTimeout(searchStudents, 300)
}

const searchStudents = async () => {
  if (!searchQuery.value.trim()) return
  
  searching.value = true
  searchPerformed.value = true
  
  try {
    const response = await fetch(`/teacher/students/search?search=${encodeURIComponent(searchQuery.value)}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    const data = await response.json()
    
    if (data.success) {
      searchResults.value = data.students
    } else {
      searchResults.value = []
    }
  } catch (error) {
    console.error('Search failed:', error)
    searchResults.value = []
  } finally {
    searching.value = false
  }
}

const toggleStudent = (studentId: number) => {
  const index = selectedStudents.value.indexOf(studentId)
  if (index > -1) {
    selectedStudents.value.splice(index, 1)
  } else {
    selectedStudents.value.push(studentId)
  }
}

const toggleStudentCheckbox = (studentId: number, checked: boolean | 'indeterminate') => {
  if (checked === true) {
    if (!selectedStudents.value.includes(studentId)) {
      selectedStudents.value.push(studentId)
    }
  } else {
    const index = selectedStudents.value.indexOf(studentId)
    if (index > -1) {
      selectedStudents.value.splice(index, 1)
    }
  }
}

const addSelectedStudents = async () => {
  if (selectedStudents.value.length === 0) return
  
  processing.value = true
  
  try {
    // Use Inertia's router for proper CSRF handling
    router.post(`/teacher/classes/${props.classId}/students/existing`, {
      student_ids: selectedStudents.value
    }, {
      onSuccess: (page: any) => {
        console.log('Students added successfully')
        
        // Check for success message in props
        if (page.props?.success || page.props?.message) {
          console.log('Success message:', page.props.message)
        }
        
        // Emit success event to parent with the selected students
        emit('studentAdded', selectedStudents.value)
        
        // Reset form
        selectedStudents.value = []
        searchResults.value = []
        searchQuery.value = ''
        searchPerformed.value = false
        processing.value = false
        
        // Show success message
        if (page.props?.message) {
          alert(page.props.message)
        }
      },
      onError: (errors: any) => {
        console.error('Failed to add students:', errors)
        alert('Failed to add students: ' + (errors.message || 'Unknown error'))
        processing.value = false
      },
      preserveScroll: true,
      preserveState: false // Allow state refresh to get updated data
    })
  } catch (error) {
    console.error('Failed to add students:', error)
    alert('Failed to add students: ' + (error instanceof Error ? error.message : 'Unknown error'))
    processing.value = false
  }
}

const addNewStudent = async () => {
  processing.value = true
  
  try {
    const response = await fetch(`/teacher/classes/${props.classId}/students`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(newStudentForm)
    })
    
    const data = await response.json()
    
    if (data.success) {
      emit('studentAdded', data.students)
      // Reset form
      Object.keys(newStudentForm).forEach(key => {
        (newStudentForm as any)[key] = ''
      })
    } else {
      alert(data.message || 'Failed to add student')
    }
  } catch (error) {
    console.error('Failed to add student:', error)
    alert('Failed to add student')
  } finally {
    processing.value = false
  }
}

// Reset state when dialog closes
watch(() => props.open, (isOpen) => {
  if (!isOpen) {
    selectedStudents.value = []
    searchResults.value = []
    searchQuery.value = ''
    searchPerformed.value = false
    activeTab.value = 'existing'
  }
})
</script>