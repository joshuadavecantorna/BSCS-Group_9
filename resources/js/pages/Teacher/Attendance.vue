<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

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

const showCreateSessionDialog = ref(false);
const showManageSessionDialog = ref(false);
const showComingSoonDialog = ref(false);
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

// QR Scanner functionality removed - use sessions instead

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
    
    // Use Inertia router for proper CSRF handling
    router.post('/teacher/attendance/quick-start', {
      class_id: classId
    }, {
      onSuccess: () => {
        console.log('Quick session started successfully');
        // The server redirects to the session page automatically
        // No need to handle redirect here as Inertia handles it
        createSessionForm.processing = false;
      },
      onError: (errors: any) => {
        console.error('Error starting quick session:', errors);
        alert('Failed to start session: ' + (errors.message || 'Unknown error'));
        createSessionForm.processing = false;
      },
      preserveScroll: true,
      preserveState: false // Allow refresh to get updated session data
    });
  } catch (error) {
    console.error('Error starting quick session:', error);
    alert(error instanceof Error ? error.message : 'Failed to start session');
    createSessionForm.processing = false;
  }
};

// Open session management
const manageSession = async (session: any) => {
  selectedSession.value = session;
  
  // Load actual attendance records for this session
  try {
    const response = await fetch(`/teacher/attendance/sessions/${session.id}`);
    if (response.ok) {
      const sessionData = await response.json();
      selectedSession.value = {
        ...session,
        attendance_records: sessionData.attendance_records || [],
        students: sessionData.students || []
      };
    }
  } catch (error) {
    console.error('Failed to load session data:', error);
  }
  
  showManageSessionDialog.value = true;
};

// Show coming soon dialog
const showComingSoon = () => {
  showComingSoonDialog.value = true;
};

// Update attendance records
const updateAttendance = () => {
  if (!selectedSession.value) return;
  
  manageAttendanceForm.put(`/teacher/attendance/sessions/${selectedSession.value.id}/records`, {
    onSuccess: () => {
      showManageSessionDialog.value = false;
      selectedSession.value = null;
      // Refresh the page to show updated data
      router.reload();
    }
  });
};

// Delete attendance session
const deleteSession = () => {
  if (!selectedSession.value) return;
  
  const sessionName = selectedSession.value.class_name;
  const confirmMessage = `Are you sure you want to delete the attendance session "${sessionName}"?\n\nThis action cannot be undone and will remove all attendance records for this session.`;
  
  if (confirm(confirmMessage)) {
    router.delete(`/teacher/attendance/sessions/${selectedSession.value.id}`, {
      onSuccess: () => {
        showManageSessionDialog.value = false;
        selectedSession.value = null;
        // Refresh the page to show updated data
        router.reload();
      },
      onError: (errors: any) => {
        console.error('Failed to delete session:', errors);
        alert('Failed to delete session: ' + (errors.message || 'Unknown error'));
      }
    });
  }
};

// Mark individual student attendance
const markStudentAttendance = (studentId: string, status: string) => {
  if (!selectedSession.value) return;
  
  router.post(`/teacher/attendance/${selectedSession.value.id}/mark`, {
    student_id: studentId,
    status: status,
    method: 'manual'
  }, {
    onSuccess: () => {
      // Refresh session data
      manageSession(selectedSession.value);
    },
    onError: (errors: any) => {
      console.error('Failed to mark attendance:', errors);
      alert('Failed to update attendance: ' + (errors.message || 'Unknown error'));
    },
    preserveState: true
  });
};

// Helper functions
const getStudentInitials = (record: any) => {
  // Handle both nested student object and flattened structure
  const name = record?.student?.name || record?.name;
  if (!name) return '??';
  const names = name.split(' ');
  return names.length > 1 ? `${names[0][0]}${names[names.length-1][0]}` : names[0][0] + names[0][1] || names[0][0];
};

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'present': return 'default';
    case 'late': return 'secondary';
    case 'absent': return 'destructive';
    case 'excused': return 'outline';
    default: return 'secondary';
  }
};

// Navigation functions
const goToSession = () => {
  if (selectedSession.value) {
    router.visit(`/teacher/attendance/session/${selectedSession.value.id}`);
  }
};

const endSessionFromDialog = () => {
  if (!selectedSession.value) return;
  
  if (confirm('Are you sure you want to end this attendance session?')) {
    router.put(`/teacher/attendance/sessions/${selectedSession.value.id}/end`, {}, {
      onSuccess: () => {
        showManageSessionDialog.value = false;
        selectedSession.value = null;
        // Refresh the page to show updated data
        router.reload();
      },
      onError: (errors: any) => {
        console.error('Failed to end session:', errors);
        alert('Failed to end session: ' + (errors.message || 'Unknown error'));
      }
    });
  }
};

const exportSession = () => {
  if (selectedSession.value) {
    window.open(`/teacher/attendance/sessions/${selectedSession.value.id}/export`, '_blank');
  }
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
          Manage attendance sessions for your classes
        </p>
      </div>

      <!-- Statistics Overview -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Classes</CardTitle>
            <span class="text-xl">📚</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ classes.length }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Active classes
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Recent Sessions</CardTitle>
            <span class="text-xl">📅</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ recent_sessions.length }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Last 10 sessions
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Students</CardTitle>
            <span class="text-xl">👥</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ classes.reduce((sum, cls) => sum + cls.student_count, 0) }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Across all classes
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Sessions</CardTitle>
            <span class="text-xl">🟢</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ recent_sessions.filter(s => s.status === 'active').length }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Currently active
            </p>
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
              <Button variant="outline" @click="showComingSoon">
                View All Sessions
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>



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
              <h4 class="font-medium">Attendance Records</h4>
              <div class="flex gap-2">
                <Button size="sm" variant="outline" @click="exportSession" v-if="selectedSession">
                  Export Report
                </Button>
                <Badge :variant="selectedSession?.status === 'active' ? 'default' : 'secondary'">
                  {{ selectedSession?.status || 'Unknown' }}
                </Badge>
              </div>
            </div>

            <!-- No Records State -->
            <div v-if="!selectedSession?.attendance_records || selectedSession.attendance_records.length === 0" 
                 class="text-center py-8 text-muted-foreground">
              <Users class="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p class="font-medium">No attendance records found</p>
              <p class="text-sm">No students have been marked for this session yet.</p>
              
              <!-- Action for sessions with no records -->
              <div class="mt-6 space-y-3">
                <div v-if="selectedSession?.status === 'active'">
                  <p class="text-sm text-blue-600 mb-2">This session is still active. You can:</p>
                  <div class="flex justify-center gap-2">
                    <Button @click="goToSession" size="sm">
                      Manage Session
                    </Button>
                    <Button @click="endSessionFromDialog" variant="outline" size="sm">
                      End Session
                    </Button>
                  </div>
                </div>
                <div v-else>
                  <p class="text-sm text-orange-600 mb-2">This completed session has no records.</p>
                  <Button @click="deleteSession" variant="destructive" size="sm">
                    Delete Empty Session
                  </Button>
                </div>
              </div>
            </div>

            <!-- Records List -->
            <div v-else class="space-y-2 max-h-60 overflow-y-auto">
              <div v-for="record in selectedSession.attendance_records" :key="record.id"
                   class="flex items-center justify-between p-3 border rounded-lg">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-sm font-medium">
                    {{ getStudentInitials(record) }}
                  </div>
                  <div>
                    <p class="font-medium">{{ record.student?.name || record.name }}</p>
                    <p class="text-sm text-muted-foreground">{{ record.student?.student_id || record.student_id }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <Badge :variant="getStatusVariant(record.status)">
                    {{ record.status }}
                  </Badge>
                  <select 
                    :value="record.status" 
                    @change="markStudentAttendance(record.student?.student_id || record.student_id, ($event.target as HTMLSelectElement).value)"
                    class="text-sm border rounded px-2 py-1"
                  >
                    <option value="present">Present</option>
                    <option value="absent">Absent</option>
                    <option value="late">Late</option>
                    <option value="excused">Excused</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Session Statistics -->
            <div v-if="selectedSession?.attendance_records && selectedSession.attendance_records.length > 0" 
                 class="border-t pt-4">
              <div class="grid grid-cols-4 gap-4 text-center">
                <div>
                  <div class="text-lg font-semibold text-green-600">
                    {{ selectedSession.attendance_records.filter((r: any) => r.status === 'present').length }}
                  </div>
                  <div class="text-xs text-muted-foreground">Present</div>
                </div>
                <div>
                  <div class="text-lg font-semibold text-yellow-600">
                    {{ selectedSession.attendance_records.filter((r: any) => r.status === 'late').length }}
                  </div>
                  <div class="text-xs text-muted-foreground">Late</div>
                </div>
                <div>
                  <div class="text-lg font-semibold text-red-600">
                    {{ selectedSession.attendance_records.filter((r: any) => r.status === 'absent').length }}
                  </div>
                  <div class="text-xs text-muted-foreground">Absent</div>
                </div>
                <div>
                  <div class="text-lg font-semibold text-blue-600">
                    {{ selectedSession.attendance_records.filter((r: any) => r.status === 'excused').length }}
                  </div>
                  <div class="text-xs text-muted-foreground">Excused</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <DialogFooter>
          <div class="flex justify-between w-full">
            <Button variant="destructive" @click="deleteSession" size="sm" v-if="selectedSession">
              Delete Session
            </Button>
            <div class="flex gap-2">
              <Button variant="outline" @click="showManageSessionDialog = false">Close</Button>
              <Button v-if="selectedSession?.status === 'active'" @click="goToSession">
                Manage Live Session
              </Button>
            </div>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Coming Soon Dialog -->
    <Dialog v-model:open="showComingSoonDialog">
      <DialogContent class="sm:max-w-[400px]">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Clock class="h-5 w-5 text-blue-500" />
            Coming Soon
          </DialogTitle>
          <DialogDescription>
            The "View All Sessions" feature is currently under development and will be available in a future update.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button @click="showComingSoonDialog = false">
            Got it
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

  </AppLayout>
</template>