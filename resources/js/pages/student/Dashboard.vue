<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { QrCode, Clock, CheckCircle, XCircle, AlertCircle, Camera } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

// Example breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/student/dashboard' }
];

// Student attendance stats
const attendanceStats = ref({
  presentPercentage: 85,
  absentPercentage: 10,
  excusedPercentage: 5,
  totalClasses: 40,
  presentCount: 34,
  absentCount: 4,
  excusedCount: 2
});

// Upcoming classes
const upcomingClasses = ref([
  { id: 1, name: 'Computer Science 101', time: '09:00 AM', date: 'Today', room: 'Room 201' },
  { id: 2, name: 'Mathematics 201', time: '11:00 AM', date: 'Today', room: 'Room 105' },
  { id: 3, name: 'Physics 101', time: '02:00 PM', date: 'Tomorrow', room: 'Lab 301' },
  { id: 4, name: 'English 101', time: '10:00 AM', date: 'Tomorrow', room: 'Room 210' }
]);

// Recent attendance history
const recentAttendance = ref([
  { class: 'Computer Science 101', date: '2024-10-01', status: 'present' },
  { class: 'Mathematics 201', date: '2024-10-01', status: 'present' },
  { class: 'Physics 101', date: '2024-09-30', status: 'excused' },
  { class: 'English 101', date: '2024-09-30', status: 'absent' },
  { class: 'Computer Science 101', date: '2024-09-29', status: 'present' }
]);

const openQRScanner = () => {
  console.log("Opening QR Scanner for attendance check-in...");
  // Here you would implement QR scanner functionality
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
        <h1 class="text-3xl font-bold">Welcome back, <span class="text-blue-600">{{ user.name }}</span> 🎓</h1>
        <p class="text-muted-foreground">Here's your attendance overview and upcoming classes</p>
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
            <p class="text-xs text-muted-foreground">{{ attendanceStats.presentCount }} of {{ attendanceStats.totalClasses }} classes</p>
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
            <p class="text-xs text-muted-foreground">{{ attendanceStats.absentCount }} of {{ attendanceStats.totalClasses }} classes</p>
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
            <p class="text-xs text-muted-foreground">{{ attendanceStats.excusedCount }} of {{ attendanceStats.totalClasses }} classes</p>
          </CardContent>
        </Card>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <!-- Check-In Section -->
        <Card>
          <CardHeader>
            <CardTitle>Quick Check-In</CardTitle>
            <CardDescription>Scan QR code or enter class ID to mark your attendance</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <Button @click="openQRScanner" class="w-full" size="lg">
              <QrCode class="mr-2 h-5 w-5" />
              Scan QR Code
            </Button>
            <div class="text-center text-sm text-muted-foreground">
              or
            </div>
            <Button variant="outline" class="w-full" size="lg">
              <Camera class="mr-2 h-5 w-5" />
              Enter Class ID
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
              <div v-for="classItem in upcomingClasses.slice(0, 4)" :key="classItem.id" 
                   class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50 transition-colors">
                <div class="space-y-1">
                  <p class="font-medium">{{ classItem.name }}</p>
                  <p class="text-sm text-muted-foreground">{{ classItem.room }}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-medium">{{ classItem.time }}</p>
                  <p class="text-xs text-muted-foreground">{{ classItem.date }}</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Recent Attendance History -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Attendance</CardTitle>
          <CardDescription>Your attendance history for the past week</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div v-for="(record, i) in recentAttendance" :key="i" 
                 class="flex items-center justify-between p-3 border rounded-lg">
              <div class="flex items-center space-x-3">
                <div :class="['w-2 h-2 rounded-full', getStatusColor(record.status)]"></div>
                <div>
                  <p class="font-medium">{{ record.class }}</p>
                  <p class="text-sm text-muted-foreground">{{ record.date }}</p>
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
  </AppLayout>
</template>
