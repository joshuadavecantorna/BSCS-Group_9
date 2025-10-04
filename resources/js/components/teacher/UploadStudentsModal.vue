<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-[700px]">
      <DialogHeader>
        <DialogTitle>Upload Student List</DialogTitle>
        <DialogDescription>
          Upload a CSV file or enter student information manually to add students to your classes.
        </DialogDescription>
      </DialogHeader>

      <Tabs v-model="activeTab" class="w-full">
        <TabsList class="grid w-full grid-cols-2">
          <TabsTrigger value="csv">CSV Upload</TabsTrigger>
          <TabsTrigger value="manual">Manual Entry</TabsTrigger>
        </TabsList>

        <!-- CSV Upload Tab -->
        <TabsContent value="csv" class="space-y-4">
          <div>
            <Label for="class-select">Select Class *</Label>
            <Select v-model="selectedClassId" required>
              <SelectTrigger>
                <SelectValue placeholder="Choose a class to add students to" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="1">CS101 - Section A</SelectItem>
                <SelectItem value="2">CS102 - Section B</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
            <div class="text-center">
              <Upload class="mx-auto h-12 w-12 text-gray-400" />
              <div class="mt-4">
                <label for="file-upload" class="cursor-pointer">
                  <span class="mt-2 block text-sm font-medium text-gray-900">
                    Drop your CSV file here, or 
                    <span class="text-indigo-600 hover:text-indigo-500">browse</span>
                  </span>
                  <input
                    id="file-upload"
                    type="file"
                    class="sr-only"
                    accept=".csv"
                    @change="handleFileUpload"
                  />
                </label>
                <p class="mt-1 text-xs text-gray-500">
                  CSV files only. Max file size: 5MB
                </p>
              </div>
            </div>
          </div>

          <Alert>
            <AlertCircle class="h-4 w-4" />
            <AlertTitle>CSV Format Requirements</AlertTitle>
            <AlertDescription>
              Your CSV file should have columns: student_id, first_name, last_name, email, phone, year, course, section
            </AlertDescription>
          </Alert>
        </TabsContent>

        <!-- Manual Entry Tab -->
        <TabsContent value="manual" class="space-y-4">
          <div>
            <Label for="manual-class-select">Select Class *</Label>
            <Select v-model="selectedClassId" required>
              <SelectTrigger>
                <SelectValue placeholder="Choose a class to add students to" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="1">CS101 - Section A</SelectItem>
                <SelectItem value="2">CS102 - Section B</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-4 max-h-96 overflow-y-auto">
            <div v-for="(student, index) in manualStudents" :key="index" class="border rounded-lg p-4">
              <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-medium">Student {{ index + 1 }}</h4>
                <Button 
                  v-if="manualStudents.length > 1" 
                  variant="ghost" 
                  size="sm" 
                  @click="removeStudent(index)"
                >
                  <X class="h-4 w-4" />
                </Button>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label :for="`student_id_${index}`">Student ID *</Label>
                  <Input
                    :id="`student_id_${index}`"
                    v-model="student.student_id"
                    placeholder="e.g., 20240001"
                    required
                  />
                </div>
                <div>
                  <Label :for="`email_${index}`">Email</Label>
                  <Input
                    :id="`email_${index}`"
                    v-model="student.email"
                    type="email"
                    placeholder="student@university.edu"
                  />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                  <Label :for="`first_name_${index}`">First Name *</Label>
                  <Input
                    :id="`first_name_${index}`"
                    v-model="student.first_name"
                    placeholder="John"
                    required
                  />
                </div>
                <div>
                  <Label :for="`last_name_${index}`">Last Name *</Label>
                  <Input
                    :id="`last_name_${index}`"
                    v-model="student.last_name"
                    placeholder="Doe"
                    required
                  />
                </div>
              </div>

              <div class="grid grid-cols-3 gap-4 mt-4">
                <div>
                  <Label :for="`year_${index}`">Year *</Label>
                  <Select v-model="student.year" required>
                    <SelectTrigger>
                      <SelectValue placeholder="Year" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="1st Year">1st Year</SelectItem>
                      <SelectItem value="2nd Year">2nd Year</SelectItem>
                      <SelectItem value="3rd Year">3rd Year</SelectItem>
                      <SelectItem value="4th Year">4th Year</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div>
                  <Label :for="`course_${index}`">Course *</Label>
                  <Input
                    :id="`course_${index}`"
                    v-model="student.course"
                    placeholder="BSCS"
                    required
                  />
                </div>
                <div>
                  <Label :for="`section_${index}`">Section *</Label>
                  <Input
                    :id="`section_${index}`"
                    v-model="student.section"
                    placeholder="A"
                    required
                  />
                </div>
              </div>
            </div>
          </div>

          <Button variant="outline" @click="addStudent" class="w-full">
            <Plus class="h-4 w-4 mr-2" />
            Add Another Student
          </Button>
        </TabsContent>
      </Tabs>

      <DialogFooter>
        <Button type="button" variant="outline" @click="closeModal">
          Cancel
        </Button>
        <Button @click="uploadStudents" :disabled="isLoading">
          <Loader2 v-if="isLoading" class="h-4 w-4 mr-2 animate-spin" />
          Upload Students
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Upload, X, AlertCircle, Plus, Loader2 } from 'lucide-vue-next'

interface Props {
  open: boolean
}

interface Emits {
  (e: 'update:open', value: boolean): void
  (e: 'students-uploaded', count: number): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const isOpen = ref(props.open)
const isLoading = ref(false)
const activeTab = ref('csv')
const selectedClassId = ref('')

const manualStudents = ref([
  {
    student_id: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    year: '',
    course: '',
    section: ''
  }
])

// Watch for prop changes
watch(() => props.open, (newValue) => {
  isOpen.value = newValue
})

watch(isOpen, (newValue) => {
  emit('update:open', newValue)
})

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (file) {
    console.log('File selected:', file.name)
    // Handle CSV parsing here
  }
}

const addStudent = () => {
  manualStudents.value.push({
    student_id: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    year: '',
    course: '',
    section: ''
  })
}

const removeStudent = (index: number) => {
  manualStudents.value.splice(index, 1)
}

const uploadStudents = async () => {
  if (!selectedClassId.value) return
  
  try {
    isLoading.value = true
    
    const studentsData = {
      students: manualStudents.value.filter(s => s.student_id && s.first_name && s.last_name)
    }
    
    router.post(`/teacher/classes/${selectedClassId.value}/students`, studentsData, {
      onSuccess: () => {
        emit('students-uploaded', studentsData.students.length)
        closeModal()
      },
      onError: (errors) => {
        console.error('Failed to upload students:', errors)
      }
    })
  } catch (error) {
    console.error('Error uploading students:', error)
  } finally {
    isLoading.value = false
  }
}

const closeModal = () => {
  isOpen.value = false
  selectedClassId.value = ''
  manualStudents.value = [{
    student_id: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    year: '',
    course: '',
    section: ''
  }]
}
</script>