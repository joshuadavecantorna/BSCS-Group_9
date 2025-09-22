<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard, reports } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { QrCode, UserPlus, Users, UserCheck, UserX, Clock, TrendingUp, Plus, Upload, Timer } from 'lucide-vue-next';
import { ref, computed } from 'vue';

// Example breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url }
];

// Example teacher data
const teacherName = ref("Prof. Juan Dela Cruz");

// Example stats data
const attendanceStats = ref({
  totalStudents: 150,
  presentToday: 142,
  absentToday: 5,
  excusedToday: 3,
  dropped: 2
});

// Example last scan
const lastScannedStudent = ref({
  name: 'Maria Santos',
  studentId: 'STU-2024-001',
  scanTime: '09:45 AM',
  status: 'present',
  avatar: null
});

// Example manual entry
const manualEntry = ref({ studentId: '', status: 'present' });

// Example activity feed
const recentActivities = ref([
  "Class BSCS-A – Attendance started at 9:00 AM",
  "3 students marked as excused",
  "Class BSIT-B – Attendance closed at 10:30 AM"
]);

// Example chart data (could be passed from backend)
const weeklyData = ref([
  { day: "Mon", present: 48 },
  { day: "Tue", present: 50 },
  { day: "Wed", present: 47 },
  { day: "Thu", present: 49 },
  { day: "Fri", present: 46 },
]);

// Computed attendance rate
const attendanceRate = computed(() => {
  const present = attendanceStats.value.presentToday + attendanceStats.value.excusedToday;
  const total = attendanceStats.value.totalStudents - attendanceStats.value.dropped;
  return Math.round((present / total) * 100);
});

// Helpers
import { router } from '@inertiajs/vue3';
const openQRScanner = () => console.log("Opening QR Scanner...");
const submitManualEntry = () => {
  if (manualEntry.value.studentId) {
    console.log("Manual entry:", manualEntry.value);
    manualEntry.value.studentId = '';
  }
};

const quickActions = [
  { label: 'Create Class', icon: Plus, handler: () => console.log('Create class') },
  { label: 'Upload Student List', icon: Upload, handler: () => console.log('Upload list') },
  { label: 'Start Attendance', icon: Timer, handler: () => console.log('Start attendance') },
  { label: 'View Reports', icon: TrendingUp, handler: () => router.visit(reports().url) },
];

const getInitials = (name: string) => name.split(' ').map(n => n[0]).join('').toUpperCase();
const getStatusColor = (status: string) => {
  switch (status) {
    case 'present': return 'bg-green-500';
    case 'absent': return 'bg-red-500';
    case 'excused': return 'bg-yellow-500';
    default: return 'bg-gray-500';
  }
};
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">

      <!-- Greeting -->
      <div>
        <h1 class="text-3xl font-bold">Welcome, <span class="text-blue-600">{{ teacherName }}</span> 👋</h1>
        <p class="text-muted-foreground">Here’s your attendance overview for today.</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
        <Card v-for="stat in [
          { label:'Total Students', value: attendanceStats.totalStudents, icon: Users, color:'text-muted-foreground', note:'Active enrollees' },
          { label:'Present Today', value: attendanceStats.presentToday, icon: UserCheck, color:'text-green-600', note:`${attendanceRate}% attendance` },
          { label:'Absent Today', value: attendanceStats.absentToday, icon: UserX, color:'text-red-600', note:'Unexcused absences' },
          { label:'Excused Today', value: attendanceStats.excusedToday, icon: Clock, color:'text-yellow-600', note:'With permission' },
          { label:'Dropped', value: attendanceStats.dropped, icon: TrendingUp, color:'text-gray-600', note:'No longer enrolled' }
        ]" :key="stat.label">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">{{ stat.label }}</CardTitle>
            <component :is="stat.icon" class="h-4 w-4" :class="stat.color" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold" :class="stat.color">{{ stat.value }}</div>
            <p class="text-xs text-muted-foreground">{{ stat.note }}</p>
          </CardContent>
        </Card>
      </div>

      <!-- Quick Actions -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card v-for="action in quickActions" :key="action.label" class="hover:shadow-md transition">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <component :is="action.icon" class="h-5 w-5" />
              {{ action.label }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <Button class="w-full" @click="action.handler">{{ action.label }}</Button>
          </CardContent>
        </Card>
      </div>

      <!-- QR Scanner + Manual Entry -->
      <div class="grid gap-4 md:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2"><QrCode class="h-5 w-5"/>QR Scanner</CardTitle>
            <CardDescription>Scan student QR codes for quick attendance</CardDescription>
          </CardHeader>
          <CardContent>
            <Button @click="openQRScanner" class="w-full" size="lg"><QrCode class="mr-2 h-4 w-4"/>Open QR Scanner</Button>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2"><UserPlus class="h-5 w-5"/>Manual Entry</CardTitle>
            <CardDescription>Manually mark student attendance</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label for="student-id">Student ID</Label>
              <Input id="student-id" v-model="manualEntry.studentId" placeholder="Enter student ID or name" />
            </div>
            <div class="space-y-2">
              <Label for="status">Status</Label>
              <Select v-model="manualEntry.status">
                <SelectTrigger><SelectValue placeholder="Select status" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="present">Present</SelectItem>
                  <SelectItem value="absent">Absent</SelectItem>
                  <SelectItem value="excused">Excused</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <Button @click="submitManualEntry" class="w-full"><UserPlus class="mr-2 h-4 w-4"/>Mark Attendance</Button>
          </CardContent>
        </Card>
      </div>

      <!-- Recent Activity Feed -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Activity</CardTitle>
          <CardDescription>Latest attendance updates</CardDescription>
        </CardHeader>
        <CardContent>
          <ul class="space-y-2">
            <li v-for="(activity, i) in recentActivities" :key="i" class="text-sm flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-blue-500"></span>
              {{ activity }}
            </li>
          </ul>
        </CardContent>
      </Card>

      <!-- Last Scanned Student -->
      <Card>
        <CardHeader>
          <CardTitle>Last Scanned Student</CardTitle>
          <CardDescription>Most recent QR scan</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex items-center space-x-4">
            <Avatar class="h-12 w-12">
              <AvatarImage :src="lastScannedStudent.avatar || ''" :alt="lastScannedStudent.name" />
              <AvatarFallback>{{ getInitials(lastScannedStudent.name) }}</AvatarFallback>
            </Avatar>
            <div class="flex-1 space-y-1">
              <div class="flex items-center gap-2">
                <p class="text-sm font-medium">{{ lastScannedStudent.name }}</p>
                <Badge :class="getStatusColor(lastScannedStudent.status)" class="text-white">{{ lastScannedStudent.status }}</Badge>
              </div>
              <p class="text-sm text-muted-foreground">ID: {{ lastScannedStudent.studentId }}</p>
            </div>
            <div class="text-sm text-muted-foreground">{{ lastScannedStudent.scanTime }}</div>
          </div>
        </CardContent>
      </Card>

      <!-- Charts -->
      <div class="grid gap-4 md:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Attendance Overview</CardTitle>
            <CardDescription>Today's attendance distribution</CardDescription>
          </CardHeader>
          <CardContent class="flex items-center justify-center h-64">
            <!-- Replace with PieChart component -->
            <div class="text-center text-muted-foreground">
              <div class="w-32 h-32 rounded-full border-8 border-green-200 border-t-green-500 mx-auto mb-4"></div>
              <p class="text-sm">{{ attendanceRate }}% Present</p>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Attendance Trends</CardTitle>
            <CardDescription>Weekly attendance</CardDescription>
          </CardHeader>
          <CardContent class="flex items-center justify-center h-64">
            <!-- Replace with BarChart component -->
            <div class="flex items-end gap-2 h-32">
              <div v-for="day in weeklyData" :key="day.day" class="w-6 bg-blue-500 rounded-t"
                :style="{ height: `${day.present * 2}px` }"
              ></div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
