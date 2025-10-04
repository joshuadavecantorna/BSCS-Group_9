<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-[500px]">
      <DialogHeader>
        <DialogTitle>Start Attendance Session</DialogTitle>
        <DialogDescription>
          Begin taking attendance for your class. Choose the method and configure the session details.
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="startSession" class="space-y-4">
        <div>
          <Label for="class-select">Select Class *</Label>
          <Select v-model="form.class_id" required>
            <SelectTrigger>
              <SelectValue placeholder="Choose a class" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="1">CS101 - Section A (25 students)</SelectItem>
              <SelectItem value="2">CS102 - Section B (30 students)</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div>
          <Label for="session_name">Session Name *</Label>
          <Input
            id="session_name"
            v-model="form.session_name"
            placeholder="e.g., Midterm Quiz, Regular Class"
            required
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <Label for="session_date">Date *</Label>
            <Input
              id="session_date"
              v-model="form.session_date"
              type="date"
              required
            />
          </div>
          <div>
            <Label for="start_time">Start Time *</Label>
            <Input
              id="start_time"
              v-model="form.start_time"
              type="time"
              required
            />
          </div>
        </div>

        <div>
          <Label>Attendance Method *</Label>
          <div class="grid grid-cols-3 gap-4 mt-2">
            <Card 
              :class="[
                'cursor-pointer transition-all hover:shadow-md',
                form.attendance_method === 'qr' ? 'ring-2 ring-blue-500 bg-blue-50' : 'hover:bg-gray-50'
              ]"
              @click="form.attendance_method = 'qr'"
            >
              <CardContent class="p-4 text-center">
                <QrCode class="h-8 w-8 mx-auto mb-2" :class="form.attendance_method === 'qr' ? 'text-blue-600' : 'text-gray-400'" />
                <p class="text-sm font-medium">QR Code</p>
                <p class="text-xs text-gray-500 mt-1">Students scan QR codes</p>
              </CardContent>
            </Card>

            <Card 
              :class="[
                'cursor-pointer transition-all hover:shadow-md',
                form.attendance_method === 'manual' ? 'ring-2 ring-blue-500 bg-blue-50' : 'hover:bg-gray-50'
              ]"
              @click="form.attendance_method = 'manual'"
            >
              <CardContent class="p-4 text-center">
                <ClipboardList class="h-8 w-8 mx-auto mb-2" :class="form.attendance_method === 'manual' ? 'text-blue-600' : 'text-gray-400'" />
                <p class="text-sm font-medium">Manual</p>
                <p class="text-xs text-gray-500 mt-1">Mark attendance manually</p>
              </CardContent>
            </Card>

            <Card 
              :class="[
                'cursor-pointer transition-all hover:shadow-md',
                form.attendance_method === 'webcam' ? 'ring-2 ring-blue-500 bg-blue-50' : 'hover:bg-gray-50'
              ]"
              @click="form.attendance_method = 'webcam'"
            >
              <CardContent class="p-4 text-center">
                <Camera class="h-8 w-8 mx-auto mb-2" :class="form.attendance_method === 'webcam' ? 'text-blue-600' : 'text-gray-400'" />
                <p class="text-sm font-medium">Webcam</p>
                <p class="text-xs text-gray-500 mt-1">Face recognition</p>
              </CardContent>
            </Card>
          </div>
        </div>

        <!-- Method-specific instructions -->
        <Alert v-if="form.attendance_method === 'qr'">
          <QrCode class="h-4 w-4" />
          <AlertTitle>QR Code Method</AlertTitle>
          <AlertDescription>
            Students will scan individual QR codes with their mobile devices. Make sure you have generated QR codes for each student.
          </AlertDescription>
        </Alert>

        <Alert v-if="form.attendance_method === 'manual'">
          <ClipboardList class="h-4 w-4" />
          <AlertTitle>Manual Method</AlertTitle>
          <AlertDescription>
            You will manually mark each student as present, absent, or excused from the attendance interface.
          </AlertDescription>
        </Alert>

        <Alert v-if="form.attendance_method === 'webcam'">
          <Camera class="h-4 w-4" />
          <AlertTitle>Webcam Method</AlertTitle>
          <AlertDescription>
            Students will use the webcam for face recognition. Ensure proper lighting and camera setup for best results.
          </AlertDescription>
        </Alert>

        <DialogFooter>
          <Button type="button" variant="outline" @click="closeModal">
            Cancel
          </Button>
          <Button type="submit" :disabled="isLoading || !form.class_id">
            <Loader2 v-if="isLoading" class="h-4 w-4 mr-2 animate-spin" />
            Start Session
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card, CardContent } from '@/components/ui/card'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { QrCode, ClipboardList, Camera, Loader2 } from 'lucide-vue-next'

interface Props {
  open: boolean
}

interface Emits {
  (e: 'update:open', value: boolean): void
  (e: 'attendance-started', sessionId: string): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const isOpen = ref(props.open)
const isLoading = ref(false)

const form = ref({
  class_id: '',
  session_name: '',
  session_date: new Date().toISOString().split('T')[0],
  start_time: new Date().toTimeString().slice(0, 5),
  attendance_method: 'qr'
})

// Watch for prop changes
watch(() => props.open, (newValue) => {
  isOpen.value = newValue
})

watch(isOpen, (newValue) => {
  emit('update:open', newValue)
})

const resetForm = () => {
  form.value = {
    class_id: '',
    session_name: '',
    session_date: new Date().toISOString().split('T')[0],
    start_time: new Date().toTimeString().slice(0, 5),
    attendance_method: 'qr'
  }
}

const closeModal = () => {
  isOpen.value = false
  resetForm()
}

const startSession = async () => {
  try {
    isLoading.value = true
    
    router.post('/teacher/attendance/sessions', form.value, {
      onSuccess: (page) => {
        // Assuming the response includes session ID
        emit('attendance-started', '1') // Replace with actual session ID
        closeModal()
      },
      onError: (errors) => {
        console.error('Failed to start attendance session:', errors)
      }
    })
  } catch (error) {
    console.error('Error starting attendance session:', error)
  } finally {
    isLoading.value = false
  }
}
</script>