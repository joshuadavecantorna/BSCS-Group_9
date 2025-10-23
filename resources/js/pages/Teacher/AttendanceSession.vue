<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import QrScanner from '@/components/QrScanner.vue';
import { ref, computed } from 'vue';
import { QrCode, Users, Clock, CheckCircle, XCircle, Loader2, Eye, EyeOff, User, Upload, FileText, Hash, ScanLine } from 'lucide-vue-next';
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
const activeTab = ref('qr-code');

// Manual input form
const manualForm = useForm({
  student_id: '',
  name: '',
  status: 'present',
  method: 'manual'
});

// File upload state
const selectedFiles = ref<File[]>([]);
const fileUploadProgress = ref(0);
const fileUploadError = ref('');
const isUploading = ref(false);
const uploadSuccess = ref('');
const fileInputRef = ref<HTMLInputElement | null>(null);

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
    // Get fresh CSRF token
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    let csrfToken = csrfTokenMeta?.getAttribute('content');
    
    // If no token found, try to refresh it
    if (!csrfToken) {
      console.warn('CSRF token not found, attempting to refresh...');
      // Try to get a fresh token by making a simple request
      const tokenResponse = await fetch(window.location.href, {
        method: 'GET',
        headers: { 'Accept': 'text/html' }
      });
      
      if (tokenResponse.ok) {
        const html = await tokenResponse.text();
        const tokenMatch = html.match(/name="csrf-token" content="([^"]*)"/);//
        csrfToken = tokenMatch ? tokenMatch[1] : null;
      }
    }

    if (!csrfToken) {
      throw new Error('Unable to get CSRF token. Please refresh the page.');
    }

    console.log('Making QR scan request with token:', !!csrfToken);

    const response = await fetch(`/teacher/attendance/${props.session.id}/qr-scan`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        qr_data: JSON.stringify(studentData)
      })
    });

    console.log('Response status:', response.status);
    console.log('Response headers:', Object.fromEntries(response.headers.entries()));

    if (!response.ok) {
      let errorMessage = `HTTP ${response.status}`;
      try {
        const errorData = await response.json();
        errorMessage = errorData.message || errorMessage;
      } catch {
        const errorText = await response.text();
        console.error('Error response text:', errorText);
        errorMessage = response.status === 419 ? 'CSRF token expired. Please refresh the page.' : errorMessage;
      }
      throw new Error(errorMessage);
    }

    const result = await response.json();
    console.log('QR scan result:', result);

    if (result.success) {
      scanSuccess.value = result.student;
      scanError.value = '';
      
      // Update the attendance records
      if (result.student?.id && !props.attendance_records.includes(result.student.id)) {
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
    scanError.value = error instanceof Error ? error.message : 'Network error. Please try again.';
  } finally {
    isProcessing.value = false;
  }
};

// Manual input handlers
const markManualAttendance = async () => {
  if (!manualForm.student_id && !manualForm.name) {
    scanError.value = 'Please enter student ID or name';
    return;
  }

  isProcessing.value = true;
  scanError.value = '';
  scanSuccess.value = null;

  try {
    let studentIdToSend = '';
    let targetStudent = null;
    
    if (manualForm.student_id) {
      // Use the student_id directly (e.g., "23-62514")
      studentIdToSend = manualForm.student_id;
      targetStudent = props.students.find(s => s.student_id === manualForm.student_id);
    } else if (manualForm.name) {
      // Find by name and get their student_id
      targetStudent = props.students.find(s => 
        s.name.toLowerCase().includes(manualForm.name.toLowerCase())
      );
      if (targetStudent) {
        studentIdToSend = targetStudent.student_id;
      }
    }
    
    if (!studentIdToSend) {
      scanError.value = 'Student not found in this class';
      return;
    }

    const response = await fetch(`/teacher/attendance/${props.session.id}/mark`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        student_id: studentIdToSend, // Send the actual student_id like "23-62514"
        status: manualForm.status,
        method: manualForm.method
      })
    });

    const data = await response.json();
    
    if (data.success) {
      // Update attendance records using the student data from response
      const studentFromResponse = data.student;
      if (studentFromResponse && !props.attendance_records.includes(studentFromResponse.id)) {
        props.attendance_records.push(studentFromResponse.id);
      }
      
      scanSuccess.value = studentFromResponse || targetStudent || { name: manualForm.name || manualForm.student_id };
      manualForm.reset();
      
      // Reset form
      setTimeout(() => {
        scanSuccess.value = null;
      }, 3000);
    } else {
      scanError.value = data.message || 'Failed to mark attendance';
    }
  } catch (error) {
    console.error('Error marking attendance:', error);
    scanError.value = 'Network error. Please try again.';
  } finally {
    isProcessing.value = false;
  }
};

// File upload handlers
const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    selectedFiles.value = Array.from(target.files);
  }
};

const uploadAttendanceFile = async () => {
  if (selectedFiles.value.length === 0) {
    fileUploadError.value = 'Please select a file to upload';
    return;
  }

  isUploading.value = true;
  fileUploadProgress.value = 0;
  fileUploadError.value = '';
  uploadSuccess.value = '';

  try {
    const formData = new FormData();
    formData.append('session_id', String(props.session.id));
    selectedFiles.value.forEach(file => {
      formData.append('file', file);
    });

    // Simulate upload progress
    const progressInterval = setInterval(() => {
      if (fileUploadProgress.value < 90) {
        fileUploadProgress.value += Math.random() * 10;
      }
    }, 200);

    const response = await fetch(`/teacher/attendance/${props.session.id}/upload`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: formData
    });

    clearInterval(progressInterval);
    fileUploadProgress.value = 100;

    const data = await response.json();
    
    if (data.success) {
      uploadSuccess.value = `${data.processed || 0} attendance records processed successfully.`;
      
      // Update attendance records with newly marked students
      if (data.records && Array.isArray(data.records)) {
        data.records.forEach((record: any) => {
          if (!props.attendance_records.includes(record.student_id)) {
            props.attendance_records.push(record.student_id);
          }
        });
      }
      
      // Reset file input
      selectedFiles.value = [];
      
      setTimeout(() => {
        fileUploadProgress.value = 0;
        uploadSuccess.value = '';
      }, 5000);
    } else {
      fileUploadError.value = data.message || 'Failed to process attendance file';
    }
  } catch (error) {
    console.error('Error uploading attendance file:', error);
    fileUploadError.value = 'Network error. Please try again.';
  } finally {
    isUploading.value = false;
  }
};

// End session
const endSessionForm = useForm({});

const endSession = () => {
  endSessionForm.put(`/teacher/attendance/sessions/${props.session.id}/end`, {
    onSuccess: () => {
      // Server will redirect automatically, no need for manual redirect
      console.log('Session ended successfully');
    },
    onError: (errors: any) => {
      console.error('Error ending session:', errors);
      alert('Failed to end session: ' + (errors.message || 'Unknown error'));
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
        
        <!-- Integrated Attendance Management -->
        <Card class="mb-6">
          <CardHeader>
            <CardTitle>Attendance Management</CardTitle>
            <CardDescription>
              Mark attendance using any of the available methods
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Tabs v-model:value="activeTab" class="w-full">
              <TabsList class="grid grid-cols-3 mb-4">
                <TabsTrigger value="qr-code" class="flex items-center gap-2">
                  <QrCode class="h-4 w-4" />
                  <span>QR Scanner</span>
                </TabsTrigger>
                <TabsTrigger value="manual-input" class="flex items-center gap-2">
                  <User class="h-4 w-4" />
                  <span>Manual Input</span>
                </TabsTrigger>
                <TabsTrigger value="file-upload" class="flex items-center gap-2">
                  <Upload class="h-4 w-4" />
                  <span>File Upload</span>
                </TabsTrigger>
              </TabsList>
              
              <!-- QR Scanner Tab -->
              <TabsContent value="qr-code" class="space-y-4">
                <div class="text-center">
                  <Button @click="openQRScanner" size="lg" class="flex items-center gap-2">
                    <ScanLine class="h-5 w-5" />
                    Open QR Scanner
                  </Button>
                  <p class="text-sm text-gray-500 mt-2">
                    Scan student QR codes to mark attendance
                  </p>
                </div>
              </TabsContent>
              
              <!-- Manual Input Tab -->
              <TabsContent value="manual-input" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
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
                <Button 
                  @click="markManualAttendance" 
                  class="w-full" 
                  :disabled="manualForm.processing || isProcessing"
                >
                  <CheckCircle class="h-4 w-4 mr-2" />
                  Mark Attendance
                </Button>
              </TabsContent>
              
              <!-- File Upload Tab -->
              <TabsContent value="file-upload" class="space-y-4">
                <div class="space-y-2">
                  <Label for="file-input">Upload Attendance File</Label>
                  <div class="mt-1">
                    <input
                      ref="fileInputRef"
                      type="file"
                      @change="handleFileSelect"
                      class="hidden"
                      accept=".csv,.xlsx,.xls"
                    />
                    <div
                      @click="fileInputRef?.click()"
                      class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-gray-400 transition-colors"
                    >
                      <Upload class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                      <p class="text-sm text-gray-600 mb-2">
                        <span class="font-medium">Click to upload</span> or drag and drop
                      </p>
                      <p class="text-xs text-gray-500">
                        CSV or Excel file with student information
                      </p>
                    </div>
                  </div>
                </div>
                
                <!-- Selected File -->
                <div v-if="selectedFiles.length > 0" class="space-y-2">
                  <div v-for="(file, index) in selectedFiles" :key="index"
                       class="flex items-center justify-between p-2 bg-gray-50 rounded">
                    <div class="flex items-center space-x-2 flex-1 min-w-0">
                      <FileText class="h-4 w-4 text-gray-500 flex-shrink-0" />
                      <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium truncate">{{ file.name }}</p>
                        <p class="text-xs text-gray-500">{{ file.size }} bytes</p>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Upload Progress -->
                  <div v-if="isUploading" class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                      <span>Uploading...</span>
                      <span>{{ Math.round(fileUploadProgress) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                      <div 
                        class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                        :style="{ width: `${fileUploadProgress}%` }"
                      ></div>
                    </div>
                  </div>
                  
                  <Button 
                    @click="uploadAttendanceFile" 
                    class="w-full"
                    :disabled="isUploading || selectedFiles.length === 0"
                  >
                    <Loader2 v-if="isUploading" class="h-4 w-4 mr-2 animate-spin" />
                    <Upload v-else class="h-4 w-4 mr-2" />
                    {{ isUploading ? 'Uploading...' : 'Upload & Process' }}
                  </Button>
                </div>
                
                <!-- Upload Messages -->
                <div v-if="fileUploadError" class="p-3 bg-red-50 border border-red-200 rounded-md">
                  <p class="text-sm text-red-600">{{ fileUploadError }}</p>
                </div>
                <div v-if="uploadSuccess" class="p-3 bg-green-50 border border-green-200 rounded-md">
                  <p class="text-sm text-green-600">{{ uploadSuccess }}</p>
                </div>
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>

        <!-- Student Lists -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Present Students -->
          <Card class="h-fit">
            <CardHeader class="pb-3">
              <CardTitle class="text-green-700 flex items-center gap-2">
                <CheckCircle class="h-5 w-5" />
                Present Students ({{ studentsPresent.length }})
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-3 max-h-96 overflow-y-auto">
                <div 
                  v-for="student in studentsPresent" 
                  :key="student.id"
                  class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors"
                >
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 truncate">{{ student.name }}</p>
                    <p class="text-sm text-green-700 font-mono">{{ student.student_id }}</p>
                  </div>
                  <Badge variant="secondary" class="bg-green-100 text-green-800 border-green-200 shrink-0">
                    Present
                  </Badge>
                </div>
                <div v-if="studentsPresent.length === 0" class="text-center py-8 text-muted-foreground">
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
              <CardTitle class="text-red-700 flex items-center gap-2">
                <XCircle class="h-5 w-5" />
                Absent Students ({{ studentsAbsent.length }})
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-3 max-h-96 overflow-y-auto">
                <div 
                  v-for="student in studentsAbsent" 
                  :key="student.id"
                  class="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors"
                >
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 truncate">{{ student.name }}</p>
                    <p class="text-sm text-red-700 font-mono">{{ student.student_id }}</p>
                  </div>
                  <Badge variant="secondary" class="bg-red-100 text-red-800 border-red-200 shrink-0">
                    Absent
                  </Badge>
                </div>
                <div v-if="studentsAbsent.length === 0" class="text-center py-8 text-muted-foreground">
                  <CheckCircle class="h-12 w-12 mx-auto mb-3 opacity-30" />
                  <p class="font-medium">All students are present!</p>
                  <p class="text-sm">Great job on attendance</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- QR Scanner Component -->
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