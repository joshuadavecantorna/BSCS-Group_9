<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-[600px]">
      <DialogHeader>
        <DialogTitle>Create New Class</DialogTitle>
        <DialogDescription>
          Add a new class to your teaching schedule. Fill in the class details below.
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="createClass" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <Label for="name">Class Name *</Label>
            <Input
              id="name"
              v-model="form.name"
              placeholder="e.g., Computer Science 101"
              required
            />
          </div>
          <div>
            <Label for="class_code">Class Code *</Label>
            <Input
              id="class_code"
              v-model="form.class_code"
              placeholder="e.g., CS101"
              required
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <Label for="section">Section *</Label>
            <Input
              id="section"
              v-model="form.section"
              placeholder="e.g., A, B, 1, 2"
              required
            />
          </div>
          <div>
            <Label for="subject">Subject *</Label>
            <Input
              id="subject"
              v-model="form.subject"
              placeholder="e.g., Programming Fundamentals"
              required
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <Label for="course">Course *</Label>
            <Input
              id="course"
              v-model="form.course"
              placeholder="e.g., BSCS"
              required
            />
          </div>
          <div>
            <Label for="year">Year *</Label>
            <Select v-model="form.year" required>
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

        <div>
          <Label for="description">Description</Label>
          <Textarea
            id="description"
            v-model="form.description"
            placeholder="Brief description of the class..."
            rows="3"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <Label for="schedule_time">Schedule Time</Label>
            <Input
              id="schedule_time"
              v-model="form.schedule_time"
              placeholder="e.g., 9:00 AM - 10:30 AM"
            />
          </div>
          <div>
            <Label for="room">Room</Label>
            <Input
              id="room"
              v-model="form.room"
              placeholder="e.g., Room 205, Lab 3"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <Label for="academic_year">Academic Year *</Label>
            <Select v-model="form.academic_year" required>
              <SelectTrigger>
                <SelectValue placeholder="Select academic year" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="2024-2025">2024-2025</SelectItem>
                <SelectItem value="2025-2026">2025-2026</SelectItem>
                <SelectItem value="2026-2027">2026-2027</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div>
            <Label for="semester">Semester *</Label>
            <Select v-model="form.semester" required>
              <SelectTrigger>
                <SelectValue placeholder="Select semester" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="1st Semester">1st Semester</SelectItem>
                <SelectItem value="2nd Semester">2nd Semester</SelectItem>
                <SelectItem value="Summer">Summer</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="closeModal">
            Cancel
          </Button>
          <Button type="submit" :disabled="isLoading">
            <Loader2 v-if="isLoading" class="h-4 w-4 mr-2 animate-spin" />
            Create Class
          </Button>
        </DialogFooter>
      </form>
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
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Loader2 } from 'lucide-vue-next'

interface Props {
  open: boolean
}

interface Emits {
  (e: 'update:open', value: boolean): void
  (e: 'class-created', classData: any): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const isOpen = ref(props.open)
const isLoading = ref(false)

const form = ref({
  name: '',
  class_code: '',
  section: '',
  subject: '',
  course: '',
  year: '',
  description: '',
  schedule_time: '',
  room: '',
  academic_year: '2024-2025',
  semester: '1st Semester'
})

// Watch for prop changes
watch(() => props.open, (newValue) => {
  isOpen.value = newValue
})

// Watch for internal changes
watch(isOpen, (newValue) => {
  emit('update:open', newValue)
})

const resetForm = () => {
  form.value = {
    name: '',
    class_code: '',
    section: '',
    subject: '',
    course: '',
    year: '',
    description: '',
    schedule_time: '',
    room: '',
    academic_year: '2024-2025',
    semester: '1st Semester'
  }
}

const closeModal = () => {
  isOpen.value = false
  resetForm()
}

const createClass = async () => {
  try {
    isLoading.value = true
    
    router.post('/teacher/classes', form.value, {
      onSuccess: (page) => {
        emit('class-created', form.value)
        closeModal()
      },
      onError: (errors) => {
        console.error('Failed to create class:', errors)
      }
    })
  } catch (error) {
    console.error('Error creating class:', error)
  } finally {
    isLoading.value = false
  }
}
</script>