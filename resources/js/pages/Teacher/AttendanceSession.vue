<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Separator } from '@/components/ui/separator';
import QrScanner from '@/components/QrScanner.vue';
import { ref, computed } from 'vue';
import { QrCode, Users, Clock, CheckCircle, XCircle, Loader2, Eye, EyeOff } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import QRCodeLib from 'qrcode';
import { onMounted } from 'vue';

interface Student {
  id: number;
  student_id: string;
  name: string;
  email: string;
  year: string;
  course: string;
  section: string;
}

interface AttendanceSession {
  id: number;
  class_id: number;
  session_name: string;
  session_date: string;
  start_time: string;
  status: string;
  qr_code?: string;
  total_students: number;
  present_count: number;
  absent_count: number;
  class: {
    name: string;
    code: string;
  };
}

interface Props {
  teacher: any;
  session: AttendanceSession;
  students: Student[];
  attendance_records: number[];
}

const props = defineProps<Props>();

// Reactive state
const showQRScanner = ref(false);
const showQRCode = ref(false);
const scanSuccess = ref<Student | null>(null);
const scanError = ref<string>('');
const isProcessing = ref(false);
const qrCodeDataUrl = ref<string>('');

// QR Scanner functions
const openQRScanner = () => {
  showQRScanner.value = true;
  scanError.value = '';
  scanSuccess.value = null;
};

const closeQRScanner = () => {
  showQRScanner.value = false;
};

const openQRCodeDisplay = async () => {
  if (props.session.qr_code && !qrCodeDataUrl.value) {
    try {
      qrCodeDataUrl.value = await QRCodeLib.toDataURL(props.session.qr_code, {
        width: 200,
        margin: 2,
        color: {
          dark: '#000000',
          light: '#FFFFFF'
        }
      });
    } catch (error) {
      console.error('Failed to generate QR code:', error);
    }
  }
  showQRCode.value = true;
};

const closeQRCodeDisplay = () => {
  showQRCode.value = false;
};

const onScanSuccess = async (studentData: any) => {
  console.log('QR scan successful:', studentData);
  isProcessing.value = true;
  scanError.value = '';

  try {
    const response = await fetch(`/teacher/attendance/${props.session.id}/qr-scan`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        qr_data: JSON.stringify(studentData)
      })
    });

    const result = await response.json();

    if (result.success) {
      scanSuccess.value = result.student;
      scanError.value = '';
      
      // Update the attendance records
      if (!props.attendance_records.includes(result.student.id)) {
        props.attendance_records.push(result.student.id);
      }
      
      // Show success message briefly, then close scanner
      setTimeout(() => {
        closeQRScanner();
        scanSuccess.value = null;
      }, 2000);
    } else {
      scanError.value = result.message || 'Failed to mark attendance';
    }
  } catch (error) {
    console.error('Error marking attendance:', error);
    scanError.value = 'Network error. Please try again.';
  } finally {
    isProcessing.value = false;
  }
};

// End session
const endSessionForm = useForm({});

const endSession = () => {
  endSessionForm.put(`/teacher/attendance/sessions/${props.session.id}/end`, {
    onSuccess: () => {
      window.location.href = '/teacher/attendance';
    }
  });
};

// Computed properties
const studentsPresent = computed(() => 
  props.students.filter(student => props.attendance_records.includes(student.id))
);

const studentsAbsent = computed(() => 
  props.students.filter(student => !props.attendance_records.includes(student.id))
);

const attendanceRate = computed(() => {
  if (props.students.length === 0) return 0;
  return Math.round((studentsPresent.value.length / props.students.length) * 100);
});
</script>

<template>
  <Head :title="`Attendance Session - ${session.session_name}`" />

  <AppLayout>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ session.session_name }}</h1>
                <p class="text-gray-600">
                  {{ session.class?.name || 'Class' }} • {{ session.session_date }} 
                  <span class="ml-2">
                    <Badge :variant="session.status === 'active' ? 'default' : 'secondary'">
                      {{ session.status }}
                    </Badge>
                  </span>
                </p>
              </div>
              <div class="flex gap-3">
                <Button @click="openQRCodeDisplay" :disabled="session.status !== 'active'">
                  <Eye class="h-4 w-4 mr-2" />
                  Show QR Code
                </Button>
                <Button @click="openQRScanner" :disabled="session.status !== 'active'" variant="outline">
                  <QrCode class="h-4 w-4 mr-2" />
                  QR Scanner
                </Button>
                <Button 
                  variant="outline" 
                  @click="endSession"
                  :disabled="endSessionForm.processing || session.status !== 'active'"
                >
                  <Clock class="h-4 w-4 mr-2" />
                  {{ endSessionForm.processing ? 'Ending...' : 'End Session' }}
                </Button>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">Total Students</CardTitle>
              <Users class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold">{{ students.length }}</div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">Present</CardTitle>
              <CheckCircle class="h-4 w-4 text-green-600" />
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold text-green-600">{{ studentsPresent.length }}</div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">Absent</CardTitle>
              <XCircle class="h-4 w-4 text-red-600" />
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold text-red-600">{{ studentsAbsent.length }}</div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">Attendance Rate</CardTitle>
              <div class="text-sm font-medium">{{ attendanceRate }}%</div>
            </CardHeader>
            <CardContent>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div 
                  class="bg-green-600 h-2 rounded-full transition-all duration-300"
                  :style="{ width: `${attendanceRate}%` }"
                ></div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Student Lists -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Present Students -->
          <Card>
            <CardHeader>
              <CardTitle class="text-green-600 flex items-center gap-2">
                <CheckCircle class="h-5 w-5" />
                Present Students ({{ studentsPresent.length }})
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-2 max-h-96 overflow-y-auto">
                <div 
                  v-for="student in studentsPresent" 
                  :key="student.id"
                  class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg"
                >
                  <div>
                    <p class="font-medium">{{ student.name }}</p>
                    <p class="text-sm text-green-700">{{ student.student_id }}</p>
                  </div>
                  <Badge variant="outline" class="bg-green-100 text-green-800">Present</Badge>
                </div>
                <div v-if="studentsPresent.length === 0" class="text-center py-8 text-muted-foreground">
                  <CheckCircle class="h-12 w-12 mx-auto mb-4 opacity-50" />
                  <p>No students marked present yet</p>
                  <p class="text-sm">Use the QR scanner to mark attendance</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Absent Students -->
          <Card>
            <CardHeader>
              <CardTitle class="text-red-600 flex items-center gap-2">
                <XCircle class="h-5 w-5" />
                Absent Students ({{ studentsAbsent.length }})
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-2 max-h-96 overflow-y-auto">
                <div 
                  v-for="student in studentsAbsent" 
                  :key="student.id"
                  class="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-lg"
                >
                  <div>
                    <p class="font-medium">{{ student.name }}</p>
                    <p class="text-sm text-red-700">{{ student.student_id }}</p>
                  </div>
                  <Badge variant="outline" class="bg-red-100 text-red-800">Absent</Badge>
                </div>
                <div v-if="studentsAbsent.length === 0" class="text-center py-8 text-muted-foreground">
                  <CheckCircle class="h-12 w-12 mx-auto mb-4 opacity-50" />
                  <p>All students are present!</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- QR Scanner -->
        <QrScanner 
          :show="showQRScanner"
          :session-id="session.id"
          @close="closeQRScanner"
          @scan-success="onScanSuccess"
        />

        <!-- QR Code Display Dialog -->
        <Dialog v-model:open="showQRCode">
          <DialogContent class="sm:max-w-md">
            <DialogHeader>
              <DialogTitle>QR Code for Attendance</DialogTitle>
              <DialogDescription>
                Students can scan this QR code to mark their attendance for this session
              </DialogDescription>
            </DialogHeader>
            <div class="flex flex-col items-center space-y-4">
              <div class="bg-white p-4 rounded-lg border-2 border-gray-200">
                <div class="w-48 h-48 flex items-center justify-center">
                  <img 
                    v-if="qrCodeDataUrl" 
                    :src="qrCodeDataUrl" 
                    alt="QR Code" 
                    class="w-full h-full object-contain"
                  />
                  <div v-else class="text-center">
                    <QrCode class="h-16 w-16 mx-auto mb-2 text-gray-400" />
                    <p class="text-sm text-gray-500">Generating QR Code...</p>
                  </div>
                </div>
              </div>
              <div class="text-center">
                <p class="text-sm font-medium">{{ session.session_name }}</p>
                <p class="text-xs text-muted-foreground">{{ session.class?.name }}</p>
                <p class="text-xs text-muted-foreground">Active Session</p>
              </div>
              <Button @click="closeQRCodeDisplay" variant="outline" class="w-full">
                Close
              </Button>
            </div>
          </DialogContent>
        </Dialog>

        <!-- Success/Error Messages -->
        <div v-if="scanSuccess || scanError" class="fixed bottom-4 right-4 z-50">
          <Alert v-if="scanSuccess" class="bg-green-50 border-green-200">
            <CheckCircle class="h-4 w-4 text-green-600" />
            <AlertDescription class="text-green-800">
              ✅ {{ scanSuccess.name }} marked as present!
            </AlertDescription>
          </Alert>
          
          <Alert v-if="scanError" class="bg-red-50 border-red-200">
            <XCircle class="h-4 w-4 text-red-600" />
            <AlertDescription class="text-red-800">
              {{ scanError }}
            </AlertDescription>
          </Alert>
        </div>

        <!-- Processing Overlay -->
        <div v-if="isProcessing" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white p-6 rounded-lg flex items-center gap-3">
            <Loader2 class="h-6 w-6 animate-spin" />
            <span>Processing attendance...</span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>