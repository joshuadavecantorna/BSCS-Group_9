<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import QrScanner from '@/components/QrScanner.vue';
import { ref, computed, onMounted } from 'vue';
import { QrCode, Users, Clock, CheckCircle, XCircle, Loader2, Eye, EyeOff, Hash, User, Camera, ScanLine } from 'lucide-vue-next';
import QRCodeLib from 'qrcode';

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
  qr_code: string;
}

interface ClassInfo {
  id: number;
  name: string;
  course: string;
  section: string;
  year: string;
  class_code: string;
}

interface Props {
  teacher: any;
  classInfo: ClassInfo;
  session: AttendanceSession;
  students: Student[];
  attendance_records: number[];
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
  { title: 'Classes', href: '/teacher/classes' },
  { title: 'Direct Attendance', href: '#' }
];

// State management
const showQRDialog = ref(false);
const showQRScanner = ref(false);
const qrCodeDataURL = ref('');
const isGeneratingQR = ref(false);
const attendanceStats = ref({
  present: 0,
  total: props.students.length
});

// Manual input form
const manualForm = useForm({
  student_id: '',
  name: '',
  status: 'present'
});

// Computed values
const presentStudents = computed(() => {
  return props.students.filter(student => 
    props.attendance_records.includes(student.id)
  );
});

const absentStudents = computed(() => {
  return props.students.filter(student => 
    !props.attendance_records.includes(student.id)
  );
});

// Update stats
const updateStats = () => {
  attendanceStats.value.present = presentStudents.value.length;
};

// Generate QR Code
const generateQRCode = async () => {
  if (!props.session?.qr_code) return;
  
  try {
    isGeneratingQR.value = true;
    const qrData = {
      session_id: props.session.id,
      class_id: props.classInfo.id,
      timestamp: Date.now()
    };
    
    qrCodeDataURL.value = await QRCodeLib.toDataURL(JSON.stringify(qrData), {
      width: 300,
      margin: 2,
      color: {
        dark: '#000000',
        light: '#FFFFFF'
      }
    });
  } catch (error) {
    console.error('Error generating QR code:', error);
  } finally {
    isGeneratingQR.value = false;
  }
};

// QR Scanner handlers
const openQRScanner = () => {
  showQRScanner.value = true;
};

const closeQRScanner = () => {
  showQRScanner.value = false;
};

const onScanSuccess = async (scannedStudent: any) => {
  // Individual scan success - no longer used for immediate marking
  // Students are now added to pending list and marked when scanning stops
};

const onBatchScanSuccess = async (scannedStudents: Student[]) => {
  try {
    // Mark all scanned students as present in batch
    const promises = scannedStudents.map(async (student) => {
      const response = await fetch(`/teacher/attendance/${props.session.id}/mark`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          student_id: student.student_id,
          status: 'present',
          method: 'qr'
        })
      });
      return response.json();
    });

    const results = await Promise.all(promises);
    const successfulMarks = results.filter(result => result.success).length;

    // Update attendance records for all successfully marked students
    scannedStudents.forEach(student => {
      const studentInClass = props.students.find(s => s.student_id === student.student_id);
      if (studentInClass && !props.attendance_records.includes(studentInClass.id)) {
        props.attendance_records.push(studentInClass.id);
      }
    });

    updateStats();

    if (successfulMarks > 0) {
      alert(`✅ ${successfulMarks} student${successfulMarks > 1 ? 's' : ''} marked as present`);
    } else {
      alert('❌ Failed to mark attendance for any students');
    }
  } catch (error) {
    console.error('Error marking batch attendance:', error);
    alert('Failed to mark attendance');
  }
};

// Manual input handlers
const markManualAttendance = async () => {
  if (!manualForm.student_id && !manualForm.name) {
    alert('Please enter student ID or name');
    return;
  }

  try {
    const response = await fetch(`/teacher/attendance/${props.session.id}/mark`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        student_id: manualForm.student_id,
        name: manualForm.name,
        status: manualForm.status,
        method: 'manual'
      })
    });

    const data = await response.json();
    
    if (data.success) {
      // Update attendance records - use the numeric student ID
      const studentId = data.student.id;
      if (!props.attendance_records.includes(studentId)) {
        props.attendance_records.push(studentId);
        updateStats();
      }

      alert(`✅ Attendance marked successfully`);
      manualForm.reset();
    } else {
      alert(`❌ ${data.message}`);
    }
  } catch (error) {
    console.error('Error marking attendance:', error);
    alert('Failed to mark attendance');
  }
};

// Initialize
onMounted(() => {
  updateStats();
  generateQRCode();
});
</script>

<template>
  <Head title="Direct Attendance" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">
      
      <!-- Header -->
      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Direct Attendance</h1>
            <p class="text-muted-foreground">
              {{ classInfo.name }} • {{ classInfo.course }} - {{ classInfo.section }}
            </p>
          </div>
          <Badge variant="default" class="text-sm">
            {{ attendanceStats.present }}/{{ attendanceStats.total }} Present
          </Badge>
        </div>
      </div>

      <!-- Attendance Methods -->
      <div class="grid gap-6 md:grid-cols-2">
        
        <!-- QR Code Method -->
        <Card>
          <CardHeader class="text-center">
            <QrCode class="h-12 w-12 mx-auto mb-2 text-primary" />
            <CardTitle>QR Code Attendance</CardTitle>
            <CardDescription>
              Students scan QR code with their phones to mark attendance
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="flex gap-2">
              <Button @click="showQRDialog = true" class="flex-1">
                <Eye class="h-4 w-4 mr-2" />
                Show QR Code
              </Button>
              <Button @click="openQRScanner" variant="outline" class="flex-1">
                <ScanLine class="h-4 w-4 mr-2" />
                Scan Student QR
              </Button>
            </div>
          </CardContent>
        </Card>

        <!-- Manual Input Method -->
        <Card>
          <CardHeader class="text-center">
            <User class="h-12 w-12 mx-auto mb-2 text-primary" />
            <CardTitle>Manual Input</CardTitle>
            <CardDescription>
              Manually enter student information to mark attendance
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label for="student_id">Student ID</Label>
              <Input
                id="student_id"
                v-model="manualForm.student_id"
                placeholder="Enter student ID"
              />
            </div>
            <div class="space-y-2">
              <Label for="name">Or Student Name</Label>
              <Input
                id="name"
                v-model="manualForm.name"
                placeholder="Enter student name"
              />
            </div>
            <div class="space-y-2">
              <Label for="status">Status</Label>
              <Select v-model="manualForm.status">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="present">Present</SelectItem>
                  <SelectItem value="late">Late</SelectItem>
                  <SelectItem value="absent">Absent</SelectItem>
                  <SelectItem value="excused">Excused</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <Button @click="markManualAttendance" class="w-full" :disabled="manualForm.processing">
              <CheckCircle class="h-4 w-4 mr-2" />
              Mark Attendance
            </Button>
          </CardContent>
        </Card>
      </div>

      <!-- Attendance Summary -->
      <div class="grid gap-6 lg:grid-cols-2">
        
        <!-- Present Students -->
        <Card class="h-fit">
          <CardHeader class="pb-3">
            <CardTitle class="flex items-center gap-2 text-green-700">
              <CheckCircle class="h-5 w-5" />
              Present Students ({{ presentStudents.length }})
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3 max-h-80 overflow-y-auto">
              <div v-for="student in presentStudents" :key="student.id" 
                   class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-900 truncate">{{ student.name }}</p>
                  <p class="text-sm text-green-700 font-mono">{{ student.student_id }}</p>
                </div>
                <Badge variant="secondary" class="bg-green-100 text-green-800 border-green-200 shrink-0">
                  Present
                </Badge>
              </div>
              <div v-if="presentStudents.length === 0" class="text-center py-8 text-muted-foreground">
                <CheckCircle class="h-12 w-12 mx-auto mb-3 opacity-30" />
                <p class="font-medium">No students marked present yet</p>
                <p class="text-sm">Use the QR scanner to mark attendance</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Absent Students -->
        <Card class="h-fit">
          <CardHeader class="pb-3">
            <CardTitle class="flex items-center gap-2 text-red-700">
              <XCircle class="h-5 w-5" />
              Absent Students ({{ absentStudents.length }})
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3 max-h-80 overflow-y-auto">
              <div v-for="student in absentStudents" :key="student.id" 
                   class="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-900 truncate">{{ student.name }}</p>
                  <p class="text-sm text-red-700 font-mono">{{ student.student_id }}</p>
                </div>
                <Badge variant="secondary" class="bg-red-100 text-red-800 border-red-200 shrink-0">
                  Absent
                </Badge>
              </div>
              <div v-if="absentStudents.length === 0" class="text-center py-8 text-muted-foreground">
                <XCircle class="h-12 w-12 mx-auto mb-3 opacity-30" />
                <p class="font-medium">All students are present!</p>
                <p class="text-sm">Great job on attendance</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- QR Code Display Dialog -->
    <Dialog v-model:open="showQRDialog">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>Attendance QR Code</DialogTitle>
          <DialogDescription>
            Students can scan this QR code to mark their attendance
          </DialogDescription>
        </DialogHeader>

        <div v-if="isGeneratingQR" class="flex items-center justify-center py-8">
          <Loader2 class="h-8 w-8 animate-spin" />
        </div>

        <div v-else-if="qrCodeDataURL" class="space-y-6">
          <!-- Session Info -->
          <div class="text-center">
            <h3 class="font-semibold text-lg">{{ classInfo.name }}</h3>
            <p class="text-sm text-gray-600">{{ classInfo.section }} • {{ classInfo.course }}</p>
            <Badge variant="default" class="mt-2">{{ session.status }}</Badge>
          </div>

          <!-- QR Code Display -->
          <div class="flex flex-col items-center space-y-4">
            <div class="w-64 h-64 bg-white border-2 border-gray-200 rounded-lg flex items-center justify-center">
              <img :src="qrCodeDataURL" alt="Attendance QR Code" class="w-full h-full object-contain" />
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
            <div class="grid grid-cols-2 gap-4 text-center">
              <div>
                <div class="text-2xl font-bold text-green-600">{{ attendanceStats.present }}</div>
                <div class="text-sm text-gray-600">Present</div>
              </div>
              <div>
                <div class="text-2xl font-bold text-gray-600">{{ attendanceStats.total - attendanceStats.present }}</div>
                <div class="text-sm text-gray-600">Absent</div>
              </div>
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <!-- QR Scanner Component -->
    <QrScanner
      :show="showQRScanner"
      @close="closeQRScanner"
      @scan-success="onScanSuccess"
      @batch-scan-success="onBatchScanSuccess"
    />
  </AppLayout>
</template>