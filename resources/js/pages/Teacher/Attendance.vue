<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import QrScanner from '@/components/QrScanner.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useForm } from '@inertiajs/vue3';
import { Plus, Clock, Users, Settings } from 'lucide-vue-next';

interface Props {
  teacher: {
    id: number;
    first_name: string;
    last_name: string;
    department: string;
    position: string;
    email: string;
  };
  classes: Array<{
    id: number;
    class_name: string;
    course_code: string;
    student_count: number;
  }>;
  recent_sessions: Array<{
    id: number;
    class_name: string;
    course_code: string;
    date: string;
    time: string;
    present_count: number;
    total_count: number;
    status: string;
  }>;
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
  { title: 'Attendance', href: '/teacher/attendance' }
];

// QR Scanner state
const showQRScanner = ref(false);
const showCreateSessionDialog = ref(false);
const showManageSessionDialog = ref(false);
const selectedSession = ref<any>(null);

// Create attendance session form
const createSessionForm = useForm({
  class_id: '',
  session_name: '',
  duration: 60,
  location_required: false,
  qr_enabled: true,
  notes: ''
});

// Manage attendance form
const manageAttendanceForm = useForm({
  attendance_records: [] as Array<{
    student_id: number;
    status: 'present' | 'absent' | 'late' | 'excused';
  }>
});

const openQRScanner = () => {
  showQRScanner.value = true;
};

const closeQRScanner = () => {
  showQRScanner.value = false;
};

const onScanSuccess = (studentData: any) => {
  console.log('Student scanned successfully:', studentData);
  alert(`Successfully scanned: ${studentData.name} (${studentData.student_id})`);
  closeQRScanner();
};

// Create attendance session
const createAttendanceSession = () => {
  createSessionForm.post('/teacher/attendance/sessions', {
    onSuccess: () => {
      showCreateSessionDialog.value = false;
      createSessionForm.reset();
    }
  });
};

// Start quick attendance session
const startQuickSession = async (classId: number) => {
  try {
    createSessionForm.processing = true;
    
    const response = await fetch('/teacher/attendance/quick-start', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ class_id: classId })
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({ message: 'Failed to start session' }));
      throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    
    if (data.success) {
      // Redirect to the session page with QR scanner
      window.location.href = `/teacher/attendance/session/${data.session.id}`;
    } else {
      throw new Error(data.message || 'Failed to start session');
    }
  } catch (error) {
    console.error('Error starting quick session:', error);
    alert(error instanceof Error ? error.message : 'Failed to start session');
  } finally {
    createSessionForm.processing = false;
  }
};

// Open session management
const manageSession = (session: any) => {
  selectedSession.value = session;
  showManageSessionDialog.value = true;
  // Load attendance records for this session
  // This would typically be loaded from the backend
};

// Update attendance records
const updateAttendance = () => {
  if (!selectedSession.value) return;
  
  manageAttendanceForm.put(`/teacher/attendance/sessions/${selectedSession.value.id}/records`, {
    onSuccess: () => {
      showManageSessionDialog.value = false;
      selectedSession.value = null;
    }
  });
};
</script>

<template>
  <Head title="Teacher Attendance" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">
      
      <!-- Header Section -->
      <div class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight">Attendance Management</h1>
        <p class="text-muted-foreground">
          Take attendance for your classes using various methods
        </p>
      </div>

      <!-- Attendance Methods -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="openQRScanner">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-6xl">📱</div>
            <CardTitle>QR Code Attendance</CardTitle>
            <CardDescription>Students scan QR code to mark attendance</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button class="w-full">Start QR Attendance</Button>
          </CardContent>
        </Card>

        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="$inertia.visit('/teacher/attendance/manual')">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-6xl">✏️</div>
            <CardTitle>Manual Attendance</CardTitle>
            <CardDescription>Manually mark attendance for students</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button variant="outline" class="w-full">Manual Entry</Button>
          </CardContent>
        </Card>

        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="$inertia.visit('/teacher/attendance/upload')">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-6xl">📊</div>
            <CardTitle>Upload Attendance</CardTitle>
            <CardDescription>Upload attendance data from file</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button variant="outline" class="w-full">Upload File</Button>
          </CardContent>
        </Card>
      </div>

      <!-- Classes Section -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="flex items-center gap-2">
                <Settings class="h-5 w-5" />
                Attendance Sessions
              </CardTitle>
              <CardDescription>Create and manage attendance sessions for your classes</CardDescription>
            </div>
            <Dialog v-model:open="showCreateSessionDialog">
              <DialogTrigger as-child>
                <Button class="flex items-center gap-2">
                  <Plus class="h-4 w-4" />
                  New Session
                </Button>
              </DialogTrigger>
              <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                  <DialogTitle>Create Attendance Session</DialogTitle>
                  <DialogDescription>Set up a new attendance session for one of your classes.</DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                  <div class="grid gap-2">
                    <Label for="class">Select Class</Label>
                    <Select v-model="createSessionForm.class_id">
                      <SelectTrigger>
                        <SelectValue placeholder="Choose a class" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="cls in props.classes" :key="cls.id" :value="cls.id.toString()">
                          {{ cls.course_code }} - {{ cls.class_name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div class="grid gap-2">
                    <Label for="session_name">Session Name</Label>
                    <Input
                      id="session_name"
                      v-model="createSessionForm.session_name"
                      placeholder="e.g., Midterm Attendance"
                    />
                  </div>
                  <div class="grid gap-2">
                    <Label for="duration">Duration (minutes)</Label>
                    <Input
                      id="duration"
                      type="number"
                      v-model="createSessionForm.duration"
                      min="5"
                      max="240"
                    />
                  </div>
                  <div class="grid gap-2">
                    <Label for="notes">Notes (optional)</Label>
                    <Input
                      id="notes"
                      v-model="createSessionForm.notes"
                      placeholder="Any additional notes..."
                    />
                  </div>
                </div>
                <DialogFooter>
                  <Button variant="outline" @click="showCreateSessionDialog = false">Cancel</Button>
                  <Button @click="createAttendanceSession" :disabled="createSessionForm.processing">
                    {{ createSessionForm.processing ? 'Creating...' : 'Create Session' }}
                  </Button>
                </DialogFooter>
              </DialogContent>
            </Dialog>
          </div>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-2">
            <div v-for="cls in props.classes" :key="cls.id" class="border rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="font-medium">{{ cls.course_code }}</h3>
                  <p class="text-sm text-muted-foreground">{{ cls.class_name }}</p>
                  <p class="text-xs text-muted-foreground mt-1">{{ cls.student_count }} students</p>
                </div>
                <Button size="sm" @click="startQuickSession(cls.id)">
                  Quick Start
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Recent Attendance Sessions -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Attendance Sessions</CardTitle>
          <CardDescription>Your latest attendance activities</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="session in props.recent_sessions" :key="session.id" class="flex items-center justify-between p-4 border rounded-lg">
              <div class="flex items-center gap-3">
                <Badge variant="secondary">{{ session.course_code }}</Badge>
                <div>
                  <p class="font-medium">{{ session.class_name }}</p>
                  <p class="text-sm text-muted-foreground">{{ session.date }} at {{ session.time }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">{{ session.present_count }}/{{ session.total_count }} students</span>
                <Badge :variant="session.status === 'completed' ? 'default' : 'secondary'">
                  {{ session.status }}
                </Badge>
                <Button size="sm" variant="outline" @click="manageSession(session)">
                  Manage
                </Button>
              </div>
            </div>

            <div v-if="props.recent_sessions.length === 0" class="text-center py-8 text-muted-foreground">
              <Clock class="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No recent attendance sessions</p>
              <p class="text-sm">Create your first session to get started</p>
            </div>

            <div class="text-center py-4">
              <Button variant="outline" @click="$inertia.visit('/teacher/attendance/history')">
                View All Sessions
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Quick Stats -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Today's Sessions</CardTitle>
            <span class="text-xl">📅</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">3</div>
            <p class="text-xs text-muted-foreground mt-1">
              Sessions completed
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Present Students</CardTitle>
            <span class="text-xl">✅</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">67</div>
            <p class="text-xs text-muted-foreground mt-1">
              Out of 75 total
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Attendance Rate</CardTitle>
            <span class="text-xl">📊</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">89%</div>
            <p class="text-xs text-muted-foreground mt-1">
              This week's average
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Late Students</CardTitle>
            <span class="text-xl">⏰</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">8</div>
            <p class="text-xs text-muted-foreground mt-1">
              Need attention
            </p>
          </CardContent>
        </Card>
      </div>

    </div>

    <!-- Manage Attendance Session Dialog -->
    <Dialog v-model:open="showManageSessionDialog">
      <DialogContent class="sm:max-w-[600px]">
        <DialogHeader>
          <DialogTitle v-if="selectedSession">Manage: {{ selectedSession.class_name }}</DialogTitle>
          <DialogDescription v-if="selectedSession">
            {{ selectedSession.date }} at {{ selectedSession.time }} - {{ selectedSession.present_count }}/{{ selectedSession.total_count }} students present
          </DialogDescription>
        </DialogHeader>
        <div class="py-4">
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h4 class="font-medium">Attendance Status</h4>
              <div class="flex gap-2">
                <Button size="sm" variant="outline">
                  Export Report
                </Button>
                <Button size="sm" variant="outline">
                  Send Notifications
                </Button>
              </div>
            </div>
            <div class="text-center py-8 text-muted-foreground">
              <Users class="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>Student attendance records would be displayed here</p>
              <p class="text-sm">Individual student status can be modified</p>
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="showManageSessionDialog = false">Close</Button>
          <Button @click="updateAttendance" :disabled="manageAttendanceForm.processing">
            {{ manageAttendanceForm.processing ? 'Updating...' : 'Update Attendance' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- QR Scanner Component -->
    <QrScanner 
      :show="showQRScanner"
      @close="closeQRScanner"
      @scan-success="onScanSuccess"
    />
  </AppLayout>
</template>