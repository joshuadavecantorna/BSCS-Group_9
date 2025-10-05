<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { QrCode, Clock, CheckCircle, XCircle, AlertCircle, Camera } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { QrcodeStream } from 'vue-qrcode-reader';

// Props from the StudentController
interface Props {
  student: {
    id: number;
    name: string;
    student_id: string;
    email: string;
    course: string;
    year: string;
    section: string;
  };
  stats: {
    totalClasses: number;
    attendanceRate: number;
    presentCount: number;
    totalAttendance: number;
  };
  classes: any[];
  upcomingClasses: any[];
  recentRequests: any[];
  recentAttendance: any[];
}

const props = defineProps<Props>();

const page = usePage();
const user = computed(() => page.props.auth.user);

// Example breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/student/dashboard' }
];

// Dialog states
const showQRDialog = ref(false);
const showClassIDDialog = ref(false);
const selectedClass = ref('');
const manualClassID = ref('');
const camera = ref<'auto' | 'off'>('off');
const qrError = ref('');
const isProcessing = ref(false);

// Use backend data with fallbacks
const attendanceStats = computed(() => {
  const stats = props.stats;
  if (!stats) {
    return {
      presentPercentage: 0,
      absentPercentage: 0,
      excusedPercentage: 0,
      totalClasses: 0,
      presentCount: 0,
      absentCount: 0,
      excusedCount: 0
    };
  }
  
  const total = stats.totalAttendance || 1; // Avoid division by zero
  const absentCount = stats.totalAttendance - stats.presentCount;
  return {
    presentPercentage: stats.attendanceRate,
    absentPercentage: Math.round((absentCount / total) * 100),
    excusedPercentage: 0, // We don't have excused count in current stats
    totalClasses: stats.totalClasses,
    presentCount: stats.presentCount,
    absentCount: absentCount,
    excusedCount: 0
  };
});

// Use backend upcoming classes data
const upcomingClasses = computed(() => {
  return props.upcomingClasses || [];
});

// Recent attendance from backend
const recentAttendance = computed(() => props.recentAttendance ?? []);

// Student's enrolled classes for selection
const enrolledClasses = computed(() => {
  return props.classes || [];
});

const openQRScanner = () => {
  if (enrolledClasses.value.length === 0) {
    alert('You are not enrolled in any classes.');
    return;
  }
  showQRDialog.value = true;
  camera.value = 'auto';
  qrError.value = '';
};

// Watch for dialog close to turn off camera
watch(showQRDialog, (newVal) => {
  if (!newVal) {
    camera.value = 'off';
    selectedClass.value = '';
    qrError.value = '';
  }
});

const onCameraError = (error: any) => {
  console.error('Camera error:', error);
  qrError.value = 'Unable to access camera. Please check permissions.';
};

const onQRDetect = (detectedCodes: any[]) => {
  if (detectedCodes.length > 0) {
    const qrData = detectedCodes[0].rawValue;
    onQRScan(qrData);
  }
};

const openClassIDEntry = () => {
  if (enrolledClasses.value.length === 0) {
    alert('You are not enrolled in any classes.');
    return;
  }
  showClassIDDialog.value = true;
};

const onQRScan = async (qrData: string) => {
  if (!selectedClass.value) {
    alert('Please select a class first.');
    return;
  }

  isProcessing.value = true;
  
  try {
    // Parse QR data - expect name,course format
    const parts = qrData.trim().split(',').map(part => part.trim());
    
    // Verify QR contains name and course
    if (parts.length < 2) {
      throw new Error('Invalid QR code format. Expected: "Name,Course"');
    }
    
    const scannedName = parts[0];
    const scannedCourse = parts[1];
    
    // Verify this matches current student's info
    if (!props.student) {
      throw new Error('Student information not available');
    }
    
    // Basic name matching (normalize spaces and case)
    const normalizedStudentName = props.student.name.toLowerCase().replace(/\s+/g, ' ').trim();
    const normalizedScannedName = scannedName.toLowerCase().replace(/\s+/g, ' ').trim();
    
    // Check if names match (allow partial matching for longer names)
    const nameMatches = normalizedStudentName.includes(normalizedScannedName) || 
                       normalizedScannedName.includes(normalizedStudentName);
    
    if (!nameMatches) {
      throw new Error(`QR code name "${scannedName}" does not match your profile name "${props.student.name}"`);
    }
    
    // Basic course matching
    const normalizedStudentCourse = (props.student.course || '').toLowerCase().replace(/\s+/g, '');
    const normalizedScannedCourse = scannedCourse.toLowerCase().replace(/\s+/g, '');
    
    if (normalizedStudentCourse && !normalizedStudentCourse.includes(normalizedScannedCourse.replace('bs', '')) && 
        !normalizedScannedCourse.includes(normalizedStudentCourse.replace('bachelor', '').replace('science', ''))) {
      throw new Error(`QR code course "${scannedCourse}" does not match your profile course "${props.student.course}"`);
    }

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
      throw new Error('CSRF token not found. Please refresh the page.');
    }

    console.log('QR Data verified - Name:', scannedName, 'Course:', scannedCourse);
    console.log('Marking attendance for class:', selectedClass.value);

    const response = await fetch('/student/self-checkin', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        class_id: selectedClass.value,
        name: scannedName,
        course: scannedCourse
      })
    });

    // Check if response is ok
    if (!response.ok) {
      const errorData = await response.json().catch(() => ({ message: 'Failed to mark attendance' }));
      console.error('Error response:', errorData);
      throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    console.log('Success response:', data);
    
    if (data.success) {
      alert(`Success! You have been marked as present.`);
      showQRDialog.value = false;
      selectedClass.value = '';
      // Refresh page to show updated attendance
      router.reload();
    } else {
      alert(data.message || 'Failed to mark attendance');
    }
  } catch (error: any) {
    console.error('Check-in error:', error);
    qrError.value = error.message || 'Failed to process check-in. Please try again.';
  } finally {
    isProcessing.value = false;
  }
};

const submitClassID = async () => {
  if (!selectedClass.value) {
    alert('Please select a class.');
    return;
  }
  
  if (!manualClassID.value) {
    alert('Please enter a session ID.');
    return;
  }

  isProcessing.value = true;
  
  try {
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (!csrfToken) {
      throw new Error('CSRF token not found');
    }

    const response = await fetch('/student/quick-checkin', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        method: 'manual',
        qr_code: manualClassID.value,
        class_id: selectedClass.value
      })
    });

    // Check if response is ok
    if (!response.ok) {
      const errorData = await response.json().catch(() => ({ message: 'Failed to mark attendance' }));
      throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    
    if (data.success) {
      alert(`Success! ${data.message}`);
      showClassIDDialog.value = false;
      selectedClass.value = '';
      manualClassID.value = '';
      // Refresh page to show updated attendance
      router.reload();
    } else {
      alert(data.message || 'Failed to mark attendance');
    }
  } catch (error: any) {
    console.error('Check-in error:', error);
    alert(error.message || 'Failed to process check-in. Please try again.');
  } finally {
    isProcessing.value = false;
  }
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'present': return 'bg-green-500';
    case 'absent': return 'bg-red-500';
    case 'excused': return 'bg-yellow-500';
    default: return 'bg-gray-500';
  }
};

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'present': return CheckCircle;
    case 'absent': return XCircle;
    case 'excused': return AlertCircle;
    default: return Clock;
  }
};
</script>

<template>
  <Head title="Student Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <!-- Greeting -->
      <div>
        <h1 class="text-3xl font-bold">Welcome back, <span class="text-blue-600">{{ props.student?.name || user.name }}</span> 🎓</h1>
        <p class="text-muted-foreground">
          Student ID: {{ props.student?.student_id || 'Not available' }} • 
          Course: {{ props.student?.course || 'Not specified' }}
        </p>
        <p class="text-sm text-muted-foreground mt-1">Here's your attendance overview and upcoming classes</p>
      </div>

      <!-- Quick Stats Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <!-- Present Percentage -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Present %</CardTitle>
            <CheckCircle class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ attendanceStats.presentPercentage }}%</div>
            <p class="text-xs text-muted-foreground">{{ attendanceStats.presentCount }} of {{ attendanceStats.totalClasses }} sessions</p>
          </CardContent>
        </Card>

        <!-- Absent Percentage -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Absent %</CardTitle>
            <XCircle class="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ attendanceStats.absentPercentage }}%</div>
            <p class="text-xs text-muted-foreground">{{ attendanceStats.absentCount }} of {{ attendanceStats.totalClasses }} sessions</p>
          </CardContent>
        </Card>

        <!-- Excused Percentage -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Excused %</CardTitle>
            <AlertCircle class="h-4 w-4 text-yellow-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-yellow-600">{{ attendanceStats.excusedPercentage }}%</div>
            <p class="text-xs text-muted-foreground">{{ attendanceStats.excusedCount }} of {{ attendanceStats.totalClasses }} sessions</p>
          </CardContent>
        </Card>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <!-- Student QR Code Info -->
        <Card class="border-blue-200 bg-blue-50 dark:bg-blue-950/20">
          <CardHeader>
            <CardTitle class="text-blue-900 dark:text-blue-100">Your QR Code Format</CardTitle>
            <CardDescription class="text-blue-700 dark:text-blue-300">
              Generate a QR code with this exact text to use for self check-in
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div class="bg-white dark:bg-gray-800 rounded-md p-3 font-mono text-sm border">
              {{ (student?.name || 'Your Name') }},{{ (student?.course || 'Your Course') }}
            </div>
            <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">
              Example: "Juan Dela Cruz,BSCS" or "Maria Santos,BSIT"
            </p>
          </CardContent>
        </Card>

        <!-- Check-In Section -->
        <Card>
          <CardHeader>
            <CardTitle>Self Check-In</CardTitle>
            <CardDescription>Use your personal QR code (name,course format) to mark attendance</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <Button @click="openQRScanner" class="w-full" size="lg">
              <QrCode class="mr-2 h-5 w-5" />
              Scan Your QR Code
            </Button>
            <div class="text-center text-sm text-muted-foreground">
              or
            </div>
            <Button @click="openClassIDEntry" variant="outline" class="w-full" size="lg">
              <Camera class="mr-2 h-5 w-5" />
              Enter Session ID
            </Button>
          </CardContent>
        </Card>

        <!-- Upcoming Classes -->
        <Card>
          <CardHeader>
            <CardTitle>Upcoming Classes</CardTitle>
            <CardDescription>Your schedule for today and tomorrow</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-if="upcomingClasses.length > 0">
                <div v-for="classItem in upcomingClasses.slice(0, 4)" :key="classItem.class_name" 
                     class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50 transition-colors">
                  <div class="space-y-1">
                    <p class="font-medium">{{ classItem.class_name }}</p>
                    <p class="text-sm text-muted-foreground">{{ classItem.teacher }}</p>
                    <p class="text-sm text-muted-foreground">Room {{ classItem.room }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-sm font-medium">{{ classItem.time }}</p>
                    <p class="text-xs text-muted-foreground">{{ classItem.date }}</p>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-6 text-muted-foreground">
                <p>No upcoming classes scheduled</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Recent Attendance History -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Attendance</CardTitle>
          <CardDescription>Your recent attendance history</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div v-for="(record, i) in recentAttendance" :key="i" 
                 class="flex items-center justify-between p-3 border rounded-lg">
              <div class="flex items-center space-x-3">
                <div :class="['w-2 h-2 rounded-full', getStatusColor(record.status)]"></div>
                <div>
                  <p class="font-medium">{{ record.class_name }}</p>
                  <p class="text-sm text-muted-foreground">{{ record.session_date }}</p>
                </div>
              </div>
              <Badge :variant="record.status === 'present' ? 'default' : record.status === 'excused' ? 'secondary' : 'destructive'">
                <component :is="getStatusIcon(record.status)" class="w-3 h-3 mr-1" />
                {{ record.status.charAt(0).toUpperCase() + record.status.slice(1) }}
              </Badge>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- QR Scanner Dialog -->
    <Dialog v-model:open="showQRDialog">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Self Check-in with Your QR Code</DialogTitle>
          <DialogDescription>
            Select your class, then scan your personal QR code containing your name and course (e.g., "Juan Dela Cruz,BSCS")
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <div class="space-y-2">
            <Label for="qr-class">Select Class</Label>
            <Select v-model="selectedClass">
              <SelectTrigger id="qr-class">
                <SelectValue placeholder="Choose a class" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="cls in enrolledClasses" :key="cls.id" :value="String(cls.id)">
                  {{ cls.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          
          <div v-if="selectedClass" class="border rounded-lg overflow-hidden bg-muted/50">
            <p class="text-sm text-center p-2">Scan your personal QR code with your name and course</p>
            <div class="relative aspect-square bg-black">
              <QrcodeStream 
                v-if="camera === 'auto'"
                :camera="camera"
                @detect="onQRDetect"
                @error="onCameraError"
                class="w-full h-full object-cover"
              />
              <div v-if="qrError" class="absolute inset-0 flex items-center justify-center bg-black/50 text-white text-center p-4">
                <div>
                  <AlertCircle class="h-8 w-8 mx-auto mb-2" />
                  <p class="text-sm">{{ qrError }}</p>
                </div>
              </div>
              <div v-if="isProcessing" class="absolute inset-0 flex items-center justify-center bg-black/50 text-white">
                <div class="text-center">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-white mx-auto mb-2"></div>
                  <p class="text-sm">Processing...</p>
                </div>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-8 text-muted-foreground">
            <QrCode class="h-12 w-12 mx-auto mb-2 opacity-50" />
            <p>Select a class to start scanning</p>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Manual Class ID Dialog -->
    <Dialog v-model:open="showClassIDDialog">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Enter Session ID</DialogTitle>
          <DialogDescription>
            Select your class and enter the session ID provided by your teacher
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <div class="space-y-2">
            <Label for="manual-class">Select Class</Label>
            <Select v-model="selectedClass">
              <SelectTrigger id="manual-class">
                <SelectValue placeholder="Choose a class" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="cls in enrolledClasses" :key="cls.id" :value="String(cls.id)">
                  {{ cls.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          
          <div class="space-y-2">
            <Label for="session-id">Session ID</Label>
            <Input 
              id="session-id"
              v-model="manualClassID" 
              type="text" 
              placeholder="Enter session ID"
              :disabled="!selectedClass || isProcessing"
            />
          </div>
          
          <Button 
            @click="submitClassID" 
            class="w-full"
            :disabled="!selectedClass || !manualClassID || isProcessing"
          >
            {{ isProcessing ? 'Processing...' : 'Submit Attendance' }}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
